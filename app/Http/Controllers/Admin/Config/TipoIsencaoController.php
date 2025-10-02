<?php

namespace App\Http\Controllers\Admin\Config;

use App\Http\Controllers\Controller;
use App\Models\TipoIsencao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoIsencaoController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $somenteAtivos = (string) $request->get('somente_ativos', '') === '1';

        $tipos = TipoIsencao::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('nome', 'like', "%{$q}%")
                       ->orWhere('titulo', 'like', "%{$q}%");
                });
            })
            ->when($somenteAtivos, function ($query) {
                $query->where('ativo', true);
            })
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        return view('admin.config.tipos-isencao.index', compact('tipos', 'q', 'somenteAtivos'));
    }

    public function create()
    {
        $tipoIsencao = new TipoIsencao();
        return view('admin.config.tipos-isencao.create', compact('tipoIsencao'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'            => ['required', 'string', 'max:255', Rule::unique('tipos_isencao', 'nome')],
            'titulo'          => ['nullable', 'string', 'max:255'],
            'observacoes'     => ['nullable', 'string'],
            'exige_arquivo'   => ['nullable', 'boolean'],   // checkbox
            'exige_cadunico'  => ['nullable', 'boolean'],   // checkbox
            'ativo'           => ['nullable', 'boolean'],   // checkbox
            'ordem'           => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        // Mapear campos do formulário para as colunas reais da tabela
        $payload = [
            'nome'             => $data['nome'],
            'titulo'           => $data['titulo'] ?? $data['nome'],
            'descricao'        => $data['observacoes'] ?? null,
            'anexo_policy'     => !empty($data['exige_arquivo']) ? 'obrigatorio' : 'nao',
            'permite_anexo'    => !empty($data['exige_arquivo']) ? 1 : 0,
            'exigir_cadunico'  => !empty($data['exige_cadunico']) ? 1 : 0,
            'ativo'            => isset($data['ativo']) ? (int)$data['ativo'] : 1,
            'ordem'            => $data['ordem'] ?? 0,
        ];

        TipoIsencao::create($payload);

        return redirect()
            ->route('admin.config.tipos-isencao.index')
            ->with('success', 'Tipo de isenção criado com sucesso.');
    }

    public function edit(TipoIsencao $tipoIsencao)
    {
        return view('admin.config.tipos-isencao.edit', compact('tipoIsencao'));
    }

    public function update(Request $request, TipoIsencao $tipoIsencao)
    {
        $data = $request->validate([
            'nome'            => ['required', 'string', 'max:255', Rule::unique('tipos_isencao', 'nome')->ignore($tipoIsencao->id)],
            'titulo'          => ['nullable', 'string', 'max:255'],
            'observacoes'     => ['nullable', 'string'],
            'exige_arquivo'   => ['nullable', 'boolean'],
            'exige_cadunico'  => ['nullable', 'boolean'],
            'ativo'           => ['nullable', 'boolean'],
            'ordem'           => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $payload = [
            'nome'             => $data['nome'],
            'titulo'           => $data['titulo'] ?? $data['nome'],
            'descricao'        => $data['observacoes'] ?? null,
            'anexo_policy'     => !empty($data['exige_arquivo']) ? 'obrigatorio' : 'nao',
            'permite_anexo'    => !empty($data['exige_arquivo']) ? 1 : 0,
            'exigir_cadunico'  => !empty($data['exige_cadunico']) ? 1 : 0,
            'ativo'            => isset($data['ativo']) ? (int)$data['ativo'] : 1,
            'ordem'            => $data['ordem'] ?? 0,
        ];

        $tipoIsencao->update($payload);

        return redirect()
            ->route('admin.config.tipos-isencao.index')
            ->with('success', 'Tipo de isenção atualizado.');
    }

    public function destroy(TipoIsencao $tipoIsencao)
    {
        $tipoIsencao->delete();

        return redirect()
            ->route('admin.config.tipos-isencao.index')
            ->with('success', 'Tipo de isenção excluído.');
    }

    public function toggleAtivo(TipoIsencao $tipoIsencao)
    {
        $tipoIsencao->ativo = !$tipoIsencao->ativo;
        $tipoIsencao->save();

        return back()->with('success', 'Status atualizado.');
    }
}
