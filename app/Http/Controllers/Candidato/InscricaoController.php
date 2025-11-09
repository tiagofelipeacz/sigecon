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

        // IDs únicos de concursos (edital_id) e cargos
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
        $inscricoesPorConcurso = $inscricoes->groupBy('concurso_id'); // accessor -> edital_id

        return view('site.candidato.inscricoes.index', compact(
            'inscricoes',
            'inscricoesPorConcurso',
            'concursos',
            'cargos'
        ));
    }

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

        return view('site.candidato.inscricoes.create', compact('concursos'));
    }

    public function cargos($concursoId)
    {
        $cargos = DB::table('concursos_vagas_cargos')
            ->where('concurso_id', $concursoId)
            ->orderBy('nome')
            ->get();

        return response()->json($cargos);
    }

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

    public function store(Request $request)
    {
        /** @var \App\Models\Candidato $user */
        $user = Auth::guard('candidato')->user();

        $data = $request->validate([
            'concurso_id' => ['required', 'integer'], // id na tabela "concursos"
            'cargo_id'    => ['required', 'integer'],
            'item_id'     => ['nullable', 'integer'],
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

        // Cria a inscrição na TABELA ANTIGA "inscricoes"
        $insc = CandidatoInscricao::create([
            'edital_id'      => $concurso->id,
            'cargo_id'       => $cargo->id,
            'item_id'        => $item->id ?? null,
            'user_id'        => null,
            'candidato_id'   => $user->id,
            'cpf'            => $user->cpf,
            'documento'      => null,
            'cidade'         => null,
            'nome_inscricao' => $user->nome,
            'nome_candidato' => $user->nome,
            'nascimento'     => $user->data_nascimento,
            'modalidade'     => 'ampla',
            'status'         => 'confirmada',
            'numero'         => $nextNumero,
            'pessoa_key'     => 'C#' . str_pad((string) $user->id, 20, '0', STR_PAD_LEFT),
            'local_key'      => 0,
            'ativo'          => 1,
        ]);

        return redirect()
            ->route('candidato.inscricoes.show', $insc->id)
            ->with('success', 'Inscrição realizada com sucesso.');
    }

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
            return $pdf->download('comprovante_' . $insc->numero . '.pdf');
        }

        // Fallback: renderiza HTML imprimível
        return view(
            'site.candidato.inscricoes.comprovante_html',
            compact('insc', 'concurso', 'cargo', 'localidade', 'user')
        );
    }
}
