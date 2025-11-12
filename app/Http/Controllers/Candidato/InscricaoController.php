<?php

namespace App\Http\Controllers\Candidato;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\CandidatoInscricao;
use App\Models\Candidato;

class InscricaoController extends Controller
{
    /**
     * Lista de inscrições do candidato.
     * - Busca na tabela "inscricoes"
     * - Considera candidato_id OU cpf
     * - Carrega dados de concursos e cargos em lote
     * - Agrupa por concurso (edital) para exibir em blocos
     */
    public function index()
    {
        /** @var \App\Models\Candidato $user */
        $user = Auth::guard('candidato')->user();

        // Todas as inscrições do candidato (candidato_id OU cpf)
        $inscricoes = CandidatoInscricao::where(function ($q) use ($user) {
                $q->where('candidato_id', $user->id);

                if (!empty($user->cpf)) {
                    $q->orWhere('cpf', $user->cpf);
                }
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        // IDs únicos de concursos (edital_id via accessor concurso_id) e cargos
        $concursoIds = $inscricoes->pluck('concurso_id')->unique()->filter()->values();
        $cargoIds    = $inscricoes->pluck('cargo_id')->unique()->filter()->values();

        // Concursos (título, edital, etc.) — tabela "concursos"
        $concursos = $concursoIds->isNotEmpty()
            ? DB::table('concursos')
                ->whereIn('id', $concursoIds)
                ->get()
                ->keyBy('id')
            : collect();

        // Cargos: tenta na tabela nova; se não achar nada, tenta na tabela antiga "cargos"
        $cargos = collect();
        if ($cargoIds->isNotEmpty()) {
            if (Schema::hasTable('concursos_vagas_cargos')) {
                $cargos = DB::table('concursos_vagas_cargos')
                    ->whereIn('id', $cargoIds)
                    ->get()
                    ->keyBy('id');
            }

            if ($cargos->isEmpty() && Schema::hasTable('cargos')) {
                $cargos = DB::table('cargos')
                    ->whereIn('id', $cargoIds)
                    ->get()
                    ->keyBy('id');
            }
        }

        // Agrupa inscrições por concurso (edital) para exibir blocos separados na tela
        // OBS: "concurso_id" aqui é um accessor no model que devolve "edital_id"
        $inscricoesPorConcurso = $inscricoes->groupBy('concurso_id');

        return view('site.candidato.inscricoes.index', compact(
            'inscricoes',
            'inscricoesPorConcurso',
            'concursos',
            'cargos'
        ));
    }

    /**
     * Formulário "Nova inscrição"
     *
     * - Lista concursos abertos
     * - Monta mapa de modalidades por (concurso_id, cargo_id)
     * - Se vier ?concurso_id=... (ou ?concurso=...), trava o concurso na view
     */
    public function create(Request $request)
    {
        // Lista concursos abertos (inscrições dentro do período e online)
        $concursos = DB::table('concursos')
            ->where('ativo', 1)
            ->where('ocultar_site', 0)
            ->where('inscricoes_online', 1)
            ->whereNotNull('inscricoes_inicio')
            ->whereNotNull('inscricoes_fim')
            ->whereRaw('NOW() BETWEEN inscricoes_inicio AND inscricoes_fim')
            ->orderBy('id', 'desc')
            ->get();

        // Concurso explicitamente selecionado (para travar na tela)
        $concursoSelecionado = null;
        $concursoParam = $request->get('concurso_id', $request->get('concurso'));
        if ($concursoParam) {
            $concursoSelecionado = DB::table('concursos')->where('id', (int) $concursoParam)->first();
        }

        // ------------------------------------------------------
        // MODALIDADES DINÂMICAS POR VAGA (CARGO NO CONCURSO)
        // ------------------------------------------------------
        $modalidadesPorCargo = [];

        if (
            $concursos->isNotEmpty() &&
            Schema::hasTable('concursos_vagas_itens') &&
            Schema::hasTable('concursos_vagas_cotas') &&
            Schema::hasTable('tipos_vagas_especiais')
        ) {
            $concursoIds = $concursos->pluck('id')->filter()->values();

            $hasVagasTotais = Schema::hasColumn('concursos_vagas_itens', 'vagas_totais');

            // Total de vagas gerais por concurso + cargo
            $totais = DB::table('concursos_vagas_itens as i')
                ->select(
                    'i.concurso_id',
                    'i.cargo_id',
                    DB::raw(
                        $hasVagasTotais
                            ? 'SUM(COALESCE(i.vagas_totais, 0)) AS total_vagas'
                            : '0 AS total_vagas'
                    )
                )
                ->whereIn('i.concurso_id', $concursoIds)
                ->groupBy('i.concurso_id', 'i.cargo_id')
                ->get();

            // Vagas especiais dinâmicas (cotas) por tipo
            $cotas = DB::table('concursos_vagas_itens as i')
                ->join('concursos_vagas_cotas as c', 'c.item_id', '=', 'i.id')
                ->join('tipos_vagas_especiais as t', 't.id', '=', 'c.tipo_id')
                ->select(
                    'i.concurso_id',
                    'i.cargo_id',
                    'c.tipo_id',
                    't.nome as tipo_nome',
                    DB::raw('SUM(COALESCE(c.vagas, 0)) AS total_cota')
                )
                ->whereIn('i.concurso_id', $concursoIds)
                ->where('t.ativo', 1)
                ->groupBy('i.concurso_id', 'i.cargo_id', 'c.tipo_id', 't.nome')
                ->get();

            // Índice auxiliar para cotas por (concurso|cargo)
            $cotasPorChave = [];
            foreach ($cotas as $row) {
                $key = $row->concurso_id . '|' . $row->cargo_id;
                $cotasPorChave[$key][] = $row;
            }

            foreach ($totais as $row) {
                $key           = $row->concurso_id . '|' . $row->cargo_id;
                $totalVagas    = (int) ($row->total_vagas ?? 0);
                $listaModalids = [];

                // Sempre que houver qualquer vaga total, habilita "Ampla concorrência"
                if ($totalVagas > 0) {
                    $listaModalids['Ampla concorrência'] = 'Ampla concorrência';
                }

                // Modalidades especiais dinâmicas vindas de tipos_vagas_especiais
                if (!empty($cotasPorChave[$key])) {
                    foreach ($cotasPorChave[$key] as $cotaRow) {
                        if ((int) $cotaRow->total_cota <= 0) {
                            continue;
                        }

                        $nomeTipo = trim((string) $cotaRow->tipo_nome);
                        if ($nomeTipo === '') {
                            continue;
                        }

                        // A chave e o label são o próprio nome da modalidade
                        $listaModalids[$nomeTipo] = $nomeTipo;
                    }
                }

                // Se, por algum motivo, não houver nada detectado, garante pelo menos "Ampla"
                if (empty($listaModalids)) {
                    $listaModalids['Ampla concorrência'] = 'Ampla concorrência';
                }

                $modalidadesPorCargo[$key] = $listaModalids;
            }
        }

        // ------------------------------------------------------
        // Lista GLOBAL de modalidades (para a view atual)
        // ------------------------------------------------------
        $modalidades = [];
        foreach ($modalidadesPorCargo as $mods) {
            foreach ($mods as $value => $label) {
                $modalidades[$value] = $label;
            }
        }

        // Se nada vier do banco, garante ao menos "Ampla concorrência"
        if (empty($modalidades)) {
            $modalidades = [
                'Ampla concorrência' => 'Ampla concorrência',
            ];
        }

        // Condições especiais: livre (campo de texto por enquanto)
        $condicoesEspeciais = [];

        // Isenção: por enquanto só o "checkbox", sem tipos dinâmicos
        $tiposIsencao     = [];
        $temIsencao       = true;  // exibe a caixa de "solicitar isenção"
        $formasPagamento  = [];    // se quiser depois, dá pra preencher via config/tabela

        return view('site.candidato.inscricoes.create', compact(
            'concursos',
            'concursoSelecionado',
            'modalidades',          // usado pela view como lista base
            'modalidadesPorCargo',  // mapa (concurso|cargo) => modalidades dinâmicas
            'condicoesEspeciais',
            'tiposIsencao',
            'temIsencao',
            'formasPagamento'
        ));
    }

    /**
     * Retorna via JSON os cargos de um concurso (para o select dinâmico)
     */
    public function cargos($concursoId)
    {
        $cargos = DB::table('concursos_vagas_cargos')
            ->where('concurso_id', $concursoId)
            ->orderBy('nome')
            ->get();

        return response()->json($cargos);
    }

    /**
     * Retorna via JSON as localidades (itens) de um cargo dentro de um concurso.
     * Essas localidades vêm de:
     *  - concursos_vagas_itens.localidade_id
     *  - concursos_vagas_localidades.nome
     */
    public function localidades($concursoId, $cargoId)
    {
        $itens = DB::table('concursos_vagas_itens as i')
            ->leftJoin('concursos_vagas_localidades as l', 'l.id', '=', 'i.localidade_id')
            ->select(
                'i.id as item_id',
                'i.localidade_id',
                'l.nome as localidade_nome'
            )
            ->where('i.concurso_id', $concursoId)
            ->where('i.cargo_id', $cargoId)
            ->orderBy('l.nome')
            ->get();

        return response()->json($itens);
    }

    /**
     * Retorna via JSON as CIDADES DE PROVA do concurso,
     * opcionalmente filtradas por cargo (quando houver pivot).
     *
     * Detecta dinamicamente:
     *   - Tabela base: concursos_cidades | concursos_cidades_prova | cidades_prova
     *   - Colunas: cidade|nome, uf|estado, ativo, ordem
     *   - Pivot opcional: concursos_cidades_cargos (cidade_id, cargo_id[, ativo])
     */
    public function cidadesProva($concursoId, $cargoId = null)
    {
        $hasTable = fn(string $t) => Schema::hasTable($t);
        $hasCol   = fn(string $t, string $c) => Schema::hasColumn($t, $c);

        // 1) Detecta tabela base
        $tblCidades = null;
        foreach (['concursos_cidades', 'concursos_cidades_prova', 'cidades_prova'] as $t) {
            if ($hasTable($t) && $hasCol($t, 'concurso_id')) {
                $tblCidades = $t;
                break;
            }
        }
        if (!$tblCidades) {
            return response()->json([]);
        }

        // 2) Query base
        $qb = DB::table($tblCidades.' as cp')
            ->where('cp.concurso_id', (int) $concursoId);

        // cidades ativas se existir coluna
        if ($hasCol($tblCidades, 'ativo')) {
            $qb->where('cp.ativo', 1);
        }

        // 3) Se tiver cargo e pivot, filtra por cargo
        if ($cargoId && $hasTable('concursos_cidades_cargos')) {
            $pivot = 'concursos_cidades_cargos';
            $qb->join($pivot.' as cc', 'cc.cidade_id', '=', 'cp.id')
               ->where('cc.cargo_id', (int) $cargoId);

            if ($hasCol($pivot, 'ativo')) {
                $qb->where('cc.ativo', 1);
            }
        }

        // 4) Seleção de colunas flexível
        $cidadeExpr = $hasCol($tblCidades, 'cidade')
            ? 'cp.cidade'
            : ($hasCol($tblCidades, 'nome') ? 'cp.nome' : "''");

        $ufExpr = $hasCol($tblCidades, 'uf')
            ? 'cp.uf'
            : ($hasCol($tblCidades, 'estado') ? 'cp.estado' : "''");

        $qb->selectRaw('cp.id, '.$cidadeExpr.' as cidade, '.$ufExpr.' as uf');

        // 5) Ordenação
        if ($hasCol($tblCidades, 'ordem')) {
            $qb->orderBy('cp.ordem');
        } elseif ($hasCol($tblCidades, 'cidade')) {
            $qb->orderBy('cp.cidade');
        } elseif ($hasCol($tblCidades, 'nome')) {
            $qb->orderBy('cp.nome');
        } else {
            $qb->orderBy('cp.id');
        }

        // distinct caso tenha join com pivot
        $rows = $qb->distinct()->get();

        // 6) Monta a saída esperada pelo front
        $out = $rows->map(function ($r) {
            $cidade = trim((string) ($r->cidade ?? ''));
            $uf     = trim((string) ($r->uf ?? ''));
            $label  = $cidade && $uf ? ($cidade.' / '.$uf) : ($cidade ?: 'Cidade #'.$r->id);

            return [
                'id'     => (int) $r->id,
                'cidade' => $cidade,
                'uf'     => $uf ?: null,
                'label'  => $label,
            ];
        });

        return response()->json($out);
    }

    /**
     * Alias de compatibilidade para rotas antigas.
     */
    public function cidades($concursoId, $cargoId = null)
    {
        return $this->cidadesProva($concursoId, $cargoId);
    }

    /**
     * Salva uma nova inscrição (tabela antiga "inscricoes")
     *
     * OBS:
     *  - o campo "modalidade" recebe exatamente o texto da opção escolhida
     *    no select ("Ampla concorrência", "Negros", "Pessoa com deficiência", etc.)
     *  - a cidade de prova pode vir:
     *      * do campo "cidade_prova" (select de cidades do concurso)
     *      * ou, se vazio, do nome da localidade do item escolhido
     */
    public function store(Request $request)
    {
        /** @var \App\Models\Candidato $user */
        $user = Auth::guard('candidato')->user();

        $data = $request->validate([
            'concurso_id'          => ['required', 'integer'], // id na tabela "concursos"
            'cargo_id'             => ['required', 'integer'],
            'item_id'              => ['nullable', 'integer'],

            // CAMPOS DA TELA
            'modalidade'           => ['required', 'string', 'max:50'],
            'condicoes_especiais'  => ['nullable', 'string'],
            'solicitou_isencao'    => ['nullable', 'boolean'],
            'forma_pagamento'      => ['nullable', 'string', 'max:50'],
            'cidade_prova'         => ['nullable', 'string', 'max:100'], // texto vindo do select de cidades
        ]);

        // Pega info do concurso/cargo/item para validar período e vínculos
        $concurso = DB::table('concursos')->where('id', $data['concurso_id'])->first();
        abort_unless($concurso, 404);

        // Checa período ainda válido
        if (!($concurso->inscricoes_inicio && $concurso->inscricoes_fim &&
            now()->between($concurso->inscricoes_inicio, $concurso->inscricoes_fim))) {
            return back()->withInput()->withErrors([
                'concurso_id' => 'Período de inscrições encerrado para este concurso.',
            ]);
        }

        // Cargo (tabela nova de cargos por concurso)
        $cargo = DB::table('concursos_vagas_cargos')
            ->where('id', $data['cargo_id'])
            ->where('concurso_id', $concurso->id)
            ->first();
        abort_unless($cargo, 404);

        $item = null;
        if (!empty($data['item_id'])) {
            $item = DB::table('concursos_vagas_itens')
                ->where('id', $data['item_id'])
                ->where('concurso_id', $concurso->id)
                ->where('cargo_id', $cargo->id)
                ->first();
            abort_unless($item, 404);
        }

        // Impede duplicidade: um candidato por concurso (edital)
        $ja = CandidatoInscricao::where(function ($q) use ($user) {
                $q->where('candidato_id', $user->id);
                if (!empty($user->cpf)) {
                    $q->orWhere('cpf', $user->cpf);
                }
            })
            ->where('edital_id', $concurso->id)
            ->exists();

        if ($ja) {
            return redirect()
                ->route('candidato.inscricoes.index')
                ->withErrors(['general' => 'Você já possui inscrição neste concurso.']);
        }

        // Número da inscrição (campo "numero" da tabela inscricoes)
        $maxNumero  = CandidatoInscricao::max('numero');
        $nextNumero = $maxNumero ? ($maxNumero + 1) : 1000001;

        // Cidade de prova:
        // 1) Se veio do form (select de cidades do concurso), usa essa
        // 2) Se não veio, tenta usar a localidade do item (concursos_vagas_localidades)
        $cidadeProva = $data['cidade_prova'] ?? null;

        if (!$cidadeProva && $item && property_exists($item, 'localidade_id') && $item->localidade_id) {
            $local = DB::table('concursos_vagas_localidades')
                ->where('id', $item->localidade_id)
                ->first();
            if ($local && isset($local->nome)) {
                $cidadeProva = $local->nome;
            }
        }

        $solicitouIsencao = !empty($data['solicitou_isencao']) ? 1 : 0;
        $formaPagamento   = $data['forma_pagamento'] ?? null;
        $pagamentoStatus  = 'pendente';

        // Cria a inscrição na TABELA ANTIGA "inscricoes"
        $insc = CandidatoInscricao::create([
            'edital_id'      => $concurso->id,
            'cargo_id'       => $cargo->id,
            'item_id'        => $item->id ?? null,
            'user_id'        => null,
            'candidato_id'   => $user->id,
            'cpf'            => $user->cpf,
            'documento'      => null,
            'cidade'         => $cidadeProva,
            'nome_inscricao' => $user->nome,
            'nome_candidato' => $user->nome,
            'nascimento'     => $user->data_nascimento,
            'modalidade'     => $data['modalidade'], // agora é o NOME da modalidade (dinâmico)
            'status'         => 'confirmada',
            'numero'         => $nextNumero,
            'pessoa_key'     => 'C#' . str_pad((string) $user->id, 20, '0', STR_PAD_LEFT),
            'local_key'      => 0,
            'ativo'          => 1,

            'condicoes_especiais' => $data['condicoes_especiais'] ?? null,
            'solicitou_isencao'   => $solicitouIsencao,
            'forma_pagamento'     => $formaPagamento,
            'pagamento_status'    => $pagamentoStatus,
        ]);

        return redirect()
            ->route('candidato.inscricoes.show', $insc->id)
            ->with('success', 'Inscrição realizada com sucesso.');
    }

    /**
     * Tela de detalhes da inscrição
     */
    public function show($id)
    {
        /** @var \App\Models\Candidato $user */
        $user = Auth::guard('candidato')->user();

        // Busca a inscrição garantindo que ela é do candidato (por id OU cpf)
        $insc = CandidatoInscricao::where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('candidato_id', $user->id);
                if (!empty($user->cpf)) {
                    $q->orWhere('cpf', $user->cpf);
                }
            })
            ->firstOrFail();

        // Concurso (tabela "concursos", usando edital_id/concurso_id)
        $concurso = DB::table('concursos')
            ->where('id', $insc->concurso_id) // accessor => edital_id
            ->first();

        // Cargo: tenta na tabela nova, depois na tabela antiga "cargos"
        $cargo = null;
        if ($insc->cargo_id) {
            $cargo = DB::table('concursos_vagas_cargos')
                ->where('id', $insc->cargo_id)
                ->first();

            if (!$cargo && Schema::hasTable('cargos')) {
                $cargo = DB::table('cargos')
                    ->where('id', $insc->cargo_id)
                    ->first();
            }
        }

        // Localidade (via item_id -> concursos_vagas_itens.localidade_id)
        $localidade = null;
        if ($insc->item_id && Schema::hasTable('concursos_vagas_itens') && Schema::hasTable('concursos_vagas_localidades')) {
            $item = DB::table('concursos_vagas_itens')
                ->where('id', $insc->item_id)
                ->first();

            if ($item && isset($item->localidade_id) && $item->localidade_id) {
                $localidade = DB::table('concursos_vagas_localidades')
                    ->where('id', $item->localidade_id)
                    ->first();
            }
        }

        return view('site.candidato.inscricoes.show', compact(
            'insc',
            'concurso',
            'cargo',
            'localidade',
            'user'
        ));
    }

    /**
     * Comprovante em PDF / HTML
     */
    public function comprovante($id)
    {
        /** @var \App\Models\Candidato $user */
        $user = Auth::guard('candidato')->user();

        $insc = CandidatoInscricao::where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('candidato_id', $user->id);
                if (!empty($user->cpf)) {
                    $q->orWhere('cpf', $user->cpf);
                }
            })
            ->firstOrFail();

        $concurso = DB::table('concursos')
            ->where('id', $insc->concurso_id)
            ->first();

        // Cargo: mesma lógica do show()
        $cargo = null;
        if ($insc->cargo_id) {
            $cargo = DB::table('concursos_vagas_cargos')
                ->where('id', $insc->cargo_id)
                ->first();

            if (!$cargo && Schema::hasTable('cargos')) {
                $cargo = DB::table('cargos')
                    ->where('id', $insc->cargo_id)
                    ->first();
            }
        }

        // Localidade idem show()
        $localidade = null;
        if ($insc->item_id && Schema::hasTable('concursos_vagas_itens') && Schema::hasTable('concursos_vagas_localidades')) {
            $item = DB::table('concursos_vagas_itens')
                ->where('id', $insc->item_id)
                ->first();

            if ($item && isset($item->localidade_id) && $item->localidade_id) {
                $localidade = DB::table('concursos_vagas_localidades')
                    ->where('id', $item->localidade_id)
                    ->first();
            }
        }

        // Se dompdf estiver instalado, gera PDF; senão retorna HTML "imprimível"
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'site.candidato.inscricoes.comprovante_pdf',
                compact('insc', 'concurso', 'cargo', 'localidade', 'user')
            );
            return $pdf->download('comprovante_' . ($insc->numero ?? $insc->id) . '.pdf');
        }

        // Fallback: renderiza HTML imprimível
        return view(
            'site.candidato.inscricoes.comprovante_html',
            compact('insc', 'concurso', 'cargo', 'localidade', 'user')
        );
    }
}
