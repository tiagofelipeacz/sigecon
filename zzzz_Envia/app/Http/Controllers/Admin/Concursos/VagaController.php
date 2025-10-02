<?php

namespace App\Http\Controllers\Admin\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VagaController extends Controller
{
    // =======================
    // LISTAGEM (UMA LINHA POR CARGO)
    // =======================
    public function index(Concurso $concurso, Request $request)
    {
        // Tipos de Vagas Especiais (ativos) -> para montar os nomes das cotas usadas
        $tiposQ = DB::table('tipos_vagas_especiais')->where('ativo', 1);
        if (Schema::hasColumn('tipos_vagas_especiais', 'ordem')) {
            $tiposQ->orderBy('ordem');
        }
        $tipos = $tiposQ->orderBy('nome')->get(['id','nome']);

        // Campos opcionais em "concursos_vagas_cargos"
        $hasNivelColCargo   = Schema::hasColumn('concursos_vagas_cargos', 'nivel');
        $hasNivelIdCargo    = Schema::hasColumn('concursos_vagas_cargos', 'nivel_id');
        $hasCodigoCargo     = Schema::hasColumn('concursos_vagas_cargos', 'codigo');
        $hasValorCargoTaxa  = Schema::hasColumn('concursos_vagas_cargos', 'taxa');
        $hasValorCargoValor = Schema::hasColumn('concursos_vagas_cargos', 'valor_inscricao');

        // Local pode ser string (coluna 'local') ou vir por FK 'localidade_id'
        $usaLocalString = Schema::hasColumn('concursos_vagas_itens', 'local');
        $localKey = $usaLocalString ? 'i.local' : 'i.localidade_id';

        // Total por item pode existir em i.vagas_totais
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

        // Query principal: agrega por CARGO
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
                COUNT(DISTINCT {$localKey})            as localidades,
                SUM({$exprTotalItem})                  as total,
                MAX({$colValorSelect})                 as valor
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

        // Mapa item_id => cargo_id (para somar as cotas por cargo)
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
                $cargoId = $itemCargo[$r->item_id] ?? null;
                if ($cargoId) {
                    $cotasPorCargo[$cargoId][$r->tipo_id] =
                        ($cotasPorCargo[$cargoId][$r->tipo_id] ?? 0) + (int)$r->vagas;
                }
            }
        }

        return view('admin.concursos.vagas.index', [
            'concurso'     => $concurso,
            'linhas'       => $linhas,
            'tipos'        => $tipos,
            'cotasPorCargo'=> $cotasPorCargo,
        ]);
    }

    // =======================
    // FORM DE CADASTRO (carrega dados quando ?cargo=ID)
    // =======================
    public function create(Concurso $concurso, Request $request)
    {
        $tiposQ = DB::table('tipos_vagas_especiais')->where('ativo', 1);
        if (Schema::hasColumn('tipos_vagas_especiais', 'ordem')) {
            $tiposQ->orderBy('ordem');
        }
        $tipos = $tiposQ->orderBy('nome')->get();

        $niveis = DB::table('niveis_escolaridade')
            ->when(Schema::hasColumn('niveis_escolaridade', 'ativo'), fn($q) => $q->where('ativo', 1))
            ->orderByRaw(Schema::hasColumn('niveis_escolaridade','ordem') ? 'ordem IS NULL, ordem ASC, nome ASC' : 'nome ASC')
            ->get();

        // >>> PRÉ-PREENCHIMENTO PARA "editar" via ?cargo=ID
        $defaults = [];
        $cargoId = (int) $request->query('cargo', 0);

        if ($cargoId) {
            $cargo = DB::table('concursos_vagas_cargos')
                ->where('id', $cargoId)
                ->where('concurso_id', $concurso->id)
                ->first();

            if ($cargo) {
                // coluna de detalhes/descrição dinâmica
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

                // select seguro (colunas opcionais)
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
            }
        }

        return view('admin.concursos.vagas.create', [
            'concurso' => $concurso,
            'tipos'    => $tipos,
            'niveis'   => $niveis,
            'defaults' => $defaults, // <<< usado pela view para pré-preencher
        ]);
    }

    // =======================
    // SALVAR (Cria novo OU atualiza se já existir o cargo)
    // =======================
    public function store(Concurso $concurso, Request $request)
    {
        // Validação base do cargo
        $rules = [
            'cargo_id'           => ['nullable','integer'],
            'nivel_id'           => ['nullable','integer'],
            'codigo'             => ['nullable','string','max:50'],
            'nome'               => ['required','string','max:180'],
            'valor_inscricao'    => ['nullable','numeric','min:0'],
            'inscricoes_online'  => ['nullable','in:0,1'],
            'limite_inscricoes'  => ['nullable','integer','min:0'],
            'salario'            => ['nullable','numeric'],
            'jornada'            => ['nullable','string','max:60'],
            'detalhes'           => ['nullable','string'],
        ];

        // Suporte ao novo formato (múltiplos locais)
        $rules += [
            'locais'                 => ['nullable','array','min:1'],
            'locais.*.local'         => ['required_with:locais','string','max:150'],
            'locais.*.qtd_total'     => ['required_without:locais.*.cr','nullable','integer','min:0'],
            'locais.*.cr'            => ['nullable','in:1'],
            'locais.*.cotas'         => ['nullable','array'],
            'locais.*.cotas.*'       => ['nullable','integer','min:0'],
        ];

        // Suporte ao formato antigo (um local + cotas_tipo/cotas_qtd)
        $rules += [
            'local'              => ['nullable','string','max:150'],
            'qtd_total'          => ['nullable','integer','min:0'],
            'cr'                 => ['nullable','in:0,1'],
            'cotas_tipo'         => ['nullable','array'],
            'cotas_tipo.*'       => ['nullable','integer'],
            'cotas_qtd'          => ['nullable','array'],
            'cotas_qtd.*'        => ['nullable','integer','min:0'],
        ];

        $data = $request->validate($rules);

        // Transação para manter consistência
        DB::transaction(function () use ($concurso, $data) {
            // Detecta coluna de detalhes/descricao
            $detCol = Schema::hasColumn('concursos_vagas_cargos','detalhes')
                ? 'detalhes'
                : (Schema::hasColumn('concursos_vagas_cargos','descricao') ? 'descricao' : null);

            // Tenta identificar se é EDIÇÃO (cargo_id enviado) ou se já existe cargo com mesmo nome no concurso
            $cargoId = null;
            if (!empty($data['cargo_id'])) {
                $cargoId = DB::table('concursos_vagas_cargos')
                    ->where('id', (int)$data['cargo_id'])
                    ->where('concurso_id', $concurso->id)
                    ->value('id');
            }
            if (!$cargoId) {
                $cargoId = DB::table('concursos_vagas_cargos')
                    ->where('concurso_id', $concurso->id)
                    ->where('nome', $data['nome'])
                    ->value('id');
            }

            // Monta payload base
            $cargoPayload = [
                'nome'       => $data['nome'],
                'updated_at' => now(),
            ];

            // Campos opcionais
            if (Schema::hasColumn('concursos_vagas_cargos', 'nivel')) {
                $cargoPayload['nivel'] = DB::table('niveis_escolaridade')->where('id', $data['nivel_id'] ?? 0)->value('nome');
            }
            if (Schema::hasColumn('concursos_vagas_cargos', 'nivel_id') && isset($data['nivel_id'])) {
                $cargoPayload['nivel_id'] = (int) $data['nivel_id'];
            }
            if (Schema::hasColumn('concursos_vagas_cargos', 'codigo') && array_key_exists('codigo', $data)) {
                $cargoPayload['codigo'] = $data['codigo'];
            }
            if (Schema::hasColumn('concursos_vagas_cargos', 'taxa') && isset($data['valor_inscricao'])) {
                $cargoPayload['taxa'] = $data['valor_inscricao'];
            }
            if (Schema::hasColumn('concursos_vagas_cargos', 'valor_inscricao') && isset($data['valor_inscricao'])) {
                $cargoPayload['valor_inscricao'] = $data['valor_inscricao'];
            }
            if (Schema::hasColumn('concursos_vagas_cargos', 'inscricoes_online') && isset($data['inscricoes_online'])) {
                $cargoPayload['inscricoes_online'] = (int) $data['inscricoes_online'];
            }
            if (Schema::hasColumn('concursos_vagas_cargos', 'limite_inscricoes') && isset($data['limite_inscricoes'])) {
                $cargoPayload['limite_inscricoes'] = (int) $data['limite_inscricoes'];
            }
            if (Schema::hasColumn('concursos_vagas_cargos', 'salario') && array_key_exists('salario', $data)) {
                $cargoPayload['salario'] = $data['salario'];
            }
            if (Schema::hasColumn('concursos_vagas_cargos', 'jornada') && array_key_exists('jornada', $data)) {
                $cargoPayload['jornada'] = $data['jornada'];
            }
            if ($detCol && array_key_exists('detalhes', $data)) {
                $cargoPayload[$detCol] = $data['detalhes'];
            }

            // Se for atualização, previne colisão de nome com outro cargo
            if ($cargoId) {
                $dup = DB::table('concursos_vagas_cargos')
                    ->where('concurso_id', $concurso->id)
                    ->where('nome', $cargoPayload['nome'])
                    ->where('id', '!=', $cargoId)
                    ->exists();
                if ($dup) {
                    abort(422, 'Já existe outro cargo com este nome neste concurso.');
                }

                DB::table('concursos_vagas_cargos')
                    ->where('id', $cargoId)
                    ->update($cargoPayload);
            } else {
                // Criação
                $cargoPayload['concurso_id'] = $concurso->id;
                $cargoPayload['created_at']  = now();
                $cargoId = DB::table('concursos_vagas_cargos')->insertGetId($cargoPayload);
            }

            // Helper: inserir/descobrir localidade_id quando tabela não tem coluna 'local'
            $usaLocalString = Schema::hasColumn('concursos_vagas_itens', 'local');
            $colHasNivelId  = Schema::hasColumn('concursos_vagas_itens', 'nivel_id');
            $colHasCR       = Schema::hasColumn('concursos_vagas_itens', 'cr');
            $colHasVagas    = Schema::hasColumn('concursos_vagas_itens', 'vagas_totais');

            $saveItem = function(array $loc) use ($concurso, $cargoId, $usaLocalString, $colHasNivelId, $colHasCR, $colHasVagas, $data) {
                $itemPayload = [
                    'concurso_id' => $concurso->id,
                    'cargo_id'    => $cargoId,
                    'observacao'  => null,
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

                $isCR = (int) (($loc['cr'] ?? 0) ? 1 : 0);
                if ($colHasCR)    $itemPayload['cr'] = $isCR;
                if ($colHasVagas) $itemPayload['vagas_totais'] = $isCR ? 0 : (int) ($loc['qtd_total'] ?? 0);
                if ($colHasNivelId && isset($data['nivel_id'])) $itemPayload['nivel_id'] = (int) $data['nivel_id'];

                return DB::table('concursos_vagas_itens')->insertGetId($itemPayload);
            };

            $saveCotas = function(int $itemId, array $cotasMap, ?int $total, bool $isCR) {
                // regra: se não for CR e houver total, Σ(cotas) <= total
                if (!$isCR && $total !== null) {
                    $soma = array_sum(array_map('intval', $cotasMap));
                    if ($soma > (int)$total) {
                        abort(422, 'A soma das vagas especiais excede a quantidade total.');
                    }
                }
                foreach ($cotasMap as $tipoId => $qtd) {
                    $tipoId = (int) $tipoId;
                    $qtd = max(0, (int) $qtd);
                    DB::table('concursos_vagas_cotas')->updateOrInsert(
                        ['item_id' => $itemId, 'tipo_id' => $tipoId],
                        ['vagas' => $qtd, 'created_at' => now(), 'updated_at' => now()]
                    );
                }
            };

            // Se for atualização, apaga itens/cotas antigos do cargo e recria conforme o form
            if ($cargoId) {
                $oldItemIds = DB::table('concursos_vagas_itens')
                    ->where('concurso_id', $concurso->id)
                    ->where('cargo_id', $cargoId)
                    ->pluck('id');

                if ($oldItemIds->isNotEmpty()) {
                    DB::table('concursos_vagas_cotas')->whereIn('item_id', $oldItemIds)->delete();
                    DB::table('concursos_vagas_itens')->whereIn('id', $oldItemIds)->delete();
                }
            }

            // ===== Novo formato: vários locais
            if (!empty($data['locais']) && is_array($data['locais'])) {
                foreach ($data['locais'] as $loc) {
                    if (!isset($loc['local']) || trim((string)$loc['local']) === '') {
                        // pular entradas vazias (usuário pode ter adicionado/removido via JS)
                        continue;
                    }
                    $itemId = $saveItem($loc);

                    $isCR = (bool) (($loc['cr'] ?? 0) ? 1 : 0);
                    $total = $isCR ? 0 : (isset($loc['qtd_total']) ? (int)$loc['qtd_total'] : null);

                    // cotas no formato assoc: [tipo_id => qtd]
                    $cotasMap = [];
                    if (!empty($loc['cotas']) && is_array($loc['cotas'])) {
                        foreach ($loc['cotas'] as $tipoId => $qtd) {
                            $tipoId = (int)$tipoId;
                            $qtd = max(0, (int)$qtd);
                            $cotasMap[$tipoId] = $qtd;
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

                $isCR  = (bool) (($data['cr'] ?? 0) ? 1 : 0);
                $total = $isCR ? 0 : (isset($data['qtd_total']) ? (int)$data['qtd_total'] : null);

                $map = [];
                $tipos = $data['cotas_tipo'] ?? [];
                $qtde  = $data['cotas_qtd']  ?? [];
                foreach ($tipos as $k => $tipoId) {
                    $tipoId = (int) $tipoId;
                    if ($tipoId <= 0) continue;
                    $q = (int) ($qtde[$k] ?? 0);
                    if ($q < 0) $q = 0;
                    $map[$tipoId] = ($map[$tipoId] ?? 0) + $q;
                }
                $saveCotas($itemId, $map, $total, $isCR);

                return;
            }

            // Nenhum local válido
            abort(422, 'Informe ao menos um Local.');
        });

        return redirect()->route('admin.concursos.vagas.index', $concurso->id)
            ->with('ok', 'Vaga salva com sucesso.');
    }

    // =======================
    // STUBS (opcionais)
    // =======================
    public function edit(Concurso $concurso, $itemId)
    {
        abort(501, 'Em breve'); // placeholder
    }

    public function update(Concurso $concurso, $itemId, Request $request)
    {
        abort(501, 'Em breve'); // placeholder
    }

    public function destroy(Concurso $concurso, $itemId)
    {
        DB::table('concursos_vagas_itens')
            ->where('id', $itemId)
            ->where('concurso_id', $concurso->id)
            ->delete();

        DB::table('concursos_vagas_cotas')->where('item_id', $itemId)->delete();

        return back()->with('ok', 'Vaga excluída.');
    }
}
