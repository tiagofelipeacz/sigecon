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
        'tipo',
        'titulo',
        'legenda',
        'grupo',
        'posicao',
        'tempo_indeterminado',
        'publicado_em',
        'visivel_de',
        'visivel_ate',
        'ativo',
        'restrito',         // algumas instalações usam "privado" (getter abaixo cobre)
        'restrito_cargos',
        'arquivo_path',     // algumas usam "arquivo" ou "path" (getter abaixo cobre)
        'link_url',         // algumas usam "url" ou "link" (getter abaixo cobre)
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

    // ---------------------------
    // Acessores tolerantes a schema
    // ---------------------------

    /**
     * Lê arquivo_path com fallback para 'arquivo' ou 'path'.
     */
    public function getArquivoPathAttribute($value): ?string
    {
        if (!empty($value)) {
            return $value;
        }
        $alt = $this->getAttributeFromArray('arquivo') ?? $this->getAttributeFromArray('path');
        return $alt ?: null;
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
     * URL pública do anexo (arquivo ou link).
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->tipo === 'link' && $this->link_url) {
            return $this->link_url;
        }
        if ($this->tipo === 'arquivo' && $this->arquivo_path) {
            return Storage::disk('public')->url($this->arquivo_path);
        }
        return null;
    }

    // ---------------------------
    // Relacionamentos
    // ---------------------------

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
