<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoIsencao extends Model
{
    protected $table = 'pedidos_isencao';

    protected $fillable = [
        'concurso_id',
        'tipo_isencao_id',
        'status',
        'resposta_texto',
        'enviado_em',
        'analisado_em',
        'candidato_nome',
        'candidato_cpf',
        'candidato_email',
        'candidato_telefone',
        'anexos_json',
        'campos_extras_json',
    ];

    protected $casts = [
        'enviado_em'        => 'datetime',
        'analisado_em'      => 'datetime',
    ];

    /** Concurso ao qual o pedido pertence */
    public function concurso(): BelongsTo
    {
        return $this->belongsTo(Concurso::class, 'concurso_id');
    }

    /** Tipo de isenção */
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoIsencao::class, 'tipo_isencao_id');
    }
}
