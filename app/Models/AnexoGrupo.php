<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnexoGrupo extends Model
{
    protected $table = 'anexo_grupos';

    protected $fillable = [
        'nome', 'ordem', 'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];
}
