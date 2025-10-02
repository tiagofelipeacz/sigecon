<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vaga extends Model
{
    protected $table = 'vagas';

    protected $fillable = [
        'concurso_id','cargo_id','localidade_id',
        'quantidade','nivel','salario','taxa','jornada',
        // compat:
        'vagas','qtd',
    ];

    public function concurso(){ return $this->belongsTo(Concurso::class); }
    public function cargo(){ return $this->belongsTo(Cargo::class); }
    public function localidade(){ return $this->belongsTo(Localidade::class); }
}
