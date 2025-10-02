<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IsencaoTipo extends Model
{
    use HasFactory;

    protected $table = 'isencao_tipos';

    protected $fillable = [
        'cliente_id',
        'nome',
        'usa_sistac',
        'usa_redome',
        'ativo',
        'exige_anexo',
        'campo_extra',
        'orientacoes_html',
    ];

    protected $casts = [
        'usa_sistac'  => 'boolean',
        'usa_redome'  => 'boolean',
        'ativo'       => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'cliente_id');
    }
}
