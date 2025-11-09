<?php

namespace App\Http\Controllers\Candidato;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\CandidatoInscricao;
use App\Models\Candidato;

class InscricaoController extends Controller
{
    /**
     * Lista de inscrições do candidato.
     * Agora já traz:
     * - inscrições do candidato
     * - dados dos concursos e cargos em lote
     * - coleção agrupada por concurso para montar blocos na tela
     */
    public function index()
    {
        $user = Auth::guard('candidato')->user();

        // Todas as inscrições do candidato
        $inscricoes = CandidatoInscricao::where('candidato_id', $user->id)
            ->orderByDesc('id')
            ->get();

        // Pega IDs únicos de concursos e cargos para buscar em lote
        $concursoIds = $inscricoes->pluck('concurso_id')->unique()->filter()->values();
        $cargoIds    = $inscricoes->pluck('cargo_id')->unique()->filter()->values();

        // Concursos (título, edital, etc.)
        $concursos = $concursoIds->isNotEmpty()
            ? DB::table('concursos')
                ->whereIn('id', $concursoIds)
                ->get()
                ->keyBy('id')
            : collect();

        // Cargos (nome do cargo)
        $cargos = $cargoIds->isNotEmpty()
            ? DB::table('concursos_vagas_cargos')
                ->whereIn('id', $cargoIds)
                ->get()
                ->keyBy('id')
            : collect();

        // Agrupa inscrições por concurso para exibir blocos separados na tela
        $inscricoesPorConcurso = $inscricoes->groupBy('concurso_id');

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
        $user = Auth::guard('candidato')->user();

        $data = $request->validate([
            'concurso_id' => ['required', 'integer'],
            'cargo_id'    => ['required', 'integer'],
            'item_id'     => ['nullable', 'integer'],
        ]);

        // Pega info do concurso/cargo/item para taxa e protocolo
        $concurso = DB::table('concursos')->where('id', $data['concurso_id'])->first();
        abort_unless($concurso, 404);

        // Checa período ainda válido
        if (!($concurso->inscricoes_inicio && $concurso->inscricoes_fim &&
            now()->between($concurso->inscricoes_inicio, $concurso->inscricoes_fim))) {
            return back()->withInput()->withErrors([
                'concurso_id' => 'Período de inscrições encerrado para este concurso.',
            ]);
        }

        $cargo = DB::table('concursos_vagas_cargos')
            ->where('id', $data['cargo_id'])
            ->where('concurso_id', $concurso->id)
            ->first();
        abort_unless($cargo, 404);

        $item = null;
        $localidade_id = null;
        if (!empty($data['item_id'])) {
            $item = DB::table('concursos_vagas_itens')
                ->where('id', $data['item_id'])
                ->where('concurso_id', $concurso->id)
                ->where('cargo_id', $cargo->id)
                ->first();
            abort_unless($item, 404);
            $localidade_id = $item->localidade_id;
        }

        // Impede duplicidade: um candidato por concurso
        $ja = CandidatoInscricao::where('candidato_id', $user->id)
            ->where('concurso_id', $concurso->id)
            ->exists();
        if ($ja) {
            return redirect()
                ->route('candidato.inscricoes.index')
                ->withErrors(['general' => 'Você já possui inscrição neste concurso.']);
        }

        // Protocolo: AAAAMMDD-CCC-######## (CCC = id concurso)
        $seq = (int) ($concurso->sequence_inscricao ?? 1);
        $protocolo = now()->format('Ymd')
            . '-' . str_pad((string) $concurso->id, 3, '0', STR_PAD_LEFT)
            . '-' . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);

        $taxa = $cargo->taxa_inscricao ?? $concurso->taxa_inscricao;

        // Cria a inscrição
        $insc = CandidatoInscricao::create([
            'candidato_id'    => $user->id,
            'concurso_id'     => $concurso->id,
            'cargo_id'        => $cargo->id,
            'localidade_id'   => $localidade_id,
            'item_id'         => $item->id ?? null,
            'protocolo'       => $protocolo,
            'status'          => 'inscrito',
            'taxa_inscricao'  => $taxa,
            'extras'          => null,
        ]);

        // Incrementa sequence do concurso (para próximos protocolos)
        DB::table('concursos')
            ->where('id', $concurso->id)
            ->update(['sequence_inscricao' => $seq + 1]);

        return redirect()
            ->route('candidato.inscricoes.show', $insc->id)
            ->with('success', 'Inscrição realizada com sucesso.');
    }

    public function show($id)
    {
        $user = Auth::guard('candidato')->user();
        $insc = CandidatoInscricao::where('id', $id)
            ->where('candidato_id', $user->id)
            ->firstOrFail();

        // Carrega alguns detalhes para exibir
        $concurso = DB::table('concursos')->where('id', $insc->concurso_id)->first();
        $cargo = DB::table('concursos_vagas_cargos')->where('id', $insc->cargo_id)->first();
        $localidade = null;
        if ($insc->localidade_id) {
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
        $user = Auth::guard('candidato')->user();
        $insc = CandidatoInscricao::where('id', $id)
            ->where('candidato_id', $user->id)
            ->firstOrFail();

        $concurso = DB::table('concursos')->where('id', $insc->concurso_id)->first();
        $cargo = DB::table('concursos_vagas_cargos')->where('id', $insc->cargo_id)->first();
        $localidade = null;
        if ($insc->localidade_id) {
            $localidade = DB::table('concursos_vagas_localidades')
                ->where('id', $insc->localidade_id)
                ->first();
        }

        // Se dompdf estiver instalado, gera PDF, senão retorna HTML "imprimível"
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'site.candidato.inscricoes.comprovante_pdf',
                compact('insc', 'concurso', 'cargo', 'localidade', 'user')
            );
            return $pdf->download('comprovante_' . $insc->protocolo . '.pdf');
        }

        // Fallback: renderiza HTML imprimível
        return view(
            'site.candidato.inscricoes.comprovante_html',
            compact('insc', 'concurso', 'cargo', 'localidade', 'user')
        );
    }
}
