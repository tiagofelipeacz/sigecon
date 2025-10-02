<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;

class Concurso extends Model
{
    use HasFactory;

    protected $table = 'concursos';

    protected $fillable = [
        // FKs possíveis (deixe todas para não bloquear mass assignment)
        'cliente_id', 'client_id', 'id_cliente', 'clients_id',

        // metadados básicos
        'tipo',
        'situacao',
        'titulo',
        'legenda_interna',
        'legenda',

        // datas e flags
        'edital_data',
        'inscricoes_online',
        'sequence_inscricao',
        'inscricoes_inicio',
        'inscricoes_fim',
        'ativo',
        'oculto',        // se existir
        'ocultar_site',  // alias usado por alguns schemas
        'arquivado',
        'testes',
        'destacar',
        'analise_documentos',

        // blobs JSON
        'configs',
        'extras',
    ];

    protected $casts = [
        'inscricoes_online'  => 'boolean',
        'ativo'              => 'boolean',
        'oculto'             => 'boolean',
        'ocultar_site'       => 'boolean',
        'arquivado'          => 'boolean',
        'testes'             => 'boolean',
        'destacar'           => 'boolean',
        'analise_documentos' => 'boolean',

        'edital_data'        => 'date',
        'inscricoes_inicio'  => 'datetime',
        'inscricoes_fim'     => 'datetime',

        'configs'            => 'array',
        'extras'             => 'array',
    ];

    // -------------------------------------------------
    // Relacionamentos cobrindo variações de FK
    // -------------------------------------------------
    public function client()       { return $this->belongsTo(Client::class, 'cliente_id'); }
    public function clientLegacy() { return $this->belongsTo(Client::class, 'client_id'); }
    public function clientAlt()    { return $this->belongsTo(Client::class, 'id_cliente'); }
    public function clientPlural() { return $this->belongsTo(Client::class, 'clients_id'); }

    // Evita N+1 e ajuda a exibir cliente sempre que possível
    protected $with = ['client', 'clientLegacy', 'clientAlt', 'clientPlural'];

    // -------------------------------------------------
    // Acessores úteis
    // -------------------------------------------------

    /** Retorna o valor da FK do cliente (ordem de preferência). */
    public function getFkClienteIdAttribute(): ?int
    {
        $attrs = $this->getAttributes();
        foreach (['cliente_id','client_id','id_cliente','clients_id'] as $k) {
            if (array_key_exists($k, $attrs) && !is_null($attrs[$k])) {
                return (int) $attrs[$k];
            }
        }
        return null;
    }

    /** Nome do cliente com fallback entre colunas comuns. */
    public function getClienteNomeAttribute(): ?string
    {
        $c = $this->client ?? $this->clientLegacy ?? $this->clientAlt ?? $this->clientPlural;

        if (!$c && ($id = $this->fk_cliente_id)) {
            $c = Client::find($id);
        }
        if (!$c) return null;

        $cands = [
            $c->cliente         ?? null,
            $c->razao_social    ?? null,
            $c->nome_fantasia   ?? null,
            $c->fantasia        ?? null,
            $c->nome            ?? null,
            $c->name            ?? null,
            $c->titulo          ?? null,
            $c->empresa         ?? null,
            $c->descricao       ?? null,
        ];

        foreach ($cands as $v) {
            if (is_string($v)) {
                $v = trim($v);
                if ($v !== '') return $v;
            }
        }
        return null;
    }
    
}


