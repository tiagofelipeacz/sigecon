<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImpugnacaoEdital extends Model
{
    use HasFactory;

    protected $table = 'impugnacoes_edital';

    protected $fillable = [
        'concurso_id',
        'nome',
        'email',
        'cpf',
        'telefone',
        'telefone_alt',
        'endereco',
        'texto',
        'anexo_path',
        'situacao',          // pendente|deferido|indeferido
        'resposta_texto',
        'resposta_html',
        'resposta',
        'respondido_em',
        'responded_at',
        'data_resposta',
    ];

    protected $casts = [
        'respondido_em' => 'datetime',
        'responded_at'  => 'datetime',
        'data_resposta' => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    /**
     * Helper para obter o nome da tabela (útil se quiser usar em Schema::hasColumn etc.)
     */
    public static function tableName(): string
    {
        return (new static)->getTable();
    }

    /**
     * Accessor de compatibilidade: usar sempre ->respondido_em
     */
    public function getRespondidoEmAttribute($value)
    {
        if ($value) {
            return $value;
        }
        // se a coluna responded_at tiver valor, retorna
        return $this->attributes['responded_at'] ?? null;
    }

    public function concurso()
    {
        return $this->belongsTo(Concurso::class, 'concurso_id');
    }
}
