<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inscricao extends Model
{
    protected $table = 'inscricoes';

    protected $fillable = [
        'edital_id', 'cargo_id', 'user_id',
        'modalidade', 'status',
    ];

    // Se quiser timestamps automáticos (já existem no schema)
    public $timestamps = true;

    // Relações leves (evitamos depender de nomes de models que você não tenha)
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function concurso()
    {
        // Seu schema usa "edital_id" apontando para "concursos.id"
        return $this->belongsTo(\App\Models\Concurso::class, 'edital_id');
    }

    // cargo: tabela concursos_vagas_cargos (se você tiver o model próprio, ajuste aqui)
    public function cargo()
    {
        return $this->belongsTo(\App\Models\ConcursosVagasCargo::class, 'cargo_id');
    }
}
