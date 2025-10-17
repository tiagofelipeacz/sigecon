{{-- resources/views/layouts/site.blade.php --}}
@php
  // Defaults, caso o controller não injete $site
  $site = $site ?? [
    'brand'        => 'GestaoConcursos',
    'primary'      => '#0f172a',
    'accent'       => '#111827',
    'banner_url'   => null,
    'banner_title' => 'Concursos e Processos Seletivos',
    'banner_sub'   => 'Inscreva-se, acompanhe publicações e consulte resultados.',
  ];
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Site') — {{ $site['brand'] ?? 'GestaoConcursos' }}</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --site-primary: {{ $site['primary'] ?? '#0f172a' }};
      --site-accent:  {{ $site['accent']  ?? '#111827' }};
      --text: #0f172a;
    }
    *{ box-sizing:border-box; }
    html,body{ height:100%; }
    body{ margin:0; font-family:Inter,system-ui,Segoe UI,Roboto,Helvetica,Arial; color:var(--text); background:#fff; }
    a{ color:inherit; text-decoration:none; }
    img{ max-width:100%; display:block; }

    .site-container{ min-height:100dvh; display:flex; flex-direction:column; }
    .container{ max-width:1100px; margin:0 auto; padding:0 16px; }

    /* Header público */
    .site-header{
      position:sticky; top:0; z-index:30; background:var(--site-primary); color:#fff;
      border-bottom:1px solid rgba(255,255,255,.1);
    }
    .nav{ display:flex; align-items:center; justify-content:space-between; height:64px; }
    .brand{ display:flex; align-items:center; gap:10px; font-weight:800; letter-spacing:-.02em; }
    .brand .logo{ width:28px; height:28px; border-radius:6px; background:#1d4ed8; box-shadow:inset 0 0 0 2px rgba(255,255,255,.25); }
    .menu{ display:flex; align-items:center; gap:14px; }
    .menu a{ font-size:14px; opacity:.95; padding:6px 8px; border-radius:8px; }
    .menu a:hover{ background:rgba(255,255,255,.1); }
    .cta{ background:#fff; color:var(--site-primary); padding:8px 12px; border-radius:10px; font-weight:700; }
    .cta:hover{ filter:brightness(.98); }

    main{ flex:1 1 auto; }

    /* Footer */
    .site-footer{ background:var(--site-primary); color:#fff; margin-top:32px; }
    .site-footer .grid{ display:grid; grid-template-columns:1.2fr 1fr 1fr; gap:18px; padding:28px 0; }
    .site-footer .muted{ opacity:.85; font-size:14px; }
    .site-footer hr{ border:0; height:1px; background:rgba(255,255,255,.18); margin:6px 0 14px; }
    .site-footer small{ opacity:.8; }
    @media (max-width: 900px){
      .site-footer .grid{ grid-template-columns:1fr; }
    }
  </style>

  @stack('head')
</head>
<body>
<div class="site-container">

  {{-- HEADER (site público) --}}
  <header class="site-header">
    <div class="container nav">
      <a href="{{ route('site.concursos.index') }}" class="brand" aria-label="Página inicial">
        <span class="logo"></span>
        <span>{{ $site['brand'] ?? 'GestaoConcursos' }}</span>
      </a>
      <nav class="menu" aria-label="Menu do site">
        <a href="{{ route('site.concursos.index') }}">Início</a>
        <a href="{{ route('site.concursos.index') }}">Concursos</a>
        @if(Route::has('candidato.login'))
          <a class="cta" href="{{ route('candidato.login') }}">Área do Candidato</a>
        @endif
      </nav>
    </div>
  </header>

  <main>
    @yield('content')
  </main>

  {{-- FOOTER --}}
  <footer class="site-footer">
    <div class="container">
      <div class="grid">
        <div>
          <div class="brand" style="font-size:18px;">
            <span class="logo"></span>
            <span>{{ $site['brand'] ?? 'GestaoConcursos' }}</span>
          </div>
          <p class="muted" style="margin:10px 0 0;">
            Organização e realização de concursos públicos e processos seletivos.
          </p>
        </div>
        <div>
          <div style="font-weight:700; margin-bottom:6px;">Concursos</div>
          <div class="muted"><a href="{{ route('site.concursos.index') }}">Em andamento</a></div>
          <div class="muted"><a href="{{ route('site.concursos.index', ['status'=>'ativos']) }}">Abertos</a></div>
          <div class="muted"><a href="{{ route('site.concursos.index', ['status'=>'inativos']) }}">Finalizados</a></div>
        </div>
        <div>
          <div style="font-weight:700; margin-bottom:6px;">Candidato</div>
          @if(Route::has('candidato.login'))   <div class="muted"><a href="{{ route('candidato.login') }}">Área do Candidato</a></div>@endif
          @if(Route::has('candidato.register'))<div class="muted"><a href="{{ route('candidato.register') }}">Criar conta</a></div>@endif
          @if(Route::has('candidato.password.request'))<div class="muted"><a href="{{ route('candidato.password.request') }}">Esqueci minha senha</a></div>@endif
        </div>
      </div>
      <hr>
      <div style="display:flex; align-items:center; justify-content:center; padding:10px 0 16px;">
        <small>© {{ date('Y') }} {{ $site['brand'] ?? 'GestaoConcursos' }}. Todos os direitos reservados.</small>
      </div>
    </div>
  </footer>

</div>

@stack('scripts')
</body>
</html>
