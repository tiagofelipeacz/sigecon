<?php

namespace App\Http\Controllers\Admin\Config;

use App\Http\Controllers\Controller;
use App\Models\TipoIsencao;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PedidosIsencaoController extends Controller
{
    /**
     * Listagem com busca.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = TipoIsencao::query()
            ->with('client')
            ->orderByDesc('created_at');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('titulo', 'like', "%{$q}%")
                  ->orWhere('nome', 'like', "%{$q}%")
                  ->orWhereHas('client', function ($wc) use ($q) {
                      $wc->where('name', 'like', "%{$q}%")
                         ->orWhere('cliente', 'like', "%{$q}%");
                  });
            });
        }

        $tipos = $query->paginate(20)->withQueryString();

        return view('admin.config.pedidos-isencao.index', compact('tipos', 'q'));
    }

    /**
     * Formulário de criação.
     */
    public function create()
    {
        $clients = $this->clientsOptions();

        $tipo = new TipoIsencao([
            'sistac'          => false,
            'redome'          => false,
            'ativo'           => true,
            'has_extra_field' => false,
            'anexo_policy'    => 'nao',
        ]);

        // Reutiliza a mesma view de edição
        return view('admin.config.pedidos-isencao.edit', compact('tipo', 'clients'));
    }

    /**
     * Salvar novo registro.
     * (Opção 1) Compatibilidade com schema antigo: preenche `nome` a partir de `titulo`.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $tipo = new TipoIsencao();
        $tipo->fill($data);

        // 🔧 LEGACY: se existir coluna `nome` e ela for NOT NULL, sincroniza com `titulo`
        $tipo->nome = $data['titulo'] ?? null;

        $tipo->save();

        return redirect()
            ->route('admin.config.pedidos-isencao.index')
            ->with('status', 'Tipo de isenção criado com sucesso.');
    }

    /**
     * Formulário de edição.
     */
    public function edit(TipoIsencao $tipo)
    {
        $clients = $this->clientsOptions();

        return view('admin.config.pedidos-isencao.edit', compact('tipo', 'clients'));
    }

    /**
     * Atualizar registro.
     * Mantém `nome` sincronizado quando estiver vazio.
     */
    public function update(Request $request, TipoIsencao $tipo)
    {
        $data = $this->validateData($request);

        $tipo->fill($data);

        // 🔧 LEGACY: se `nome` estiver vazio/nulo, sincroniza com `titulo`
        if (!isset($tipo->nome) || $tipo->nome === '' || $tipo->nome === null) {
            $tipo->nome = $tipo->titulo;
        }

        $tipo->save();

        return redirect()
            ->route('admin.config.pedidos-isencao.index')
            ->with('status', 'Tipo de isenção atualizado com sucesso.');
    }

    /**
     * Excluir registro.
     */
    public function destroy(TipoIsencao $tipo)
    {
        $tipo->delete();

        return redirect()
            ->route('admin.config.pedidos-isencao.index')
            ->with('status', 'Tipo de isenção removido.');
    }

    /**
     * Alternar flags booleanas.
     * Rota sugerida: admin.config.pedidos-isencao.toggle
     */
    public function toggle(TipoIsencao $tipo, string $field)
    {
        $allowed = ['sistac', 'redome', 'ativo', 'has_extra_field'];

        abort_unless(in_array($field, $allowed, true), 404);

        $tipo->{$field} = ! (bool) $tipo->{$field};
        $tipo->save();

        return back()->with('status', "Campo '{$field}' atualizado.");
    }

    /**
     * Validação + normalização dos dados do formulário.
     */
    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'titulo'          => ['required', 'string', 'max:255'],
            'descricao'       => ['nullable', 'string'],
            'client_id'       => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'sistac'          => ['nullable', Rule::in([0,1,'0','1',true,false,'true','false'])],
            'redome'          => ['nullable', Rule::in([0,1,'0','1',true,false,'true','false'])],
            'ativo'           => ['nullable', Rule::in([0,1,'0','1',true,false,'true','false'])],
            'has_extra_field' => ['nullable', Rule::in([0,1,'0','1',true,false,'true','false'])],
            'anexo_policy'    => ['nullable', Rule::in(['nao','opcional','obrigatorio'])],
        ], [
            'titulo.required' => 'Informe o título.',
        ]);

        // Normaliza booleans
        foreach (['sistac','redome','ativo','has_extra_field'] as $flag) {
            $validated[$flag] = filter_var($request->input($flag, false), FILTER_VALIDATE_BOOL);
        }

        // Política de anexo padrão
        $validated['anexo_policy'] = $validated['anexo_policy'] ?? 'nao';

        // client_id: trata '', null ou string "null" como NULL
        $rawClient = $request->input('client_id', null);
        if ($rawClient === null || $rawClient === '' || strtolower((string) $rawClient) === 'null') {
            $validated['client_id'] = null;
        } else {
            $validated['client_id'] = (int) $rawClient;
        }

        // Retorna somente chaves conhecidas
        return array_intersect_key($validated, array_flip([
            'titulo', 'descricao', 'client_id',
            'sistac', 'redome', 'ativo',
            'has_extra_field', 'anexo_policy',
        ]));
    }

    /**
     * Opções de clientes (id => nome) com fallback para 'cliente' quando 'name' for nulo.
     */
    private function clientsOptions(): array
    {
        $rows = Client::select('id', 'name', 'cliente')
            ->orderByRaw('COALESCE(name, cliente) ASC')
            ->get();

        return $rows->mapWithKeys(function ($c) {
            $label = $c->name ?: $c->cliente ?: ('Cliente #'.$c->id);
            return [$c->id => $label];
        })->toArray();
    }
}
