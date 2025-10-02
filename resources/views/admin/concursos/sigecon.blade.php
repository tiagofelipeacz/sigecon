<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'SIGECON')</title>

  <!-- Fonte -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- CSS do layout (sem subpasta "sigecon") -->
  <link rel="stylesheet" href="{{ asset('assets/css/sigecon.css') }}">
  <script defer src="{{ asset('assets/js/sigecon.js') }}"></script>
</head>
<body>
  <!-- Topbar do design -->
  <div class="topbar">
    <div class="container inner">
      <img class="logo" src="{{ asset('assets/img/logo.svg') }}" alt="SIGECON">
      <a href="{{ url('/admin') }}" class="brand"><strong>SIGECON</strong></a>

      <nav class="nav">
        <a href="{{ url('/admin') }}">Início</a>
        <a href="{{ url('/admin/concursos') }}">Processos Seletivos</a>
        <a href="{{ url('/admin/clients') }}">Clientes</a>
        <a href="{{ url('/admin/candidatos') }}">Candidatos</a>
        <a href="#">Site</a>
        <a href="#">Publicidade</a>
        <a href="#">Ferramentas</a>
        <a href="{{ url('/admin/config/condicoes-especiais') }}">Configurações</a>

        <div class="spacer"></div>

        @auth
          <span class="user">{{ Auth::user()->name ?? 'Usuário' }}</span>
          <form action="{{ route('logout') }}" method="POST" style="margin:0">
            @csrf
            <button type="submit" class="iconbtn" title="Sair">Sair</button>
          </form>
        @else
          <a href="{{ route('login') }}" class="iconbtn">Entrar</a>
        @endauth
      </nav>
    </div>
  </div>

  <div class="container page">
    @yield('content')
  </div>
</body>
</html>
