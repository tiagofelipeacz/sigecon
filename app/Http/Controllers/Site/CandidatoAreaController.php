<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CandidatoAreaController extends Controller
{
    public function dashboard()
    {
        $user = auth('candidato')->user();

        // total de inscrições
        $totalInscricoes = DB::table('inscricoes')
            ->where('candidato_id', $user->id)
            ->count();

        return view('site.candidato.dashboard', compact('user','totalInscricoes'));
    }

    /**
     * Lista de concursos (você pode filtrar só os em andamento, etc.)
     */
    public function concursos()
    {
        $qb = DB::table('concursos')->orderByDesc('id');

        if (Schema::hasColumn('concursos','ativo')) {
            $qb->where('ativo',1);
        }

        $concursos = $qb->select('id','titulo','edital_numero','created_at')->paginate(10);

        return view('site.candidato.concursos.index', compact('concursos'));
    }

    /**
     * Página de detalhes resumida do concurso dentro da área do candidato
     * (com botão de inscrição).
     */
    public function concursoShow(int $concursoId)
    {
        $concurso = DB::table('concursos')->where('id',$concursoId)->first();
        abort_unless($concurso, 404);

        // checa se já está inscrito
        $jaInscrito = DB::table('inscricoes')
            ->where('candidato_id', auth('candidato')->id())
            ->where('concurso_id', $concursoId)
            ->exists();

        // aqui você pode pegar mais infos se quiser (datas, etc.)
        return view('site.candidato.concursos.show', compact('concurso','jaInscrito'));
    }

    /**
     * Efetiva inscrição do candidato no concurso (sem escolha de cargo aqui).
     * Se você tiver tabela de cargos, pode receber "cargo_id" no Request.
     */
    public function inscrever(Request $r, int $concursoId)
    {
        $userId = auth('candidato')->id();

        // evita duplicar
        $exists = DB::table('inscricoes')
            ->where('candidato_id',$userId)
            ->where('concurso_id',$concursoId)
            ->exists();

        if ($exists) {
            return back()->with('status','Você já está inscrito neste concurso.');
        }

        DB::table('inscricoes')->insert([
            'candidato_id' => $userId,
            'concurso_id'  => $concursoId,
            'status'       => 'em_andamento',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return redirect()
            ->route('candidato.inscricoes')
            ->with('status','Inscrição realizada com sucesso!');
    }

    /**
     * Minhas inscrições + infos básicas do concurso.
     */
    public function minhasInscricoes()
    {
        $rows = DB::table('inscricoes as i')
            ->join('concursos as c','c.id','=','i.concurso_id')
            ->where('i.candidato_id', auth('candidato')->id())
            ->orderByDesc('i.id')
            ->select(
                'i.id',
                'i.status',
                'i.created_at',
                'c.id as concurso_id',
                'c.titulo as concurso_titulo'
            )
            ->get()
            ->map(function ($r) {
                $r->data_inscricao = Carbon::parse($r->created_at)->format('d/m/Y H:i');
                return $r;
            });

        return view('site.candidato.inscricoes.index', compact('rows'));
    }
}
