{{-- resources/views/site/candidato/auth/verify.blade.php --}}
@extends('layouts.site')

@section('title', 'Confirmação de E-mail')

@php
    $primary = $site['primary_color'] ?? $site['primary'] ?? '#0f172a';
    $accent  = $site['accent_color']  ?? $site['accent']  ?? '#111827';
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

    .auth-page{
        min-height: calc(100vh - 140px);
        padding: 32px 16px 40px;
        background: radial-gradient(circle at top left, #ffffff 0, #eef2ff 35%, #f9fafb 100%);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:15px;
    }
    .auth-container{
        max-width: 640px;
        width: 100%;
        margin: 0 auto;
    }

    .auth-card{
        background:#ffffff;
        border-radius:18px;
        border:1px solid var(--c-border);
        box-shadow:0 18px 40px rgba(15,23,42,0.08);
        padding:22px 22px 24px;
    }
    .auth-title{
        font-size:20px;
        font-weight:800;
        margin:0 0 4px;
        letter-spacing:-.02em;
        color:#0f172a;
    }
    .auth-sub{
        font-size:14px;
        color:var(--c-muted);
        margin:0 0 12px;
    }

    .auth-status{
        font-size:13px;
        padding:8px 10px;
        border-radius:10px;
        background:#ecfdf3;
        color:#166534;
        border:1px solid #bbf7d0;
        margin-bottom:10px;
    }
    .auth-error{
        font-size:13px;
        padding:8px 10px;
        border-radius:10px;
        background:#fef2f2;
        color:#b91c1c;
        border:1px solid #fecaca;
        margin-bottom:10px;
    }

    .auth-btn-row{
        margin-top:16px;
        display:flex;
        justify-content:flex-end;
        gap:10px;
        flex-wrap:wrap;
    }
    .auth-btn{
        border-radius:999px;
        padding:8px 18px;
        font-size:14px;
        font-weight:700;
        border:1px solid transparent;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:6px;
        transition: filter .15s, transform .05s, box-shadow .15s, background-color .15s, color .15s, border-color .15s;
    }
    .auth-btn-primary{
        background: var(--c-primary);
        color:#fff;
        box-shadow:0 10px 25px rgba(15,23,42,0.25);
    }
    .auth-btn-primary:hover{
        filter:brightness(1.05);
        transform:translateY(-1px);
        box-shadow:0 14px 28px rgba(15,23,42,0.27);
    }
    .auth-btn-secondary{
        background:#f9fafb;
        color:#374151;
        border-color:var(--c-border);
        text-decoration:none;
    }
    .auth-btn-secondary:hover{
        background:#e5e7eb;
    }

    .auth-note{
        font-size:12px;
        color:var(--c-muted);
        margin-top:8px;
    }
</style>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Confirme seu e-mail</h1>
            <p class="auth-sub">
                Enviamos um link de verificação para o e-mail informado no cadastro.
                Clique no link para confirmar seu endereço de e-mail.
            </p>

            @if (session('status'))
                <div class="auth-status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="auth-error">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('candidato.verification.send') }}">
                @csrf

                <div class="auth-note">
                    Não recebeu o e-mail? Clique em <strong>Reenviar e-mail de verificação</strong>.
                </div>

                <div class="auth-btn-row">
                    <a href="{{ route('candidato.home') }}" class="auth-btn auth-btn-secondary">
                        Voltar para a área do candidato
                    </a>
                    <button type="submit" class="auth-btn auth-btn-primary">
                        Reenviar e-mail de verificação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
