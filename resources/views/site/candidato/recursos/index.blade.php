@extends('layouts.site')

@section('title', 'Recursos - Área do Candidato')

@php
    $primary = $site['primary_color'] ?? $site['primary'] ?? '#0f172a';
    $accent  = $site['accent_color']  ?? $site['accent']  ?? '#111827';
    $brand   = $site['brand'] ?? 'GestaoConcursos';

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

    .c-page{
        min-height: calc(100vh - 140px);
        padding: 32px 16px 40px;
        background: radial-gradient(circle at top left, #ffffff 0, #eef2ff 35%, #f9fafb 100%);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:15px;
    }
    .c-container{
        width:100%;
        max-width: 980px;
        margin:0 auto;
        background:#ffffff;
        border-radius:18px;
        border:1px solid var(--c-border);
        box-shadow:0 18px 40px rgba(15,23,42,0.08);
        padding:18px 20px 20px;
    }
    .c-title{
        font-size:18px;
        font-weight:800;
        margin:0 0 4px;
        letter-spacing:-.02em;
        color:#0f172a;
    }
    .c-sub{
        font-size:13px;
        color:var(--c-muted);
        margin:0 0 14px;
    }
</style>

<div class="c-page">
    <div class="c-container">
        <h1 class="c-title">Recursos</h1>
        <p class="c-sub">
            Aqui você poderá interpor recursos e acompanhar o andamento
            para cada fase dos concursos em que estiver inscrito.
        </p>

        {{-- Depois você coloca a listagem de concursos/fases e os formulários de recurso aqui --}}
        <p style="font-size:13px; color:var(--c-muted);">
            (Tela em construção) – contate a banca caso tenha dúvidas sobre prazos e procedimentos de recurso.
        </p>
    </div>
</div>
@endsection
