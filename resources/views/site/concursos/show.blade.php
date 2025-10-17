{{-- resources/views/site/concursos/show.blade.php --}}
@extends('layouts.site')
@section('title', $concurso->titulo ?? 'Concurso')

@section('content')
<style>
  .hero-detail{
    background: linear-gradient(180deg, var(--site-primary) 0%, #0b1222 100%);
    color:#fff;
  }
  .hero-detail .container{ padding:24px 16px; }
  .hero-img{ width:100%; max-width:1100px; margin:0 auto; padding:0 16px 16px; }
  .hero-img img{ width:100%; border-radius:12px; display:block; }
  .content{ max-width:900px; margin:16px auto 28px; padding:0 16px; }
  .muted{ color:#6b7280; }
</style>

<section class="hero-detail">
  <div class="container">
    <h1 style="margin:0">{{ $concurso->titulo ?? ('Concurso #'.$concurso->id) }}</h1>
    @if(!empty($concurso->cliente_nome))
      <div class="muted" style="margin-top:4px;">{{ $concurso->cliente_nome }}</div>
    @endif
  </div>
  @if($concurso->hero_image)
    <div class="hero-img">
      <img src="{{ $concurso->hero_image }}" alt="Imagem do cliente">
    </div>
  @endif
</section>

<div class="content">
  @if(!empty($concurso->descricao))
    <div>{!! nl2br(e($concurso->descricao)) !!}</div>
  @else
    <div class="muted">Sem descrição.</div>
  @endif
</div>
@endsection
