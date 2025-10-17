<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use App\Models\ConcursoAnexo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ConcursoAnexoController extends Controller
{
    /**
     * Lista anexos do concurso.
     */
    public function index(Concurso $concurso, Request $req)
    {
        $q      = trim((string) $req->get('q', ''));
        $ativo  = (string) $req->get('ativo',  ''); // '' | '1' | '0'
        $tipo   = (string) $req->get('tipo',   ''); // '' | 'arquivo' | 'link'
        $restr  = (string) $req->get('restrito', ''); // '' | '1' | '0'

        // Algumas instalações usam "privado" no lugar de "restrito"
        $colRestr = Schema::hasColumn('concursos_anexos', 'restrito')
            ? 'restrito'
            : (Schema::hasColumn('concursos_anexos', 'privado') ? 'privado' : null);

        $orderCol = Schema::hasColumn('concursos_anexos', 'posicao')
            ? 'posicao'
            : (Schema::hasColumn('concursos_anexos', 'ordem') ? 'ordem' : null);

        $rows = ConcursoAnexo::where('concurso_id', $concurso->id)
            // Soft delete: se existir a coluna, não listar "apagados"
            ->when(Schema::hasColumn('concursos_anexos', 'deleted_at'), fn ($w) =>
                $w->whereNull('deleted_at')
            )
            ->when($q !== '', function ($w) use ($q) {
                $like = "%{$q}%";
                $w->where(function ($x) use ($like) {
                    $x->where('titulo', 'like', $like)
                      ->orWhere('grupo', 'like', $like);
                });
            })
            ->when($ativo !== '' && Schema::hasColumn('concursos_anexos', 'ativo'), fn ($w) => $w->where('ativo', (int) $ativo))
            ->when($tipo  !== '' && Schema::hasColumn('concursos_anexos', 'tipo'),  fn ($w) => $w->where('tipo', $tipo))
            ->when($restr !== '' && $colRestr, fn ($w)        => $w->where($colRestr, (int) $restr))
            ->when($orderCol, fn($w) => $w->orderBy($orderCol))
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.concursos.anexos.index', [
            'concurso' => $concurso,
            'rows'     => $rows,
            'q'        => $q,
            'ativo'    => $ativo,
            'tipo'     => $tipo,
            'restrito' => $restr,
        ]);
    }

    /**
     * Formulário de criação.
     */
    public function create(Concurso $concurso)
    {
        $anexo  = new ConcursoAnexo([
            'tipo'                => 'arquivo',
            'tempo_indeterminado' => true,   // padrão: SIM
            'ativo'               => true,
            // alguns bancos usam "privado", outros "restrito" — o formulário envia "restrito"
            'restrito'            => false,
            'posicao'             => 0,
        ]);

        $cargos  = $this->carregarCargos($concurso);
        $grupos  = $this->carregarGruposAnexos($concurso); // << novo

        return view('admin.concursos.anexos.form', [
            'concurso' => $concurso,
            'anexo'    => $anexo,
            'isEdit'   => false,
            'cargos'   => $cargos,
            'grupos'   => $grupos,          // << novo
        ]);
    }

    /**
     * Salva um novo anexo.
     */
    public function store(Concurso $concurso, Request $req)
    {
        $data = $this->validated($req);
        $data['concurso_id'] = $concurso->id;

        // AJUSTE: se indeterminado, zera AMBAS as datas
        if (!empty($data['tempo_indeterminado'])) {
            $data['visivel_de']  = null;
            $data['visivel_ate'] = null;
        }

        // Upload de arquivo quando tipo = arquivo
        if (($data['tipo'] ?? '') === 'arquivo' && $req->hasFile('arquivo')) {
            $stored = $req->file('arquivo')->store("anexos/{$concurso->id}", 'public');
            $data['arquivo_path'] = $stored;  // será mapeado para a coluna correta abaixo
            $data['link_url']     = null;     // idem
        }

        // Mapeia/limpa colunas conforme o schema real
        $this->mapColumnsForPersist($data);

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
        $grupos = $this->carregarGruposAnexos($concurso); // << novo

        return view('admin.concursos.anexos.form', [
            'concurso' => $concurso,
            'anexo'    => $anexo,
            'isEdit'   => true,
            'cargos'   => $cargos,
            'grupos'   => $grupos,          // << novo
        ]);
    }

    /**
     * Atualiza um anexo.
     */
    public function update(Concurso $concurso, ConcursoAnexo $anexo, Request $req)
    {
        $this->assertOwner($concurso, $anexo);

        $data = $this->validated($req);

        // AJUSTE: se indeterminado, zera AMBAS as datas
        if (!empty($data['tempo_indeterminado'])) {
            $data['visivel_de']  = null;
            $data['visivel_ate'] = null;
        }

        // Upload novo substitui arquivo antigo
        if (($data['tipo'] ?? '') === 'arquivo' && $req->hasFile('arquivo')) {
            $oldPath = $anexo->arquivo_path ?? $anexo->arquivo ?? $anexo->path ?? null;
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $stored = $req->file('arquivo')->store("anexos/{$concurso->id}", 'public');
            $data['arquivo_path'] = $stored;  // será mapeado para a coluna correta abaixo
            $data['link_url']     = null;
        }

        // Se mudou para LINK, apaga arquivo antigo (se houver)
        if (($data['tipo'] ?? '') === 'link') {
            $oldPath = $anexo->arquivo_path ?? $anexo->arquivo ?? $anexo->path ?? null;
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $data['arquivo_path'] = null; // mapeado abaixo
        }

        // Mapeia/limpa colunas conforme o schema real
        $this->mapColumnsForPersist($data);

        $anexo->update($data);

        return redirect()
            ->route('admin.concursos.anexos.index', $concurso)
            ->with('status', 'Anexo atualizado com sucesso!');
    }

    /**
     * Alterna o campo "ativo" (PATCH).
     */
    public function toggleAtivo(Concurso $concurso, ConcursoAnexo $anexo, Request $req)
    {
        $this->assertOwner($concurso, $anexo);

        if (!Schema::hasColumn('concursos_anexos', 'ativo')) {
            return back()->with('error', 'Coluna "ativo" não existe na tabela concursos_anexos.');
        }

        $anexo->ativo = (int)!((int)$anexo->ativo);
        $anexo->save();

        if ($req->wantsJson()) {
            return response()->json(['ok' => true, 'ativo' => (bool)$anexo->ativo]);
        }

        return back()->with('status', 'Status de publicação atualizado.');
    }

    /**
     * Alterna o campo "restrito" (PATCH).
     * Quando restrito = true, o anexo deve aparecer apenas na área do candidato.
     * (Em alguns bancos a coluna é "privado")
     */
    public function toggleRestrito(Concurso $concurso, ConcursoAnexo $anexo, Request $req)
    {
        $this->assertOwner($concurso, $anexo);

        $hasRestrito = Schema::hasColumn('concursos_anexos', 'restrito');
        $hasPrivado  = Schema::hasColumn('concursos_anexos', 'privado');

        if (!$hasRestrito && !$hasPrivado) {
            return back()->with('error', 'Nenhuma coluna de restrição ("restrito" ou "privado") existe na tabela concursos_anexos.');
        }

        if ($hasRestrito) {
            $anexo->restrito = (int)!((int)($anexo->restrito ?? 0));
        } else {
            $anexo->privado  = (int)!((int)($anexo->privado  ?? 0));
        }

        $anexo->save();

        if ($req->wantsJson()) {
            return response()->json([
                'ok'       => true,
                'restrito' => (bool)($hasRestrito ? $anexo->restrito : $anexo->privado),
            ]);
        }

        return back()->with('status', 'Visibilidade (restrito) atualizada.');
    }

    /**
     * Remove um anexo (soft delete se existir deleted_at; caso contrário, mantém o comportamento original).
     */
    public function destroy(Concurso $concurso, ConcursoAnexo $anexo)
    {
        $this->assertOwner($concurso, $anexo);

        $hasDeletedAt = Schema::hasColumn('concursos_anexos', 'deleted_at');

        if ($hasDeletedAt) {
            // SOFT DELETE: mantém o arquivo e só marca como excluído
            $anexo->deleted_at = now();
            $anexo->save();
        } else {
            // Comportamento antigo: remove arquivo e apaga o registro
            $oldPath = $anexo->arquivo_path ?? $anexo->arquivo ?? $anexo->path ?? null;
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $anexo->delete();
        }

        return redirect()
            ->route('admin.concursos.anexos.index', $concurso)
            ->with('status', 'Anexo excluído com sucesso!');
    }

    /**
     * ABRE o anexo (stream inline) independente de symlink.
     * - Se for do tipo LINK, redireciona para a URL.
     * - Se for arquivo, tenta servir via disk('public') e fallbacks locais.
     */
    public function open(Concurso $concurso, int $anexoId)
    {
        $anexo = ConcursoAnexo::query()
            ->where('id', $anexoId)
            ->where('concurso_id', $concurso->id)
            ->when(Schema::hasColumn('concursos_anexos', 'deleted_at'), fn($q) => $q->whereNull('deleted_at'))
            ->firstOrFail();

        // Tipo link? redireciona
        $tipo = (string)($anexo->tipo ?? '');
        if ($tipo === 'link') {
            $link = $anexo->link_url ?? $anexo->url ?? $anexo->link ?? null;
            if ($link) {
                return redirect()->away($link);
            }
            abort(404);
        }

        // Descobrir caminho salvo no banco
        $rawPath = $anexo->arquivo_path ?? $anexo->arquivo ?? $anexo->path ?? null;
        if (!$rawPath) {
            abort(404);
        }

        // Normaliza
        $p = str_replace('\\', '/', (string)$rawPath);
        $p = ltrim($p, '/');

        // se veio "storage/..." ou "public/..." ou "app/public/...", recorta para ficar relativo ao disk('public')
        if (stripos($p, 'storage/') === 0)     { $p = substr($p, strlen('storage/')); }
        if (stripos($p, 'public/') === 0)      { $p = substr($p, strlen('public/')); }
        if (stripos($p, 'app/public/') === 0)  { $p = substr($p, strlen('app/public/')); }

        // 1) disk('public')
        $disk = Storage::disk('public');
        if ($disk->exists($p)) {
            $absolute = $disk->path($p);
            $name     = basename($absolute);
            // inline (abre no navegador)
            return response()->file($absolute, [
                'Cache-Control'      => 'private, max-age=0',
                'Content-Disposition'=> 'inline; filename="'.$name.'"',
            ]);
        }

        // 2) Caminho público absoluto informado no banco?
        $publicAbs = public_path($rawPath);
        if (file_exists($publicAbs)) {
            $name = basename($publicAbs);
            return response()->file($publicAbs, [
                'Cache-Control'      => 'private, max-age=0',
                'Content-Disposition'=> 'inline; filename="'.$name.'"',
            ]);
        }

        // 3) Fallback para public/storage/...
        $publicStorage = public_path('storage/'.ltrim($p, '/'));
        if (file_exists($publicStorage)) {
            $name = basename($publicStorage);
            return response()->file($publicStorage, [
                'Cache-Control'      => 'private, max-age=0',
                'Content-Disposition'=> 'inline; filename="'.$name.'"',
            ]);
        }

        abort(404);
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

            // AJUSTE: aceitar apenas 0/1 para habilitar required_if
            'tempo_indeterminado'  => ['nullable', 'in:0,1'],

            'publicado_em'         => ['nullable', 'date'],

            // AJUSTE: datas obrigatórias quando NÃO indeterminado
            'visivel_de'           => ['nullable', 'date', 'required_if:tempo_indeterminado,0'],
            'visivel_ate'          => ['nullable', 'date', 'after_or_equal:visivel_de', 'required_if:tempo_indeterminado,0'],

            'ativo'                => ['nullable'], // tratado abaixo
            'restrito'             => ['nullable'], // tratado abaixo (ou mapeado para "privado")
            'restrito_cargos'      => ['nullable', 'array'],
            'restrito_cargos.*'    => ['integer'],
            'link_url'             => ['nullable', 'url', 'max:1000'],
            'arquivo'              => ['nullable', 'file', 'max:15360'], // 15MB
        ]);

        // Coerções simples (checkbox/select vinda como string)
        // Defaults: tempo indeterminado = true, ativo = true, restrito = false
        $data['tempo_indeterminado'] = filter_var($req->input('tempo_indeterminado', true), FILTER_VALIDATE_BOOLEAN);
        $data['ativo']               = filter_var($req->input('ativo', true), FILTER_VALIDATE_BOOLEAN);
        $data['restrito']            = filter_var($req->input('restrito', false), FILTER_VALIDATE_BOOLEAN);
        $data['posicao']             = (int) ($req->input('posicao', 0));

        // Se tipo = link, zera arquivo; se tipo = arquivo, zera link
        if (($data['tipo'] ?? '') === 'link') {
            $data['arquivo_path'] = null; // mapeado depois
        } else {
            $data['link_url'] = null;     // mapeado depois
        }

        // Normaliza array de cargos restritos vazio
        if (isset($data['restrito_cargos']) && is_array($data['restrito_cargos']) && empty($data['restrito_cargos'])) {
            $data['restrito_cargos'] = [];
        }

        return $data;
    }

    /**
     * Mapeia as chaves do $data para as colunas reais existentes no schema
     * e remove as que não existem.
     */
    private function mapColumnsForPersist(array &$data): void
    {
        $tbl = 'concursos_anexos';

        // ativo
        if (!Schema::hasColumn($tbl, 'ativo')) {
            unset($data['ativo']);
        }

        // tempo_indeterminado
        if (!Schema::hasColumn($tbl, 'tempo_indeterminado')) {
            unset($data['tempo_indeterminado']);
        }

        // publicado_em / visível de/até
        if (!Schema::hasColumn($tbl, 'publicado_em')) unset($data['publicado_em']);
        if (!Schema::hasColumn($tbl, 'visivel_de'))   unset($data['visivel_de']);
        if (!Schema::hasColumn($tbl, 'visivel_ate'))  unset($data['visivel_ate']);

        // legenda e cargos
        if (!Schema::hasColumn($tbl, 'legenda'))          unset($data['legenda']);
        if (!Schema::hasColumn($tbl, 'restrito_cargos'))  unset($data['restrito_cargos']);

        // posicao -> ordem (se aplicável)
        if (isset($data['posicao']) && !Schema::hasColumn($tbl, 'posicao')) {
            if (Schema::hasColumn($tbl, 'ordem')) {
                $data['ordem'] = $data['posicao'];
            }
            unset($data['posicao']);
        }

        // restrito -> privado (quando aplicável)
        if (array_key_exists('restrito', $data)) {
            if (Schema::hasColumn($tbl, 'restrito')) {
                // ok
            } elseif (Schema::hasColumn($tbl, 'privado')) {
                $data['privado'] = $data['restrito'];
                unset($data['restrito']);
            } else {
                unset($data['restrito']);
            }
        }

        // link_url -> url|link (quando aplicável)
        if (array_key_exists('link_url', $data)) {
            if (Schema::hasColumn($tbl, 'link_url')) {
                // ok
            } elseif (Schema::hasColumn($tbl, 'url')) {
                $data['url'] = $data['link_url'];
                unset($data['link_url']);
            } elseif (Schema::hasColumn($tbl, 'link')) {
                $data['link'] = $data['link_url'];
                unset($data['link_url']);
            } else {
                unset($data['link_url']);
            }
        }

        // arquivo_path -> arquivo|path (quando aplicável)
        if (array_key_exists('arquivo_path', $data)) {
            if (Schema::hasColumn($tbl, 'arquivo_path')) {
                // ok
            } elseif (Schema::hasColumn($tbl, 'arquivo')) {
                $data['arquivo'] = $data['arquivo_path'];
                unset($data['arquivo_path']);
            } elseif (Schema::hasColumn($tbl, 'path')) {
                $data['path'] = $data['arquivo_path'];
                unset($data['arquivo_path']);
            } else {
                unset($data['arquivo_path']);
            }
        }
    }

    /**
     * Sugestões de grupos de anexos (config/global ou existentes).
     */
    private function carregarGruposAnexos(Concurso $concurso): array
    {
        $nomes = [];

        // 1) Tabelas de configuração (todas que existirem)
        try {
            foreach ([['anexo_grupos','nome'], ['grupos_anexos','nome'], ['config_grupos_anexos','nome']] as $t) {
                [$tbl, $col] = $t;
                if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, $col)) {
                    $nomes = array_merge($nomes, DB::table($tbl)->orderBy($col)->pluck($col)->all());
                }
            }
        } catch (\Throwable $e) {
            // ignora erro de leitura de configurações
        }

        // 2) Também incluir os grupos já usados pelos anexos deste concurso
        if (Schema::hasColumn('concursos_anexos', 'grupo')) {
            $usados = ConcursoAnexo::query()
                ->where('concurso_id', $concurso->id)
                ->whereNotNull('grupo')
                ->whereRaw("TRIM(grupo) <> ''")
                ->distinct()
                ->orderBy('grupo')
                ->pluck('grupo')
                ->all();
            $nomes = array_merge($nomes, $usados);
        }

        // Normaliza
        $nomes = array_values(array_unique(array_map(
            fn($v) => trim((string)$v),
            array_filter($nomes, fn($v) => is_string($v) && trim($v) !== '')
        )));
        sort($nomes, SORT_NATURAL | SORT_FLAG_CASE);

        return $nomes;
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
        // Se houver implementação de cargos, substitua por um select real:
        // return Cargo::where('concurso_id', $concurso->id)->orderBy('nome')->get()->toArray();
        return [];
    }
}
