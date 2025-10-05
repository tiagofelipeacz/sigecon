<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ImpugnacaoEdital extends Model
{
    /**
     * Tabela dinâmica (adapta a instalações diferentes).
     * Tenta, na ordem: impugnacoes, impugnacoes_edital, concurso_impugnacoes.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(self::resolveTableName());
    }

    public static function resolveTableName(): string
    {
        if (Schema::hasTable('impugnacoes')) return 'impugnacoes';
        if (Schema::hasTable('impugnacoes_edital')) return 'impugnacoes_edital';
        if (Schema::hasTable('concurso_impugnacoes')) return 'concurso_impugnacoes';
        // fallback seguro
        return 'impugnacoes';
    }

    protected $guarded = [];

    protected $casts = [
        'respondida' => 'boolean',
        'publicada'  => 'boolean',
        'ativo'      => 'boolean',
    ];

    public function concurso()
    {
        // Só funciona se existir a coluna; sem problemas se não existir
        return $this->belongsTo(Concurso::class);
    }
}
