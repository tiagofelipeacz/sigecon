<?php

namespace App\Http\Controllers\Admin\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use Illuminate\Support\Facades\DB;

class VisaoGeralController extends Controller
{
    public function index(Concurso $concurso)
    {
        // Defaults
        $totais = [
            'total'        => 0,
            'confirmadas'  => 0,
            'pendentes'    => 0,
            'porSituacao'  => [], // ['pago'=>10, 'pendente'=>5, ...]
        ];
        $series           = []; // [{d:'2025-01-01', v:10}, ...]
        $porCargo         = []; // [['k'=>'Cargo X','v'=>12], ...]
        $porEscolaridade  = []; // [['k'=>'Superior','v'=>50], ...]
        $porCidade        = []; // [['k'=>'Cidade/UF','v'=>22], ...]
        $pedidosIsencao   = 0;

        try {
            // Base de inscrições (aceita concurso_id ou edital_id)
            $ins = DB::table('inscricoes as i');
            if (DB::getSchemaBuilder()->hasColumn('inscricoes', 'concurso_id')) {
                $ins->where('i.concurso_id', $concurso->id);
            } elseif (DB::getSchemaBuilder()->hasColumn('inscricoes', 'edital_id')) {
                $ins->where('i.edital_id', $concurso->id);
            } else {
                $ins->whereRaw('1=0');
            }

            // Totais
            $totais['total'] = (clone $ins)->count();

            $stConfirm = array_map('mb_strtolower', [
                'confirmado','confirmada','valida','válida','confirmed','confirmados'
            ]);
            $totais['confirmadas'] = (clone $ins)
                ->whereIn(DB::raw('LOWER(i.status)'), $stConfirm)
                ->count();

            $totais['pendentes'] = max(0, (int)$totais['total'] - (int)$totais['confirmadas']);

            // Por situação (normaliza em lowercase; vazio => "—")
            $rawSitu = (clone $ins)
                ->select(DB::raw('LOWER(COALESCE(i.status,"")) as s'), DB::raw('COUNT(*) as qtd'))
                ->groupBy('s')->orderByDesc('qtd')->get();
            foreach ($rawSitu as $r) {
                $label = $r->s !== '' ? $r->s : '—';
                $totais['porSituacao'][$label] = (int)$r->qtd;
            }

            // Série por dia -> {d, v}
            $series = (clone $ins)
                ->select(DB::raw('DATE(i.created_at) as d'), DB::raw('COUNT(*) as v'))
                ->groupBy('d')->orderBy('d','asc')->get()
                ->map(fn($r)=>['d'=>$r->d,'v'=>(int)$r->v])->all();

            // Por cargo (se houver tabela cargos)
            if (DB::getSchemaBuilder()->hasTable('cargos')) {
                $rows = (clone $ins)
                    ->leftJoin('cargos as cg', 'cg.id', '=', 'i.cargo_id')
                    ->select([
                        DB::raw('COALESCE(cg.nome, cg.titulo, CONCAT("Cargo #", i.cargo_id)) as cargo'),
                        DB::raw('COUNT(*) as total'),
                    ])
                    ->groupBy('i.cargo_id','cg.nome','cg.titulo')
                    ->orderByDesc('total')
                    ->limit(50)
                    ->get();
                $porCargo = $rows->map(fn($r)=>['k'=>$r->cargo, 'v'=>(int)$r->total])->all();
            }

            // Por escolaridade (se coluna existir)
            if (DB::getSchemaBuilder()->hasTable('cargos') &&
                (DB::getSchemaBuilder()->hasColumn('cargos','nivel_escolaridade') || DB::getSchemaBuilder()->hasColumn('cargos','nivel'))) {
                $rows = (clone $ins)
                    ->leftJoin('cargos as cg', 'cg.id', '=', 'i.cargo_id')
                    ->select([
                        DB::raw('COALESCE(cg.nivel_escolaridade, cg.nivel, "—") as nivel'),
                        DB::raw('COUNT(*) as total'),
                    ])
                    ->groupBy('nivel')
                    ->orderByDesc('total')
                    ->get();
                $porEscolaridade = $rows->map(fn($r)=>['k'=>$r->nivel, 'v'=>(int)$r->total])->all();
            }

            // Por cidade (se houver candidatos)
            if (DB::getSchemaBuilder()->hasTable('candidatos')) {
                $rows = (clone $ins)
                    ->leftJoin('candidatos as ca', 'ca.id', '=', 'i.candidato_id')
                    ->select([
                        DB::raw('COALESCE(ca.cidade,"—") as cidade'),
                        DB::raw('COALESCE(ca.estado,"") as uf'),
                        DB::raw('COUNT(*) as total'),
                    ])
                    ->groupBy('cidade','uf')
                    ->orderByDesc('total')
                    ->limit(100)
                    ->get();
                $porCidade = $rows->map(function($r){
                    $k = trim($r->cidade . ($r->uf ? " / {$r->uf}" : ''));
                    return ['k' => $k, 'v' => (int)$r->total];
                })->all();
            }

            // Pedidos de isenção (tolerante a concurso_id/edital_id)
            if (DB::getSchemaBuilder()->hasTable('isencoes')) {
                $pedidosIsencao = DB::table('isencoes')
                    ->where(function($q) use ($concurso){
                        if (DB::getSchemaBuilder()->hasColumn('isencoes','concurso_id')) {
                            $q->where('concurso_id', $concurso->id);
                        } elseif (DB::getSchemaBuilder()->hasColumn('isencoes','edital_id')) {
                            $q->where('edital_id', $concurso->id);
                        } else {
                            $q->whereRaw('1=0');
                        }
                    })
                    ->count();
            }

        } catch (\Throwable $e) {
            // mantém defaults silenciosamente
        }

        return view('admin.concursos.visao-geral', [
            'concurso'         => $concurso,
            'concursoId'       => $concurso->id,
            'totais'           => $totais,
            'series'           => $series,
            'porCargo'         => $porCargo,
            'porEscolaridade'  => $porEscolaridade,
            'porCidade'        => $porCidade,
            'pedidosIsencao'   => $pedidosIsencao,
        ]);
    }
}
