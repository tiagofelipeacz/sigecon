{{-- resources/views/site/concursos/index.blade.php --}}
@extends('layouts.site')
@section('title', 'Concursos')

@php
  // Salvaguardas
  $q      = $q      ?? '';
  $status = $status ?? '';
  $site   = $site   ?? [
      'brand'        => 'GestaoConcursos',
      'primary'      => '#0f172a',
      'accent'       => '#111827',
      'banner_url'   => null,
      'banner_title' => 'Concursos e Processos Seletivos',
      'banner_sub'   => 'Inscreva-se, acompanhe publicações e consulte resultados.'
  ];

  // Usa $belt do controller (coleção de URLs) ou cria a partir dos concursos
  $beltImgs = collect(isset($belt) ? $belt : [])
    ->when(!isset($belt) || collect($belt)->isEmpty(), function($c) use ($concursos){
        return $concursos->getCollection()->pluck('card_image')->filter()->unique()->take(10);
    })
    ->values();
@endphp

@section('content')
<style>
  :root{
    --site-primary: {{ $site['primary'] ?? '#0f172a' }};
    --site-accent:  {{ $site['accent']  ?? '#111827' }};
  }

  /* ===== util ===== */
  .container{ max-width:1100px; margin:0 auto; padding:0 16px; }
  .site a:link,
  .site a:visited{ color:inherit; }
  .muted{ color:#6b7280; }
  .btn{ display:inline-flex; align-items:center; gap:8px; border:1px solid #e5e7eb; background:#fff; padding:8px 12px; border-radius:10px; cursor:pointer; text-decoration:none; }
  .btn:hover{ background:#f9fafb; }
  .btn.primary{ background:var(--site-accent); border-color:var(--site-accent); color:#fff; }
  .btn.primary:hover{ filter:brightness(1.05); }

  /* ===== HERO ===== */
  .hero{ background:var(--site-primary); color:#fff; }
  .hero-grid{ display:grid; grid-template-columns: 1.2fr 1fr; gap:24px; align-items:center; padding:46px 0; }
  .hero h1{ font-size:40px; line-height:1.1; margin:0 0 8px; font-weight:800; letter-spacing:-.02em; }
  .hero p{ font-size:16px; opacity:.95; margin:0 0 16px; }
  .hero .bullet{ display:flex; align-items:center; gap:8px; opacity:.95; font-size:14px; margin:4px 0; }
  .hero .media{
    border-radius:16px; overflow:hidden; box-shadow:0 8px 28px rgba(0,0,0,.24);
    background:#0b1220; aspect-ratio:4/5;
  }
  .hero .media img{ width:100%; height:100%; object-fit:cover; display:block; }

  /* ===== BELT ===== */
  .img-belt{ background:linear-gradient(180deg, rgba(15,23,42,.06), transparent); padding:18px 0; overflow:hidden; }
  .img-belt-track{ display:flex; gap:14px; align-items:center; animation:belt-move 30s linear infinite; }
  .img-belt:hover .img-belt-track{ animation-play-state:paused; }
  @keyframes belt-move{ 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
  .belt-item{ position:relative; width:260px; aspect-ratio:16/9; flex:0 0 auto; border-radius:14px; overflow:hidden; border:1px solid #e5e7eb; background:#f8fafc; }
  .belt-item img{ width:100%; height:100%; object-fit:cover; display:block; }
  .belt-item::after{ content:""; position:absolute; inset:0; background:linear-gradient(0deg, rgba(15,23,42,.28), rgba(15,23,42,.1)); mix-blend-mode:multiply; pointer-events:none; }

  /* ===== filtros ===== */
  .filters{ display:grid; grid-template-columns: 1fr auto auto auto; gap:10px; margin:16px 0 8px; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; }
  .tab{ border:1px solid #e5e7eb; padding:8px 12px; border-radius:10px; background:#fff; font-size:14px; cursor:pointer; text-decoration:none; color:#111827; }
  .tab.active{ background:#eef2ff; border-color:#e0e7ff; color:#1e3a8a; }

  /* ===== grid de cards ===== */
  .cards{ display:grid; grid-template-columns: repeat(3, 1fr); gap:18px; margin:12px 0 26px; }
  @media (max-width: 980px){ .hero-grid{ grid-template-columns: 1fr; } .cards{ grid-template-columns: repeat(2, 1fr);} }
  @media (max-width: 640px){ .cards{ grid-template-columns: 1fr; } }

  .card{ border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; background:#fff; display:flex; flex-direction:column; }
  .card a{ text-decoration:none; color:#111827; display:block; }
  .card .cover{ position:relative; width:100%; aspect-ratio:16/9; background:#f8fafc; overflow:hidden; }
  .card .cover img{ width:100%; height:100%; object-fit:cover; display:block; }
  .card .cover::after{ content:""; position:absolute; inset:0; background:linear-gradient(180deg,transparent,rgba(15,23,42,.35)); }
  .card .body{ padding:12px; display:flex; flex-direction:column; gap:10px; }
  .card .title{ font-weight:700; line-height:1.25; }
  .pub-badge{ font-size:12px; padding:3px 8px; border-radius:999px; border:1px solid #e5e7eb; background:#fff; }
  .pub-badge.active{ background:#dcfce7; border-color:#bbf7d0; color:#166534; }
  .more{ margin-top:auto; }
</style>

<div class="site">

  {{-- ================= HERO ================= --}}
  <section class="hero">
    <div class="container">
      <div class="hero-grid">
        <div>
          <h1>{{ $site['banner_title'] ?? 'Concursos e Processos Seletivos' }}</h1>
          <p>{{ $site['banner_sub'] ?? 'Inscreva-se, acompanhe publicações e consulte resultados.' }}</p>

          <div class="bullet">✔️ Concursos Públicos & Processos Seletivos</div>
          <div class="bullet">✔️ Transparência & Segurança</div>
          <div class="bullet">✔️ Área do Candidato integrada</div>

          <div style="margin-top:14px; display:flex; gap:10px; flex-wrap:wrap">
            @if(Route::has('candidato.login'))
              <a href="{{ route('candidato.login') }}" class="btn primary">Área do Candidato</a>
            @endif
            <a href="{{ route('site.concursos.index') }}" class="btn">Ver todos os concursos</a>
          </div>
        </div>

        <div class="media">
          @if(!empty($site['banner_url']))
            <img src="{{ $site['banner_url'] }}" alt="Banner">
          @else
            <img src="{{ asset('images/hero-portrait.jpg') }}" alt="">
          @endif
        </div>
      </div>
    </div>
  </section>

  {{-- ============== FAIXA DE IMAGENS (belt) ============== --}}
  @if($beltImgs->isNotEmpty())
    <section class="img-belt" aria-label="Clientes">
      <div class="container">
        <div class="img-belt-track">
          @foreach([$beltImgs, $beltImgs] as $loopset) {{-- duplicado p/ loop suave --}}
            @foreach($loopset as $imgUrl)
              <div class="belt-item">
                <img src="{{ $imgUrl }}" alt="Imagem do cliente">
              </div>
            @endforeach
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- ================= FILTROS ================= --}}
  <div class="container">
    <form method="GET" class="filters" aria-label="Filtrar concursos">
      <input class="input" type="text" name="q" placeholder="Buscar por título…" value="{{ $q }}">
      <a class="tab {{ $status==='' ? 'active':'' }}"
         href="{{ route('site.concursos.index', array_filter(['q'=>$q])) }}">Todos</a>
      <a class="tab {{ $status==='ativos' ? 'active':'' }}"
         href="{{ route('site.concursos.index', array_filter(['q'=>$q,'status'=>'ativos'])) }}">Ativos</a>
      <a class="tab {{ $status==='inativos' ? 'active':'' }}"
         href="{{ route('site.concursos.index', array_filter(['q'=>$q,'status'=>'inativos'])) }}">Inativos</a>
    </form>

    {{-- ================= GRID ================= --}}
    @if($concursos->count() === 0)
      <div class="muted" style="padding:18px 0">Nenhum concurso encontrado.</div>
    @else
      <div class="cards">
        @foreach($concursos as $c)
          @php
            $url   = route('site.concursos.show', $c->slug);
            $img   = $c->card_image ?? null;
            $ativo = (bool) ($c->ativo ?? false);
            $cliente = trim((string)($c->cliente_nome ?? ''));
          @endphp

          <article class="card">
            <a href="{{ $url }}">
              <div class="cover">
                @if($img)
                  <img src="{{ $img }}" alt="Imagem do concurso {{ $c->titulo }}">
                @else
                  <img src="{{ asset('images/placeholder-16x9.png') }}" alt="">
                @endif
              </div>

              <div class="body">
                <div class="title">{{ $c->titulo }}</div>

                @if($cliente !== '')
                  <div class="muted" style="font-size:12px;">{{ $cliente }}</div>
                @endif>

                <div style="display:flex;align-items:center;justify-content:space-between;">
                  <span class="pub-badge {{ $ativo ? 'active':'' }}">{{ $ativo ? 'Ativo' : 'Inativo' }}</span>
                  <span class="muted" style="font-size:12px;">
                    {{ optional($c->created_at)->format('d/m/Y') }}
                  </span>
                </div>

                <div class="more">
                  <span class="btn primary" style="width:100%; justify-content:center;">Ver detalhes</span>
                </div>
              </div>
            </a>
          </article>
        @endforeach
      </div>

      <div style="margin:4px 0 28px">
        {{ $concursos->onEachSide(1)->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
