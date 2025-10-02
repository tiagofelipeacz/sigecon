<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use App\Models\ConcursoAnexo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ConcursoAnexoController extends Controller
{
    /**
     * Lista anexos do concurso.
     */
    public function index(Concurso $concurso, Request $req)
    {
        $q     = trim((string) $req->get('q', ''));
        $ativo = (string) $req->get('ativo', ''); // '' | '1' | '0'

        $rows = ConcursoAnexo::where('concurso_id', $concurso->id)
            ->when($q !== '', function ($w) use ($q) {
                $like = "%{$q}%";
                $w->where(function ($x) use ($like) {
                    $x->where('titulo', 'like', $like)
                      ->orWhere('grupo', 'like', $like);
                });
            })
            ->when($ativo !== '', fn ($w) => $w->where('ativo', (int) $ativo))
            ->orderBy('posicao')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.concursos.anexos.index', [
            'concurso' => $concurso,
            'rows'     => $rows,
            'q'        => $q,
            'ativo'    => $ativo,
        ]);
    }

    /**
     * Formulário de criação.
     */
    public function create(Concurso $concurso)
    {
        $anexo  = new ConcursoAnexo([
            'tipo'                => 'arquivo',
            'tempo_indeterminado' => false, // padrão: NÃO
            'ativo'               => true,
            'restrito'            => false,
            'posicao'             => 0,
        ]);

        $cargos = $this->carregarCargos($concurso);

        return view('admin.concursos.anexos.form', [
            'concurso' => $concurso,
            'anexo'    => $anexo,
            'isEdit'   => false,
            'cargos'   => $cargos,
        ]);
    }

    /**
     * Salva um novo anexo.
     */
    public function store(Concurso $concurso, Request $req)
    {
        $data = $this->validated($req);
        $data['concurso_id'] = $concurso->id;

        // Upload de arquivo quando tipo = arquivo
        if ($data['tipo'] === 'arquivo' && $req->hasFile('arquivo')) {
            $data['arquivo_path'] = $req->file('arquivo')->store("anexos/{$concurso->id}", 'public');
            $data['link_url'] = null;
        }

        // Colunas opcionais (dependem do schema)
        if (!Schema::hasColumn('concursos_anexos', 'legenda')) {
            unset($data['legenda']);
        }
        if (!Schema::hasColumn('concursos_anexos', 'restrito_cargos')) {
            unset($data['restrito_cargos']);
        }
        if (!Schema::hasColumn('concursos_anexos', 'visivel_de')) {
            unset($data['visivel_de']);
        }
        if (!Schema::hasColumn('concursos_anexos', 'visivel_ate')) {
            unset($data['visivel_ate']);
        }

        ConcursoAnexo::create($data);

        return redirect()
            ->route('admin.concursos.anexos.index', $concurso)
            ->with('status', 'Anexo criado com sucesso!');
    }

    /**
     * Formulário de edição.
     */
    public function edit(Concurso $concurso, ConcursoAnexo $anexo)
    {
        $this->assertOwner($concurso, $anexo);

        $cargos = $this->carregarCargos($concurso);

        return view('admin.concursos.anexos.form', [
            'concurso' => $concurso,
            'anexo'    => $anexo,
            'isEdit'   => true,
            'cargos'   => $cargos,
        ]);
    }

    /**
     * Atualiza um anexo.
     */
    public function update(Concurso $concurso, ConcursoAnexo $anexo, Request $req)
    {
        $this->assertOwner($concurso, $anexo);

        $data = $this->validated($req);

        // Upload novo substitui arquivo antigo
        if ($data['tipo'] === 'arquivo' && $req->hasFile('arquivo')) {
            // apaga arquivo antigo, se houver
            if ($anexo->arquivo_path) {
                Storage::disk('public')->delete($anexo->arquivo_path);
            }
            $data['arquivo_path'] = $req->file('arquivo')->store("anexos/{$concurso->id}", 'public');
            $data['link_url'] = null;
        }

        // Colunas opcionais (dependem do schema)
        if (!Schema::hasColumn('concursos_anexos', 'legenda')) {
            unset($data['legenda']);
        }
        if (!Schema::hasColumn('concursos_anexos', 'restrito_cargos')) {
            unset($data['restrito_cargos']);
        }
        if (!Schema::hasColumn('concursos_anexos', 'visivel_de')) {
            unset($data['visivel_de']);
        }
        if (!Schema::hasColumn('concursos_anexos', 'visivel_ate')) {
            unset($data['visivel_ate']);
        }

        $anexo->update($data);

        return redirect()
            ->route('admin.concursos.anexos.index', $concurso)
            ->with('status', 'Anexo atualizado com sucesso!');
    }

    /**
     * Remove um anexo.
     */
    public function destroy(Concurso $concurso, ConcursoAnexo $anexo)
    {
        $this->assertOwner($concurso, $anexo);

        if ($anexo->arquivo_path) {
            Storage::disk('public')->delete($anexo->arquivo_path);
        }

        $anexo->delete();

        return redirect()
            ->route('admin.concursos.anexos.index', $concurso)
            ->with('status', 'Anexo excluído com sucesso!');
    }

    /**
     * Validação dos campos do formulário.
     */
    private function validated(Request $req): array
    {
        $data = $req->validate([
            'tipo'                 => ['required', 'in:arquivo,link'],
            'titulo'               => ['required', 'string', 'max:255'],
            'legenda'              => ['nullable', 'string', 'max:500'],
            'grupo'                => ['nullable', 'string', 'max:190'],
            'posicao'              => ['nullable', 'integer', 'min:0'],
            'tempo_indeterminado'  => ['nullable'], // checkbox/select -> trataremos abaixo
            'publicado_em'         => ['nullable', 'date'],
            'visivel_de'           => ['nullable', 'date'],
            'visivel_ate'          => ['nullable', 'date', 'after_or_equal:visivel_de'],
            'ativo'                => ['required'],
            'restrito'             => ['required'],
            'restrito_cargos'      => ['nullable', 'array'],
            'restrito_cargos.*'    => ['integer'],
            'link_url'             => ['nullable', 'url', 'max:1000'],
            'arquivo'              => ['nullable', 'file', 'max:15360'], // 15MB
        ]);

        // Coerções simples (checkbox/select vinda como string)
        $data['tempo_indeterminado'] = filter_var($req->input('tempo_indeterminado', false), FILTER_VALIDATE_BOOLEAN);
        $data['ativo']               = filter_var($req->input('ativo', true), FILTER_VALIDATE_BOOLEAN);
        $data['restrito']            = filter_var($req->input('restrito', false), FILTER_VALIDATE_BOOLEAN);
        $data['posicao']             = (int) ($req->input('posicao', 0));

        // Se tipo = link, zera arquivo; se tipo = arquivo, zera link
        if ($data['tipo'] === 'link') {
            $data['arquivo_path'] = null;
        } else {
            $data['link_url'] = null;
        }

        return $data;
    }

    /**
     * Garante que o anexo pertence ao concurso.
     */
    private function assertOwner(Concurso $concurso, ConcursoAnexo $anexo): void
    {
        if ((int) $anexo->concurso_id !== (int) $concurso->id) {
            abort(404);
        }
    }

    /**
     * Carrega lista de cargos (se houver model/relacionamento).
     * Aqui retornamos array vazio para evitar erro quando não existir.
     */
    private function carregarCargos(Concurso $concurso): array
    {
        // Se você tiver uma tabela de cargos, substitua por um select real.
        // return Cargo::where('concurso_id', $concurso->id)->orderBy('nome')->get();
        return [];
    }
}
