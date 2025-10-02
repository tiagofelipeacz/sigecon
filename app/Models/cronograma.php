<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cronograma extends Model
{
    protected $table = 'cronogramas';

    protected $fillable = [
        'concurso_id', 'titulo', 'descricao', 'local',
        'inicio', 'fim', 'publicar', 'ordem',
    ];

    protected $casts = [
        'publicar' => 'boolean',
        'inicio'   => 'datetime',
        'fim'      => 'datetime',
    ];

    public function concurso()
    {
        return $this->belongsTo(Concurso::class, 'concurso_id');
    }
}
