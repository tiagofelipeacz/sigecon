<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidatoInscricao extends Model
{
    use SoftDeletes;

    // TABELA ANTIGA, A CORRETA PARA AS INSCRIÇÕES
    protected $table = 'inscricoes';

    protected $fillable = [
        'edital_id',
        'cargo_id',
        'item_id',
        'user_id',
        'candidato_id',
        'cpf',
        'documento',
        'cidade',
        'nome_inscricao',
        'nome_candidato',
        'nascimento',
        'modalidade',
        'status',
        'numero',
        'pessoa_key',
        'local_key',
        'ativo',

        // NOVOS CAMPOS RELACIONADOS À INSCRIÇÃO
        'condicoes_especiais',
        'solicitou_isencao',
        'forma_pagamento',
        'pagamento_status',
    ];

    protected $casts = [
        'nascimento' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'solicitou_isencao' => 'boolean',
    ];

    /**
     * Alias para facilitar: tratar edital_id como se fosse concurso_id
     */
    public function getConcursoIdAttribute()
    {
        return $this->edital_id;
    }

    /**
     * Alias: tratar numero como se fosse "protocolo" / nº de inscrição
     */
    public function getProtocoloAttribute()
    {
        return $this->numero;
    }
}
