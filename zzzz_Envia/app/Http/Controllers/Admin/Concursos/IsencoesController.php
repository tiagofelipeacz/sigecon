<?php

namespace App\Http\Controllers\Admin\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use App\Models\PedidoIsencao;
use App\Models\TipoIsencao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IsencoesController extends Controller
{
    /**
     * Lista os pedidos de isenção de um concurso.
     */
    public function index(Request $request, Concurso $concurso)
    {
        $q       = trim((string) $request->get('q', ''));
        $status  = $request->get('status');
        $tipoId  = $request->get('tipo');

        $query = PedidoIsencao::query()
            ->with(['tipo']) // carrega título do tipo
            ->where('concurso_id', $concurso->id)
            ->orderByDesc('created_at');

        // Busca livre
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('id', $q)
                  ->orWhere('candidato_nome', 'like', "%{$q}%")
                  ->orWhere('candidato_cpf', 'like', "%{$q}%")
                  ->orWhereHas('tipo', fn($t) => $t->where('titulo', 'like', "%{$q}%"));
            });
        }

        // Filtro por status (usa a coluna real `status`)
        if ($status && in_array($status, ['pendente','deferido','indeferido','cancelado'])) {
            $query->where('status', $status);
        }

        // Filtro por tipo
        if (!empty($tipoId)) {
            $query->where('tipo_isencao_id', $tipoId);
        }

        $pedidos = $query->paginate(20)->withQueryString();

        // KPIs (usa `status`, não `resposta`)
        $total     = PedidoIsencao::where('concurso_id', $concurso->id)->count();
        $deferidos = PedidoIsencao::where('concurso_id', $concurso->id)->where('status','deferido')->count();
        $outros    = max(0, $total - $deferidos);
        $pctOk     = $total ? round($deferidos * 100 / $total, 2) : 0;
        $pctOutros = $total ? round($outros    * 100 / $total, 2) : 0;

        $tipos = TipoIsencao::orderBy('titulo')->pluck('titulo', 'id');

        return view('admin.concursos.isencoes.index', compact(
            'concurso','pedidos','q','status','tipoId','tipos',
            'total','deferidos','outros','pctOk','pctOutros'
        ));
    }

    /**
     * Tela de análise/edição do pedido.
     */
    public function edit(Concurso $concurso, PedidoIsencao $pedido)
    {
        // Garante que o pedido pertence ao concurso da URL
        abort_unless($pedido->concurso_id === $concurso->id, 404);

        $pedido->load(['tipo']);

        return view('admin.concursos.isencoes.edit', [
            'concurso' => $concurso,
            'pedido'   => $pedido,
        ]);
    }

    /**
     * Atualiza o status e observações do pedido.
     */
    public function update(Request $request, Concurso $concurso, PedidoIsencao $pedido)
    {
        abort_unless($pedido->concurso_id === $concurso->id, 404);

        $data = $request->validate([
            'status'         => ['required', Rule::in(['pendente','deferido','indeferido','cancelado'])],
            'resposta_texto' => ['nullable','string','max:5000'],
        ]);

        $pedido->status        = $data['status'];
        $pedido->resposta_texto = $data['resposta_texto'] ?? null;
        // carimba analisado_em quando sair de "pendente"
        if ($pedido->status !== 'pendente' && is_null($pedido->analisado_em)) {
            $pedido->analisado_em = now();
        }
        $pedido->save();

        return redirect()
            ->route('admin.concursos.isencoes.index', $concurso)
            ->with('success', 'Pedido atualizado com sucesso.');
    }
}
