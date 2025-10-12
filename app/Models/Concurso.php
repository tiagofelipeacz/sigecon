<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class Concurso extends Model
{
    use HasFactory, SoftDeletes;

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

        // Soft delete
        'deleted_at'         => 'datetime',
    ];

    // Eager-load para evitar N+1 (mantendo sua intenção original)
    protected $with = ['client', 'clientLegacy', 'clientAlt', 'clientPlural', 'cliente'];

    // Acessores que expomos por padrão
    protected $appends = [
        'situacao_operacional',
        'situacao_operacional_label',
        'situacao_badge_class',
        'url_show_public',
        'url_edit',
        'fk_cliente_id',
        'cliente_nome',
    ];

    // -------------------------------------------------
    // Relacionamentos cobrindo variações de FK
    // -------------------------------------------------
    public function client()
    {
        // normalmente apontaria para client_id, mas você já usa client() => cliente_id
        // então mantemos sua assinatura original e adicionamos withDefault
        return $this->belongsTo(Client::class, 'cliente_id')->withDefault([
            'nome' => null,
            'name' => null,
        ]);
    }

    public function clientLegacy()
    {
        return $this->belongsTo(Client::class, 'client_id')->withDefault([
            'nome' => null,
            'name' => null,
        ]);
    }

    public function clientAlt()
    {
        return $this->belongsTo(Client::class, 'id_cliente')->withDefault([
            'nome' => null,
            'name' => null,
        ]);
    }

    public function clientPlural()
    {
        return $this->belongsTo(Client::class, 'clients_id')->withDefault([
            'nome' => null,
            'name' => null,
        ]);
    }

    // Alias adicional usado em algumas views (ex.: optional($c->cliente)->nome)
    public function cliente()
    {
        // aponta para a mesma FK mais comum do seu schema (client_id)
        // mas, para manter seu histórico, deixamos cliente() = client_id
        return $this->belongsTo(Client::class, 'client_id')->withDefault([
            'nome' => null,
            'name' => null,
        ]);
    }

    // -------------------------------------------------
    // Acessores/Helpers existentes no seu arquivo
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
        $c = $this->client ?? $this->clientLegacy ?? $this->clientAlt ?? $this->clientPlural ?? $this->cliente;

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

    // -------------------------------------------------
    // Helpers de URL (usados na sua view)
    // -------------------------------------------------

    public function getUrlShowPublicAttribute(): ?string
    {
        if (function_exists('route') && \Route::has('site.concursos.show')) {
            return route('site.concursos.show', $this);
        }
        return url('/concursos/' . $this->id);
    }

    public function getUrlEditAttribute(): ?string
    {
        if (\Route::has('admin.concursos.config')) {
            return route('admin.concursos.config', $this);
        }
        if (\Route::has('admin.concursos.edit')) {
            return route('admin.concursos.edit', $this);
        }
        return url('/admin/concursos/' . $this->id . '/config');
    }

    // -------------------------------------------------
    // Situação operacional (centralizada p/ alinhar com /admin/inicio)
    // -------------------------------------------------

    /** Slug interno da situação operacional */
    public function calcSituacaoOperacional(): string
    {
        // 0) Inativo tem precedência
        if ($this->ativo === false || (int)($this->ativo ?? 0) === 0) {
            return 'inativo';
        }

        // 1) Se houver coluna 'situacao', normaliza
        $sit = trim((string) ($this->situacao ?? ''));
        if ($sit !== '') {
            $map = [
                'em_andamento' => 'em_andamento',
                'andamento'    => 'em_andamento',
                'publicado'    => 'em_andamento',
                'encerrado'    => 'inscricoes_encerradas',
                'aberto'       => 'inscricoes_abertas',
            ];
            return $map[$sit] ?? Str::slug($sit, '_');
        }

        // 2) Datas de inscrição (aceita inscricoes_*; se não houver, tenta JSON configs)
        $tz    = config('app.timezone', 'America/Fortaleza');
        $agora = Carbon::now($tz);

        $ini = $this->inscricoes_inicio;
        $fim = $this->inscricoes_fim;

        if ((!$ini || !$fim) && isset($this->configs)) {
            $cfg   = is_array($this->configs) ? $this->configs
                   : (is_string($this->configs) ? json_decode($this->configs, true) : []);
            $iniS  = Arr::get($cfg, 'inscricoes_inicio');
            $fimS  = Arr::get($cfg, 'inscricoes_fim');
            if (!$ini && $iniS) { try { $ini = Carbon::parse($iniS, $tz); } catch (\Throwable $e) {} }
            if (!$fim && $fimS) { try { $fim = Carbon::parse($fimS, $tz); } catch (\Throwable $e) {} }
        }

        if ($ini && $fim) {
            if ($agora->lt($ini))              return 'inscricoes_aguardando';
            if ($agora->between($ini, $fim))   return 'inscricoes_abertas';
            if ($agora->gt($fim))              return 'inscricoes_encerradas';
        }

        // 3) Como último recurso, usa 'status' bruto se existir
        $statusBruto = trim((string) ($this->status ?? ''));
        if ($statusBruto !== '') {
            $map = [
                'rascunho'  => 'rascunho',
                'publicado' => 'em_andamento',
            ];
            return $map[$statusBruto] ?? Str::slug($statusBruto, '_');
        }

        // 4) Fallback padrão
        return 'em_andamento';
    }

    public function getSituacaoOperacionalAttribute(): string
    {
        return $this->calcSituacaoOperacional();
    }

    public function getSituacaoOperacionalLabelAttribute(): string
    {
        $map = [
            'inscricoes_abertas'    => 'Inscrições abertas',
            'inscricoes_encerradas' => 'Inscrições encerradas',
            'inscricoes_aguardando' => 'Inscrições abrirão em breve',
            'em_andamento'          => 'Em andamento',
            'inativo'               => 'Inativo',
            'rascunho'              => 'Rascunho',
        ];
        $slug = $this->situacao_operacional;
        return $map[$slug] ?? ucfirst(str_replace('_', ' ', $slug));
    }

    public function getSituacaoBadgeClassAttribute(): string
    {
        return match ($this->situacao_operacional) {
            'inscricoes_abertas'    => 'badge-success',   // mapeie na view p/ .pill.ok
            'inscricoes_aguardando' => 'badge-warning',   // .pill.info
            'inscricoes_encerradas' => 'badge-secondary', // .pill.nok
            'inativo'               => 'badge-dark',      // .pill.nok
            'rascunho'              => 'badge-light',     // .pill.nok
            default                 => 'badge-primary',   // em_andamento -> .pill.info
        };
    }
}
