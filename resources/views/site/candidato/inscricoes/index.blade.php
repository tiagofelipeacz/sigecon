{{-- resources/views/site/candidato/inscricoes/index.blade.php --}}
@extends('layouts.site')

@section('title', 'Minhas inscrições')

@php
    // Cores vindas da config do site (fallback se não tiver)
    $primary = $site['primary_color'] ?? $site['primary'] ?? '#0f172a';
    $accent  = $site['accent_color']  ?? $site['accent']  ?? '#111827';
    $brand   = $site['brand'] ?? 'GestaoConcursos';

    /** @var \App\Models\Candidato|null $candidato */
    $candidato = auth('candidato')->user();
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

    .c-insc-page{
        min-height: calc(100vh - 140px);
        padding: 32px 16px 40px;
        background: radial-gradient(circle at top left, #ffffff 0, #eef2ff 35%, #f9fafb 100%);
        display:flex;
        align-items:flex-start;
        justify-content:center;
        font-size:15px;
    }
    .c-insc-container{
        width:100%;
        max-width: 980px;
        margin:0 auto;
        display:flex;
        flex-direction:column;
        gap:18px;
    }

    .c-insc-header{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:16px;
    }
    .c-insc-title-wrap{
        max-width: 70%;
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

    .c-insc-btn-new{
        border-radius:999px;
        padding:8px 16px;
        font-size:14px;
        font-weight:700;
        border:1px solid transparent;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:6px;
        background: var(--c-primary);
        color:#fff;
        box-shadow:0 10px 25px rgba(15,23,42,0.20);
        text-decoration:none;
        transition: filter .15s, transform .05s, box-shadow .15s;
        white-space:nowrap;
    }
    .c-insc-btn-new:hover{
        filter:brightness(1.05);
        transform:translateY(-1px);
        box-shadow:0 14px 28px rgba(15,23,42,0.24);
    }

    .c-insc-alert{
        font-size:13px;
        padding:8px 10px;
        border-radius:10px;
        margin-bottom:4px;
    }
    .c-insc-alert-success{
        background:#ecfdf3;
        color:#166534;
        border:1px solid #bbf7d0;
    }
    .c-insc-alert-error{
        background:#fef2f2;
        color:#b91c1c;
        border:1px solid #fecaca;
    }

    .c-insc-empty{
        margin-top:12px;
        padding:16px 14px;
        border-radius:14px;
        border:1px dashed var(--c-border);
        background:#f9fafb;
        font-size:14px;
        color:#4b5563;
    }
    .c-insc-empty a{
        color:var(--c-primary);
        font-weight:600;
        text-decoration:none;
    }
    .c-insc-empty a:hover{
        text-decoration:underline;
    }

    .c-insc-block{
        background:#ffffff;
        border-radius:18px;
        border:1px solid var(--c-border);
        box-shadow:0 12px 30px rgba(15,23,42,0.06);
        padding:16px 16px 14px;
        margin-top:8px;
    }
    .c-insc-block-header{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:10px;
        margin-bottom:10px;
    }
    .c-insc-block-title{
        font-size:15px;
        font-weight:800;
        margin:0 0 2px;
        letter-spacing:-.02em;
        color:#111827;
    }
    .c-insc-block-meta{
        font-size:12px;
        color:var(--c-muted);
    }

    .c-insc-table-wrap{
        width:100%;
        overflow-x:auto;
    }
    .c-insc-table{
        width:100%;
        border-collapse:separate;
        border-spacing:0;
        font-size:13px;
        margin-top:6px;
    }
    .c-insc-table thead{
        background:#f9fafb;
    }
    .c-insc-table th,
    .c-insc-table td{
        padding:8px 10px;
        border-bottom:1px solid #e5e7eb;
        text-align:left;
        white-space:nowrap;
    }
    .c-insc-table th:first-child{
        border-top-left-radius:10px;
    }
    .c-insc-table th:last-child{
        border-top-right-radius:10px;
    }
    .c-insc-table tr:last-child td:first-child{
        border-bottom-left-radius:10px;
    }
    .c-insc-table tr:last-child td:last-child{
        border-bottom-right-radius:10px;
    }
    .c-insc-badge-status{
        display:inline-flex;
        align-items:center;
        padding:3px 8px;
        border-radius:999px;
        font-size:11px;
        font-weight:600;
        text-transform:uppercase;
        letter-spacing:.06em;
    }
    .c-insc-badge-inscrito{
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

    .c-insc-btn-mini{
        border-radius:999px;
        padding:5px 10px;
        font-size:12px;
        font-weight:600;
        border:1px solid var(--c-border);
        background:#f9fafb;
        color:#111827;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        gap:4px;
        transition:background-color .15s, border-color .15s, transform .05s, box-shadow .15s;
    }
    .c-insc-btn-mini:hover{
        background:#ffffff;
        border-color:var(--c-primary);
        box-shadow:0 8px 18px rgba(15,23,42,0.12);
        transform:translateY(-1px);
    }

    @media (max-width: 840px){
        .c-insc-page{
            padding-top:20px;
        }
        .c-insc-header{
            flex-direction:column;
            align-items:flex-start;
        }
        .c-insc-title-wrap{
            max-width:100%;
        }
    }
</style>

<div class="c-insc-page">
    <div class="c-insc-container">

        <div class="c-insc-header">
            <div class="c-insc-title-wrap">
                <div class="c-insc-kicker">Área do Candidato</div>
                <h1 class="c-insc-title">Minhas inscrições</h1>
                <p class="c-insc-sub">
                    Aqui você acompanha todas as inscrições realizadas,
                    separadas por concurso, e acessa detalhes como local de prova,
                    notas, recursos e outros documentos.
                </p>
            </div>

                 <a href="{{ route('site.concursos.index') }}" class="btn btn-primary">
                 + Nova inscrição
                </a>

        </div>

        {{-- Mensagens de sucesso/erro --}}
        @if(session('success'))
            <div class="c-insc-alert c-insc-alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->has('general'))
            <div class="c-insc-alert c-insc-alert-error">
                {{ $errors->first('general') }}
            </div>
        @endif

        {{-- Sem inscrições --}}
        @if($inscricoes->isEmpty())
            <div class="c-insc-empty">
                Você ainda não possui inscrições ativas em concursos.
                <br>
                Clique em <a href="{{ route('candidato.inscricoes.create') }}">Nova inscrição</a>
                para se inscrever em um concurso disponível.
            </div>
        @endif

        {{-- Lista agrupada por concurso --}}
        @foreach($inscricoesPorConcurso as $concursoId => $lista)
            @php
                $conc = $concursos->get($concursoId);
                $concursoTitulo = $conc->titulo
                    ?? $conc->nome
                    ?? ('Concurso #'.$concursoId);
                $concursoCodigo = $conc->codigo ?? null;
            @endphp

            <div class="c-insc-block">
                <div class="c-insc-block-header">
                    <div>
                        <div class="c-insc-block-title">
                            {{ $concursoTitulo }}
                        </div>
                        <div class="c-insc-block-meta">
                            @if($concursoCodigo)
                                <strong>Código:</strong> {{ $concursoCodigo }} ·
                            @endif
                            <strong>ID:</strong> {{ $concursoId }}
                        </div>
                    </div>
                </div>

                <div class="c-insc-table-wrap">
                    <table class="c-insc-table">
                        <thead>
                        <tr>
                            <th>Nº inscrição</th>
                            <th>Cargo</th>
                            <th>Data da inscrição</th>
                            <th>Situação</th>
                            <th style="text-align:center;">Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($lista as $insc)
                            @php
                                $cargo = $cargos->get($insc->cargo_id);
                                $cargoNome = $cargo->nome ?? '—';

                                $dataInsc = $insc->created_at
                                    ? $insc->created_at->format('d/m/Y H:i')
                                    : '—';

                                $status = strtolower((string)$insc->status);
                                if ($status === 'inscrito') {
                                    $badgeClass = 'c-insc-badge-inscrito';
                                } elseif (in_array($status, ['cancelado','cancelada'])) {
                                    $badgeClass = 'c-insc-badge-cancelado';
                                } else {
                                    $badgeClass = 'c-insc-badge-outro';
                                }
                            @endphp
                            <tr>
                                <td>{{ $insc->protocolo ?? $insc->id }}</td>
                                <td>{{ $cargoNome }}</td>
                                <td>{{ $dataInsc }}</td>
                                <td>
                                    <span class="c-insc-badge-status {{ $badgeClass }}">
                                        {{ strtoupper($insc->status) }}
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    {{-- Informações da inscrição (detalhes, etapas, local de prova, notas etc.) --}}
                                    <a href="{{ route('candidato.inscricoes.show', $insc->id) }}" class="c-insc-btn-mini">
                                        Informações
                                    </a>

                                    {{-- Comprovante (opcional, se quiser sempre mostrar) --}}
                                    <a href="{{ route('candidato.inscricoes.comprovante', $insc->id) }}" class="c-insc-btn-mini" style="margin-left:4px;">
                                        Comprovante
                                    </a>

                                    {{-- Exemplo de botão para upload de documentos,
                                         só aparece se o concurso exigir (ajuste a condição conforme sua coluna real). --}}
                                    @if(isset($conc->exige_documentos) && $conc->exige_documentos)
                                        <a href="{{ route('candidato.documentos.index') }}" class="c-insc-btn-mini" style="margin-left:4px;">
                                            Enviar documentos
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        @endforeach

    </div>
</div>
@endsection
