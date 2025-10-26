<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ConcursoAnexo extends Model
{
    use SoftDeletes;

    protected $table = 'concursos_anexos';

    protected $fillable = [
        'concurso_id',
        'grupo_id',
        'tipo',                // 'arquivo' | 'link'
        'titulo',
        'legenda',
        'grupo',
        'posicao',
        'tempo_indeterminado',
        'publicado_em',
        'visivel_de',
        'visivel_ate',
        'ativo',
        'restrito',            // algumas instalações usam "privado" (getter cobre)
        'restrito_cargos',
        'arquivo_path',        // algumas usam "arquivo" ou "path" (getter cobre)
        'link_url',            // algumas usam "url" ou "link" (getter cobre)
        'metadados',
    ];

    protected $casts = [
        'tempo_indeterminado' => 'boolean',
        'ativo'               => 'boolean',
        'restrito'            => 'boolean',
        'publicado_em'        => 'datetime',
        'visivel_de'          => 'datetime',
        'visivel_ate'         => 'datetime',
        'restrito_cargos'     => 'array',
        'metadados'           => 'array',
        'deleted_at'          => 'datetime',
    ];

    // ---------------------------------------------------------------------
    // ACESSORES TOLERANTES A SCHEMA
    // ---------------------------------------------------------------------

    /**
     * Lê arquivo_path com fallback para 'arquivo' ou 'path'.
     */
    public function getArquivoPathAttribute($value): ?string
    {
        if (!empty($value)) {
            return $this->normalizeStoredPath($value);
        }
        $alt = $this->getAttributeFromArray('arquivo') ?? $this->getAttributeFromArray('path');
        return $alt ? $this->normalizeStoredPath($alt) : null;
    }

    /**
     * Lê link_url com fallback para 'url' ou 'link'.
     */
    public function getLinkUrlAttribute($value): ?string
    {
        if (!empty($value)) {
            return $value;
        }
        $alt = $this->getAttributeFromArray('url') ?? $this->getAttributeFromArray('link');
        return $alt ?: null;
    }

    /**
     * Lê restrito com fallback para 'privado'.
     */
    public function getRestritoAttribute($value): ?bool
    {
        if (!is_null($value)) {
            return (bool) $value;
        }
        $priv = $this->getAttributeFromArray('privado');
        return is_null($priv) ? null : (bool) $priv;
    }

    /**
     * Lê posicao com fallback para 'ordem'.
     */
    public function getPosicaoAttribute($value): int
    {
        if (!is_null($value)) {
            return (int) $value;
        }
        $ordem = $this->getAttributeFromArray('ordem');
        return (int) ($ordem ?? 0);
    }

    /**
     * Apenas o nome do arquivo (basename) derivado do arquivo_path/arquivo/path.
     */
    public function getFilenameAttribute(): ?string
    {
        $path = $this->arquivo_path;
        if (!$path) return null;

        $p = str_replace('\\', '/', $path);
        return basename($p);
    }

    /**
     * URL pública do anexo:
     * - Se tipo = 'link' e houver link_url => retorna o link externo.
     * - Se tipo = 'arquivo' => retorna rota curta /anexos/{concurso}/{filename}.
     *   (Fallback para Storage::disk('public')->url(...) se a rota não existir por algum motivo.)
     */
    public function getUrlAttribute(): ?string
    {
        // LINK externo tem prioridade
        if (($this->tipo === 'link') && $this->link_url) {
            return $this->link_url;
        }

        // ARQUIVO local => rota pública curta
        if ($this->tipo === 'arquivo') {
            $filename = $this->filename;
            if ($filename && $this->concurso_id) {
                // Tenta usar a rota nomeada do site
                try {
                    return route('site.anexos.file', [$this->concurso_id, $filename]);
                } catch (\Throwable $e) {
                    // Fallback: URL do disco public (útil se storage:link estiver ativo)
                    if ($this->arquivo_path) {
                        return Storage::disk('public')->url($this->arquivo_path);
                    }
                }
            }
        }

        return null;
    }

    // ---------------------------------------------------------------------
    // HELPERS
    // ---------------------------------------------------------------------

    /**
     * Normaliza paths vindos do banco removendo prefixos comuns
     * para que sejam relativos ao disco quando apropriado.
     */
    protected function normalizeStoredPath(string $raw): string
    {
        $p = str_replace('\\', '/', $raw);
        $p = ltrim($p, '/');

        // Remove prefixos redundantes comuns
        foreach (['storage/', 'public/', 'app/public/'] as $prefix) {
            if (stripos($p, $prefix) === 0) {
                $p = substr($p, strlen($prefix));
                break;
            }
        }
        return $p;
    }

    /**
     * Indica se o anexo é restrito/privado (compatível com colunas alternativas).
     */
    public function isRestrito(): bool
    {
        $val = $this->attributes['restrito'] ?? $this->attributes['privado'] ?? null;
        if (is_null($val)) return false;
        if (is_bool($val)) return $val;
        $s = strtolower((string)$val);
        return $s === '1' || $s === 'true' || $s === 'on';
    }

    // ---------------------------------------------------------------------
    // RELACIONAMENTOS
    // ---------------------------------------------------------------------

    public function concurso()
    {
        return $this->belongsTo(Concurso::class, 'concurso_id');
    }

    public function grupoRef()
    {
        // opcional: quando houver tabela anexo_grupos (grupo_id)
        return $this->belongsTo(AnexoGrupo::class, 'grupo_id');
    }
}
