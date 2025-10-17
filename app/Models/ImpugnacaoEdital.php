<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImpugnacaoEdital extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'impugnacoes_edital';

    protected $fillable = [
        'concurso_id',
        'nome',
        'email',
        'cpf',
        'telefone',
        'endereco',
        'texto',
        'anexo_path',
        'situacao',              // pendente|deferido|indeferido
        'resposta_texto',
        'resposta_html',
        'respondido_em',         // se sua tabela usa responded_at, o accessor abaixo cobre
        'responded_at',
    ];

    protected $casts = [
        'respondido_em' => 'datetime',
        'responded_at'  => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime', // <- Soft Delete
    ];

    // compat: usar sempre ->respondido_em na aplicação
    public function getRespondidoEmAttribute()
    {
        return $this->attributes['respondido_em'] ?? $this->attributes['responded_at'] ?? null;
    }

    public function concurso()
    {
        return $this->belongsTo(Concurso::class, 'concurso_id');
    }
}
