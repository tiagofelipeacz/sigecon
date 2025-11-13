{{-- resources/views/site/candidato/inscricoes/show.blade.php --}}
@extends('layouts.site')

@section('title', 'Detalhes da inscrição')

@php
    // Cores vindas da config do site (fallback se não tiver)
    $primary = $site['primary_color'] ?? $site['primary'] ?? '#0f172a';
    $accent  = $site['accent_color']  ?? $site['accent']  ?? '#111827';

    /** @var \App\Models\Candidato|null $candidato */
    $candidato = auth('candidato')->user();

    // Concurso
    $concursoTitulo = $concurso->titulo
        ?? $concurso->nome
        ?? ('Concurso #'.($insc->concurso_id ?? $insc->edital_id ?? '—'));

    $concursoCodigo = $concurso->codigo ?? null;

    // Cargo / Localidade
    $cargoNome       = $cargo->nome ?? '—';
    $localidadeNome  = $localidade->nome ?? null;

    // Datas
    $dataInsc = $insc->created_at
        ? ($insc->created_at instanceof \Illuminate\Support\Carbon
            ? $insc->created_at->format('d/m/Y H:i')
            : \Illuminate\Support\Carbon::parse($insc->created_at)->format('d/m/Y H:i'))
        : '—';

    // Número da inscrição (campo numero da tabela inscricoes)
    $numeroInscricao = $insc->numero ?? $insc->id;

    // Status / badge
    $status = strtolower((string) $insc->status);
    if (in_array($status, ['confirmada', 'confirmado', 'inscrito'])) {
        $statusLabel = strtoupper($insc->status);
        $statusClass = 'c-insc-badge-ok';
    } elseif (in_array($status, ['cancelada', 'cancelado'])) {
        $statusLabel = strtoupper($insc->status);
        $statusClass = 'c-insc-badge-cancelado';
    } else {
        $statusLabel = strtoupper($insc->status ?? 'indefinido');
        $statusClass = 'c-insc-badge-outro';
    }

    // ====== DADOS DINÂMICOS (sempre atualizados do perfil, com fallback para o que foi salvo na inscrição) ======
    $nomeCandidato = trim((string)($candidato->nome ?? ''));
    if ($nomeCandidato === '') $nomeCandidato = (string)($insc->nome_candidato ?? $insc->nome_inscricao ?? '—');

    $cpfCandidato = trim((string)($candidato->cpf ?? ''));
    if ($cpfCandidato === '') $cpfCandidato = (string)($insc->cpf ?? '—');

    // ====== MODALIDADE DINÂMICA / BONITA ======
    // 1) Se o controller já passar $modalidadeLabel, usamos.
    // 2) Senão, normalizamos localmente a partir de $insc->modalidade.
    $modalidadeLabel = $modalidadeLabel ?? null;

    if (!$modalidadeLabel) {
        $raw = trim((string)($insc->modalidade ?? ''));
        $norm = mb_strtolower($raw);

        // Mapeamentos comuns (case-insensitive)
        $map = [
            'ampla'                                => 'Ampla concorrência',
            'ampla concorrencia'                   => 'Ampla concorrência',
            'ampla concorrência'                   => 'Ampla concorrência',
            'pp'                                   => 'PP - Pessoas Pretas ou Pardas',
            'pessoas pretas ou pardas'             => 'PP - Pessoas Pretas ou Pardas',
            'negros'                               => 'PP - Pessoas Pretas ou Pardas',
            'pcd'                                  => 'PCD - Pessoa com Deficiência',
            'pessoa com deficiência'               => 'PCD - Pessoa com Deficiência',
            'pessoa com deficiencia'               => 'PCD - Pessoa com Deficiência',
            'pcd - pessoa com deficiência'         => 'PCD - Pessoa com Deficiência',
            'pcd - pessoa com deficiencia'         => 'PCD - Pessoa com Deficiência',
        ];

        $modalidadeLabel = $raw; // default: o que veio do banco
        foreach ($map as $k => $bonito) {
            if ($norm === $k) {
                $modalidadeLabel = $bonito;
                break;
            }
            // também casa "contém" para entradas como "COTA PCD", "Modalidade: pcd", etc.
            if (str_contains($norm, $k)) {
                $modalidadeLabel = $bonito;
                break;
            }
        }

        if ($modalidadeLabel === '' || $modalidadeLabel === null) {
            $modalidadeLabel = 'Ampla concorrência';
        }
    }
@endphp

@section('content')
<style>
    :root{
        --c-primary: {{ $primary }};
        --c-accent:  {{ $accent }};
        --c-muted:   #6b7280;
        --c-border:  #e5e7eb;
        --c-bg:      #f3f4f6;
    }

    .c-insc-show-page{
        min-height: calc(100vh - 140px);
        padding: 32px 16px 40px;
        background: radial-gradient(circle at top left, #ffffff 0, #eef2ff 35%, #f9fafb 100%);
        display:flex;
        align-items:flex-start;
        justify-content:center;
        font-size:15px;
    }
    .c-insc-show-container{
        width:100%;
        max-width: 980px;
        margin:0 auto;
        display:flex;
        flex-direction:column;
        gap:18px;
    }

    .c-insc-show-header{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:16px;
    }
    .c-insc-show-title-wrap{
        max-width:70%;
    }
    .c-insc-kicker{
        text-transform:uppercase;
        font-size:11px;
        letter-spacing:.12em;
        font-weight:700;
        color:var(--c-accent);
        margin-bottom:6px;
    }
    .c-insc-title{
        font-size:22px;
        line-height:1.25;
        margin:0 0 6px;
        letter-spacing:-.03em;
        color:#0f172a;
    }
    .c-insc-sub{
        font-size:13px;
        color:var(--c-muted);
        max-width:520px;
        margin:0;
    }

    .c-insc-actions{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
    }
    .c-btn{
        border-radius:999px;
        padding:8px 14px;
        font-size:13px;
        font-weight:600;
        border:1px solid var(--c-border);
        background:#fff;
        color:#111827;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        gap:6px;
        cursor:pointer;
        transition:background-color .15s, border-color .15s, transform .05s, box-shadow .15s;
        box-shadow:0 4px 10px rgba(15,23,42,0.06);
    }
    .c-btn:hover{
        background:#f9fafb;
        border-color:var(--c-primary);
        transform:translateY(-1px);
        box-shadow:0 8px 18px rgba(15,23,42,0.10);
    }
    .c-btn-primary{
        background:var(--c-primary);
        border-color:var(--c-primary);
        color:#fff;
    }
    .c-btn-primary:hover{
        background:var(--c-primary);
        border-color:var(--c-primary);
        color:#fff;
        filter:brightness(1.05);
    }

    .c-card{
        background:#ffffff;
        border-radius:18px;
        border:1px solid var(--c-border);
        box-shadow:0 12px 30px rgba(15,23,42,0.06);
        padding:16px 18px 16px;
    }

    .c-card-title{
        font-size:15px;
        font-weight:700;
        margin:0 0 10px;
        letter-spacing:-.02em;
        color:#111827;
    }
    .c-card-sub{
        font-size:13px;
        color:var(--c-muted);
        margin:0 0 14px;
    }

    .c-insc-summary{
        display:grid;
        grid-template-columns:1.1fr 1fr;
        gap:18px;
    }
    .c-insc-main-info-heading{
        font-size:13px;
        text-transform:uppercase;
        letter-spacing:.14em;
        color:var(--c-muted);
        margin-bottom:4px;
    }
    .c-insc-numero{
        font-size:22px;
        font-weight:800;
        letter-spacing:.08em;
        margin-bottom:4px;
    }
    .c-insc-status-badge{
        display:inline-flex;
        align-items:center;
        padding:4px 10px;
        border-radius:999px;
        font-size:11px;
        font-weight:600;
        text-transform:uppercase;
        letter-spacing:.08em;
    }
    .c-insc-badge-ok{
        background:#ecfdf3;
        color:#166534;
        border:1px solid #bbf7d0;
    }
    .c-insc-badge-cancelado{
        background:#fef2f2;
        color:#b91c1c;
        border:1px solid #fecaca;
    }
    .c-insc-badge-outro{
        background:#eff6ff;
        color:#1d4ed8;
        border:1px solid #bfdbfe;
    }

    .c-insc-meta{
        font-size:13px;
        color:#374151;
        margin-top:10px;
    }
    .c-insc-meta strong{
        font-weight:600;
    }

    .c-def-list{
        margin:0;
        padding:0;
        list-style:none;
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px 24px;
        font-size:13px;
    }
    .c-def-item-label{
        font-size:11px;
        text-transform:uppercase;
        letter-spacing:.12em;
        color:var(--c-muted);
        margin-bottom:2px;
    }
    .c-def-item-value{
        font-size:13px;
        color:#111827;
        font-weight:500;
        word-break:break-word;
    }

    .c-etapas-list{
        margin:0;
        padding-left:18px;
        font-size:13px;
        color:#374151;
    }
    .c-etapas-list li + li{
        margin-top:4px;
    }

    .c-doc-alert{
        border-radius:14px;
        padding:10px 12px;
        background:#fffbeb;
        border:1px solid #facc15;
        font-size:13px;
        color:#92400e;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
    }

    @media (max-width: 840px){
        .c-insc-show-page{ padding-top:20px; }
        .c-insc-show-header{ flex-direction:column; align-items:flex-start; }
        .c-insc-show-title-wrap{ max-width:100%; }
        .c-insc-summary{ grid-template-columns:1fr; }
        .c-def-list{ grid-template-columns:1fr; }
    }
</style>

<div class="c-insc-show-page">
    <div class="c-insc-show-container">

        {{-- Cabeçalho --}}
        <div class="c-insc-show-header">
            <div class="c-insc-show-title-wrap">
                <div class="c-insc-kicker">Área do Candidato</div>
                <h1 class="c-insc-title">Detalhes da inscrição</h1>
                <p class="c-insc-sub">
                    Nesta tela você acompanha as informações completas da sua inscrição,
                    assim como o status, dados do concurso e orientações para as próximas etapas.
                </p>
            </div>

            <div class="c-insc-actions">
                <a href="{{ route('candidato.inscricoes.index') }}" class="c-btn">
                    ← Voltar para minhas inscrições
                </a>

                <a href="{{ route('candidato.inscricoes.comprovante', $insc->id) }}" class="c-btn c-btn-primary">
                    Comprovante em PDF
                </a>
            </div>
        </div>

        {{-- CARD PRINCIPAL (resumo completo) --}}
        <div class="c-card">
            <div class="c-insc-summary">
                <div>
                    <div class="c-insc-main-info-heading">Nº DE INSCRIÇÃO</div>
                    <div class="c-insc-numero">{{ $numeroInscricao }}</div>

                    <div class="c-insc-status" style="margin-top:6px;">
                        <span class="c-insc-status-badge {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div class="c-insc-meta">
                        <div>
                            <strong>Concurso:</strong> {{ $concursoTitulo }}
                        </div>
                        <div>
                            @php
                                $concursoIdMostrar = $insc->concurso_id ?? $insc->edital_id ?? null;
                            @endphp
                            @if($concursoCodigo)
                                <strong>Código:</strong> {{ $concursoCodigo }} ·
                            @endif
                            @if($concursoIdMostrar)
                                <strong>ID:</strong> {{ $concursoIdMostrar }}
                            @endif
                        </div>
                        <div>
                            <strong>Cargo:</strong> {{ $cargoNome }}
                            @if($localidadeNome)
                                · <strong>Localidade:</strong> {{ $localidadeNome }}
                            @endif
                        </div>
                        <div>
                            <strong>Data da inscrição:</strong> {{ $dataInsc }}
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="c-card-title" style="margin-bottom:6px;">Dados do candidato</h2>
                    <ul class="c-def-list">
                        <li>
                            <div class="c-def-item-label">Nome</div>
                            <div class="c-def-item-value">{{ $nomeCandidato }}</div>
                        </li>
                        <li>
                            <div class="c-def-item-label">CPF</div>
                            <div class="c-def-item-value">{{ $cpfCandidato }}</div>
                        </li>
                        <li>
                            <div class="c-def-item-label">Modalidade</div>
                            <div class="c-def-item-value">{{ $modalidadeLabel }}</div>
                        </li>
                        <li>
                            <div class="c-def-item-label">Situação</div>
                            <div class="c-def-item-value">{{ ucfirst($status) }}</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Etapas e acompanhamento --}}
        <div class="c-card">
            <h2 class="c-card-title">Etapas e acompanhamento</h2>
            <p class="c-card-sub">
                As informações abaixo serão atualizadas pela banca organizadora ao longo do concurso.
                Sempre que novos dados forem publicados, você poderá consultá-los aqui.
            </p>

            <ul class="c-etapas-list">
                <li><strong>Local de prova:</strong> será divulgado quando houver publicação do cartão de confirmação de inscrição.</li>
                <li><strong>Notas e resultados:</strong> após cada etapa, as notas e resultados oficiais serão disponibilizados nesta área.</li>
                <li><strong>Espelho do cartão-resposta / provas:</strong> se o concurso disponibilizar, os arquivos ficarão acessíveis para download.</li>
                <li><strong>Recursos:</strong> caso o edital permita recursos, links e orientações também aparecerão aqui.</li>
            </ul>
        </div>

        {{-- Documentos exigidos (se houver flag no concurso) --}}
        @if(!empty($concurso->exige_documentos))
            <div class="c-doc-alert">
                <div>
                    Este concurso exige o envio de documentos complementares
                    pelo candidato. Verifique os documentos solicitados no edital
                    e envie os arquivos dentro do prazo.
                </div>
                <div>
                    <a href="{{ route('candidato.documentos.index') }}" class="c-btn c-btn-primary">
                        Enviar / consultar documentos
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
