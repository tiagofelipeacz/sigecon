<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use App\Models\ImpugnacaoEdital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ImpugnacaoController extends Controller
{
    /**
     * GET /admin/concursos/{concurso}/impugnacoes
     */
    public function index(Request $request, Concurso $concurso)
    {
        $q        = trim((string) $request->input('q', ''));
        $situacao = (string) $request->input('situacao', '');

        $query = ImpugnacaoEdital::query()->where('concurso_id', $concurso->id);

        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(function ($w) use ($like) {
                $w->where('nome', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('cpf', 'like', $like)
                  ->orWhere('texto', 'like', $like);
            });
        }

        if (in_array($situacao, ['pendente','deferido','indeferido'], true)) {
            $query->where('situacao', $situacao);
        }

        $impugnacoes = $query->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        return view('admin.concursos.impugnacoes.index', [
            'concurso'    => $concurso,
            'impugnacoes' => $impugnacoes,
            'q'           => $q,
            'situacao'    => $situacao,
        ]);
    }

    /**
     * GET /admin/concursos/{concurso}/impugnacoes/{impugnacao}/editar
     */
    public function edit(Concurso $concurso, ImpugnacaoEdital $impugnacao)
    {
        abort_if($impugnacao->concurso_id !== $concurso->id, 404);
        return view('admin.concursos.impugnacoes.edit', compact('concurso', 'impugnacao'));
    }

    /**
     * PUT /admin/concursos/{concurso}/impugnacoes/{impugnacao}
     */
    public function update(Request $request, Concurso $concurso, ImpugnacaoEdital $impugnacao)
    {
        abort_if($impugnacao->concurso_id !== $concurso->id, 404);

        $data = $request->validate([
            'situacao' => 'required|in:pendente,deferido,indeferido',
            'resposta' => 'nullable|string',
        ]);

        $impugnacao->situacao       = $data['situacao'];
        $impugnacao->resposta_texto = $data['resposta'] ?? null;
        $impugnacao->resposta_html  = $data['resposta'] ?? null;

        // seta responded_at/respondido_em (compatível com os dois nomes de coluna)
        $now = now();
        if (Schema::hasColumn($impugnacao->getTable(), 'respondido_em')) {
            $impugnacao->respondido_em = in_array($data['situacao'], ['deferido','indeferido'], true) ? $now : null;
        }
        if (Schema::hasColumn($impugnacao->getTable(), 'responded_at')) {
            $impugnacao->responded_at = in_array($data['situacao'], ['deferido','indeferido'], true) ? $now : null;
        }

        $impugnacao->save();

        return redirect()
            ->route('admin.concursos.impugnacoes.index', $concurso)
            ->with('success', 'Impugnação atualizada com sucesso.');
    }
}
