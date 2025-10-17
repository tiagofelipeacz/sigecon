<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inscricao extends Model
{
    use SoftDeletes;

    protected $table = 'inscricoes';

    protected $fillable = [
        // FKs principais (mantém compatibilidade com seu schema atual)
        'edital_id',        // FK para concursos.id (no seu schema atual)
        'cargo_id',
        'user_id',
        'candidato_id',     // vínculo forte com o cadastro do candidato

        // Identificação/controle da inscrição
        'numero',           // número da inscrição (persistido)
        'modalidade',
        'status',

        // Snapshot de dados do momento da inscrição (usados no controller/telas)
        'cpf',
        'nome_inscricao',
        'nome_candidato',
        'nascimento',

        // Localidade/itens (quando existir no schema)
        'item_id',
        'localidade_id',

        // Valor congelado (quando houver coluna correspondente)
        'valor_inscricao',
    ];

    public $timestamps = true;

    protected $casts = [
        'nascimento'      => 'date',
        'valor_inscricao' => 'decimal:2',
        'deleted_at'      => 'datetime',
    ];

    // --- Relações ---

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function candidato()
    {
        return $this->belongsTo(\App\Models\Candidato::class, 'candidato_id');
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

    /**
     * Fallback útil: número final caso a coluna 'numero' não exista/esteja vazia.
     * Uso: $inscricao->numero_final
     */
    public function getNumeroFinalAttribute(): ?string
    {
        if (!empty($this->numero)) {
            return (string) $this->numero;
        }

        // Fallback: calcula como você fazia na view (sequence_inscricao + id)
        $conc = $this->relationLoaded('concurso') ? $this->concurso : $this->concurso()->first();
        if ($conc && isset($conc->sequence_inscricao) && isset($this->id)) {
            return (string) ((int) $conc->sequence_inscricao + (int) $this->id);
        }

        return null;
    }
}
