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

        // Se indeterminado, zera AMBAS as datas
        if (!empty($data['tempo_indeterminado'])) {
            $data['visivel_de']  = null;
            $data['visivel_ate'] = null;
        }

        // Upload de arquivo quando tipo = arquivo
        if (($data['tipo'] ?? '') === 'arquivo' && $req->hasFile('arquivo')) {
            $stored = $req->file('arquivo')->store("anexos/{$concurso->id}", 'public');
            $data['arquivo_path'] = $stored;
            $data['link_url']     = null;
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

        // Se indeterminado, zera AMBAS as datas
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
            $data['arquivo_path'] = $stored;
            $data['link_url']     = null;
        }

        // Se mudou para LINK, apaga arquivo antigo (se houver)
        if (($data['tipo'] ?? '') === 'link') {
            $oldPath = $anexo->arquivo_path ?? $anexo->arquivo ?? $anexo->path ?? null;
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $data['arquivo_path'] = null;
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
     * ABRE o anexo (stream inline) no ADMIN (protegido).
     * - Se for do tipo LINK, redireciona para a URL.
     * - Se for arquivo, tenta servir via discos e fallbacks locais.
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

        // Admin ignora "restrito" porque já está autenticado; apenas streama
        return $this->streamAnexoFile($anexo);
    }

    /**
     * PÚBLICO: abre por NOME DE ARQUIVO (rota curta /anexos/{concurso}/{arquivo})
     * - Bloqueia anexos restritos/privados, inativos e fora da janela pública.
     * - Se houver link externo, redireciona (se público).
     * - Stream inline do arquivo físico.
     */
    public function openPublicByFilename(int $concurso, string $arquivo)
    {
        // Normaliza para evitar path traversal e aceita urlencoded
        $arquivoOriginal = $arquivo;
        $arquivo  = str_replace(['..', '\\'], ['', '/'], $arquivo);
        $arquivo  = ltrim($arquivo, '/');
        $filename = basename($arquivo);
        $filenameDecoded = basename(rawurldecode($arquivoOriginal));

        // ============================
        // Localiza o registro por concurso + filename (somente em colunas que EXISTEM)
        // ============================
        $table = 'concursos_anexos';
        $fileColumnsAll = [
            'arquivo_path','arquivo','path','file','filename','filepath',
            'storage_path','original_name','nome_arquivo',
        ];
        $fileColumns = array_values(array_filter($fileColumnsAll, fn($c) => Schema::hasColumn($table, $c)));

        $anexo = null;
        if (!empty($fileColumns)) {
            $anexo = ConcursoAnexo::query()
                ->where('concurso_id', $concurso)
                ->where(function ($q) use ($fileColumns, $filename, $filenameDecoded) {
                    $needles = array_unique(array_filter([$filename, $filenameDecoded]));
                    $q->where(function($w) use ($fileColumns, $needles) {
                        foreach ($fileColumns as $col) {
                            foreach ($needles as $n) {
                                // usa LIKE terminando com o nome do arquivo
                                $w->orWhere($col, 'like', '%'.$n);
                            }
                        }
                    });
                })
                ->when(Schema::hasColumn($table, 'deleted_at'), fn($q) => $q->whereNull($table.'.deleted_at'))
                ->first();
        }

        if ($anexo) {
            // Verificação de visibilidade pública
            if (!$this->isPubliclyVisible($anexo)) {
                abort(403, 'Este anexo não está disponível publicamente.');
            }

            // Link externo?
            $tipo = (string)($anexo->tipo ?? '');
            if ($tipo === 'link') {
                $link = $anexo->link_url ?? $anexo->url ?? $anexo->link ?? null;
                if ($link) {
                    return redirect()->away($link);
                }
                abort(404, 'Link inválido.');
            }

            // Stream
            return $this->streamAnexoFile($anexo, $filenameDecoded ?: $filename, $concurso, $arquivo);
        }

        // Sem registro no banco: tenta caminhos padrão por concurso/arquivo
        $resp = $this->tryStreamByStandardPaths($concurso, $filenameDecoded ?: $filename);
        if ($resp) {
            return $resp;
        }

        abort(404, 'Arquivo não encontrado.');
    }

    /**
     * Faz a validação dos campos do formulário.
     */
    private function validated(Request $req): array
    {
        $data = $req->validate([
            'tipo'                 => ['required', 'in:arquivo,link'],
            'titulo'               => ['required', 'string', 'max:255'],
            'legenda'              => ['nullable', 'string', 'max:500'],
            'grupo'                => ['nullable', 'string', 'max:190'],
            'posicao'              => ['nullable', 'integer', 'min:0'],

            // aceitar apenas 0/1 para usar required_if corretamente
            'tempo_indeterminado'  => ['nullable', 'in:0,1'],

            'publicado_em'         => ['nullable', 'date'],

            // datas obrigatórias quando NÃO indeterminado
            'visivel_de'           => ['nullable', 'date', 'required_if:tempo_indeterminado,0'],
            'visivel_ate'          => ['nullable', 'date', 'after_or_equal:visivel_de', 'required_if:tempo_indeterminado,0'],

            'ativo'                => ['nullable'],
            'restrito'             => ['nullable'],
            'restrito_cargos'      => ['nullable', 'array'],
            'restrito_cargos.*'    => ['integer'],
            'link_url'             => ['nullable', 'url', 'max:1000'],
            'arquivo'              => ['nullable', 'file', 'max:15360'], // 15MB
        ]);

        // Coerções simples (checkbox/select como string)
        // Defaults
        $data['tempo_indeterminado'] = filter_var($req->input('tempo_indeterminado', true), FILTER_VALIDATE_BOOLEAN);
        $data['ativo']               = filter_var($req->input('ativo', true), FILTER_VALIDATE_BOOLEAN);
        $data['restrito']            = filter_var($req->input('restrito', false), FILTER_VALIDATE_BOOLEAN);
        $data['posicao']             = (int) ($req->input('posicao', 0));

        // Se tipo = link, zera arquivo; se tipo = arquivo, zera link
        if (($data['tipo'] ?? '') === 'link') {
            $data['arquivo_path'] = null;
        } else {
            $data['link_url'] = null;
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
     */
    private function carregarCargos(Concurso $concurso): array
    {
        // Implementação real se tiver cargos
        return [];
    }

    // =====================================================================
    // Helpers internos de stream/arquivo (compartilhados por admin e público)
    // =====================================================================

    /**
     * Verifica se um anexo está público (não restrito/privado, ativo, dentro da janela).
     */
    private function isPubliclyVisible(ConcursoAnexo $anexo): bool
    {
        // restrito/privado
        $restritoAttr = $anexo->restrito ?? $anexo->privado ?? null;
        $isRestrito = is_bool($restritoAttr)
            ? $restritoAttr
            : ((string)$restritoAttr === '1' || strtolower((string)$restritoAttr) === 'true');
        if ($isRestrito) return false;

        // ativo
        if (Schema::hasColumn('concursos_anexos', 'ativo')) {
            if (!((int)($anexo->ativo ?? 0) === 1)) return false;
        }

        // janela de visibilidade
        $hasIndet = Schema::hasColumn('concursos_anexos', 'tempo_indeterminado');
        $indet = $hasIndet ? (bool)$anexo->tempo_indeterminado : true;

        if (!$indet) {
            $de  = Schema::hasColumn('concursos_anexos', 'visivel_de')  ? ($anexo->visivel_de  ?? null) : null;
            $ate = Schema::hasColumn('concursos_anexos', 'visivel_ate') ? ($anexo->visivel_ate ?? null) : null;
            $now = now();

            if ($de && $now->lt($de))  return false;
            if ($ate && $now->gt($ate)) return false;
        }

        return true;
    }

    /**
     * Streama um anexo por seu registro (resolve caminho físico e cabeçalhos).
     */
    private function streamAnexoFile(ConcursoAnexo $anexo, ?string $forceFilename = null, ?int $concursoId = null, ?string $arquivo = null)
    {
        // 1) Tenta via caminho salvo no banco
        $rawPath = $anexo->arquivo_path ?? $anexo->arquivo ?? $anexo->path ?? null;
        if ($rawPath) {
            $absolute = $this->resolveAbsolutePath($rawPath);
            if ($absolute && is_file($absolute)) {
                return $this->respondInlineFile($absolute, $forceFilename);
            }
        }

        // 2) Se não encontrou, tenta padrões por /anexos/{concurso}/{arquivo}
        if ($concursoId !== null && $arquivo !== null) {
            $resp = $this->tryStreamByStandardPaths($concursoId, $arquivo, $forceFilename);
            if ($resp) return $resp;
        }

        // 3) Último recurso: tenta pelo nome do arquivo do caminho salvo
        if ($rawPath) {
            $filename = basename(str_replace('\\', '/', $rawPath));
            if ($filename && $concursoId !== null) {
                $resp = $this->tryStreamByStandardPaths($concursoId, $filename, $forceFilename);
                if ($resp) return $resp;
            }
        }

        abort(404, 'Arquivo não encontrado.');
    }

    /**
     * Tenta streamar pelos caminhos padrão:
     * - storage/app/anexos/{concurso}/{arquivo} (disk local)
     * - storage/app/public/anexos/{concurso}/{arquivo} (disk public)
     */
    private function tryStreamByStandardPaths(int $concursoId, string $arquivo, ?string $forceFilename = null)
    {
        // trabalha com o nome “como veio” e também decodificado
        $cands = array_unique(array_filter([$arquivo, rawurldecode($arquivo)]));

        $diskLocal  = Storage::disk();         // storage/app
        $diskPublic = Storage::disk('public'); // storage/app/public

        foreach ($cands as $name) {
            $name = ltrim(str_replace('\\', '/', $name), '/');

            $localRel  = "anexos/{$concursoId}/{$name}";
            $publicRel = "anexos/{$concursoId}/{$name}";

            if ($diskLocal->exists($localRel)) {
                return $this->respondInlineFile($diskLocal->path($localRel), $forceFilename);
            }
            if ($diskPublic->exists($publicRel)) {
                return $this->respondInlineFile($diskPublic->path($publicRel), $forceFilename);
            }
        }

        return null;
    }

    /**
     * Resolve um caminho absoluto a partir de um valor salvo no banco (suporta variações).
     */
    private function resolveAbsolutePath(string $rawPath): ?string
    {
        $raw = $rawPath;
        $p = str_replace('\\', '/', $rawPath);
        $p = ltrim($p, '/');

        // Caminho absoluto? (Linux/Mac) ou (Windows "C:\")
        $isAbsUnix = DIRECTORY_SEPARATOR === '/' && str_starts_with($raw, '/');
        $isAbsWin  = preg_match('/^[A-Za-z]\:\\\\/', $raw) === 1;
        if ($isAbsUnix || $isAbsWin) {
            return is_file($raw) ? $raw : null;
        }

        // Normaliza prefixes comuns
        foreach (['storage/', 'public/', 'app/public/'] as $prefix) {
            if (stripos($p, $prefix) === 0) {
                $p = substr($p, strlen($prefix));
            }
        }

        // Tenta no disco 'public'
        $diskPublic = Storage::disk('public');
        if ($diskPublic->exists($p)) {
            return $diskPublic->path($p);
        }

        // Tenta no disco 'local'
        $diskLocal = Storage::disk();
        if ($diskLocal->exists($p)) {
            return $diskLocal->path($p);
        }

        // Tenta em public_path()
        $publicAbs = public_path($raw);
        if (is_file($publicAbs)) {
            return $publicAbs;
        }

        // Tenta em public/storage/...
        $publicStorage = public_path('storage/'.ltrim($p, '/'));
        if (is_file($publicStorage)) {
            return $publicStorage;
        }

        return null;
    }

    /**
     * Responde o arquivo inline com MIME e cabeçalhos adequados.
     */
    private function respondInlineFile(string $absolutePath, ?string $forceFilename = null)
    {
        $name = $forceFilename ?: basename($absolutePath);

        // Descobre MIME com fallbacks
        $mime = 'application/octet-stream';
        try {
            if (function_exists('mime_content_type')) {
                $mime = mime_content_type($absolutePath) ?: $mime;
            }
        } catch (\Throwable $e) {
            // ignora
        }

        return response()->file($absolutePath, [
            'Content-Type'           => $mime,
            'Content-Disposition'    => 'inline; filename="'.$name.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'public, max-age=604800, immutable', // 7 dias
        ]);
    }
}
