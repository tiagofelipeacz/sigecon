<?php

namespace App\Http\Controllers\Admin\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VagaController extends Controller
{
    // =======================
    // LISTAGEM (UMA LINHA POR CARGO) + ITENS
    // =======================
    public function index(Concurso $concurso, Request $request)
    {
        $payload = $this->renderIndexPayload($concurso, null);
        return view('admin.concursos.vagas.index', $payload);
    }

    // =======================
    // FORM DE CADASTRO / EDIÇÃO (usa a MESMA view index)
    // =======================
    public function create(Concurso $concurso, Request $request)
    {
        $cargoId = (int) $request->query('cargo', 0) ?: null;
        $payload = $this->renderIndexPayload($concurso, $cargoId);
        return view('admin.concursos.vagas.index', $payload);
    }

    public function edit(Concurso $concurso, int $cargoId)
    {
        $payload = $this->renderIndexPayload($concurso, $cargoId);
        return view('admin.concursos.vagas.index', $payload);
    }

    /**
     * Monta o payload usado na index para exibir o formulário (novo/edição),
     * a lista de cargos, itens e cabeçalhos/cotas.
     */
    private function renderIndexPayload(Concurso $concurso, ?int $cargoId): array
    {
        // Tipos de Vagas Especiais (ativos): para montar cabeçalhos/colunas de cotas
        $tiposQ = DB::table('tipos_vagas_especiais')->where('ativo', 1);
        if (Schema::hasColumn('tipos_vagas_especiais', 'ordem')) {
            $tiposQ->orderBy('ordem');
        }
        $tipos = $tiposQ->orderBy('nome')->get(['id','nome']);

        // Níveis (para o <select> no formulário)
        $niveis = DB::table('niveis_escolaridade')
            ->when(
                Schema::hasColumn('niveis_escolaridade', 'ativo'),
                fn($q) => $q->where('ativo', 1)
            )
            ->orderByRaw(
                Schema::hasColumn('niveis_escolaridade', 'ordem')
                    ? 'ordem IS NULL, ordem ASC, nome ASC'
                    : 'nome ASC'
            )
            ->get(['id','nome']);

        // Campos opcionais em "concursos_vagas_cargos"
        $hasNivelColCargo   = Schema::hasColumn('concursos_vagas_cargos', 'nivel');             // string
        $hasNivelIdCargo    = Schema::hasColumn('concursos_vagas_cargos', 'nivel_id');          // fk
        $hasCodigoCargo     = Schema::hasColumn('concursos_vagas_cargos', 'codigo');
        $hasValorCargoTaxa  = Schema::hasColumn('concursos_vagas_cargos', 'taxa');
        $hasValorCargoValor = Schema::hasColumn('concursos_vagas_cargos', 'valor_inscricao');
        $hasJornadaCargo    = Schema::hasColumn('concursos_vagas_cargos', 'jornada');
        $hasSalarioCargo    = Schema::hasColumn('concursos_vagas_cargos', 'salario');

        // Local pode ser string ('local') ou via FK 'localidade_id'
        $usaLocalString = Schema::hasColumn('concursos_vagas_itens', 'local');
        $localKey = $usaLocalString ? 'i.local' : 'i.localidade_id';

        // Total por item (vagas_totais) pode existir
        $hasVagasTotais = Schema::hasColumn('concursos_vagas_itens', 'vagas_totais');
        $exprTotalItem  = $hasVagasTotais
            ? 'COALESCE(i.vagas_totais, x.total_cotas, 0)'
            : 'COALESCE(x.total_cotas, 0)';

        // Coluna de Nível (prioriza c.nivel; se não existir, tenta c.nivel_id -> niveis_escolaridade.nome)
        $colNivelSelect = 'NULL';
        $joinNivel = false;
        if ($hasNivelColCargo) {
            $colNivelSelect = 'c.nivel';
        } elseif ($hasNivelIdCargo) {
            $colNivelSelect = 'n.nome';
            $joinNivel = true;
        }

        // Coluna de Código (se existir)
        $colCodigoSelect = $hasCodigoCargo ? 'c.codigo' : 'NULL';

        // Coluna de Valor (se existir taxa ou valor_inscricao)
        $colValorSelect = '0';
        if ($hasValorCargoValor) {
            $colValorSelect = 'COALESCE(c.valor_inscricao,0)';
        } elseif ($hasValorCargoTaxa) {
            $colValorSelect = 'COALESCE(c.taxa,0)';
        }

        // Query principal: agrega por CARGO (resumo/analytics)
        $qb = DB::table('concursos_vagas_itens as i')
            ->join('concursos_vagas_cargos as c', 'c.id', '=', 'i.cargo_id')
            ->leftJoin(DB::raw('
                (SELECT item_id, SUM(vagas) AS total_cotas
                 FROM concursos_vagas_cotas
                 GROUP BY item_id) AS x
            '), 'x.item_id', '=', 'i.id');

        if ($joinNivel) {
            $qb->leftJoin('niveis_escolaridade as n', 'n.id', '=', 'c.nivel_id');
        }

        $linhas = $qb->where('i.concurso_id', $concurso->id)
            ->selectRaw("
                c.id   as cargo_id,
                c.nome as cargo,
                {$colCodigoSelect} as codigo,
                {$colNivelSelect}  as nivel,
                COUNT(DISTINCT {$localKey}) as localidades,
                SUM({$exprTotalItem})       as total,
                MAX({$colValorSelect})      as valor
            ")
            ->groupBy('c.id','c.nome');

        if ($hasCodigoCargo) {
            $linhas->groupBy('c.codigo');
        }
        if ($joinNivel) {
            $linhas->groupBy('n.nome');
        } elseif ($hasNivelColCargo) {
            $linhas->groupBy('c.nivel');
        }

        $linhas = $linhas->orderBy('c.nome')->get();

        // Mapa item_id => cargo_id (para somar cotas por cargo)
        $itemCargo = DB::table('concursos_vagas_itens')
            ->where('concurso_id', $concurso->id)
            ->pluck('cargo_id','id'); // [item_id => cargo_id]

        // Somatório de cotas por cargo/tipo: [cargo_id][tipo_id] => qtd
        $cotasPorCargo = [];
        if ($itemCargo->isNotEmpty()) {
            $rawCotas = DB::table('concursos_vagas_cotas')
                ->whereIn('item_id', $itemCargo->keys())
                ->get(['item_id','tipo_id','vagas']);

            foreach ($rawCotas as $r) {
                $cargoIdMap = $itemCargo[$r->item_id] ?? null;
                if ($cargoIdMap) {
                    $cotasPorCargo[$cargoIdMap][$r->tipo_id] =
                        ($cotasPorCargo[$cargoIdMap][$r->tipo_id] ?? 0) + (int)$r->vagas;
                }
            }
        }

        // CARGOS para a tabela "Cargos cadastrados" (com nome do nível)
        $cargos = DB::table('concursos_vagas_cargos as c')
            ->leftJoin('niveis_escolaridade as n', 'n.id', '=', 'c.nivel_id')
            ->where('c.concurso_id', $concurso->id)
            ->orderBy('c.nome')
            ->get(['c.*', DB::raw('n.nome as nivel_escolaridade')]);

        // ===== Itens para a tabela "Itens de Vaga (Cargo x Localidade)" =====
        $hasOrdemItem = Schema::hasColumn('concursos_vagas_itens', 'ordem');
        $selects = [
            'i.id',
            'i.cargo_id',
        ];
        if ($hasVagasTotais) {
            $selects[] = DB::raw('i.vagas_totais as quantidade'); // alias para a view
        } else {
            $selects[] = DB::raw('0 as quantidade');
        }
        if (Schema::hasColumn('concursos_vagas_itens', 'cr')) $selects[] = 'i.cr';
        if ($hasOrdemItem) $selects[] = 'i.ordem';

        // texto do local
        $selects[] = DB::raw($usaLocalString ? 'i.local as local_nome' : 'l.nome as local_nome');

        // dados do cargo para colunas
        $selects[] = 'c.nome as cargo_nome';
        if ($hasJornadaCargo) $selects[] = 'c.jornada';
        if ($hasSalarioCargo) $selects[] = 'c.salario';
        if ($hasValorCargoValor) $selects[] = 'c.valor_inscricao';
        elseif ($hasValorCargoTaxa) $selects[] = 'c.taxa as valor_inscricao';

        $itensQB = DB::table('concursos_vagas_itens as i')
            ->join('concursos_vagas_cargos as c', 'c.id', '=', 'i.cargo_id')
            ->leftJoin('concursos_vagas_localidades as l', 'l.id', '=', 'i.localidade_id')
            ->where('i.concurso_id', $concurso->id)
            ->select($selects);

        if ($hasOrdemItem) {
            $itensQB->orderBy('i.ordem');
        }
        $itensQB->orderBy('c.nome');
        if ($usaLocalString) {
            $itensQB->orderBy('i.local');
        } else {
            $itensQB->orderBy('l.nome');
        }

        $itens = $itensQB->get();

        // Defaults para edição (preenche o formulário)
        $defaults = $cargoId ? $this->loadDefaultsCargo($concurso, $cargoId) : [];

        return [
            'concurso'      => $concurso,
            'linhas'        => $linhas,
            'tipos'         => $tipos,
            'cotasPorCargo' => $cotasPorCargo,
            'itens'         => $itens,
            'hasOrdemItem'  => $hasOrdemItem,
            'niveis'        => $niveis,   // para o <select> de Nível
            'cargos'        => $cargos,   // lista de cargos
            'defaults'      => $defaults, // quando edição
        ];
    }

    private function loadDefaultsCargo(Concurso $concurso, int $cargoId): array
    {
        if (!$cargoId) return [];

        $cargo = DB::table('concursos_vagas_cargos')
            ->where('id', $cargoId)
            ->where('concurso_id', $concurso->id)
            ->first();

        if (!$cargo) return [];

        $detCol = Schema::hasColumn('concursos_vagas_cargos','detalhes')
            ? 'detalhes'
            : (Schema::hasColumn('concursos_vagas_cargos','descricao') ? 'descricao' : null);

        $defaults = [
            'cargo_id'          => $cargo->id,
            'nivel_id'          => Schema::hasColumn('concursos_vagas_cargos','nivel_id')          ? $cargo->nivel_id          : null,
            'codigo'            => Schema::hasColumn('concursos_vagas_cargos','codigo')            ? $cargo->codigo            : null,
            'nome'              => $cargo->nome,
            'valor_inscricao'   => Schema::hasColumn('concursos_vagas_cargos','taxa')              ? $cargo->taxa              : (Schema::hasColumn('concursos_vagas_cargos','valor_inscricao') ? $cargo->valor_inscricao : null),
            'inscricoes_online' => Schema::hasColumn('concursos_vagas_cargos','inscricoes_online') ? (int) $cargo->inscricoes_online : null,
            'limite_inscricoes' => Schema::hasColumn('concursos_vagas_cargos','limite_inscricoes') ? $cargo->limite_inscricoes : null,
            'salario'           => Schema::hasColumn('concursos_vagas_cargos','salario')           ? $cargo->salario           : null,
            'jornada'           => Schema::hasColumn('concursos_vagas_cargos','jornada')           ? $cargo->jornada           : null,
            'detalhes'          => $detCol ? ($cargo->{$detCol} ?? null) : null,
        ];

        $usaLocalString = Schema::hasColumn('concursos_vagas_itens','local');
        $colHasCR       = Schema::hasColumn('concursos_vagas_itens','cr');
        $colHasVagas    = Schema::hasColumn('concursos_vagas_itens','vagas_totais');

        $itensQB = DB::table('concursos_vagas_itens as i')
            ->leftJoin('concursos_vagas_localidades as l', 'l.id', '=', 'i.localidade_id')
            ->where('i.concurso_id', $concurso->id)
            ->where('i.cargo_id', $cargoId);

        $selects = ['i.id'];
        $selects[] = $colHasVagas ? 'i.vagas_totais' : DB::raw('0 as vagas_totais');
        $selects[] = $colHasCR    ? 'i.cr'          : DB::raw('0 as cr');
        $selects[] = DB::raw($usaLocalString ? 'i.local as local_nome' : 'l.nome as local_nome');

        $itens = $itensQB->select($selects)->get();

        $ids = $itens->pluck('id')->all();
        $cotasRaw = DB::table('concursos_vagas_cotas')
            ->whereIn('item_id', $ids ?: [0])
            ->get()
            ->groupBy('item_id');

        $locais = [];
        foreach ($itens as $it) {
            $map = [];
            foreach ($cotasRaw->get($it->id, collect()) as $c) {
                $map[(int)$c->tipo_id] = (int)$c->vagas;
            }
            $locais[] = [
                'local'     => $it->local_nome ?: '',
                'qtd_total' => $colHasVagas ? (int)$it->vagas_totais : array_sum($map),
                'cr'        => $colHasCR ? (int)$it->cr : 0,
                'cotas'     => $map,
            ];
        }
        $defaults['locais'] = $locais;

        return $defaults;
    }

    // =======================
    // SALVAR (Cria novo OU atualiza cargo existente)
    // =======================
    public function store(Concurso $concurso, Request $request)
    {
        $data = $this->validateCargoRequest($request);

        DB::transaction(function () use ($concurso, $data) {
            [$cargoId] = $this->upsertCargo($concurso, $data);

            // Zera itens/cotas antigos do cargo (para regravar conforme o formulário)
            $oldItemIds = DB::table('concursos_vagas_itens')
                ->where('concurso_id', $concurso->id)
                ->where('cargo_id', $cargoId)
                ->pluck('id');

            if ($oldItemIds->isNotEmpty()) {
                DB::table('concursos_vagas_cotas')->whereIn('item_id', $oldItemIds)->delete();
                DB::table('concursos_vagas_itens')->whereIn('id', $oldItemIds)->delete();
            }

            $this->persistLocais($concurso, $cargoId, $data);
        });

        return redirect()->route('admin.concursos.vagas.index', $concurso->id)
            ->with('ok', 'Vaga salva com sucesso.');
    }

    public function update(Concurso $concurso, int $cargoId, Request $request)
    {
        $data = $this->validateCargoRequest($request);
        // força que é edição do cargo informado
        $data['cargo_id'] = $cargoId;

        DB::transaction(function () use ($concurso, $data, $cargoId) {
            [$cargoId] = $this->upsertCargo($concurso, $data);

            // mesma estratégia: apaga itens/cotas antigos e recria
            $oldItemIds = DB::table('concursos_vagas_itens')
                ->where('concurso_id', $concurso->id)
                ->where('cargo_id', $cargoId)
                ->pluck('id');

            if ($oldItemIds->isNotEmpty()) {
                DB::table('concursos_vagas_cotas')->whereIn('item_id', $oldItemIds)->delete();
                DB::table('concursos_vagas_itens')->whereIn('id', $oldItemIds)->delete();
            }

            $this->persistLocais($concurso, $cargoId, $data);
        });

        return redirect()->route('admin.concursos.vagas.index', $concurso->id)
            ->with('ok', 'Vaga atualizada com sucesso.');
    }

    // =======================
    // ITENS (edição simples de um item)
    // =======================
    public function updateItem(Concurso $concurso, int $itemId, Request $request)
    {
        $usaLocalString = Schema::hasColumn('concursos_vagas_itens', 'local');
        $colHasCR       = Schema::hasColumn('concursos_vagas_itens', 'cr');
        $colHasVagas    = Schema::hasColumn('concursos_vagas_itens', 'vagas_totais');

        $rules = [
            'local'     => ['nullable','string','max:150'],
            'qtd_total' => ['nullable','integer','min:0'],
            'cr'        => ['nullable','in:0,1'],
            'cotas'     => ['nullable','array'],
            'cotas.*'   => ['nullable','integer','min:0'],
        ];
        $data = $request->validate($rules);

        DB::transaction(function () use ($concurso, $itemId, $data, $usaLocalString, $colHasCR, $colHasVagas) {
            $item = DB::table('concursos_vagas_itens')
                ->where('id', $itemId)
                ->where('concurso_id', $concurso->id)
                ->first();

            abort_if(!$item, 404);

            $payload = ['updated_at' => now()];

            if ($usaLocalString && array_key_exists('local', $data)) {
                $payload['local'] = $data['local'];
            } elseif (array_key_exists('local', $data)) {
                // via tabela de localidades
                $localidadeId = DB::table('concursos_vagas_localidades')
                    ->where('concurso_id', $concurso->id)
                    ->where('nome', $data['local'])
                    ->value('id');

                if (!$localidadeId) {
                    $localidadeId = DB::table('concursos_vagas_localidades')->insertGetId([
                        'concurso_id' => $concurso->id,
                        'nome'        => $data['local'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
                $payload['localidade_id'] = $localidadeId;
            }

            $isCR = (int) (($data['cr'] ?? $item->cr ?? 0) ? 1 : 0);
            if ($colHasCR && array_key_exists('cr', $data)) {
                $payload['cr'] = $isCR;
            }

            if ($colHasVagas) {
                $payload['vagas_totais'] = $isCR ? 0 : (int) ($data['qtd_total'] ?? $item->vagas_totais ?? 0);
            }

            DB::table('concursos_vagas_itens')
                ->where('id', $itemId)
                ->update($payload);

            // cotas
            $cotasMap = [];
            if (!empty($data['cotas']) && is_array($data['cotas'])) {
                // valida soma quando não for CR
                if (!$isCR && isset($payload['vagas_totais'])) {
                    $soma = array_sum(array_map('intval', $data['cotas']));
                    if ($soma > (int)$payload['vagas_totais']) {
                        throw ValidationException::withMessages([
                            'cotas' => 'A soma das vagas especiais excede a quantidade total.'
                        ]);
                    }
                }

                foreach ($data['cotas'] as $tipoId => $qtd) {
                    $tipoId = (int) $tipoId;
                    $qtd    = max(0, (int) $qtd);
                    $cotasMap[$tipoId] = $qtd;

                    DB::table('concursos_vagas_cotas')->updateOrInsert(
                        ['item_id' => $itemId, 'tipo_id' => $tipoId],
                        ['vagas' => $qtd, 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }

            // Atualiza colunas-resumo (qtd_total, qtd_pcds, qtd_negros, qtd_indigenas)
            $total = $colHasVagas
                ? ($payload['vagas_totais'] ?? $item->vagas_totais ?? 0)
                : null;

            $this->syncResumoModalidades($itemId, $total, $cotasMap, (bool)$isCR);
        });

        return back()->with('ok', 'Item atualizado.');
    }

    public function destroyItem(Concurso $concurso, int $itemId)
    {
        DB::transaction(function () use ($concurso, $itemId) {
            DB::table('concursos_vagas_cotas')->where('item_id', $itemId)->delete();
            DB::table('concursos_vagas_itens')
                ->where('id', $itemId)
                ->where('concurso_id', $concurso->id)
                ->delete();
        });

        return back()->with('ok', 'Item excluído.');
    }

    // Retrocompat: caso alguma rota aponte para destroy
    public function destroy(Concurso $concurso, $itemId)
    {
        return $this->destroyItem($concurso, (int)$itemId);
    }

    public function destroyCargo(Concurso $concurso, int $cargoId)
    {
        DB::transaction(function () use ($concurso, $cargoId) {
            $itemIds = DB::table('concursos_vagas_itens')
                ->where('concurso_id', $concurso->id)
                ->where('cargo_id', $cargoId)
                ->pluck('id');

            if ($itemIds->isNotEmpty()) {
                DB::table('concursos_vagas_cotas')->whereIn('item_id', $itemIds)->delete();
                DB::table('concursos_vagas_itens')->whereIn('id', $itemIds)->delete();
            }

            DB::table('concursos_vagas_cargos')
                ->where('concurso_id', $concurso->id)
                ->where('id', $cargoId)
                ->delete();
        });

        return back()->with('ok', 'Cargo e suas localidades foram excluídos.');
    }

    // =======================
    // REORDENAÇÃO DE ITENS
    // =======================
    public function reorder(Concurso $concurso, Request $request)
    {
        if (!Schema::hasColumn('concursos_vagas_itens', 'ordem')) {
            return back()->with('ok', 'Campo de ordem não existe na tabela de itens.');
        }

        // Aceita dois formatos:
        // 1) ordem[item_id] = pos
        // 2) ordens[] = item_id // em ordem desejada
        $ordemAssoc  = (array) $request->input('ordem', []);
        $ordensLista = (array) $request->input('ordens', []);

        $pares = [];

        if (!empty($ordemAssoc)) {
            foreach ($ordemAssoc as $id => $ordem) {
                $id    = (int) $id;
                $ordem = max(1, (int) $ordem);
                if ($id > 0) {
                    $pares[$id] = $ordem;
                }
            }
        } elseif (!empty($ordensLista)) {
            $pos = 1;
            foreach ($ordensLista as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $pares[$id] = $pos++;
                }
            }
        }

        if (empty($pares)) {
            return back()->with('ok', 'Nada para ordenar.');
        }

        DB::transaction(function () use ($concurso, $pares) {
            foreach ($pares as $id => $ordem) {
                DB::table('concursos_vagas_itens')
                    ->where('concurso_id', $concurso->id)
                    ->where('id', $id)
                    ->update([
                        'ordem'      => $ordem,
                        'updated_at' => now(),
                    ]);
            }
        });

        return back()->with('ok', 'Ordem atualizada.');
    }

    // =======================
    // IMPORTAÇÃO (CSV)
    // =======================
    public function importForm(Concurso $concurso)
    {
        $tipos = DB::table('tipos_vagas_especiais')
            ->where('ativo', 1)
            ->orderBy('nome')
            ->get(['id','nome']);

        return view('admin.concursos.vagas.import', [
            'concurso' => $concurso,
            'tipos'    => $tipos,
        ]);
    }

    /**
     * CSV esperado com cabeçalho, ex:
     * cargo,nivel_id,codigo,salario,jornada,valor_inscricao,local,qtd_total,cr,cota_1,cota_2,...
     * Onde cota_{ID} corresponde ao tipo de vaga especial ID.
     */
    public function importStore(Concurso $concurso, Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:csv,txt|max:20480',
        ]);

        $path = $request->file('arquivo')->getRealPath();
        $rows = array_map('str_getcsv', file($path));
        if (empty($rows)) {
            return back()->withErrors(['arquivo' => 'Arquivo vazio.']);
        }

        // cabeçalho
        $header = array_map(fn($h) => Str::of($h)->trim()->lower()->toString(), array_shift($rows));

        DB::transaction(function () use ($concurso, $rows, $header) {
            foreach ($rows as $r) {
                if (count($r) === 1 && trim((string)$r[0]) === '') continue;
                $row = [];
                foreach ($header as $i => $col) {
                    $row[$col] = $r[$i] ?? null;
                }

                // monta payload semelhante ao form
                $payload = [
                    'cargo_id'          => null,
                    'nivel_id'          => $row['nivel_id'] ?? null,
                    'codigo'            => $row['codigo'] ?? null,
                    'nome'              => $row['cargo'] ?? '',
                    'valor_inscricao'   => $this->normalizeNumber($row['valor_inscricao'] ?? null),
                    'salario'           => $this->normalizeNumber($row['salario'] ?? null),
                    'jornada'           => $row['jornada'] ?? null,
                    'locais'            => [],
                ];

                // único local por linha (pode repetir cargo em linhas diferentes para várias localidades)
                $cotas = [];
                foreach ($row as $k => $v) {
                    if (Str::startsWith($k, 'cota_')) {
                        $tipoId = (int) Str::after($k, 'cota_');
                        $cotas[$tipoId] = (int) $v;
                    }
                }
                $payload['locais'][] = [
                    'local'     => $row['local'] ?? '',
                    'qtd_total' => isset($row['cr']) && (int)$row['cr'] ? 0 : (int) ($row['qtd_total'] ?? 0),
                    'cr'        => (int) ($row['cr'] ?? 0),
                    'cotas'     => $cotas,
                ];

                // upsert cargo + inserir local
                [$cargoId] = $this->upsertCargo($concurso, $payload, /*import*/ true, /*noWipe*/ true);
                $this->persistLocais($concurso, $cargoId, $payload, /*append*/ true);
            }
        });

        return back()->with('ok', 'Importação concluída.');
    }

    // =======================
    // HELPERS
    // =======================
    private function validateCargoRequest(Request $request): array
    {
        // pré-normaliza números no formato brasileiro (ex.: 2.345,67)
        $input = $request->all();
        foreach (['valor_inscricao','salario'] as $k) {
            if (array_key_exists($k, $input)) {
                $input[$k] = $this->normalizeNumber($input[$k]);
            }
        }
        $request->replace($input);

        $rules = [
            'cargo_id'           => ['nullable','integer'],
            'nivel_id'           => ['nullable','integer'],
            'codigo'             => ['nullable','string','max:20'], // código menor
            'nome'               => ['required','string','max:180'],
            'valor_inscricao'    => ['nullable','numeric','min:0'],
            'inscricoes_online'  => ['nullable','in:0,1'],
            'limite_inscricoes'  => ['nullable','integer','min:0'],
            'salario'            => ['nullable','numeric'],
            'jornada'            => ['nullable','string','max:60'],
            'detalhes'           => ['nullable','string'],

            // Locais (novo formato)
            'locais'             => ['nullable','array','min:1'],
            'locais.*.local'     => ['required_with:locais','string','max:150'],
            'locais.*.qtd_total' => ['nullable','integer','min:0'], // checaremos manualmente se não for CR
            'locais.*.cr'        => ['nullable','in:1'],
            'locais.*.cotas'     => ['nullable','array'],
            'locais.*.cotas.*'   => ['nullable','integer','min:0'],

            // Formato antigo (fallback)
            'local'              => ['nullable','string','max:150'],
            'qtd_total'          => ['nullable','integer','min:0'],
            'cr'                 => ['nullable','in:0,1'],
            'cotas_tipo'         => ['nullable','array'],
            'cotas_tipo.*'       => ['nullable','integer'],
            'cotas_qtd'          => ['nullable','array'],
            'cotas_qtd.*'        => ['nullable','integer','min:0'],
        ];

        $data = $request->validate($rules);

        // Checagem manual: se não for CR, exigir qtd_total
        if (!empty($data['locais']) && is_array($data['locais'])) {
            foreach ($data['locais'] as $i => $loc) {
                $isCR = !empty($loc['cr']);
                if (!$isCR && (!isset($loc['qtd_total']) || $loc['qtd_total'] === '')) {
                    throw ValidationException::withMessages([
                        "locais.$i.qtd_total" => 'Informe a quantidade total quando o local não for cadastro de reserva (CR).'
                    ]);
                }
            }
        } elseif (!empty($data['local'])) {
            $isCR = !empty($data['cr']);
            if (!$isCR && (!isset($data['qtd_total']) || $data['qtd_total'] === '')) {
                throw ValidationException::withMessages([
                    "qtd_total" => 'Informe a quantidade total quando o local não for cadastro de reserva (CR).'
                ]);
            }
        }

        return $data;
    }

    /**
     * Converte "2.345,67" -> 2345.67. Mantém float/string numérica; valores vazios viram null.
     */
    private function normalizeNumber($v): ?float
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return (float)$v;
        $s = (string)$v;
        $s = str_replace(['R$', ' ', ' '], '', $s); // remove símbolo e espaços (inclui nbsp)
        $s = str_replace('.', '', $s);              // remove milhar
        $s = str_replace(',', '.', $s);             // vírgula -> ponto
        // mantém apenas dígitos, ponto e sinal
        $s = preg_replace('/[^0-9\.\-]/', '', $s) ?: '0';
        return (float)$s;
    }

    /**
     * Cria/atualiza cargo. Retorna [cargoId, payloadGravado]
     *
     * @param bool $import    quando true, não força unicidade pelo nome para evitar conflito em import em lote
     * @param bool $noWipe    quando true, não limpa itens/cotas (útil em import linha a linha)
     */
    private function upsertCargo(Concurso $concurso, array $data, bool $import = false, bool $noWipe = false): array
    {
        $detCol = Schema::hasColumn('concursos_vagas_cargos','detalhes')
            ? 'detalhes'
            : (Schema::hasColumn('concursos_vagas_cargos','descricao') ? 'descricao' : null);

        // tentar identificar cargo (edição) por ID ou por nome (quando não import)
        $cargoId = null;
        if (!empty($data['cargo_id'])) {
            $cargoId = DB::table('concursos_vagas_cargos')
                ->where('id', (int)$data['cargo_id'])
                ->where('concurso_id', $concurso->id)
                ->value('id');
        }
        if (!$cargoId && !$import) {
            $cargoId = DB::table('concursos_vagas_cargos')
                ->where('concurso_id', $concurso->id)
                ->where('nome', $data['nome'])
                ->value('id');
        }

        $payload = [
            'nome'       => $data['nome'],
            'updated_at' => now(),
        ];

        // Campos opcionais
        if (Schema::hasColumn('concursos_vagas_cargos', 'nivel')) {
            $payload['nivel'] = DB::table('niveis_escolaridade')->where('id', $data['nivel_id'] ?? 0)->value('nome');
        }
        if (Schema::hasColumn('concursos_vagas_cargos', 'nivel_id') && isset($data['nivel_id'])) {
            $payload['nivel_id'] = (int) $data['nivel_id'];
        }
        if (Schema::hasColumn('concursos_vagas_cargos', 'codigo') && array_key_exists('codigo', $data)) {
            $payload['codigo'] = $data['codigo'];
        }
        if (Schema::hasColumn('concursos_vagas_cargos', 'taxa') && isset($data['valor_inscricao'])) {
            $payload['taxa'] = $data['valor_inscricao'];
        }
        if (Schema::hasColumn('concursos_vagas_cargos', 'valor_inscricao') && isset($data['valor_inscricao'])) {
            $payload['valor_inscricao'] = $data['valor_inscricao'];
        }
        if (Schema::hasColumn('concursos_vagas_cargos', 'inscricoes_online') && isset($data['inscricoes_online'])) {
            $payload['inscricoes_online'] = (int) $data['inscricoes_online'];
        }
        if (Schema::hasColumn('concursos_vagas_cargos', 'limite_inscricoes') && isset($data['limite_inscricoes'])) {
            $payload['limite_inscricoes'] = (int) $data['limite_inscricoes'];
        }
        if (Schema::hasColumn('concursos_vagas_cargos', 'salario') && array_key_exists('salario', $data)) {
            $payload['salario'] = $data['salario'];
        }
        if (Schema::hasColumn('concursos_vagas_cargos', 'jornada') && array_key_exists('jornada', $data)) {
            $payload['jornada'] = $data['jornada'];
        }
        if ($detCol && array_key_exists('detalhes', $data)) {
            $payload[$detCol] = $data['detalhes'];
        }

        if ($cargoId) {
            // evita duplicar por nome
            $dup = DB::table('concursos_vagas_cargos')
                ->where('concurso_id', $concurso->id)
                ->where('nome', $payload['nome'])
                ->where('id', '!=', $cargoId)
                ->exists();
            if ($dup) {
                throw ValidationException::withMessages([
                    'nome' => 'Já existe outro cargo com este nome neste concurso.'
                ]);
            }

            DB::table('concursos_vagas_cargos')
                ->where('id', $cargoId)
                ->update($payload);
        } else {
            $payload['concurso_id'] = $concurso->id;
            $payload['created_at']  = now();
            $cargoId = DB::table('concursos_vagas_cargos')->insertGetId($payload);
        }

        return [$cargoId, $payload];
    }

    private function persistLocais(Concurso $concurso, int $cargoId, array $data, bool $append = false): void
    {
        $usaLocalString = Schema::hasColumn('concursos_vagas_itens', 'local');
        $colHasNivelId  = Schema::hasColumn('concursos_vagas_itens', 'nivel_id');
        $colHasCR       = Schema::hasColumn('concursos_vagas_itens', 'cr');
        $colHasVagas    = Schema::hasColumn('concursos_vagas_itens', 'vagas_totais');
        $colHasObs      = Schema::hasColumn('concursos_vagas_itens', 'observacao');

        $saveItem = function(array $loc) use ($concurso, $cargoId, $usaLocalString, $colHasNivelId, $colHasCR, $colHasVagas, $colHasObs, $data) {
            $itemPayload = [
                'concurso_id' => $concurso->id,
                'cargo_id'    => $cargoId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];

            if ($usaLocalString) {
                $itemPayload['local'] = $loc['local'];
            } else {
                $localidadeId = DB::table('concursos_vagas_localidades')
                    ->where('concurso_id', $concurso->id)
                    ->where('nome', $loc['local'])
                    ->value('id');

                if (!$localidadeId) {
                    $localidadeId = DB::table('concursos_vagas_localidades')->insertGetId([
                        'concurso_id' => $concurso->id,
                        'nome'        => $loc['local'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
                $itemPayload['localidade_id'] = $localidadeId;
            }

            $isCR = (int) (!empty($loc['cr']) ? 1 : 0);
            if ($colHasCR) {
                $itemPayload['cr'] = $isCR;
            }
            if ($colHasVagas) {
                $itemPayload['vagas_totais'] = $isCR ? 0 : (int) ($loc['qtd_total'] ?? 0);
            }
            if ($colHasNivelId && isset($data['nivel_id'])) {
                $itemPayload['nivel_id'] = (int) $data['nivel_id'];
            }
            if ($colHasObs) {
                $itemPayload['observacao'] = null;
            }

            return DB::table('concursos_vagas_itens')->insertGetId($itemPayload);
        };

        $saveCotas = function(int $itemId, array $cotasMap, ?int $total, bool $isCR) {
            if (!$isCR && $total !== null) {
                $soma = array_sum(array_map('intval', $cotasMap));
                if ($soma > (int)$total) {
                    throw ValidationException::withMessages([
                        'cotas' => 'A soma das vagas especiais excede a quantidade total.'
                    ]);
                }
            }
            foreach ($cotasMap as $tipoId => $qtd) {
                DB::table('concursos_vagas_cotas')->updateOrInsert(
                    ['item_id' => $itemId, 'tipo_id' => (int)$tipoId],
                    ['vagas' => max(0, (int)$qtd), 'created_at' => now(), 'updated_at' => now()]
                );
            }

            // Atualiza colunas-resumo de modalidades (qtd_total, qtd_pcds, qtd_negros, qtd_indigenas)
            $this->syncResumoModalidades($itemId, $total, $cotasMap, $isCR);
        };

        // ===== Novo formato: vários locais
        if (!empty($data['locais']) && is_array($data['locais'])) {
            foreach ($data['locais'] as $loc) {
                if (!isset($loc['local']) || trim((string)$loc['local']) === '') {
                    continue; // ignora linhas vazias
                }
                $itemId = $saveItem($loc);

                $isCR  = !empty($loc['cr']);
                $total = $isCR ? 0 : (isset($loc['qtd_total']) ? (int)$loc['qtd_total'] : null);

                // cotas no formato assoc: [tipo_id => qtd]
                $cotasMap = [];
                if (!empty($loc['cotas']) && is_array($loc['cotas'])) {
                    foreach ($loc['cotas'] as $tipoId => $qtd) {
                        $cotasMap[(int)$tipoId] = max(0, (int)$qtd);
                    }
                }
                $saveCotas($itemId, $cotasMap, $total, $isCR);
            }
            return;
        }

        // ===== Formato antigo: um local + pares cotas_tipo/cotas_qtd
        if (!empty($data['local'])) {
            $itemId = $saveItem([
                'local'      => $data['local'],
                'qtd_total'  => $data['qtd_total'] ?? 0,
                'cr'         => $data['cr'] ?? 0,
            ]);

            $isCR  = !empty($data['cr']);
            $total = $isCR ? 0 : (isset($data['qtd_total']) ? (int)$data['qtd_total'] : null);

            $map = [];
            $tipos = $data['cotas_tipo'] ?? [];
            $qtde  = $data['cotas_qtd']  ?? [];
            foreach ($tipos as $k => $tipoId) {
                $tipoId = (int) $tipoId;
                if ($tipoId <= 0) continue;
                $q = (int) ($qtde[$k] ?? 0);
                $map[$tipoId] = ($map[$tipoId] ?? 0) + max(0, $q);
            }
            $saveCotas($itemId, $map, $total, $isCR);
            return;
        }

        throw ValidationException::withMessages(['locais' => 'Informe ao menos um Local.']);
    }

    /**
     * Atualiza colunas-resumo de modalidades em concursos_vagas_itens
     * (qtd_total = ampla, qtd_pcds, qtd_negros, qtd_indigenas)
     *
     * NÃO atualiza mais qtd_outros — valores de cotas "diferentes" permanecem apenas
     * na tabela concursos_vagas_cotas, de forma totalmente dinâmica.
     */
    private function syncResumoModalidades(int $itemId, ?int $total, array $cotasMap, bool $isCR = false): void
    {
        static $cols = null;
        static $tipos = null;

        if ($cols === null) {
            $cols = [
                'qtd_total'     => Schema::hasColumn('concursos_vagas_itens', 'qtd_total'),
                'qtd_pcds'      => Schema::hasColumn('concursos_vagas_itens', 'qtd_pcds'),
                'qtd_negros'    => Schema::hasColumn('concursos_vagas_itens', 'qtd_negros'),
                'qtd_indigenas' => Schema::hasColumn('concursos_vagas_itens', 'qtd_indigenas'),
                // NÃO usamos mais qtd_outros
            ];
        }

        if (!($cols['qtd_total'] || $cols['qtd_pcds'] || $cols['qtd_negros'] || $cols['qtd_indigenas'])) {
            return; // nada a fazer
        }

        if ($tipos === null) {
            if (Schema::hasTable('tipos_vagas_especiais')) {
                $tipos = DB::table('tipos_vagas_especiais')->pluck('nome', 'id')->toArray();
            } else {
                $tipos = [];
            }
        }

        $totalVagas = (!$isCR && $total !== null) ? (int)$total : 0;

        $qtdPcds      = 0;
        $qtdNegros    = 0;
        $qtdIndigenas = 0;
        $qtdOutros    = 0; // só para calcular ampla corretamente

        foreach ($cotasMap as $tipoId => $qtd) {
            $qtd = max(0, (int)$qtd);
            $nome = mb_strtolower($tipos[$tipoId] ?? '');

            if (str_contains($nome, 'pcd') || str_contains($nome, 'deficien')) {
                $qtdPcds += $qtd;
            } elseif (str_contains($nome, 'negro') || str_contains($nome, 'preto') || str_contains($nome, 'pardo') || str_contains($nome, 'afro')) {
                $qtdNegros += $qtd;
            } elseif (str_contains($nome, 'indigen')) {
                $qtdIndigenas += $qtd;
            } else {
                // outras cotas não mapeadas em campos fixos
                $qtdOutros += $qtd;
            }
        }

        $somaCotas = $qtdPcds + $qtdNegros + $qtdIndigenas + $qtdOutros;
        $qtdAmpla  = max(0, $totalVagas - $somaCotas);

        $update = ['updated_at' => now()];
        if ($cols['qtd_total'])     $update['qtd_total']     = $qtdAmpla;
        if ($cols['qtd_pcds'])      $update['qtd_pcds']      = $qtdPcds;
        if ($cols['qtd_negros'])    $update['qtd_negros']    = $qtdNegros;
        if ($cols['qtd_indigenas']) $update['qtd_indigenas'] = $qtdIndigenas;
        // NÃO setamos qtd_outros aqui

        DB::table('concursos_vagas_itens')
            ->where('id', $itemId)
            ->update($update);
    }
}
