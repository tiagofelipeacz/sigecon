<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ConcursoAnexo extends Model
{
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
        'restrito',
        'restrito_cargos',
        'arquivo_path',
        'link_url',
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
    ];

    // URL pública do arquivo (se existir)
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

    public function concurso()
    {
        return $this->belongsTo(Concurso::class, 'concurso_id');
    }
}
