<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoIsencao extends Model
{
    protected $table = 'tipos_isencao';

    protected $fillable = [
        'client_id',
        'sistac',
        'redome',
        'titulo',
        'descricao',
        'nome',
        'ativo',
        'has_extra_field',
        'anexo_policy',
        'flag_sistac',
        'flag_redome',
        'permite_anexo',
        'exigir_cadunico',
        'campo_extra_label',
        'ordem',
        'orientacoes_html',
    ];

    protected $casts = [
        'sistac'            => 'boolean',
        'redome'            => 'boolean',
        'ativo'             => 'boolean',
        'has_extra_field'   => 'boolean',
        'flag_sistac'       => 'boolean',
        'flag_redome'       => 'boolean',
        'permite_anexo'     => 'boolean',
        'exigir_cadunico'   => 'boolean',
        'ordem'             => 'integer',
    ];

    /** Cliente (organizadora) dono do tipo de isenção */
    public function client(): BelongsTo
    {
        // Tabela `tipos_isencao` tem `client_id`
        return $this->belongsTo(Client::class, 'client_id');
    }

    /** Pedidos que usam este tipo */
    public function pedidos(): HasMany
    {
        return $this->hasMany(PedidoIsencao::class, 'tipo_isencao_id');
    }
}
