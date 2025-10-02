<?php

namespace App\Http\Controllers\Admin\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TipoCondicaoEspecial;

class TipoCondicaoEspecialController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $tipos = TipoCondicaoEspecial::when($q !== '', function ($qb) use ($q) {
                $qb->where('titulo', 'like', "%{$q}%")
                   ->orWhere('grupo', 'like', "%{$q}%");
            })
            ->orderBy('titulo')
            ->paginate(20)
            ->withQueryString();

        return view('admin.config.condicoes-especiais.index', compact('tipos', 'q'));
    }

    public function create()
    {
        return view('admin.config.condicoes-especiais.form', ['tipo' => new TipoCondicaoEspecial()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        TipoCondicaoEspecial::create($data);
        return redirect()->route('admin.config.condicoes_especiais.index')->with('success', 'Condição especial criada.');
    }

    public function edit(TipoCondicaoEspecial $tipo)
    {
        return view('admin.config.condicoes-especiais.form', compact('tipo'));
    }

    public function update(Request $request, TipoCondicaoEspecial $tipo)
    {
        $data = $this->validated($request, $tipo->id);
        $tipo->update($data);
        return redirect()->route('admin.config.condicoes_especiais.index')->with('success', 'Condição especial atualizada.');
    }

    public function destroy(TipoCondicaoEspecial $tipo)
    {
        $tipo->delete();
        return redirect()->route('admin.config.condicoes_especiais.index')->with('success', 'Condição especial removida.');
    }

    public function toggleAtivo(TipoCondicaoEspecial $tipo)
    {
        $tipo->ativo = !$tipo->ativo;
        $tipo->save();
        return redirect()->route('admin.config.condicoes_especiais.index')->with('success', 'Status atualizado.');
    }

    private function validated(Request $request, $ignoreId = null): array
    {
        $rules = [
            'grupo'                  => 'nullable|string|max:100',
            'titulo'                 => 'required|string|max:255|unique:tipos_condicao_especial,titulo' . ($ignoreId ? ',' . $ignoreId : ''),
            'exibir_observacoes'     => 'nullable|boolean',
            'necessita_laudo_medico' => 'nullable|boolean',
            'laudo_obrigatorio'      => 'nullable|boolean',
            'exige_arquivo_outros'   => 'nullable|boolean',
            'tamanho_fonte_especial' => 'nullable|string|max:50',
            'ativo'                  => 'nullable|boolean',
            'impressao_duplicada'    => 'nullable|boolean',
            'info_candidato'         => 'nullable|string',
        ];

        $data = $request->validate($rules);
        foreach (['exibir_observacoes','necessita_laudo_medico','laudo_obrigatorio','exige_arquivo_outros','ativo','impressao_duplicada'] as $b) {
            $data[$b] = $request->boolean($b);
        }
        return $data;
    }
}
