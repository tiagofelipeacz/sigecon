<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\IsencaoTipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TipoIsencaoController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        $query = IsencaoTipo::query()->with('client');

        if ($q !== '') {
            $query->where(function ($x) use ($q) {
                $like = "%{$q}%";
                $x->where('nome', 'like', $like)
                  ->orWhere('orientacoes_html', 'like', $like);
            });
        }

        $tipos = $query->orderBy('id')->paginate(20)->withQueryString();

        return view('admin.tipos_isencao.index', compact('tipos','q'));
    }

    public function create()
    {
        $tipo = new IsencaoTipo([
            'usa_sistac'  => false,
            'usa_redome'  => false,
            'ativo'       => true,
            'exige_anexo' => 'nao',
            'campo_extra' => 'nenhum',
        ]);

        $clients = $this->clientsList();
        return view('admin.tipos_isencao.edit', compact('tipo','clients'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        IsencaoTipo::create($data);

        return redirect()
            ->route('admin.config.tipos-isencao.index')
            ->with('success', 'Tipo de isenção criado com sucesso.');
    }

    public function edit(IsencaoTipo $tipo)
    {
        $clients = $this->clientsList();
        return view('admin.tipos_isencao.edit', compact('tipo','clients'));
    }

    public function update(Request $request, IsencaoTipo $tipo)
    {
        $data = $this->validated($request);
        $tipo->update($data);

        return redirect()
            ->route('admin.config.tipos-isencao.index')
            ->with('success', 'Tipo de isenção atualizado.');
    }

    public function destroy(IsencaoTipo $tipo)
    {
        $tipo->delete();
        return back()->with('success', 'Tipo de isenção removido.');
    }

    // --------- helpers ---------
    private function validated(Request $request): array
    {
        return $request->validate([
            'nome'            => 'required|string|max:255',
            'cliente_id'      => 'nullable|integer|exists:clients,id',
            'usa_sistac'      => 'nullable|boolean',
            'usa_redome'      => 'nullable|boolean',
            'ativo'           => 'nullable|boolean',
            'exige_anexo'     => 'required|in:nao,sim,obrigatorio',
            'campo_extra'     => 'required|in:nenhum,cadunico',
            'orientacoes_html'=> 'nullable|string',
        ], [], [
            'nome' => 'Tipo de Isenção',
        ]) + [
            'usa_sistac'  => $request->boolean('usa_sistac'),
            'usa_redome'  => $request->boolean('usa_redome'),
            'ativo'       => $request->boolean('ativo'),
        ];
    }

    private function clientsList()
    {
        // tenta pegar um label “legal” baseado nas colunas existentes
        $cols = Schema::getColumnListing((new Client)->getTable());
        foreach (['cliente','razao_social','nome_fantasia','fantasia','name','nome','titulo','empresa','descricao'] as $c) {
            if (in_array($c, $cols, true)) {
                return Client::orderBy($c)->pluck($c, 'id');
            }
        }
        return Client::orderBy('id')->pluck('id','id');
    }
}
