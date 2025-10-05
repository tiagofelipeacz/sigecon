<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use App\Models\ImpugnacaoEdital;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ImpugnacaoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * GET /admin/concursos/{concurso}/impugnacoes
     * Lista de impugnações
     */
    public function index(Concurso $concurso, Request $request)
    {
        $q        = trim((string) $request->get('q', ''));
        $situacao = (string) $request->get('situacao', '');

        $rows = ImpugnacaoEdital::query()
            ->where('concurso_id', $concurso->id)
            ->when($q !== '', function ($w) use ($q) {
                $like = '%' . str_replace(' ', '%', $q) . '%';
                $w->where(function ($x) use ($like) {
                    // Somente colunas que existem na tabela impugnacoes_edital
                    $x->orWhere('nome', 'like', $like)
                      ->orWhere('email', 'like', $like)
                      ->orWhere('cpf', 'like', $like)
                      ->orWhere('texto', 'like', $like);
                });
            })
            ->when(in_array($situacao, ['pendente','deferido','indeferido'], true), function ($w) use ($situacao) {
                $w->where('situacao', $situacao);
            })
            ->orderByRaw("FIELD(situacao, 'pendente','indeferido','deferido')")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.impugnacoes.index', [
            'concurso'     => $concurso,
            'impugnacoes'  => $rows,
            'q'            => $q,
            'situacao'     => $situacao,
        ]);
    }

    /**
     * GET /admin/concursos/{concurso}/impugnacoes/{impugnacao}/editar
     */
    public function edit(Concurso $concurso, ImpugnacaoEdital $impugnacao)
    {
        abort_if((int)$impugnacao->concurso_id !== (int)$concurso->id, 404);

        return view('admin.impugnacoes.edit', [
            'concurso'    => $concurso,
            'impugnacao'  => $impugnacao,
        ]);
    }

    /**
     * PUT /admin/concursos/{concurso}/impugnacoes/{impugnacao}
     * Salvar e fechar
     */
    public function update(Request $request, Concurso $concurso, ImpugnacaoEdital $impugnacao)
    {
        abort_if((int)$impugnacao->concurso_id !== (int)$concurso->id, 404);

        $data = $request->validate([
            'situacao'      => 'required|in:pendente,deferido,indeferido',
            'resposta'      => 'nullable|string',
            'data_resposta' => 'nullable|date',
        ]);

        // Salva situação
        $impugnacao->situacao = $data['situacao'];

        // Salva resposta em campos compatíveis
        $texto = $data['resposta'] ?? null;
        if ($texto !== null) {
            $impugnacao->resposta_texto = $texto;
            $impugnacao->resposta_html  = $texto; // se preferir usar HTML num editor, já fica compatível
            // coluna 'resposta' também existe na sua tabela — mantemos compatibilidade:
            $impugnacao->resposta       = $texto;
        }

        // Data da resposta (se fornecida). Caso a situação seja diferente de "pendente",
        // garantimos os carimbos mesmo sem a data vinda do form.
        if (!empty($data['data_resposta'])) {
            $dt = Carbon::parse($data['data_resposta']);
        } elseif ($data['situacao'] !== 'pendente') {
            $dt = now();
        } else {
            $dt = null;
        }

        if ($dt) {
            $impugnacao->data_resposta = $dt;
            $impugnacao->respondido_em = $impugnacao->respondido_em ?: $dt;
            $impugnacao->responded_at  = $impugnacao->responded_at ?: $dt;
        }

        $impugnacao->save();

        return redirect()
            ->route('admin.concursos.impugnacoes.index', $concurso)
            ->with('success', 'Impugnação atualizada com sucesso.');
    }
}
