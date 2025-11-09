<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidatoInscricao extends Model
{
    use SoftDeletes;

    protected $table = 'concursos_inscritos';

    protected $fillable = [
        'candidato_id',
        'concurso_id',
        'cargo_id',
        'localidade_id',
        'item_id',
        'protocolo',
        'status',
        'taxa_inscricao',
        'extras',
    ];

    protected $casts = [
        'extras' => 'array',
    ];
}
