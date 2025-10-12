<?php

namespace App\Http\Controllers\Admin\Concursos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Models\Concurso;
use App\Models\User;
use App\Models\Inscricao;
use App\Models\Candidato;
use Illuminate\Validation\ValidationException;

class InscritosController extends Controller
{
    public const STATUS = ['rascunho','pendente_pagamento','confirmada','cancelada','importada'];

    public const STATUS_LBL = [
        'rascunho'            => 'Rascunho',
        'pendente_pagamento'  => 'Pendente',
        'confirmada'          => 'Confirmada',
        'cancelada'           => 'Cancelada',
        'importada'           => 'Importada',
    ];

    public const MODALIDADES = ['ampla','pcd','negros','outras'];

    /** Descobre a FK em INSCRICOES: retorna [colunaFK, tabelaReferenciada] */
    private function fkInscricoes(): array
    {
        if (Schema::hasColumn('inscricoes', 'concurso_id')) return ['concurso_id', 'concursos'];
        return ['edital_id', 'editais'];
    }

    /** Descobre a FK em CARGOS: retorna [colunaFK, tabelaReferenciada] */
    private function fkCargos(): array
    {
        if (Schema::hasColumn('cargos', 'concurso_id')) return ['concurso_id', 'concursos'];
        return ['edital_id', 'editais'];
    }

    /** Garante um "edital sombra" com o mesmo ID do concurso quando o schema usa edital_id */
    private function ensureShadowEdital(Concurso $concurso): void
    {
        if (!Schema::hasTable('editais')) return;

        $exists = DB::table('editais')->where('id', $concurso->id)->exists();
        if ($exists) return;

        $tenantId = (int) (auth()->user()->tenant_id ?? 1);
        $numero   = (string) ($concurso->numero_edital ?? $concurso->id);
        $titulo   = (string) ($concurso->titulo ?? ('Concurso #'.$concurso->id));

        DB::table('editais')->insert([
            'id'         => $concurso->id,
            'tenant_id'  => $tenantId,
            'numero'     => $numero,
            'titulo'     => $titulo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Existe o registro referenciado? (para respeitar FK atual do banco) */
    private function referencedExists(string $refTable, int $id): bool
    {
        return DB::table($refTable)->where('id', $id)->exists();
    }

    /** Listagem */
    public function index(Request $request, Concurso $concurso)
    {
        [$fkInsc, $refInsc] = $this->fkInscricoes();

        $q = trim((string) $request->get('q', ''));

        $qb = DB::table('inscricoes as i')
            ->leftJoin('users as u', 'u.id', '=', 'i.user_id')
            ->leftJoin('cargos as cg', 'cg.id', '=', 'i.cargo_id')
            ->where("i.$fkInsc", $concurso->id);

        // Expor nome da localidade quando existir
        if (Schema::hasColumn('inscricoes', 'item_id') && Schema::hasTable('concursos_vagas_itens')) {
            $qb->leftJoin('concursos_vagas_itens as cvi', 'cvi.id', '=', 'i.item_id');
            if (Schema::hasColumn('concursos_vagas_itens', 'local')) {
                $qb->addSelect(DB::raw('cvi.local as localidade_nome'));
            } elseif (Schema::hasTable('concursos_vagas_localidades')) {
                $colLocal = $this->firstExistingColumn('concursos_vagas_localidades', [
                    'nome','local_nome','descricao','titulo','cidade','municipio','nome_local','nome_cidade'
                ]) ?? 'nome';
                $qb->leftJoin('concursos_vagas_localidades as cvl', 'cvl.id', '=', 'cvi.localidade_id');
                $qb->addSelect(DB::raw("cvl.`{$colLocal}` as localidade_nome"));
            }
        } elseif (Schema::hasColumn('inscricoes', 'localidade_id') && Schema::hasTable('concursos_vagas_localidades')) {
            $colLocal = $this->firstExistingColumn('concursos_vagas_localidades', [
                'nome','local_nome','descricao','titulo','cidade','municipio','nome_local','nome_cidade'
            ]) ?? 'nome';
            $qb->leftJoin('concursos_vagas_localidades as cvl', 'cvl.id', '=', 'i.localidade_id');
            $qb->addSelect(DB::raw("cvl.`{$colLocal}` as localidade_nome"));
        }

        if ($q !== '') {
            $qb->where(function ($w) use ($q) {
                $like = '%'.Str::of($q)->replace(' ', '%').'%';
                $w->orWhere('i.nome_inscricao', 'like', $like)
                  ->orWhere('i.nome_candidato', 'like', $like)
                  ->orWhere('i.cpf', 'like', '%'.preg_replace('/\D/','',$q).'%')
                  ->orWhere('cg.nome', 'like', $like)
                  ->orWhere('i.status', 'like', $like)
                  ->orWhere('i.id', '=', (int) $q);
            });
        }

        if ($st = $request->get('status')) $qb->where('i.status', $st);
        if ($mod = $request->get('modalidade')) $qb->where('i.modalidade', $mod);

        $adv = (array) $request->get('adv', []);
        if (!empty($adv['inscricao']))   $qb->where('i.id', (int) $adv['inscricao']);
        if (!empty($adv['nome_inscricao'])) $qb->where('i.nome_inscricao', 'like', '%'.$adv['nome_inscricao'].'%');
        if (!empty($adv['nome_candidato'])) $qb->where('i.nome_candidato', 'like', '%'.$adv['nome_candidato'].'%');
        if (!empty($adv['cpf'])) $qb->where('i.cpf', 'like', '%'.preg_replace('/\D/','',$adv['cpf']).'%');
        if (!empty($adv['documento']) && Schema::hasColumn('inscricoes', 'documento')) $qb->where('i.documento', 'like', '%'.$adv['documento'].'%');
        if (!empty($adv['nascimento'])) $qb->whereDate('i.nascimento', $adv['nascimento']);
        if (!empty($adv['cargo_id']))   $qb->where('i.cargo_id', (int) $adv['cargo_id']);
        if (!empty($adv['data_inscricao'])) $qb->whereDate('i.created_at', $adv['data_inscricao']);
        if (!empty($adv['situacao']))   $qb->where('i.status', $adv['situacao']);
        if (!empty($adv['cidade']) && Schema::hasColumn('inscricoes', 'cidade')) $qb->where('i.cidade', 'like', '%'.$adv['cidade'].'%');

        $qb->selectRaw('i.*, cg.nome as cargo_nome')->orderByDesc('i.id');

        $inscricoes = $qb->paginate(20)->withQueryString();

        $statusCounts = DB::table('inscricoes')
            ->select('status', DB::raw('count(*) as total'))
            ->where($fkInsc, $concurso->id)
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        [$fkCargo] = $this->fkCargos();
        $cargos = DB::table('cargos as cg')
            ->select('cg.id','cg.nome')
            ->where("cg.$fkCargo", $concurso->id)
            ->orderBy('cg.nome')
            ->get();

        return view('admin.concursos.inscritos.index', [
            'concurso'     => $concurso,
            'inscricoes'   => $inscricoes,
            'statusCounts' => $statusCounts,
            'q'            => $q,
            'STATUS'       => self::STATUS,
            'STATUS_LBL'   => self::STATUS_LBL,
            'MODALIDADES'  => self::MODALIDADES,
            'cargos'       => $cargos,
        ]);
    }

    /** Form Nova */
    public function create(Request $request, Concurso $concurso)
    {
        [$fkInsc, $refInsc] = $this->fkInscricoes();
        [$fkCargo, $refCargo] = $this->fkCargos();

        // Se o schema usa edital_id, garanta o edital sombra
        if ($fkCargo === 'edital_id') {
            $this->ensureShadowEdital($concurso);
        }

        $cpf = preg_replace('/\D/','', (string) $request->get('cpf'));
        $candidatoId = $request->get('candidato_id');

        $candidato = null;
        if ($candidatoId)      $candidato = Candidato::query()->find($candidatoId);
        elseif ($cpf)          $candidato = Candidato::query()->where('cpf', $cpf)->first();

        // Cargos reais já existentes
        $cargosReais = DB::table('cargos as cg')
            ->select('cg.id','cg.nome')
            ->where("cg.$fkCargo", $concurso->id)
            ->orderBy('cg.nome')
            ->get();

        // Podemos criar/mostrar "fakes"?
        $canCreateCargo = ($fkCargo === 'concurso_id')
            || ($fkCargo === 'edital_id' && $this->referencedExists('editais', $concurso->id));

        // Monta fakes para NOMES que existam em concursos_vagas_cargos e AINDA NÃO existam em cargos
        $fakeRows = collect();
        $fakeMap  = [];
        $usandoFakes = false;

        if ($canCreateCargo && Schema::hasTable('concursos_vagas_cargos')) {
            $nomesVagas = DB::table('concursos_vagas_cargos')
                ->where('concurso_id', $concurso->id)
                ->orderBy('nome')
                ->pluck('nome')
                ->filter()
                ->map(fn($n)=>trim($n))
                ->unique()
                ->values();

            // nomes reais normalizados para comparação
            $reaisNorm = $cargosReais->map(fn($r) => $this->normalizeName($r->nome))->all();

            $i = 1;
            foreach ($nomesVagas as $nome) {
                $norm = $this->normalizeName($nome);
                if (!in_array($norm, $reaisNorm, true)) {
                    $id = -$i++;
                    $fakeRows->push((object)['id' => $id, 'nome' => $nome]);
                    $fakeMap[$id] = $nome;
                }
            }

            $usandoFakes = $fakeRows->isNotEmpty();
            if ($usandoFakes) {
                session()->put("inscritos.fakeCargoMap.{$concurso->id}", $fakeMap);
            } else {
                session()->forget("inscritos.fakeCargoMap.{$concurso->id}");
            }
        } else {
            session()->forget("inscritos.fakeCargoMap.{$concurso->id}");
        }

        // Cargos finais = reais + fakes (se houver)
        $cargos = $cargosReais->concat($fakeRows)->values();

        // Itens/Localidades por cargo
        $cargoNameToId = $cargosReais
            ->mapWithKeys(fn($r) => [$this->normalizeName($r->nome) => (int)$r->id])
            ->all();

        $itensLocais = collect();
        if (Schema::hasTable('concursos_vagas_itens') && Schema::hasTable('concursos_vagas_cargos')) {
            $usaLocalString = Schema::hasColumn('concursos_vagas_itens', 'local');

            $q = DB::table('concursos_vagas_itens as i')
                ->join('concursos_vagas_cargos as cvc', 'cvc.id', '=', 'i.cargo_id')
                ->where('i.concurso_id', $concurso->id)
                ->select('i.id', DB::raw('cvc.nome as cargo_nome'));

            if (!$usaLocalString && Schema::hasTable('concursos_vagas_localidades')) {
                $colLocal = $this->firstExistingColumn('concursos_vagas_localidades', [
                    'nome','local_nome','descricao','titulo','cidade','municipio','nome_local','nome_cidade'
                ]) ?? 'nome';
                $q->leftJoin('concursos_vagas_localidades as cvl', 'cvl.id', '=', 'i.localidade_id')
                  ->addSelect(DB::raw("cvl.`{$colLocal}` as local_nome"));
            } else {
                $q->addSelect(DB::raw('i.local as local_nome'));
            }

            $raw = $q->orderBy('cvc.nome')->orderBy('local_nome')->get();

            $out = [];
            foreach ($raw as $r) {
                $norm = $this->normalizeName($r->cargo_nome);
                $cargoId = $cargoNameToId[$norm] ?? null;

                // Se não for real e estivermos usando fakes, tenta mapear pelo nome no mapa fake
                if ($cargoId === null && $usandoFakes) {
                    $fakeId = array_search($r->cargo_nome, $fakeMap, true);
                    if ($fakeId !== false) $cargoId = (int)$fakeId; // negativo
                }

                if ($cargoId !== null && $r->local_nome) {
                    $out[] = (object)[
                        'id'         => (int)$r->id,
                        'cargo_id'   => (int)$cargoId,
                        'local_nome' => (string)$r->local_nome,
                    ];
                }
            }
            $itensLocais = collect($out);
        }

        // Condições especiais
        $condicoesEspeciais = collect();
        if (Schema::hasTable('concurso_tipo_condicao_especial') && Schema::hasTable('tipos_condicoes_especiais')) {
            $fkCol  = $this->firstExistingColumn('concurso_tipo_condicao_especial', [
                'tipo_condicao_especial_id','tipo_id','condicao_id','tipo_condicao_id',
                'tipos_condicoes_especiais_id','tce_id','id_tipo','id_condicao','id_tipo_condicao'
            ]);
            $concCol = $this->firstExistingColumn('concurso_tipo_condicao_especial', ['concurso_id','edital_id']) ?? 'concurso_id';

            if ($fkCol) {
                $condicoesEspeciais = DB::table('concurso_tipo_condicao_especial as ctce')
                    ->join('tipos_condicoes_especiais as tce', function ($j) use ($fkCol) {
                        $j->on('tce.id', '=', DB::raw("ctce.`{$fkCol}`"));
                    })
                    ->where("ctce.$concCol", $concurso->id)
                    ->orderBy('tce.nome')
                    ->get(['tce.id','tce.nome']);
            }
        }

        return view('admin.concursos.inscritos.create', [
            'concurso'           => $concurso,
            'cpf'                => $cpf,
            'candidato'          => $candidato,
            'cargos'             => $cargos,
            'itensLocais'        => $itensLocais,
            'condicoesEspeciais' => $condicoesEspeciais,
            'STATUS'             => self::STATUS,
            'STATUS_LBL'         => self::STATUS_LBL,
            'MODALIDADES'        => self::MODALIDADES,
        ]);
    }

    /** Salvar */
    public function store(Request $request, Concurso $concurso)
    {
        [$fkInsc, $refInsc]     = $this->fkInscricoes();
        [$fkCargo, $refCargo]   = $this->fkCargos();

        // Se for schema de edital, garante o edital sombra ANTES de mexer com cargos/inscrições
        if ($fkCargo === 'edital_id' || $fkInsc === 'edital_id') {
            $this->ensureShadowEdital($concurso);
        }

        $data = $request->validate([
            'cpf'                   => ['required','string','size:11'],
            'nome_inscricao'        => ['nullable','string','max:255'],
            'nome_candidato'        => ['required','string','max:255'],
            'nascimento'            => ['nullable','date'],
            'cargo_id'              => ['required','integer'],
            'modalidade'            => ['nullable','in:ampla,pcd,negros,outras'],
            // status agora OBRIGATÓRIO
            'status'                => ['required','in:rascunho,pendente_pagamento,confirmada,cancelada,importada'],
            'candidato_id'          => ['nullable','integer'],
            'preenchimento'         => ['required','in:automatico,manual'],
            'inscricao_numero'      => ['nullable','string','max:30'],
            'item_id'               => ['nullable','integer'],
            'condicoes_especiais'   => ['nullable','array'],
            'condicoes_especiais.*' => ['integer'],
        ]);

        // Checa duplicidade por CPF no mesmo concurso/edital
        $ja = DB::table('inscricoes')
            ->where($fkInsc, $concurso->id)
            ->where('cpf', $data['cpf'])
            ->exists();
        if ($ja) return back()->withErrors(['cpf' => 'Este CPF já possui inscrição neste concurso.'])->withInput();

        // ===== Cargo fake -> cria real (apenas se o schema permitir)
        $cargoId = (int) $data['cargo_id'];
        $fakeMap = (array) session()->get("inscritos.fakeCargoMap.{$concurso->id}", []);

        if ($cargoId < 0) {
            $canCreateCargo = ($fkCargo === 'concurso_id')
                || ($fkCargo === 'edital_id' && $this->referencedExists('editais', $concurso->id));

            if (!$canCreateCargo) {
                return back()->withErrors([
                    'cargo_id' => 'Não é possível criar automaticamente a vaga neste banco. Cadastre a vaga em "Vagas" antes de salvar a inscrição.'
                ])->withInput();
            }

            $nomeCargo = $fakeMap[$cargoId] ?? null;
            if (!$nomeCargo) {
                return back()->withErrors(['cargo_id' => 'Não foi possível identificar o cargo selecionado. Reabra a tela e selecione novamente.'])->withInput();
            }

            $cargoId = DB::table('cargos')->insertGetId([
                $fkCargo    => $concurso->id,
                'nome'       => $nomeCargo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ===== Regra: se o cargo tiver localidades configuradas, item_id é obrigatório
        $temLocalidades = false;
        if (Schema::hasTable('concursos_vagas_itens') && Schema::hasTable('concursos_vagas_cargos')) {

            if ($cargoId < 0) {
                // cargo "fake": checa por nome vindo do mapa fake
                $nomeCargo = $fakeMap[$cargoId] ?? null;
                if ($nomeCargo) {
                    $temLocalidades = DB::table('concursos_vagas_itens as i')
                        ->join('concursos_vagas_cargos as cvc', 'cvc.id', '=', 'i.cargo_id')
                        ->where('i.concurso_id', $concurso->id)
                        ->where('cvc.nome', $nomeCargo)
                        ->exists();
                }
            } else {
                // cargo real: casa pelo nome com a tabela de cargos
                $temLocalidades = DB::table('concursos_vagas_itens as i')
                    ->join('concursos_vagas_cargos as cvc', 'cvc.id', '=', 'i.cargo_id')
                    ->join('cargos as cg2', 'cg2.nome', '=', 'cvc.nome')
                    ->where('i.concurso_id', $concurso->id)
                    ->where('cg2.id', $cargoId)
                    ->exists();
            }
        }
        if ($temLocalidades && empty($data['item_id'])) {
            return back()->withErrors(['item_id' => 'Selecione a localidade para esta vaga.'])->withInput();
        }

        // Verifica se o cargo pertence ao mesmo concurso
        [$fkCargoCol] = $this->fkCargos();
        $cargoOk = DB::table('cargos')->where('id', $cargoId)->where($fkCargoCol, $concurso->id)->exists();
        if (!$cargoOk) return back()->withErrors(['cargo_id' => 'Selecione um cargo válido deste concurso.'])->withInput();

        // Número
        $numeroFinal = null;
        $numeroManual = null;
        if ($data['preenchimento'] === 'manual') {
            $numeroManual = preg_replace('/\D+/', '', (string)($data['inscricao_numero'] ?? ''));
            if ($numeroManual === '') return back()->withErrors(['inscricao_numero' => 'Informe o número da inscrição.'])->withInput();
            if (Schema::hasColumn('inscricoes', 'numero')) {
                $existeNumero = DB::table('inscricoes')->where($fkInsc, $concurso->id)->where('numero', $numeroManual)->exists();
                if ($existeNumero) return back()->withErrors(['inscricao_numero' => 'Este número de inscrição já está em uso.'])->withInput();
            }
        }

        // ===== Transação
        [$inscricaoId, $numeroFinal] = DB::transaction(function() use ($concurso, $fkInsc, $data, $numeroManual, $cargoId) {
            // bloqueia concurso p/ sequência
            $conc = Concurso::query()->lockForUpdate()->find($concurso->id);

            // Calcula valor congelado (snapshot) e coluna destino na inscrição
            $colInscValor   = $this->firstExistingColumn('inscricoes', ['valor_inscricao','taxa_inscricao','taxa','valor']);
            $valorCongelado = $colInscValor
                ? $this->resolveValorInscricao($concurso->id, $cargoId, $data['item_id'] ?? null)
                : null;

            $payload = [
                $fkInsc          => $concurso->id,
                'user_id'        => null,
                'candidato_id'   => $data['candidato_id'] ?? null,
                'cpf'            => $data['cpf'],
                'nome_inscricao' => $data['nome_inscricao'] ?? $data['nome_candidato'],
                'nome_candidato' => $data['nome_candidato'],
                'nascimento'     => $data['nascimento'] ?? null,
                'cargo_id'       => (int) $cargoId,
                'modalidade'     => $data['modalidade'] ?? 'ampla',
                // usa o valor obrigatório validado
                'status'         => $data['status'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            // Anexa o valor congelado se houver coluna correspondente
            if ($colInscValor) {
                $payload[$colInscValor] = $valorCongelado;
            }

            // Localidade na inscrição (se existir coluna)
            if (Schema::hasColumn('inscricoes', 'item_id') && !empty($data['item_id'])) {
                $payload['item_id'] = (int) $data['item_id'];
            } elseif (Schema::hasColumn('inscricoes', 'localidade_id') && !empty($data['item_id'])) {
                $payload['localidade_id'] = (int) $data['item_id'];
            }

            // Número
            if (($data['preenchimento'] ?? 'automatico') === 'manual') {
                $numeroFinal = $numeroManual;
                if (Schema::hasColumn('inscricoes', 'numero')) $payload['numero'] = $numeroFinal;
            } else {
                $proximo = ((int) $conc->sequence_inscricao) + 1;
                $numeroFinal = $proximo;
                if (Schema::hasColumn('inscricoes', 'numero')) $payload['numero'] = $numeroFinal;
                $conc->sequence_inscricao = $proximo;
                $conc->updated_at = now();
                $conc->save();
            }

            $id = DB::table('inscricoes')->insertGetId($payload);

            $this->persistCondicoesEspeciais($id, (array)($data['condicoes_especiais'] ?? []));
            return [$id, $numeroFinal];
        });

        if (!$numeroFinal) $numeroFinal = (int)$concurso->sequence_inscricao + (int)$inscricaoId;

        session()->forget("inscritos.fakeCargoMap.{$concurso->id}");

        return redirect()
            ->route('admin.concursos.inscritos.show', ['concurso' => $concurso->id, 'inscricao' => $inscricaoId])
            ->with('ok', 'Inscrição #'.$numeroFinal.' criada com sucesso.');
    }

    /** Show (resumo enriquecido) */
    public function show(Concurso $concurso, int $inscricaoId)
    {
        [$fkInsc] = $this->fkInscricoes();

        $insc = DB::table('inscricoes as i')
            ->leftJoin('cargos as cg', 'cg.id', '=', 'i.cargo_id')
            ->where('i.id', $inscricaoId)
            ->where("i.$fkInsc", $concurso->id)
            ->select('i.*', 'cg.nome as cargo_nome')
            ->first();

        abort_if(!$insc, 404, 'Inscrição não encontrada.');

        // =========================
        // LOCALIDADE (robusto)
        // =========================
        $localidade = null;
        $provaCidade = null;
        $provaUF = null;

        // 1) Quando a inscrição guarda o item_id
        if (!empty($insc->item_id) && Schema::hasTable('concursos_vagas_itens')) {
            $item = DB::table('concursos_vagas_itens')->where('id', $insc->item_id)->first();
            if ($item) {
                // a) coluna 'local' diretamente no item
                if (Schema::hasColumn('concursos_vagas_itens', 'local') && !empty($item->local)) {
                    $localidade = $item->local;
                }
                // b) via FK para concursos_vagas_localidades
                elseif (!empty($item->localidade_id) && Schema::hasTable('concursos_vagas_localidades')) {
                    $colLocal = $this->firstExistingColumn('concursos_vagas_localidades', [
                        'nome','local_nome','descricao','titulo','cidade','municipio','nome_local','nome_cidade'
                    ]) ?? 'nome';

                    $loc = DB::table('concursos_vagas_localidades')->where('id', $item->localidade_id)->first();
                    if ($loc) {
                        $localidade = $loc->{$colLocal} ?? null;
                        $cCol = $this->firstExistingColumn('concursos_vagas_localidades', ['cidade','municipio','nome_cidade']);
                        $uCol = $this->firstExistingColumn('concursos_vagas_localidades', ['uf','estado']);
                        $provaCidade = $cCol ? ($loc->{$cCol} ?? null) : null;
                        $provaUF     = $uCol ? ($loc->{$uCol} ?? null) : null;
                    }
                }
            }
        }

        // 2) Quando a inscrição guarda localidade_id diretamente
        if ($localidade === null && Schema::hasColumn('inscricoes', 'localidade_id') && !empty($insc->localidade_id) && Schema::hasTable('concursos_vagas_localidades')) {
            $colLocal = $this->firstExistingColumn('concursos_vagas_localidades', [
                'nome','local_nome','descricao','titulo','cidade','municipio','nome_local','nome_cidade'
            ]) ?? 'nome';

            $loc = DB::table('concursos_vagas_localidades')->where('id', $insc->localidade_id)->first();
            if ($loc) {
                $localidade = $loc->{$colLocal} ?? null;
                $cCol = $this->firstExistingColumn('concursos_vagas_localidades', ['cidade','municipio','nome_cidade']);
                $uCol = $this->firstExistingColumn('concursos_vagas_localidades', ['uf','estado']);
                $provaCidade = $cCol ? ($loc->{$cCol} ?? null) : null;
                $provaUF     = $uCol ? ($loc->{$uCol} ?? null) : null;
            }
        }

        // =========================
        // VALOR DA INSCRIÇÃO (ordem de fallback)
        // =========================
        $valorInscricao = null;

        // 1) Campo direto na inscrição
        $colInscValor = $this->firstExistingColumn('inscricoes', ['valor_inscricao','taxa_inscricao','taxa','valor']);
        if ($colInscValor && isset($insc->{$colInscValor}) && $insc->{$colInscValor} !== null) {
            $valorInscricao = (float) $insc->{$colInscValor};
        }

        // 2) Valor configurado no cargo da TELA DE VAGAS (concursos_vagas_cargos), via item_id
        if ($valorInscricao === null
            && !empty($insc->item_id)
            && Schema::hasTable('concursos_vagas_itens')
            && Schema::hasTable('concursos_vagas_cargos')) {

            $colCvcValor = $this->firstExistingColumn('concursos_vagas_cargos', ['valor_inscricao','taxa']);
            if ($colCvcValor) {
                $v = DB::table('concursos_vagas_itens as i')
                    ->join('concursos_vagas_cargos as cvc', 'cvc.id', '=', 'i.cargo_id')
                    ->where('i.id', $insc->item_id)
                    ->value(DB::raw("cvc.`{$colCvcValor}`"));
                if ($v !== null) $valorInscricao = (float) $v;
            }
        }

        // 3) Valor no cargo das VAGAS (concursos_vagas_cargos) casando pelo nome do cargo
        if ($valorInscricao === null
            && !empty($insc->cargo_id)
            && Schema::hasTable('concursos_vagas_cargos')) {

            $colCvcValor = $this->firstExistingColumn('concursos_vagas_cargos', ['valor_inscricao','taxa']);
            if ($colCvcValor) {
                $nomeCargo = DB::table('cargos')->where('id', $insc->cargo_id)->value('nome');
                if ($nomeCargo) {
                    $v = DB::table('concursos_vagas_cargos')
                        ->where('concurso_id', $concurso->id)
                        ->where('nome', $nomeCargo)
                        ->value($colCvcValor);
                    if ($v !== null) $valorInscricao = (float) $v;
                }
            }
        }

        // 4) Valor no cargo da tabela "cargos" (se houver coluna correspondente)
        if ($valorInscricao === null && !empty($insc->cargo_id)) {
            $colCargoValor = $this->firstExistingColumn('cargos', ['valor_inscricao','taxa_inscricao','taxa','valor']);
            if ($colCargoValor) {
                $v = DB::table('cargos')->where('id', $insc->cargo_id)->value($colCargoValor);
                if ($v !== null) $valorInscricao = (float) $v;
            }
        }

        // 5) Valor padrão no concurso
        if ($valorInscricao === null) {
            $colConcValor = $this->firstExistingColumn('concursos', ['valor_inscricao','taxa_inscricao','taxa','valor']);
            if ($colConcValor) {
                $v = DB::table('concursos')->where('id', $concurso->id)->value($colConcValor);
                if ($v !== null) $valorInscricao = (float) $v;
            }
        }

        // -------- Condições especiais --------
        $condicoesSelecionadas = $this->fetchCondicoesEspeciais($inscricaoId);

        // -------- Contatos (prioriza inscrição; fallback candidato) --------
        $contatos = [
            'email'    => null,
            'telefone' => null,
            'celular'  => null,
        ];
        $emailCol = $this->firstExistingColumn('inscricoes', ['email']);
        $telCol   = $this->firstExistingColumn('inscricoes', ['telefone','tel','phone']);
        $celCol   = $this->firstExistingColumn('inscricoes', ['celular','cel','mobile','whatsapp']);

        if ($emailCol && isset($insc->{$emailCol})) $contatos['email'] = $insc->{$emailCol};
        if ($telCol   && isset($insc->{$telCol}))   $contatos['telefone'] = $insc->{$telCol};
        if ($celCol   && isset($insc->{$celCol}))   $contatos['celular']  = $insc->{$celCol};

        if (empty($contatos['email']) || empty($contatos['telefone']) || empty($contatos['celular'])) {
            if (!empty($insc->candidato_id) && Schema::hasTable('candidatos')) {
                $cand = DB::table('candidatos')->where('id', $insc->candidato_id)->first();
                if ($cand) {
                    if (empty($contatos['email']) && isset($cand->email))       $contatos['email']    = $cand->email;
                    if (empty($contatos['telefone']) && isset($cand->telefone)) $contatos['telefone'] = $cand->telefone ?? ($cand->phone ?? null);
                    if (empty($contatos['celular']) && isset($cand->celular))   $contatos['celular']  = $cand->celular ?? null;
                }
            }
        }

        // -------- Detalhes da vaga --------
        $vaga = [
            'escolaridade'  => null,
            'carga_horaria' => null,
            'remuneracao'   => null,
        ];
        if ($insc->cargo_id) {
            $escCol = $this->firstExistingColumn('cargos', ['escolaridade','nivel_escolaridade','nivel','escolaridade_exigida']);
            $chCol  = $this->firstExistingColumn('cargos', ['carga_horaria','carga','horas_semanais']);
            $salCol = $this->firstExistingColumn('cargos', ['salario','faixa_salarial','remuneracao','vencimentos']);

            $row = DB::table('cargos')->where('id', $insc->cargo_id)->first();
            if ($row) {
                $vaga['escolaridade']  = $escCol && isset($row->{$escCol}) ? $row->{$escCol} : null;
                $vaga['carga_horaria'] = $chCol  && isset($row->{$chCol})  ? $row->{$chCol}  : null;
                $vaga['remuneracao']   = $salCol && isset($row->{$salCol}) ? $row->{$salCol} : null;
            }
        }

        // -------- Pagamento (mesma lógica que você já tinha) --------
        $pagamento = null;
        $tryTables = ['boletos','financeiro_boletos','pagamentos','inscricoes_pagamentos'];
        foreach ($tryTables as $tb) {
            if (!Schema::hasTable($tb)) continue;

            $fkCol = $this->firstExistingColumn($tb, ['inscricao_id','id_inscricao','insc_id','reference_id']);
            if (!$fkCol) continue;

            $q = DB::table($tb)->where($fkCol, $inscricaoId);
            $typeCol = $this->firstExistingColumn($tb, ['reference_type','model','tipo_ref','model_type']);
            if ($typeCol) {
                $q->where(function($w) use ($typeCol) {
                    $w->where($typeCol, 'inscricao')->orWhere($typeCol, 'Inscricao')->orWhere($typeCol, 'App\\Models\\Inscricao');
                });
            }

            $rec = $q->orderByDesc('id')->first();
            if ($rec) {
                $statusCol = $this->firstExistingColumn($tb, ['status','situacao']);
                $vencCol   = $this->firstExistingColumn($tb, ['vencimento','due_at','data_vencimento']);
                $valorCol  = $this->firstExistingColumn($tb, ['valor','valor_boleto','valor_total','amount']);
                $formaCol  = $this->firstExistingColumn($tb, ['forma','meio','metodo','gateway']);
                $linhaCol  = $this->firstExistingColumn($tb, ['linha_digitavel','linha','digitable_line']);
                $urlCol    = $this->firstExistingColumn($tb, ['url','link','pdf','boleto_url']);
                $qrCol     = $this->firstExistingColumn($tb, ['qr','qr_code','qrcode']);
                $txidCol   = $this->firstExistingColumn($tb, ['txid','transaction_id','payment_id','nosso_numero']);

                $pagamento = [
                    'status'          => $statusCol ? ($rec->{$statusCol} ?? null) : null,
                    'vencimento'      => $vencCol   ? ($rec->{$vencCol}   ?? null) : null,
                    'valor'           => $valorCol  ? ($rec->{$valorCol}  ?? null) : null,
                    'forma'           => $formaCol  ? ($rec->{$formaCol}  ?? null) : null,
                    'linha_digitavel' => $linhaCol  ? ($rec->{$linhaCol}  ?? null) : null,
                    'url'             => $urlCol    ? ($rec->{$urlCol}    ?? null) : null,
                    'qr'              => $qrCol     ? ($rec->{$qrCol}     ?? null) : null,
                    'txid'            => $txidCol   ? ($rec->{$txidCol}   ?? null) : null,
                ];
                break;
            }
        }

        // -------- Observações internas --------
        $obs = null;
        $obsCol = $this->firstExistingColumn('inscricoes', ['observacoes','obs','anotacoes','nota','observacao']);
        if ($obsCol && isset($insc->{$obsCol})) $obs = $insc->{$obsCol};

        // -------- Histórico de status (inalterado) --------
        $historico = [];
        $histTables = ['inscricoes_status_logs','inscricoes_logs','inscricoes_historico','inscricao_logs'];
        foreach ($histTables as $tb) {
            if (!Schema::hasTable($tb)) continue;
            $fkCol = $this->firstExistingColumn($tb, ['inscricao_id','id_inscricao','insc_id']);
            if (!$fkCol) continue;
            $rows = DB::table($tb)->where($fkCol, $inscricaoId)->orderByDesc('created_at')->limit(10)->get();
            if ($rows->count()) {
                $statusCol = $this->firstExistingColumn($tb, ['status','novo_status','to_status']);
                $userCol   = $this->firstExistingColumn($tb, ['user_id','created_by','id_usuario']);
                $descCol   = $this->firstExistingColumn($tb, ['descricao','description','obs','observacao','motivo']);
                foreach ($rows as $r) {
                    $historico[] = [
                        'quando'  => $r->created_at ?? null,
                        'status'  => $statusCol ? ($r->{$statusCol} ?? null) : null,
                        'user_id' => $userCol ? ($r->{$userCol} ?? null) : null,
                        'texto'   => $descCol ? ($r->{$descCol} ?? null) : null,
                    ];
                }
                break;
            }
        }

        if (!empty($historico)) {
            $ids = collect($historico)->pluck('user_id')->filter()->unique()->values();
            if ($ids->count() && Schema::hasTable('users')) {
                $map = DB::table('users')->whereIn('id', $ids)->pluck('name','id')->toArray();
                foreach ($historico as &$h) {
                    $h['user_nome'] = $h['user_id'] && isset($map[$h['user_id']]) ? $map[$h['user_id']] : null;
                }
                unset($h);
            }
        }

        return view('admin.concursos.inscritos.show', [
            'concurso'        => $concurso,
            'inscricao'       => $insc,
            'localidade'      => $localidade,
            'condicoes'       => $condicoesSelecionadas,
            'STATUS_LBL'      => self::STATUS_LBL,
            'valorInscricao'  => $valorInscricao,
            'contatos'        => $contatos,
            'vaga'            => $vaga,
            'provaCidade'     => $provaCidade,
            'provaUF'         => $provaUF,
            'pagamento'       => $pagamento,
            'observacoes'     => $obs,
            'historico'       => $historico,
        ]);
    }

    public function importForm(Concurso $concurso)
    {
        return view('admin.concursos.inscritos.import', ['concurso' => $concurso]);
    }

    public function importStore(Request $request, Concurso $concurso)
    {
        return back()->with('ok', 'Importação processada.');
    }

    public function dadosExtras(Concurso $concurso)
    {
        return view('admin.concursos.inscritos.dados-extras', ['concurso' => $concurso]);
    }

    public function destroy(Concurso $concurso, int $inscricao)
    {
        [$fkInsc] = $this->fkInscricoes();

        DB::table('inscricoes')
            ->where($fkInsc, $concurso->id)
            ->where('id', $inscricao)
            ->delete();

        return back()->with('ok', 'Inscrição excluída.');
    }

    public function checkCpf(Request $request, Concurso $concurso)
    {
        [$fkInsc] = $this->fkInscricoes();

        $cpf = preg_replace('/\D/', '', (string) $request->input('cpf'));

        if (!$this->isValidCpf($cpf)) {
            return response()->json(['ok'=>false,'message'=>'CPF inválido. Digite 11 dígitos.'], 422);
        }

        $jaInscrito = DB::table('inscricoes')
            ->where($fkInsc, $concurso->id)
            ->where(function($w) use ($cpf) {
                if (Schema::hasColumn('inscricoes', 'documento')) $w->where('cpf', $cpf)->orWhere('documento', $cpf);
                else $w->where('cpf', $cpf);
            })->exists();

        if ($jaInscrito) {
            return response()->json([
                'ok'=>true,'exists'=>true,'ja_inscrito'=>true,
                'message'=>'Este CPF já possui inscrição neste concurso.',
            ]);
        }

        $cand = Candidato::query()->where('cpf', $cpf)->first();

        return response()->json([
            'ok'=>true,
            'exists'=>(bool)$cand,
            'ja_inscrito'=>false,
            'candidato_id'=>$cand->id ?? null,
            'candidato'=>$cand ? [
                'nome'=>$cand->nome ?? $cand->name ?? null,
                'email'=>$cand->email ?? null,
                'telefone'=>$cand->telefone ?? $cand->phone ?? null,
            ] : null,
            'message'=>$cand
                ? 'CPF localizado. Dados do candidato encontrados.'
                : 'CPF não encontrado. Você poderá cadastrar os dados.',
        ]);
    }

    private function isValidCpf(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) $d += $cpf[$c] * (($t + 1) - $c);
            $d = ((10 * $d) % 11) % 10;
            if ((int)$cpf[$c] !== (int)$d) return false;
        }
        return true;
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $col) if (Schema::hasColumn($table, $col)) return $col;
        return null;
    }

    /** Resolve o valor da inscrição de forma robusta (item → vagas_cargos → cargos → concurso) */
    private function resolveValorInscricao(int $concursoId, ?int $cargoId, ?int $itemId): ?float
    {
        // 1) Pelo item → concursos_vagas_cargos
        if ($itemId && Schema::hasTable('concursos_vagas_itens') && Schema::hasTable('concursos_vagas_cargos')) {
            $col = $this->firstExistingColumn('concursos_vagas_cargos', ['valor_inscricao','taxa']);
            if ($col) {
                $v = DB::table('concursos_vagas_itens as i')
                    ->join('concursos_vagas_cargos as cvc','cvc.id','=','i.cargo_id')
                    ->where('i.id', $itemId)
                    ->value(DB::raw("cvc.`{$col}`"));
                if ($v !== null) return (float)$v;
            }
        }

        // 2) Pelo nome do cargo em concursos_vagas_cargos
        if ($cargoId && Schema::hasTable('concursos_vagas_cargos')) {
            $col  = $this->firstExistingColumn('concursos_vagas_cargos', ['valor_inscricao','taxa']);
            $nome = DB::table('cargos')->where('id', $cargoId)->value('nome');
            if ($col && $nome) {
                $v = DB::table('concursos_vagas_cargos')
                    ->where('concurso_id', $concursoId)
                    ->where('nome', $nome)
                    ->value($col);
                if ($v !== null) return (float)$v;
            }
        }

        // 3) Cargo (tabela cargos)
        if ($cargoId) {
            $col = $this->firstExistingColumn('cargos', ['valor_inscricao','taxa_inscricao','taxa','valor']);
            if ($col) {
                $v = DB::table('cargos')->where('id', $cargoId)->value($col);
                if ($v !== null) return (float)$v;
            }
        }

        // 4) Concurso (padrão)
        $col = $this->firstExistingColumn('concursos', ['valor_inscricao','taxa_inscricao','taxa','valor']);
        if ($col) {
            $v = DB::table('concursos')->where('id', $concursoId)->value($col);
            if ($v !== null) return (float)$v;
        }

        return null;
    }

    private function normalizeName(?string $s): string
    {
        $s = (string)$s;
        $s = Str::of($s)->squish()->upper()->toString();
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        if ($t !== false) $s = $t;
        $s = preg_replace('/[^A-Z0-9 ]/','', $s) ?: $s;
        return trim($s);
    }

    private function persistCondicoesEspeciais(int $inscricaoId, array $tipoIds): void
    {
        if (empty($tipoIds)) return;
        $tables = [
            'inscricoes_condicoes_especiais',
            'inscricao_condicao_especial',
            'inscricao_tipo_condicao_especial',
            'inscricoes_tipos_condicoes_especiais',
        ];
        $table = null;
        foreach ($tables as $t) if (Schema::hasTable($t)) { $table = $t; break; }
        if (!$table) return;

        $colInsc = $this->firstExistingColumn($table, ['inscricao_id','id_inscricao']);
        $colTipo = $this->firstExistingColumn($table, ['tipo_id','tipo_condicao_especial_id','id_tipo','id_tipo_condicao','condicao_id']);
        if (!$colInsc || !$colTipo) return;

        DB::table($table)->where($colInsc, $inscricaoId)->delete();

        $now = now();
        $rows = [];
        foreach ($tipoIds as $tid) {
            $tid = (int) $tid;
            if ($tid <= 0) continue;
            $payload = [$colInsc => $inscricaoId, $colTipo => $tid];
            if (Schema::hasColumn($table, 'created_at')) $payload['created_at'] = $now;
            if (Schema::hasColumn($table, 'updated_at')) $payload['updated_at'] = $now;
            $rows[] = $payload;
        }
        if ($rows) DB::table($table)->insert($rows);
    }

    private function fetchCondicoesEspeciais(int $inscricaoId): array
    {
        $tables = [
            'inscricoes_condicoes_especiais',
            'inscricao_condicao_especial',
            'inscricao_tipo_condicao_especial',
            'inscricoes_tipos_condicoes_especiais',
        ];
        $table = null;
        foreach ($tables as $t) if (Schema::hasTable($t)) { $table = $t; break; }
        if (!$table || !Schema::hasTable('tipos_condicoes_especiais')) return [];

        $colInsc = $this->firstExistingColumn($table, ['inscricao_id','id_inscricao']);
        $colTipo = $this->firstExistingColumn($table, ['tipo_id','tipo_condicao_especial_id','id_tipo','id_tipo_condicao','condicao_id']);
        if (!$colInsc || !$colTipo) return [];

        return DB::table($table.' as p')
            ->join('tipos_condicoes_especiais as t', 't.id', '=', DB::raw("p.`{$colTipo}`"))
            ->where("p.$colInsc", $inscricaoId)
            ->orderBy('t.nome')
            ->pluck('t.nome')
            ->all();
    }
}
