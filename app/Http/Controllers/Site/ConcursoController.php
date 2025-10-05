<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Concurso;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConcursoController extends Controller
{
    /**
     * GET /concursos – lista pública
     */
    public function index(Request $request)
    {
        $q      = trim((string) $request->input('q', ''));
        $status = (string) $request->input('status', 'todos');

        $query = Concurso::query()->with(['client','clientLegacy','clientAlt','clientPlural']);

        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(function ($x) use ($like) {
                $x->where('titulo', 'like', $like)
                  ->orWhere('nome', 'like', $like)
                  ->orWhere('legenda', 'like', $like)
                  ->orWhere('legenda_interna', 'like', $like)
                  ->orWhere('descricao', 'like', $like);
            });
        }

        if ($status !== '' && $status !== 'todos') {
            $query->where(function ($x) use ($status) {
                $x->where('status', $status)
                  ->orWhere('situacao', $status);
            });
        }

        $concursos = (clone $query)
            ->orderByDesc('created_at')
            ->paginate(12)
            ->appends($request->query());

        $todos = (clone $query)->orderByDesc('created_at')->get();

        $agora    = now();
        $abertos  = $todos->filter(function ($c) use ($agora) {
            $cfg = (array) ($c->configs ?? []);
            $on  = (int) ($cfg['inscricoes_online'] ?? $c->inscricoes_online ?? 1) === 1;
            $ini = $cfg['inscricoes_inicio'] ?? $c->inscricoes_inicio ?? null;
            $fim = $cfg['inscricoes_fim']    ?? $c->inscricoes_fim    ?? null;

            try {
                return $on && $ini && $fim
                    && $agora->between(Carbon::parse($ini), Carbon::parse($fim));
            } catch (\Throwable $e) {
                return false;
            }
        });

        $andamento = $todos->diff($abertos)->filter(function ($c) {
            $sit = (string) ($c->situacao ?? $c->status ?? 'rascunho');
            return in_array($sit, ['em_andamento', 'homologado'], true);
        });

        $encerrados = $todos->diff($abertos)->diff($andamento);

        return view('site.concursos.index', [
            'concursos'  => $concursos,
            'q'          => $q,
            'status'     => $status,
            'abertos'    => $abertos,
            'andamento'  => $andamento,
            'encerrados' => $encerrados,
        ]);
    }

    /**
     * GET /concursos/{concurso} – detalhes públicos
     */
    public function show(Concurso $concurso)
    {
        $concurso->loadMissing(['client','clientLegacy','clientAlt','clientPlural']);

        // =========================
        // VAGAS (cargos + locais)
        // =========================
        $hasCodigoCargo     = Schema::hasColumn('concursos_vagas_cargos', 'codigo');
        $hasNivelColCargo   = Schema::hasColumn('concursos_vagas_cargos', 'nivel');
        $hasNivelIdCargo    = Schema::hasColumn('concursos_vagas_cargos', 'nivel_id');
        $hasValorCargoTaxa  = Schema::hasColumn('concursos_vagas_cargos', 'taxa');
        $hasValorCargoValor = Schema::hasColumn('concursos_vagas_cargos', 'valor_inscricao');
        $hasSalario         = Schema::hasColumn('concursos_vagas_cargos', 'salario');
        $hasJornada         = Schema::hasColumn('concursos_vagas_cargos', 'jornada');
        $hasDetalhes        = Schema::hasColumn('concursos_vagas_cargos', 'detalhes');
        $hasDescCargo       = Schema::hasColumn('concursos_vagas_cargos', 'descricao_cargo');

        $usaLocalString = Schema::hasColumn('concursos_vagas_itens', 'local');
        $hasVagasTotais = Schema::hasColumn('concursos_vagas_itens', 'vagas_totais');

        $tipos = DB::table('tipos_vagas_especiais')
            ->when(Schema::hasColumn('tipos_vagas_especiais', 'ativo'), fn($q) => $q->where('ativo', 1))
            ->orderBy('nome')
            ->pluck('nome', 'id');

        $cargosQ = DB::table('concursos_vagas_cargos as c')
            ->where('c.concurso_id', $concurso->id);

        if ($hasNivelIdCargo) {
            $cargosQ->leftJoin('niveis_escolaridade as n', 'n.id', '=', 'c.nivel_id');
        }

        $cargos = $cargosQ->selectRaw('
                c.id,
                c.nome
            ' .
            ($hasCodigoCargo     ? ', c.codigo' : ', NULL as codigo') .
            ($hasNivelColCargo   ? ', c.nivel as nivel_texto' : ', NULL as nivel_texto') .
            ($hasNivelIdCargo    ? ', n.nome as nivel_nome'   : ', NULL as nivel_nome') .
            ($hasValorCargoValor ? ', COALESCE(c.valor_inscricao,0) as valor' : ($hasValorCargoTaxa ? ', COALESCE(c.taxa,0) as valor' : ', 0 as valor')) .
            ($hasSalario         ? ', c.salario' : ', NULL as salario') .
            ($hasJornada         ? ', c.jornada' : ', NULL as jornada') .
            ($hasDetalhes        ? ', c.detalhes' : ', NULL as detalhes') .
            ($hasDescCargo       ? ', c.descricao_cargo' : ', NULL as descricao_cargo')
        )->orderBy('c.nome')->get();

        $subTotCotas = DB::raw("
            (SELECT item_id, SUM(vagas) AS total_cotas
             FROM concursos_vagas_cotas
             GROUP BY item_id) x
        ");

        $itensQ = DB::table('concursos_vagas_itens as i')
            ->leftJoin($subTotCotas, 'x.item_id', '=', 'i.id');

        if (!$usaLocalString) {
            $itensQ->leftJoin('concursos_vagas_localidades as l', 'l.id', '=', 'i.localidade_id');
        }

        $itens = $itensQ->where('i.concurso_id', $concurso->id)
            ->selectRaw('
                i.id,
                i.cargo_id,
                ' . ($usaLocalString ? 'i.local as local_nome' : 'l.nome as local_nome') . ',
                ' . ($hasVagasTotais ? 'COALESCE(i.vagas_totais, x.total_cotas, 0)' : 'COALESCE(x.total_cotas, 0)') . ' as total_item
            ')
            ->orderBy('local_nome')
            ->get();

        $cotasRaw = DB::table('concursos_vagas_cotas')
            ->whereIn('item_id', $itens->pluck('id')->all() ?: [0])
            ->get();

        $cotasPorItem = [];
        foreach ($cotasRaw as $r) {
            $cotasPorItem[$r->item_id][(int)$r->tipo_id] = (int)$r->vagas;
        }

        $vagas = [];
        foreach ($cargos as $c) {
            $nivel = $c->nivel_texto ?: $c->nivel_nome;
            $valor = (float) $c->valor;

            $locais = [];
            $totalGeral = 0;

            foreach ($itens->where('cargo_id', $c->id) as $it) {
                $map   = $cotasPorItem[$it->id] ?? [];
                $cotas = [];
                foreach ($map as $tipoId => $qtd) {
                    $nomeTipo = $tipos[$tipoId] ?? ('Tipo #'.$tipoId);
                    if ($qtd > 0) $cotas[$nomeTipo] = $qtd;
                }

                $totalLocal = (int) $it->total_item;
                $totalGeral += $totalLocal;

                $locais[] = [
                    'nome'  => $it->local_nome ?: '—',
                    'total' => $totalLocal,
                    'cotas' => $cotas,
                ];
            }

            $detalhesTexto = $c->descricao_cargo ?? $c->detalhes;

            $vagas[] = [
                'cargo_id'    => $c->id,
                'codigo'      => $c->codigo,
                'cargo'       => $c->nome,
                'nivel'       => $nivel,
                'valor'       => $valor,
                'salario'     => $c->salario,
                'jornada'     => $c->jornada,
                'detalhes'    => $detalhesTexto,
                'total'       => $totalGeral,
                'localidades' => $locais,
            ];
        }

        // =========================
        // ANEXOS (quadro lateral) — públicos
        // =========================
        $anexos = [];
        $agora = Carbon::now();

        if (Schema::hasTable('concursos_anexos')) {
            $rows = DB::table('concursos_anexos')
                ->where('concurso_id', $concurso->id)
                ->when(Schema::hasColumn('concursos_anexos','ativo'), fn($q)=>$q->where('ativo',1))
                ->when(Schema::hasColumn('concursos_anexos','restrito'), fn($q)=>$q->where('restrito',0)) // NÃO mostra restritos no público
                ->orderByRaw(Schema::hasColumn('concursos_anexos','posicao') ? 'posicao asc, id asc' : (Schema::hasColumn('concursos_anexos','ordem') ? 'ordem asc, id asc' : 'id asc'))
                ->get();

            foreach ($rows as $r) {
                // Visibilidade por data (se existir); caso não exista, trata como indeterminado (visível)
                $mostrar = true;
                $temInd  = Schema::hasColumn('concursos_anexos','tempo_indeterminado');
                $temDe   = Schema::hasColumn('concursos_anexos','visivel_de');
                $temAte  = Schema::hasColumn('concursos_anexos','visivel_ate');

                if ($temInd || $temDe || $temAte) {
                    $ind = $temInd ? ((int)($r->tempo_indeterminado ?? 0) === 1) : false;
                    $de  = $temDe  ? $r->visivel_de  : null;
                    $ate = $temAte ? $r->visivel_ate : null;

                    if (!$ind && ($de || $ate)) {
                        $inicio = $de  ? Carbon::parse($de)  : null;
                        $fim    = $ate ? Carbon::parse($ate) : null;

                        if ($inicio && $fim) {
                            $mostrar = $agora->between($inicio, $fim);
                        } elseif ($inicio && !$fim) {
                            $mostrar = $agora->greaterThanOrEqualTo($inicio);
                        } elseif (!$inicio && $fim) {
                            $mostrar = $agora->lessThanOrEqualTo($fim);
                        }
                    }
                }

                if (!$mostrar) continue;

                $titulo = $r->titulo ?? $r->nome ?? 'Documento';
                $tipo   = (string)($r->tipo ?? '');
                $url    = null;

                if ($tipo === 'link') {
                    $url = $r->link_url ?? $r->url ?? null;
                } else {
                    $path = $r->arquivo_path ?? $r->arquivo ?? $r->path ?? null;
                    if ($path) {
                        $p = str_replace('\\','/',$path);
                        if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) {
                            $url = $p;
                        } elseif (str_starts_with($p, 'storage/') || str_starts_with($p, '/storage/')) {
                            $url = asset(ltrim($p,'/'));
                        } elseif (app('filesystem')->disk('public')->exists($p)) {
                            $url = app('filesystem')->disk('public')->url($p);
                        } elseif (file_exists(public_path($p))) {
                            $url = asset($p);
                        } elseif (file_exists(public_path('storage/'.$p))) {
                            $url = asset('storage/'.$p);
                        }
                    }
                }

                if ($url) {
                    $anexos[] = ['titulo'=>$titulo, 'url'=>$url];
                }
            }
        } elseif (Schema::hasTable('concursos_arquivos')) {
            $rows = DB::table('concursos_arquivos')
                ->where('concurso_id', $concurso->id)
                ->orderBy('id')
                ->get();
            foreach ($rows as $r) {
                $titulo = $r->titulo ?? $r->nome ?? 'Documento';
                $url    = $r->url    ?? $r->arquivo ?? null;
                if ($url) {
                    $anexos[] = ['titulo'=>$titulo, 'url'=>$url];
                }
            }
        }

        // Fallback para link de edital direto no concurso
        if (empty($anexos)) {
            $editalUrl = $concurso->edital_url ?? $concurso->link_edital ?? null;
            if ($editalUrl) {
                $anexos[] = ['titulo'=>'Edital', 'url'=>$editalUrl];
            }
        }

        return view('site.concursos.show', [
            'concurso' => $concurso,
            'vagas'    => $vagas,
            'anexos'   => $anexos,
        ]);
    }
}
