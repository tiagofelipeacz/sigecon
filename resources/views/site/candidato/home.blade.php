{{-- resources/views/site/candidato/home.blade.php --}}
@extends('layouts.site')

@section('title', 'Área do Candidato')

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

    .c-home-page{
        min-height: calc(100vh - 140px);
        padding: 32px 16px 40px;
        background: radial-gradient(circle at top left, #ffffff 0, #eef2ff 35%, #f9fafb 100%);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:15px;
    }
    .c-home-container{
        width:100%;
        max-width: 980px;
        margin:0 auto;
        display:grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
        gap:24px;
        align-items:stretch;
    }

    .c-home-hero{
        padding-right:10px;
    }
    .c-home-kicker{
        text-transform:uppercase;
        font-size:11px;
        letter-spacing:.12em;
        font-weight:700;
        color:var(--c-accent);
        margin-bottom:6px;
    }
    .c-home-title{
        font-size:26px;
        line-height:1.25;
        margin:0 0 8px;
        letter-spacing:-.03em;
        color:#0f172a;
    }
    .c-home-sub{
        font-size:14px;
        color:var(--c-muted);
        max-width:420px;
        margin-bottom:14px;
    }
    .c-home-user{
        margin-top:8px;
        font-size:14px;
        color:#111827;
    }
    .c-home-user strong{
        font-weight:700;
    }

    .c-home-card{
        background:#ffffff;
        border-radius:18px;
        border:1px solid var(--c-border);
        box-shadow:0 18px 40px rgba(15,23,42,0.08);
        padding:18px 18px 20px;
    }
    .c-home-card-title{
        font-size:16px;
        font-weight:800;
        margin:0 0 6px;
        letter-spacing:-.02em;
        color:#0f172a;
    }
    .c-home-card-sub{
        font-size:13px;
        color:var(--c-muted);
        margin:0 0 10px;
    }

    .c-home-status{
        font-size:13px;
        padding:8px 10px;
        border-radius:10px;
        background:#ecfdf3;
        color:#166534;
        border:1px solid #bbf7d0;
        margin-bottom:10px;
    }

    .c-home-links{
        display:grid;
        grid-template-columns:1fr;
        gap:8px;
        margin-top:10px;
    }
    .c-home-link{
        display:flex;
        align-items:center;
        justify-content:space-between;
        padding:9px 11px;
        border-radius:12px;
        border:1px solid var(--c-border);
        text-decoration:none;
        color:#111827;
        font-size:14px;
        background:#f9fafb;
        transition:background-color .15s, box-shadow .15s, transform .05s, border-color .15s;
    }
    .c-home-link:hover{
        background:#ffffff;
        border-color:var(--c-primary);
        box-shadow:0 10px 22px rgba(15,23,42,0.12);
        transform:translateY(-1px);
    }
    .c-home-link-label{
        font-weight:600;
    }
    .c-home-link-desc{
        font-size:12px;
        color:var(--c-muted);
        margin-top:2px;
    }
    .c-home-link-arrow{
        font-size:16px;
    }

    .c-home-footer{
        font-size:11px;
        color:var(--c-muted);
        margin-top:12px;
    }

    @media (max-width: 840px){
        .c-home-page{
            padding-top:20px;
        }
        .c-home-container{
            grid-template-columns:1fr;
            gap:18px;
        }
        .c-home-hero{
            padding-right:0;
        }
    }
</style>

<div class="c-home-page">
    <div class="c-home-container">
        <div class="c-home-hero">
            <div class="c-home-kicker">Área do Candidato</div>
            <h1 class="c-home-title">
                Olá, {{ $candidato?->nome ?? 'candidato(a)' }}.
            </h1>
            <p class="c-home-sub">
                Aqui você acompanha suas inscrições, dados cadastrais, documentos e recursos.
            </p>

            <div class="c-home-user">
                <div><strong>CPF:</strong> {{ $candidato?->cpf }}</div>
                <div><strong>E-mail:</strong> {{ $candidato?->email }}</div>
            </div>
        </div>

        <div class="c-home-card">
            <h2 class="c-home-card-title">O que você deseja fazer?</h2>
            <p class="c-home-card-sub">
                Escolha uma das opções abaixo para continuar.
            </p>

            @if(session('status'))
                <div class="c-home-status">
                    {{ session('status') }}
                </div>
            @endif

            <div class="c-home-links">
                {{-- Minhas inscrições --}}
                <a href="{{ route('candidato.inscricoes.index') }}" class="c-home-link">
                    <div>
                        <div class="c-home-link-label">Minhas inscrições</div>
                        <div class="c-home-link-desc">
                            Consultar inscrições realizadas e emitir comprovantes.
                        </div>
                    </div>
                    <span class="c-home-link-arrow">›</span>
                </a>

                {{-- Meus dados cadastrais --}}
                <a href="{{ route('candidato.perfil.edit') }}" class="c-home-link">
                    <div>
                        <div class="c-home-link-label">Meus dados cadastrais</div>
                        <div class="c-home-link-desc">
                            Atualizar endereço, contatos e informações pessoais.
                        </div>
                    </div>
                    <span class="c-home-link-arrow">›</span>
                </a>


                {{-- Recursos --}}
                <a href="{{ route('candidato.recursos.index') }}" class="c-home-link">
                    <div>
                        <div class="c-home-link-label">Recursos</div>
                        <div class="c-home-link-desc">
                            Interpor recursos e acompanhar o andamento por fase do concurso.
                        </div>
                    </div>
                    <span class="c-home-link-arrow">›</span>
                </a>
            </div>

            <div class="c-home-footer">
                Em caso de dúvidas sobre o acesso ou sobre o concurso,
                entre em contato com a banca organizadora.
            </div>
        </div>
    </div>
</div>
@endsection
