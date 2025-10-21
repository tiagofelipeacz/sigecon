<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'SIGECON')</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="{{ asset('css/gc-sigecon.css') }}?v={{ @filemtime(public_path('css/gc-sigecon.css')) }}">
  <script defer src="{{ asset('js/gc-sigecon.js') }}?v={{ @filemtime(public_path('js/gc-sigecon.js')) }}"></script>

  <!-- Tipografia global mais compacta -->
  <style id="sigecon-typography-compact">
    :root{
      --fs-base: 14px;
      --lh-base: 1.45;

      --fs-sm: 0.875rem;
      --fs-md: 0.95rem;
      --fs-lg: 1.05rem;
      --fs-h1: 1.45rem;
      --fs-h2: 1.25rem;
      --fs-h3: 1.1rem;
    }

    html{ font-size: var(--fs-base); }
    body{ font-size: 1rem; line-height: var(--lh-base); }

    h1{ font-size: var(--fs-h1); margin:.6em 0 .35em; }
    h2{ font-size: var(--fs-h2); margin:.6em 0 .35em; }
    h3{ font-size: var(--fs-h3); margin:.6em 0 .35em; }

    .btn{ font-size: var(--fs-md); }

    .card-min .hd{ font-size: var(--fs-md); }

    .form-min label{ font-size: var(--fs-sm); }
    .form-min input[type="text"],
    .form-min textarea,
    .form-min select{ font-size: var(--fs-md); }

    .sub, .muted{ font-size: var(--fs-sm); }

    .rmenu .title{ font-size: var(--fs-md); }
    .rmenu .sub{ font-size: var(--fs-sm); }
    .rmenu .mi{ font-size: var(--fs-md); }
    .rmenu .mi i{ width:16px; height:16px; }

    table, th, td{ font-size: var(--fs-md); }
  </style>

  <!-- Estilos do dropdown Configurações + modo full-width -->
  <style>
    /* ====== FULL-WIDTH (apenas com body.layout-fluid) ====== */
    .layout-fluid .container,
    .layout-fluid .container.inner,
    .layout-fluid .container.page{
      max-width: 100% !important;
      width: 100% !important;
    }
    .layout-fluid .container.inner,
    .layout-fluid .container.page{
      padding-left: 24px;
      padding-right: 24px;
    }

    /* Alinhamento do topo */
    .topbar .nav{
      display:flex;
      align-items:center;   /* tudo na mesma linha */
      gap:.25rem;
    }
    .topbar .nav > a{
      display:inline-flex; align-items:center;
      padding:.4rem .6rem; line-height:1; text-decoration:none;
    }

    /* Dropdown */
    .nav .dd{
      position:relative;
      display:inline-flex;
      align-items:center;
    }

    /* Botão toggle com a MESMA cara dos links do topo (branco) */
    .nav .dd > .dd-toggle{
      display:inline-flex; align-items:center;
      padding:.4rem .6rem; line-height:1;
      background:transparent; border:0; cursor:pointer;
      font: inherit; text-decoration:none;
      color:#fff;                 /* <- força branco como os links */
      font-weight:600;
      border-radius:8px;
    }

    /* Hover/active como os demais links em topbar escura */
    .topbar .nav .dd > .dd-toggle:hover,
    .topbar .nav .dd.open > .dd-toggle,
    .topbar .nav .dd:focus-within > .dd-toggle{
      background: rgba(255,255,255,.08);
      color:#fff;
      outline:none;
    }

    /* Painel: sem GAP entre botão e caixa */
    .nav .dd .dd-panel{
      position:absolute; left:0; top:100%;
      min-width:260px;
      background:#fff; border:1px solid #e5e7eb; border-radius:10px;
      box-shadow:0 10px 25px rgba(0,0,0,.08);
      padding:6px;
      display:none; z-index:1000;
    }

    /* Abre em hover, foco ou classe .open (JS) */
    .nav .dd:hover .dd-panel,
    .nav .dd:focus-within .dd-panel,
    .nav .dd.open .dd-panel{ display:block; }

    .nav .dd .dd-item{
      display:block; padding:8px 10px; border-radius:8px;
      color:#0f172a; text-decoration:none; white-space:nowrap;
    }
    .nav .dd .dd-item:hover{ background:#f3f4f6; }

    /* Deixa o toggle EXACTAMENTE igual aos demais links do topo */
    .topbar .nav > a,
    .topbar .nav .dd > .dd-toggle{
      color: rgba(255,255,255,.85);
      font-weight: 600;
      padding: .4rem .6rem;
      border-radius: 8px;
    }
    .topbar .nav > a:hover,
    .topbar .nav .dd > .dd-toggle:hover,
    .topbar .nav .dd.open > .dd-toggle,
    .topbar .nav .dd:focus-within > .dd-toggle{
      color: #fff;
      background: rgba(255,255,255,.08);
    }
  </style>

  <!-- SÓ o botão "Sair" (não afeta o menu Configurações) -->
  <style>
    .topbar .nav .auth a.btn[onclick*="logout-form"]{
      background: transparent !important;
      border: 0 !important;
      color: rgba(255,255,255,.85) !important;
      font-weight: 600;
      padding: .4rem .6rem;
      border-radius: 8px;
      line-height: 1;
      text-decoration: none;
    }
    .topbar .nav .auth a.btn[onclick*="logout-form"]:hover,
    .topbar .nav .auth a.btn[onclick*="logout-form"]:focus{
      color: #fff !important;
      background: rgba(255,255,255,.08) !important;
      outline: none;
    }
  </style>
</head>

<body class="layout-fluid">
  @php
    use Illuminate\Support\Facades\Route;

    // Helper para melhor rota disponível ou fallback
    $href = function(array $cands, string $fallbackPath){
      foreach($cands as $rn){
        if (Route::has($rn)) return route($rn);
      }
      return url($fallbackPath);
    };

    // URLs do dropdown "Configurações"
    $urlConfigIndex        = $href(['admin.config.index'],                             '/admin/config');
    $urlTiposIsencao       = $href(['admin.config.tipos-isencao.index'],              '/admin/config/tipos-isencao');
    $urlCondicoesEspeciais = $href([
                                  'admin.config.condicoes-especiais.index',
                                  'admin.config.condicoes_especiais.index',
                                ],                                                    '/admin/config/condicoes-especiais');
    $urlNiveisEscolaridade = $href(['admin.config.niveis-escolaridade.index'],        '/admin/config/niveis-escolaridade');
    $urlTiposVagasEsp      = $href(['admin.config.tipos-vagas-especiais.index'],      '/admin/config/tipos-vagas-especiais');

    // NOVO: Grupo de Anexos
    $urlGruposAnexos       = $href([
                                  'admin.config.grupos-anexos.index',
                                  'admin.config.grupos_anexos.index',
                                ],                                                    '/admin/config/grupos-anexos');

    // Itens opcionais
    $urlUsuarios           = $href(['admin.users.index','admin.usuarios.index'],      '/admin/usuarios');
    $urlEmailsAuto         = $href(['admin.config.emails-automaticos.index'],         '/admin/config/emails-automaticos');
    $urlFormasPagto        = $href(['admin.config.formas-pagamento.index'],           '/admin/config/formas-pagamento');
    $urlModelosResp        = $href(['admin.config.modelos-respostas.index'],          '/admin/config/modelos-respostas');
    $urlOmrCalibracao      = $href(['admin.config.omr.calibracao.index'],             '/admin/config/omr/calibracao');
  @endphp

  <div class="topbar">
    <div class="container inner">
      <img class="logo" src="{{ asset('img/logo.svg') }}" alt="SIGECON">
      <a href="{{ url('/admin') }}" class="brand"><strong>SIGECON</strong></a>

      <nav class="nav">
        <a href="{{ url('/admin/inicio') }}">Início</a>
        <a href="{{ url('/admin/concursos') }}">Gerenciar Processos</a>
        <a href="{{ url('/admin/clientes') }}">Clientes</a>
        <a href="{{ url('/admin/candidatos') }}">Candidatos</a>
        <a href="{{ url('/admin/site') }}">Site</a>
       <!--  <a href="#">Publicidade</a>
        <a href="#">Ferramentas</a> -->

        {{-- Dropdown: Configurações (sem navegação no toggle) --}}
        <div class="dd" data-dd>
          <button type="button" class="dd-toggle" aria-haspopup="true" aria-expanded="false">
            Configurações ▾
          </button>
          <div class="dd-panel" role="menu" aria-label="Configurações">
            <a class="dd-item" href="{{ $urlUsuarios }}">Usuários</a>
            <a class="dd-item" href="{{ $urlTiposIsencao }}">Tipos de Isenção</a>
            <a class="dd-item" href="{{ $urlCondicoesEspeciais }}">Tipos de Condições Especiais</a>
            <a class="dd-item" href="{{ $urlNiveisEscolaridade }}">Níveis de Escolaridade</a>
            <a class="dd-item" href="{{ $urlTiposVagasEsp }}">Tipos de Vagas Especiais</a>
            <a class="dd-item" href="{{ $urlGruposAnexos }}">Grupo de Anexos</a>
            <a class="dd-item" href="{{ $urlEmailsAuto }}">E-mails Automáticos</a>
            <a class="dd-item" href="{{ $urlFormasPagto }}">Formas de Pagamento</a>
            <a class="dd-item" href="{{ $urlModelosResp }}">Modelos de Respostas</a>
            <a class="dd-item" href="{{ $urlOmrCalibracao }}">OMR - Calibração</a>
            <div style="border-top:1px solid #e5e7eb; margin:6px 4px;"></div>
            <a class="dd-item" href="{{ $urlConfigIndex }}">Configurações Gerais</a>
          </div>
        </div>

        <div class="spacer"></div>

        <span class="iconbtn">🔔</span>

        {{-- Usuário/Logout --}}
        <div class="auth" style="display:flex; align-items:center; gap:.5rem;">
          <div class="user">
            {{ Auth::user()->name ?? 'Admin' }}
          </div>

          {{-- Form de logout (POST) --}}
          <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
            @csrf
          </form>

          <a href="#" class="btn"
             onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            Sair
          </a>
        </div>
      </nav>
    </div>
  </div>

  <div class="container page">
    @yield('content')
  </div>

  <!-- JS: click/touch para abrir/fechar, fecha fora e com ESC -->
  <script>
    (function(){
      const dd = document.querySelector('[data-dd]');
      if (!dd) return;

      const toggle = dd.querySelector('.dd-toggle');

      function openDD(){
        dd.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
      }
      function closeDD(){
        dd.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      }
      function isOpen(){ return dd.classList.contains('open'); }

      // Toggle por clique/touch
      toggle.addEventListener('click', (e) => {
        e.preventDefault();
        isOpen() ? closeDD() : openDD();
      });

      // Fecha ao clicar fora
      document.addEventListener('click', (e) => {
        if (!dd.contains(e.target)) closeDD();
      });

      // Fecha com ESC
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeDD();
      });
    })();
  </script>
</body>
</html>
