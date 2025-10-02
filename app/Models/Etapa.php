<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Etapa extends Model
{
    protected $table = 'etapas';

    protected $fillable = [
        'concurso_id',
        'titulo',   // ex.: "Prova Objetiva"
        'tipo',     // ex.: "objetiva" | "titulo" | "pratica" | ...
        'slug',     // ex.: "prova-objetiva"
    ];

    public function concurso()
    {
        return $this->belongsTo(Concurso::class, 'concurso_id');
    }
}
