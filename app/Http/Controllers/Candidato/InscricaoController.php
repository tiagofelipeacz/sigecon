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
     */
    public function create()
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

        /**
         * NESTA PARTE TORNAMOS AS OPÇÕES DINÂMICAS.
         *
         * Como eu não consigo ver seu banco diretamente, estou usando um padrão:
         * - Se existirem colunas JSON no concursos, do tipo:
         *   - modalidades_json
         *   - condicoes_especiais_json
         *   - isencoes_json
         *   elas são lidas e usadas.
         * - Se não existirem, caímos em defaults seguros.
         *
         * Se o seu nome de coluna for outro, você ajusta APENAS dentro dos "if (Schema::hasColumn(...))",
         * o resto do fluxo continua igual.
         */

        // MODALIDADES
        $modalidades = [];

        if (Schema::hasColumn('concursos', 'modalidades_json')) {
            foreach ($concursos as $c) {
                $arr = json_decode($c->modalidades_json ?? '[]', true) ?: [];
                foreach ($arr as $m) {
                    $m = trim((string)$m);
                    if ($m !== '') {
                        $modalidades[$m] = $m;
                    }
                }
            }
        }

        // Se nada vier do banco, usamos "ampla" como default
        if (empty($modalidades)) {
            $modalidades = [
                'ampla' => 'Ampla concorrência',
            ];
        }

        // CONDIÇÕES ESPECIAIS
        $condicoesEspeciais = [];
        if (Schema::hasColumn('concursos', 'condicoes_especiais_json')) {
            foreach ($concursos as $c) {
                $arr = json_decode($c->condicoes_especiais_json ?? '[]', true) ?: [];
                foreach ($arr as $txt) {
                    $txt = trim((string)$txt);
                    if ($txt !== '') {
                        $condicoesEspeciais[$txt] = $txt;
                    }
                }
            }
        }

        // ISENÇÕES
        $isencoes = [];
        if (Schema::hasColumn('concursos', 'isencoes_json')) {
            foreach ($concursos as $c) {
                $arr = json_decode($c->isencoes_json ?? '[]', true) ?: [];
                foreach ($arr as $txt) {
                    $txt = trim((string)$txt);
                    if ($txt !== '') {
                        $isencoes[$txt] = $txt;
                    }
                }
            }
        }

        // Se não tiver config específica, pelo menos "solicitar / não solicitar"
        if (empty($isencoes)) {
            $isencoes = [
                '0' => 'Não solicitar isenção',
                '1' => 'Solicitar isenção',
            ];
        }

        return view('site.candidato.inscricoes.create', compact(
            'concursos',
            'modalidades',
            'condicoesEspeciais',
            'isencoes'
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
     * Retorna via JSON as localidades (itens) de um cargo dentro de um concurso
     */
    public function localidades($concursoId, $cargoId)
    {
        // itens que combinam cargo+localidade
        $itens = DB::table('concursos_vagas_itens as i')
            ->leftJoin('concursos_vagas_localidades as l', 'l.id', '=', 'i.localidade_id')
            ->select('i.id as item_id', 'i.localidade_id', 'l.nome as localidade_nome')
            ->where('i.concurso_id', $concursoId)
            ->where('i.cargo_id', $cargoId)
            ->orderBy('l.nome')
            ->get();

        return response()->json($itens);
    }

    /**
     * Salva uma nova inscrição (tabela antiga "inscricoes")
     */
    public function store(Request $request)
    {
        /** @var \App\Models\Candidato $user */
        $user = Auth::guard('candidato')->user();

        $data = $request->validate([
            'concurso_id'          => ['required', 'integer'], // id na tabela "concursos"
            'cargo_id'             => ['required', 'integer'],
            'item_id'              => ['nullable', 'integer'],

            // NOVOS CAMPOS – estes vêm da tela
            'modalidade'           => ['required', 'string', 'max:50'],
            'condicoes_especiais'  => ['nullable', 'string'],
            'solicitou_isencao'    => ['nullable', 'boolean'],
            'forma_pagamento'      => ['nullable', 'string', 'max:50'],
            'cidade_prova'         => ['nullable', 'string', 'max:100'],
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

        // Cidade de prova: se veio do form, usamos; senão, podemos tentar o nome da localidade (se existir)
        $cidadeProva = $data['cidade_prova'] ?? null;
        if (!$cidadeProva && $item && property_exists($item, 'localidade_id')) {
            $local = DB::table('concursos_vagas_localidades')
                ->where('id', $item->localidade_id)
                ->first();
            if ($local && isset($local->nome)) {
                $cidadeProva = $local->nome;
            }
        }

        $solicitouIsencao = !empty($data['solicitou_isencao']) ? 1 : 0;

        // Forma de pagamento: se não vier nada, deixa null ou um default
        $formaPagamento = $data['forma_pagamento'] ?? null;

        // Status do pagamento: começa sempre como pendente
        $pagamentoStatus = 'pendente';

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
            'modalidade'     => $data['modalidade'],
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

        $localidade = null;
        if ($insc->localidade_id ?? null) {
            $localidade = DB::table('concursos_vagas_localidades')
                ->where('id', $insc->localidade_id)
                ->first();
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

        $localidade = null;
        if ($insc->localidade_id ?? null) {
            $localidade = DB::table('concursos_vagas_localidades')
                ->where('id', $insc->localidade_id)
                ->first();
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
