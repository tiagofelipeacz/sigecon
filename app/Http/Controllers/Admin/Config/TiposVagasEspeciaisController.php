<?php

namespace App\Http\Controllers\Admin\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TipoVagaEspecial;

class TiposVagasEspeciaisController extends Controller
{
    public function index()
    {
        $tipos = TipoVagaEspecial::orderBy('ordem')->orderBy('nome')->get();
        return view('admin.config.tipos-vagas-especiais.index', compact('tipos'));
    }

    public function create()
    {
        $clientes = class_exists(\App\Models\Client::class)
            ? \App\Models\Client::select('id','name')->orderBy('name')->get()
            : collect();

        return view('admin.config.tipos-vagas-especiais.create', compact('clientes'));
    }

    public function store(Request $req)
    {
        $data = $this->validated($req);
        $tipo = TipoVagaEspecial::create($data);

        return $this->shouldClose($req)
            ? redirect()->route('admin.config.tipos-vagas-especiais.index')->with('success', 'Criado.')
            : redirect()->route('admin.config.tipos-vagas-especiais.edit', $tipo->id)->with('success', 'Criado.');
    }

    public function edit(TipoVagaEspecial $id)
    {
        $tipo = $id;

        $clientes = class_exists(\App\Models\Client::class)
            ? \App\Models\Client::select('id','name')->orderBy('name')->get()
            : collect();

        return view('admin.config.tipos-vagas-especiais.edit', compact('tipo','clientes'));
    }

    public function update(Request $req, TipoVagaEspecial $id)
    {
        $tipo = $id;
        $tipo->update($this->validated($req));

        return $this->shouldClose($req)
            ? redirect()->route('admin.config.tipos-vagas-especiais.index')->with('success','Atualizado.')
            : back()->with('success','Atualizado.');
    }

    public function destroy(TipoVagaEspecial $id)
    {
        $id->delete();
        return back()->with('success','Excluído.');
    }

    public function toggleAtivo(TipoVagaEspecial $id)
    {
        $id->ativo = ! $id->ativo;
        $id->save();
        return back()->with('success','Status alterado.');
    }

    /**
     * Converte e valida a requisição no formato que o model usa.
     */
    private function validated(Request $req): array
    {
        // --- Fallbacks/normalizações antes de validar ---

        // 1) titulo -> nome (se nome vier vazio)
        if (!$req->filled('nome') && $req->filled('titulo')) {
            $req->merge(['nome' => $req->input('titulo')]);
        }

        // 2) Necessita de laudo (UI: necessita_laudo_medico) -> coluna necessita_laudo
        $necessita = null;
        if ($req->has('necessita_laudo_medico')) {
            $necessita = $req->boolean('necessita_laudo_medico');
        } elseif ($req->has('necessita_laudo')) {
            $necessita = $req->boolean('necessita_laudo');
        }
        if ($necessita !== null) {
            $req->merge(['necessita_laudo' => $necessita]);
        }

        // 3) "Envio de laudo obrigatório?" (UI: laudo_obrigatorio_choice) -> laudo_obrigatorio
        $laudoObr = null;
        if ($req->has('laudo_obrigatorio_choice')) {
            $laudoObr = $req->boolean('laudo_obrigatorio_choice');
        } elseif ($req->has('laudo_obrigatorio')) {
            $laudoObr = $req->boolean('laudo_obrigatorio');
        }
        if ($laudoObr !== null) {
            $req->merge(['laudo_obrigatorio' => $laudoObr]);
        }

        // 4) "Envio de arquivo (Outros)" (UI: exige_arquivo_outros 0/1) -> enum envio_arquivo
        if ($req->has('exige_arquivo_outros')) {
            $req->merge([
                'envio_arquivo' => $req->boolean('exige_arquivo_outros') ? 'obrigatorio' : 'nao',
            ]);
        }

        // 5) Observações -> info_candidato (se info_candidato vier vazio)
        if (!$req->filled('info_candidato') && $req->filled('observacoes')) {
            $req->merge(['info_candidato' => $req->input('observacoes')]);
        }

        // --- Validação (mais permissiva para casar com a UI) ---
        $data = $req->validate([
            'nome'        => ['required','string','max:255'],
            'ordem'       => ['nullable','integer','min:0'],
            'cliente_id'  => ['nullable','integer'],
            'grupo'       => ['nullable','string','max:100'],

            // booleans opcionais (default = false)
            'sistac'                   => ['nullable','boolean'],
            'necessita_laudo'          => ['nullable','boolean'],
            'laudo_obrigatorio'        => ['nullable','boolean'],
            'informar_tipo_deficiencia'=> ['nullable','boolean'],
            'autodeclaracao'           => ['nullable','boolean'],

            // enum do banco: 'nao','opcional','obrigatorio'
            'envio_arquivo'  => ['nullable','in:nao,opcional,obrigatorio'],

            'info_candidato' => ['nullable','string'],
            'ativo'          => ['nullable','boolean'],
        ]);

        // --- Normalizações pós-validação ---

        // Se não veio nada, padroniza envio_arquivo como 'nao'
        if (!isset($data['envio_arquivo']) || $data['envio_arquivo'] === null || $data['envio_arquivo'] === '') {
            $data['envio_arquivo'] = 'nao';
        }

        // Bools garantidos
        foreach ([
            'sistac',
            'necessita_laudo',
            'laudo_obrigatorio',
            'informar_tipo_deficiencia',
            'autodeclaracao',
        ] as $b) {
            $data[$b] = (bool) ($data[$b] ?? false);
        }

        // Ativo default true
        $data['ativo'] = (bool) ($data['ativo'] ?? true);

        // Ordem default 0
        $data['ordem'] = (int) ($data['ordem'] ?? 0);

        return $data;
    }

    /**
     * Decide se deve "Salvar e Fechar".
     * Aceita tanto '__action=save-close' quanto 'fechar=1' (legado).
     */
    private function shouldClose(Request $req): bool
    {
        if ($req->input('__action') === 'save-close') {
            return true;
        }
        return $req->boolean('fechar'); // compatibilidade
    }
}
