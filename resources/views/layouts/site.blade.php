{{-- resources/views/layouts/site.blade.php --}}
@php
  use Illuminate\Support\Str;

  // Carrega as configurações do site (se o método existir) e mescla com o que vier do controller.
  $baseSite = [];
  try {
    if (method_exists(\App\Http\Controllers\Admin\Config\SiteSettingsController::class, 'current')) {
      $baseSite = \App\Http\Controllers\Admin\Config\SiteSettingsController::current();
    }
  } catch (\Throwable $e) {
    $baseSite = [];
  }

  // Defaults seguros
  $defaults = [
    'brand'        => 'GestaoConcursos',
    'primary'      => '#0f172a',
    'accent'       => '#111827',
    'banner_url'   => null,
    'banner_title' => 'Concursos e Processos Seletivos',
    'banner_sub'   => 'Inscreva-se, acompanhe publicações e consulte resultados.',
  ];

  $site = array_merge($defaults, $baseSite, $site ?? []);

  // Helper para converter qualquer caminho salvo em URL pública.
  $resolvePublicUrl = function (?string $p): ?string {
    if (!$p) return null;
    $p = trim($p);
    if ($p === '') return null;

    if (Str::startsWith($p, ['http://','https://','data:image'])) return $p;
    if (Str::startsWith($p, ['/storage/','storage/'])) return asset(ltrim($p,'/'));

    $norm = ltrim($p,'/');
    if (Str::startsWith($norm, 'public/')) return asset('storage/'.substr($norm,7));
    if (file_exists(public_path($p)))               return asset($p);
    if (file_exists(public_path($norm)))            return asset($norm);
    if (file_exists(public_path('storage/'.$norm))) return asset('storage/'.$norm);

    // fallback: assume disco public
    return asset('storage/'.$norm);
  };

  // Resolve logo a partir das chaves conhecidas
  $logoCandidate = $site['logo_url']
                ?? $site['logo_path']
                ?? $site['logo']
                ?? $site['logotipo']
                ?? $site['logo_image']
                ?? $site['site_logo']
                ?? $site['brand_logo']
                ?? $site['header_logo']
                ?? null;

  $logoUrl = $resolvePublicUrl($logoCandidate);
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Site') - {{ $site['brand'] ?? 'GestaoConcursos' }}</title>
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

    /* Header (topo branco com borda) — ajustado para logo >= 90px */
    .site-header{ position:sticky; top:0; z-index:30; background:#fff; border-bottom:1px solid #e5e7eb; }
    .nav{
      display:flex; align-items:center; justify-content:space-between; gap:12px;
      min-height: 110px;  /* acomoda logo de 90px com folga */
      padding: 10px 0;    /* respiro vertical */
    }
    .brand{ display:flex; align-items:center; gap:14px; font-weight:800; letter-spacing:-.01em; }
    .logo-img{ max-height: 90px; width:auto; display:block; } /* tamanho solicitado */
    .brand-text{ color:#111827; }

    .menu{ display:flex; align-items:center; gap:10px; }
    .menu a{ font-size:14px; padding:10px 12px; border-radius:10px; border:1px solid transparent; }
    .menu a:hover{ background:#f9fafb; border-color:#e5e7eb; }

    .btn{ display:inline-flex; align-items:center; gap:8px; border:1px solid #e5e7eb; background:#fff; padding:10px 14px; border-radius:10px; cursor:pointer; text-decoration:none; color:#111827; }
    .btn:hover{ background:#f9fafb; }
    .btn.primary{ background:var(--site-accent); border-color:var(--site-accent); color:#fff; }
    .btn.primary:hover{ filter:brightness(1.05); }

    main{ flex:1 1 auto; }

    /* Footer */
    .site-footer{ background:var(--site-primary); color:#fff; margin-top:32px; }
    .site-footer .grid{ display:grid; grid-template-columns:1.2fr 1fr 1fr; gap:18px; padding:28px 0; }
    .site-footer .muted{ opacity:.85; font-size:14px; }
    .site-footer hr{ border:0; height:1px; background:rgba(255,255,255,.18); margin:6px 0 14px; }
    .site-footer small{ opacity:.8; }
    .logo-footer{ max-height: 60px; width:auto; display:block; } /* um pouco menor no rodapé */
    @media (max-width: 900px){
      .site-footer .grid{ grid-template-columns:1fr; }
      .nav{ min-height: 90px; padding:8px 0; } /* header reduzido em telas menores */
      .logo-img{ max-height: 72px; }
      .menu{ flex-wrap:wrap; }
    }
  </style>

  @stack('head')
</head>
<body>
<div class="site-container">

  {{-- HEADER --}}
  <header class="site-header">
    <div class="container nav">
      <a href="{{ route('site.concursos.index') }}" class="brand" aria-label="Página inicial">
        @if($logoUrl)
          <img class="logo-img" src="{{ $logoUrl }}" alt="{{ $site['brand'] ?? 'GestaoConcursos' }}">
          {{-- Quando há logo, NÃO exibimos o texto --}}
        @else
          <span class="brand-text">{{ $site['brand'] ?? 'GestaoConcursos' }}</span>
        @endif
      </a>

      <nav class="menu" aria-label="Menu do site">
        {{-- Somente "Início" e "Área do Candidato" --}}
        <a href="{{ route('site.concursos.index') }}">Início</a>

        @auth('candidato')
          <a class="btn primary" href="{{ route('candidato.home') }}">Área do Candidato</a>
        @else
          @if(Route::has('candidato.login'))
            <a class="btn primary" href="{{ route('candidato.login') }}">Área do Candidato</a>
          @endif
        @endauth
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
            @if($logoUrl)
              <img class="logo-footer" src="{{ $logoUrl }}" alt="{{ $site['brand'] ?? 'GestaoConcursos' }}">
            @else
              <span>{{ $site['brand'] ?? 'GestaoConcursos' }}</span>
            @endif
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
