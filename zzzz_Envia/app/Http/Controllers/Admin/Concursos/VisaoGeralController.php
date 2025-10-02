<?php

namespace App\Http\Controllers\Admin\Concursos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class VisaoGeralController extends Controller
{
    public function index(Request $request, $concursoId)
    {
        $concursoId = (int) $concursoId;
        $ttl = now()->addMinutes(5);

        // Layout existente
        $layout = collect(['admin.layouts.app','layouts.admin','layouts.app'])
            ->first(fn ($v) => View::exists($v)) ?? 'layouts.app';

        // Query base com alias fixo "i" + filtro do concurso QUALIFICADO
        $base = $this->inscricoesBase($concursoId);

        // ---------- TOTAIS / SITUAÇÕES ----------
        $totais = Cache::remember("vg:$concursoId:totais", $ttl, function () use ($base) {
            $total = (clone $base)->count();

            $statusCol = Schema::hasColumn('inscricoes', 'situacao') ? 'i.situacao'
                        : (Schema::hasColumn('inscricoes', 'status') ? 'i.status' : null);

            $porSituacao = [];
            if ($statusCol) {
                $porSituacao = (clone $base)
                    ->select(DB::raw("$statusCol as k"), DB::raw('COUNT(*) as v'))
                    ->groupBy('k')->pluck('v','k')->toArray();
            }

            // Confirmadas x Pendentes (heurística robusta)
            $confirmadas = 0; $pendentes = 0;
            if (Schema::hasColumn('inscricoes', 'pago')) {
                $confirmadas = (clone $base)->where('i.pago', 1)->count();
                $pendentes   = $total - $confirmadas;
            } elseif ($statusCol) {
                $confirmadas = (clone $base)->whereIn(DB::raw($statusCol), [
                    'confirmada','confirmado','pago','pagamento confirmado','aprovada'
                ])->count();
                $pendentes = $total - $confirmadas;
            }

            return compact('total','porSituacao','confirmadas','pendentes');
        });

        // ---------- POR CARGO ----------
        $porCargo = Cache::remember("vg:$concursoId:porCargo", $ttl, function () use ($base) {
            // texto direto
            if (Schema::hasColumn('inscricoes','cargo')) {
                $rows = (clone $base)
                    ->select(DB::raw('COALESCE(NULLIF(i.cargo,""),"—") as k'), DB::raw('COUNT(*) as v'))
                    ->groupBy('k')->orderByDesc('v')->limit(20)->get();
                return $rows->map(fn($r)=>['k'=>$r->k,'v'=>$r->v])->all();
            }

            // fk cargo_id
            if (Schema::hasColumn('inscricoes','cargo_id')) {
                $q = clone $base;
                if (Schema::hasTable('cargos')) {
                    $q->leftJoin('cargos as c','c.id','=','i.cargo_id');
                    $rows = $q->select(
                                DB::raw('COALESCE(c.nome, CONCAT("Cargo #", c.id)) as k'),
                                DB::raw('COUNT(*) as v')
                            )
                            ->groupBy('k')->orderByDesc('v')->limit(20)->get();
                    return $rows->map(fn($r)=>['k'=>$r->k,'v'=>$r->v])->all();
                } else {
                    $rows = $q->select(
                                DB::raw("CONCAT('Cargo #', i.cargo_id) as k"),
                                DB::raw('COUNT(*) as v')
                            )
                            ->groupBy('k')->orderByDesc('v')->limit(20)->get();
                    return $rows->map(fn($r)=>['k'=>$r->k,'v'=>$r->v])->all();
                }
            }

            return [];
        });

        // ---------- POR ESCOLARIDADE ----------
        $porEscolaridade = Cache::remember("vg:$concursoId:porEscolaridade", $ttl, function () use ($base) {
            // 1) texto direto na inscrição
            foreach (['escolaridade','nivel_escolaridade'] as $col) {
                if (Schema::hasColumn('inscricoes', $col)) {
                    $rows = (clone $base)
                        ->select(DB::raw("COALESCE(NULLIF(i.$col,''),'Não informado') as k"), DB::raw('COUNT(*) as v'))
                        ->groupBy('k')->orderByDesc('v')->limit(20)->get();
                    return $rows->map(fn($r)=>['k'=>$r->k,'v'=>$r->v])->all();
                }
            }

            // 2) fk para nível de escolaridade
            $fk = null;
            foreach (['nivel_escolaridade_id','escolaridade_id'] as $c) {
                if (Schema::hasColumn('inscricoes', $c)) { $fk = $c; break; }
            }
            if ($fk) {
                $q = clone $base;

                // Tabelas possíveis
                $tab = null; $alias = null; $nameCols = [];
                if (Schema::hasTable('niveis_escolaridade')) {
                    $tab = 'niveis_escolaridade'; $alias='ne';
                    $nameCols = ['nome','descricao','titulo'];
                } elseif (Schema::hasTable('escolaridades')) {
                    $tab = 'escolaridades'; $alias='ne';
                    $nameCols = ['nome','descricao','titulo'];
                }

                if ($tab) {
                    $q->leftJoin("$tab as $alias", "$alias.id", "=", "i.$fk");
                    $chosen = null;
                    foreach ($nameCols as $nc) {
                        if (Schema::hasColumn($tab,$nc)) { $chosen = "$alias.$nc"; break; }
                    }
                    if (!$chosen) { $chosen = "CONCAT('Nível #', $alias.id)"; }
                    $rows = $q->select(
                                DB::raw("COALESCE($chosen, CONCAT('Nível #', $alias.id)) as k"),
                                DB::raw('COUNT(*) as v')
                            )
                            ->groupBy('k')->orderByDesc('v')->limit(20)->get();
                    return $rows->map(fn($r)=>['k'=>$r->k,'v'=>$r->v])->all();
                }

                // sem tabela de apoio
                $rows = $q->select(
                            DB::raw("CONCAT('Nível #', i.$fk) as k"),
                            DB::raw('COUNT(*) as v')
                        )
                        ->groupBy('k')->orderByDesc('v')->limit(20)->get();
                return $rows->map(fn($r)=>['k'=>$r->k,'v'=>$r->v])->all();
            }

            return [];
        });

        // ---------- POR CIDADE ----------
        $porCidade = Cache::remember("vg:$concursoId:porCidade", $ttl, function () use ($base) {
            // 1) cidade em colunas da própria inscrição
            foreach (['cidade','municipio','cidade_residencia','cidade_prova','localidade'] as $col) {
                if (Schema::hasColumn('inscricoes', $col)) {
                    $rows = (clone $base)
                        ->select(DB::raw("COALESCE(NULLIF(i.$col,''),'Não informado') as k"), DB::raw('COUNT(*) as v'))
                        ->groupBy('k')->orderByDesc('v')->limit(50)->get();
                    return $rows->map(fn($r)=>['k'=>$r->k,'v'=>$r->v])->all();
                }
            }

            // 2) via localidade_id -> localidades.nome
            if (Schema::hasColumn('inscricoes', 'localidade_id') && Schema::hasTable('localidades')) {
                $q = (clone $base)->leftJoin('localidades as l','l.id','=','i.localidade_id');
                $nameCol = Schema::hasColumn('localidades','nome') ? 'l.nome'
                         : (Schema::hasColumn('localidades','cidade') ? 'l.cidade' : null);
                if ($nameCol) {
                    $rows = $q->select(DB::raw("$nameCol as k"), DB::raw('COUNT(*) as v'))
                              ->groupBy('k')->orderByDesc('v')->limit(50)->get();
                    return $rows->map(fn($r)=>['k'=>$r->k,'v'=>$r->v])->all();
                }
            }

            // 3) via candidato_id -> candidatos.cidade
            if (Schema::hasColumn('inscricoes','candidato_id') && Schema::hasTable('candidatos') && Schema::hasColumn('candidatos','cidade')) {
                $q = (clone $base)->leftJoin('candidatos as cand','cand.id','=','i.candidato_id');
                $rows = $q->select(DB::raw("COALESCE(NULLIF(cand.cidade,''),'Não informado') as k"), DB::raw('COUNT(*) as v'))
                          ->groupBy('k')->orderByDesc('v')->limit(50)->get();
                return $rows->map(fn($r)=>['k'=>$r->k,'v'=>$r->v])->all();
            }

            return [];
        });

        // ---------- SÉRIE POR DATA (para gráfico) ----------
        $dateCol = Schema::hasColumn('inscricoes','data_inscricao') ? 'i.data_inscricao'
                 : (Schema::hasColumn('inscricoes','created_at') ? 'i.created_at' : null);

        $series = [];
        if ($dateCol) {
            $rows = (clone $base)
                ->select(DB::raw("DATE($dateCol) as d"), DB::raw('COUNT(*) as v'))
                ->groupBy('d')->orderBy('d')->get();
            $series = $rows->map(fn($r)=>['d'=>$r->d,'v'=>$r->v])->all();
        }

        // ---------- PEDIDOS DE ISENÇÃO ----------
        $pedidosIsencao = Cache::remember("vg:$concursoId:isencao", $ttl, function () use ($concursoId) {
            // isencoes
            if (Schema::hasTable('isencoes')) {
                $q = DB::table('isencoes');
                foreach (['concurso_id','edital_id','id_concurso'] as $c) {
                    if (Schema::hasColumn('isencoes',$c)) { $q->where($c, $concursoId); break; }
                }
                return $q->count();
            }
            // pedidos_isencao
            if (Schema::hasTable('pedidos_isencao')) {
                $q = DB::table('pedidos_isencao');
                foreach (['concurso_id','edital_id','id_concurso'] as $c) {
                    if (Schema::hasColumn('pedidos_isencao',$c)) { $q->where($c, $concursoId); break; }
                }
                return $q->count();
            }
            // outros nomes comuns
            foreach (['inscricoes_isencao','isencao_pedidos'] as $t) {
                if (Schema::hasTable($t)) {
                    $q = DB::table($t);
                    foreach (['concurso_id','edital_id','id_concurso'] as $c) {
                        if (Schema::hasColumn($t,$c)) { $q->where($c, $concursoId); break; }
                    }
                    return $q->count();
                }
            }
            return 0;
        });

        // Dados auxiliares de gráfico de situação (se a view usar)
        $porSituacao = $totais['porSituacao'] ?? [];
        $situacaoLabels = array_values(array_map(fn($k)=> (string) $k, array_keys($porSituacao)));
        $situacaoValues = array_values(array_map(fn($v)=> (int) $v, $porSituacao));

        // Escolha da view (hífen vs sublinhado)
        $viewName = View::exists('admin.concursos.visao-geral')
            ? 'admin.concursos.visao-geral'
            : (View::exists('admin.concursos.visao_geral')
                ? 'admin.concursos.visao_geral'
                : 'admin.concursos.show');

        return view($viewName, [
            'layout'          => $layout,
            'concursoId'      => $concursoId,
            'totais'          => $totais,
            'porCargo'        => $porCargo,
            'porEscolaridade' => $porEscolaridade,
            'porCidade'       => $porCidade,
            'series'          => $series,
            'pedidosIsencao'  => $pedidosIsencao,
            'porSituacao'     => $porSituacao,    // caso a view use diretamente
            'situacaoLabels'  => $situacaoLabels, // caso a view use arrays prontos
            'situacaoValues'  => $situacaoValues,
        ]);
    }

    /**
     * Query base para inscrições com alias "i" e filtro do concurso QUALIFICADO.
     */
    private function inscricoesBase(int $concursoId)
    {
        $q = DB::table('inscricoes as i');

        if (Schema::hasColumn('inscricoes', 'concurso_id')) {
            $q->where('i.concurso_id', $concursoId);
        } elseif (Schema::hasColumn('inscricoes', 'edital_id')) {
            $q->where('i.edital_id', $concursoId);
        } elseif (Schema::hasColumn('inscricoes', 'id_concurso')) {
            $q->where('i.id_concurso', $concursoId);
        }

        return $q;
    }
}
