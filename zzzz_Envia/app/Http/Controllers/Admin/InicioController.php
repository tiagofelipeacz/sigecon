<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class InicioController extends Controller
{
    public function index(Request $request)
    {
        $now       = Carbon::now();
        $busca     = trim($request->get('q', ''));
        $clienteId = $request->get('client_id');
        $status    = $request->get('status');
        $situacao  = $request->get('situacao');
        $order     = $request->get('order', 'published_desc');
        $soAtivos  = $request->boolean('ativos');

        // Base
        $query = DB::table('concursos as c')
            ->leftJoin('clients as cl', 'cl.id', '=', 'c.client_id');

        // Contagem de inscritos (opcional, sem alterar o BD)
        $hasInscricoes = Schema::hasTable('inscricoes') && Schema::hasTable('editais');

        // Campos (inclui inscritos_total)
        $inscritosExpr = $hasInscricoes
            ? DB::raw('COALESCE(ins.inscritos_total, 0) as inscritos_total')
            : DB::raw('0 as inscritos_total');

        $query->select([
            'c.id',
            DB::raw("COALESCE(c.titulo, CONCAT('Concurso #', c.id)) as titulo"),
            'c.numero_edital',
            'c.cidade',
            'c.estado',
            'c.status',
            'c.ativo',
            'c.inscricoes_inicio',
            'c.inscricoes_fim',
            DB::raw("COALESCE(cl.cliente, cl.name, cl.razao_social) as cliente"),
            'c.created_at',
            $inscritosExpr,
        ]);

        if ($hasInscricoes) {
            $sub = DB::table('inscricoes as i')
                ->join('editais as e', 'e.id', '=', 'i.edital_id')
                ->select('e.numero as numero_edital', DB::raw('COUNT(*) as inscritos_total'))
                ->groupBy('e.numero');

            $query->leftJoinSub($sub, 'ins', function ($join) {
                $join->on('ins.numero_edital', '=', 'c.numero_edital');
            });
        }

        // Busca
        if ($busca !== '') {
            $termo = "%{$busca}%";
            $query->where(function ($w) use ($termo) {
                $w->where('c.titulo', 'like', $termo)
                  ->orWhere('c.descricao', 'like', $termo)
                  ->orWhere('c.numero_edital', 'like', $termo)
                  ->orWhere('c.cidade', 'like', $termo)
                  ->orWhere('c.estado', 'like', $termo);
            });
        }

        // Filtros
        if ($clienteId !== null && $clienteId !== '') {
            $query->where('c.client_id', $clienteId);
        }
        if ($status !== null && $status !== '') {
            $query->where('c.status', $status);
        }
        if ($situacao !== null && $situacao !== '') {
            $query->where('c.situacao', $situacao);
        }
        if ($soAtivos) {
            $query->where('c.ativo', 1);
        }

        // Ordenação
        $coalesceTitulo = DB::raw("COALESCE(c.titulo, CONCAT('Concurso #', c.id))");
        switch ($order) {
            case 'published_asc':   $query->orderBy('c.created_at', 'asc'); break;
            case 'inicio_asc':      $query->orderBy('c.inscricoes_inicio', 'asc'); break;
            case 'inicio_desc':     $query->orderBy('c.inscricoes_inicio', 'desc'); break;
            case 'fim_asc':         $query->orderBy('c.inscricoes_fim', 'asc'); break;
            case 'fim_desc':        $query->orderBy('c.inscricoes_fim', 'desc'); break;
            case 'titulo_asc':      $query->orderBy($coalesceTitulo, 'asc'); break;
            case 'titulo_desc':     $query->orderBy($coalesceTitulo, 'desc'); break;
            default:                $query->orderBy('c.created_at', 'desc'); // published_desc
        }

        $concursos = $query->paginate(12)->withQueryString();

        // Clientes para filtro
        $clientes = DB::table('concursos as c')
            ->leftJoin('clients as cl', 'cl.id', '=', 'c.client_id')
            ->select('cl.id', DB::raw("COALESCE(cl.cliente, cl.name, cl.razao_social) as nome"))
            ->whereNotNull('cl.id')
            ->groupBy('cl.id', 'nome')
            ->orderBy('nome')
            ->get();

        // URL do botão "+ Novo Processo Seletivo" com fallback inteligente
        $urlCreate = $this->resolveCreateUrl();

        // Mapeia dados p/ a view
        $mapped = $concursos->through(function ($row) use ($now) {
            // Garante Carbon
            $row->created_at = $row->created_at ? Carbon::parse($row->created_at) : null;

            $inicio = $row->inscricoes_inicio ? Carbon::parse($row->inscricoes_inicio) : null;
            $fim    = $row->inscricoes_fim ? Carbon::parse($row->inscricoes_fim) : null;

            $statusExib = 'Publicado';
            if (isset($row->ativo) && (int)$row->ativo === 0) {
                $statusExib = 'Rascunho';
            } elseif ($inicio && $fim) {
                if ($now->between($inicio, $fim))      $statusExib = 'Inscrições Abertas';
                elseif ($now->lt($inicio))             $statusExib = 'Em Breve';
                elseif ($now->gt($fim))                $statusExib = 'Encerrado';
            }

            $periodo = ($inicio ? $inicio->format('d/m/Y') : '—') . ' – ' . ($fim ? $fim->format('d/m/Y') : '—');

            $row->status_exibicao  = $statusExib;
            $row->periodo          = $periodo;
            $row->url_show         = url("/admin/concursos/{$row->id}");
            $row->url_edit         = url("/admin/concursos/{$row->id}/edit");
            $row->url_publicacoes  = $row->url_show . '#publicacoes';
            $row->url_relatorios   = $row->url_show . '#relatorios';

            return $row;
        });

        return view('admin.concursos.index-design', [
            'concursos' => $mapped,
            'paginator' => $concursos,
            'clientes'  => $clientes,
            'tem_inscricoes' => $hasInscricoes,
            'url_create' => $urlCreate,
            'filtros' => [
                'q' => $busca,
                'client_id' => $clienteId,
                'status' => $status,
                'situacao' => $situacao,
                'order' => $order,
                'ativos' => $soAtivos,
            ],
            // aliases esperados pela blade
            'q' => $busca,
            'client_id' => $clienteId,
            'status' => $status,
            'order' => $order,
        ]);
    }

    private function resolveCreateUrl(): string
    {
        $candidates = ['admin.concursos.create','concursos.create','admin.concurso.create'];
        foreach ($candidates as $rn) {
            if (Route::has($rn)) {
                try { return route($rn); } catch (\Throwable $e) {}
            }
        }
        return url('/admin/concursos/create');
    }
}
