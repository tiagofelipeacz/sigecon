<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Gestão Concursos'))</title>

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      window.tailwind = window.tailwind || {};
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              primary: {
                50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81'
              }
            }
          }
        }
      }
    </script>

    <!-- Compat CSS (botões, cards, tabelas, formulários, etc.) -->
    <link href="{{ asset('css/gc-theme.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

    @php
        // Detecta se a tela atual é de autenticação (login/reset/etc.) para ocultar o chrome do admin
        $isAuthScreen = (isset($auth_page) && $auth_page)
            || request()->routeIs('login')
            || request()->routeIs('admin.login')
            || request()->routeIs('password.*')
            || request()->routeIs('register')
            || request()->is('login')
            || request()->is('admin/login');

        // Helpers de navegação já existentes
        $is = function ($patterns) {
            foreach ((array)$patterns as $p) {
                if (request()->routeIs($p) || request()->is($p)) return true;
            }
            return false;
        };
        $linkBase   = 'px-3 py-2 rounded-md text-sm font-medium transition';
        $linkIdle   = 'text-gray-700 hover:text-primary-700 hover:bg-primary-50';
        $linkActive = 'text-primary-800 bg-primary-100';
    @endphp

    {{-- ===== Top Nav ===== --}}
    @unless($isAuthScreen)
    <header class="bg-white border-b border-gray-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 items-center justify-between">
                <div class="flex items-center gap-6">
                    <a href="{{ route('admin.home') }}" class="text-lg font-semibold text-primary-700">
                        {{ config('app.name', 'Gestão Concursos') }}
                    </a>

                    <nav class="hidden md:flex items-center gap-1">
                        {{-- Início (antes: Processos Seletivos) --}}
                        <a href="{{ route('admin.concursos.index') }}"
                           class="{{ $linkBase }} {{ $is('admin.concursos.*') ? $linkActive : $linkIdle }}">
                           Início
                        </a>

                        {{-- Administrativo --}}
                        <a href="{{ route('admin.home') }}"
                           class="{{ $linkBase }} {{ $is('admin.home') ? $linkActive : $linkIdle }}">
                           Administrativo
                        </a>

                        {{-- Clientes (mantido) --}}
                        <a href="{{ url('/admin/clientes') }}"
                        class="{{ $linkBase }} {{ $is(['admin/clientes*']) ? $linkActive : $linkIdle }}">
                        Clientes
                        </a>

                        {{-- Candidatos (novo; troca para route() quando tiver a rota nomeada) --}}
                        <a href="{{ url('/admin/candidatos') }}"
                           class="{{ $linkBase }} {{ $is(['admin/candidatos*']) ? $linkActive : $linkIdle }}">
                           Candidatos
                        </a>

                        {{-- Site (novo; troca para route() quando tiver a rota nomeada) --}}
                        <a href="{{ url('/admin/site') }}"
                           class="{{ $linkBase }} {{ $is(['admin/site*']) ? $linkActive : $linkIdle }}">
                           Site
                        </a>

                        {{-- Configurações (dropdown) --}}
                        <details class="relative group">
                            <summary class="{{ $linkBase }} cursor-pointer list-none {{ $is(['admin.config.*']) ? $linkActive : $linkIdle }}">
                                Configurações
                            </summary>
                            <div class="absolute left-0 mt-2 w-64 rounded-md border border-gray-200 bg-white shadow-lg z-20 hidden group-open:block">
                                <div class="py-1 text-sm">
                                    {{-- Já existente: Pedidos de Isenção (mantido) --}}
                                    <a href="{{ route('admin.config.pedidos-isencao.index') }}"
                                       class="block px-3 py-2 hover:bg-gray-50">
                                        Pedidos de Isenção
                                    </a>

                                    {{-- ====== ITENS ADICIONADOS (ordem solicitada) ====== --}}

                                    {{-- Usuários --}}
                                    @php
                                        $urlUsuarios = '/admin/usuarios';
                                        foreach (['admin.users.index','admin.usuarios.index','users.index','admin.config.usuarios.index'] as $rn) {
                                            if (Route::has($rn)) { $urlUsuarios = route($rn); break; }
                                        }
                                    @endphp
                                    <a href="{{ $urlUsuarios }}" class="block px-3 py-2 hover:bg-gray-50">Usuários</a>

                                    {{-- E-mails Automáticos --}}
                                    @php
                                        $urlEmails = '/admin/config/emails-automaticos';
                                        foreach (['admin.config.emails-automaticos.index','admin.emails-automaticos.index','admin.config.emails.index'] as $rn) {
                                            if (Route::has($rn)) { $urlEmails = route($rn); break; }
                                        }
                                    @endphp
                                    <a href="{{ $urlEmails }}" class="block px-3 py-2 hover:bg-gray-50">E-mails Automáticos</a>

                                    {{-- Formas de Pagamento --}}
                                    @php
                                        $urlFormas = '/admin/config/formas-pagamento';
                                        foreach (['admin.config.formas-pagamento.index','admin.formas-pagamento.index','admin.config.pagamentos.formas.index'] as $rn) {
                                            if (Route::has($rn)) { $urlFormas = route($rn); break; }
                                        }
                                    @endphp
                                    <a href="{{ $urlFormas }}" class="block px-3 py-2 hover:bg-gray-50">Formas de Pagamento</a>

                                    {{-- Modelos de Respostas --}}
                                    @php
                                        $urlModelos = '/admin/config/modelos-respostas';
                                        foreach (['admin.config.modelos-respostas.index','admin.modelos-respostas.index','admin.config.modelos-resposta.index'] as $rn) {
                                            if (Route::has($rn)) { $urlModelos = route($rn); break; }
                                        }
                                    @endphp
                                    <a href="{{ $urlModelos }}" class="block px-3 py-2 hover:bg-gray-50">Modelos de Respostas</a>

                                    {{-- Tipos de Deficiências --}}
                                    @php
                                        $urlDef = '/admin/config/tipos-deficiencia';
                                        foreach (['admin.config.tipos-deficiencia.index','admin.tipos-deficiencia.index'] as $rn) {
                                            if (Route::has($rn)) { $urlDef = route($rn); break; }
                                        }
                                    @endphp
                                    <a href="{{ $urlDef }}" class="block px-3 py-2 hover:bg-gray-50">Tipos de Deficiências</a>

                                    {{-- Motivos de Reprovação --}}
                                    @php
                                        $urlMotivos = '/admin/config/motivos-reprovacao';
                                        foreach (['admin.config.motivos-reprovacao.index','admin.motivos-reprovacao.index'] as $rn) {
                                            if (Route::has($rn)) { $urlMotivos = route($rn); break; }
                                        }
                                    @endphp
                                    <a href="{{ $urlMotivos }}" class="block px-3 py-2 hover:bg-gray-50">Motivos de Reprovação</a>

                                    {{-- Tipos de Vagas Especiais --}}
                                    @php
                                        $urlVagas = '/admin/config/tipos-vagas-especiais';
                                        foreach (['admin.config.tipos-vagas-especiais.index','admin.tipos-vagas-especiais.index'] as $rn) {
                                            if (Route::has($rn)) { $urlVagas = route($rn); break; }
                                        }
                                    @endphp
                                    <a href="{{ $urlVagas }}" class="block px-3 py-2 hover:bg-gray-50">Tipos de Vagas Especiais</a>

                                    {{-- Tipos de Condições Especiais --}}
                                    @php
                                        $urlCondEsp = '/admin/config/condicoes-especiais';
                                        foreach ([
                                            'admin.config.condicoes-especiais.index',   // hífen
                                            'admin.config.condicoes_especiais.index',   // underscore
                                        ] as $rn) {
                                            if (Route::has($rn)) { $urlCondEsp = route($rn); break; }
                                        }
                                    @endphp
                                    <a href="{{ $urlCondEsp }}" class="block px-3 py-2 hover:bg-gray-50">Tipos de Condições Especiais</a>

                                    {{-- Níveis de Escolaridade --}}
                                    @php
                                        $urlNiveis = '/admin/config/niveis-escolaridade';
                                        foreach (['admin.config.niveis-escolaridade.index','admin.niveis-escolaridade.index'] as $rn) {
                                            if (Route::has($rn)) { $urlNiveis = route($rn); break; }
                                        }
                                    @endphp
                                    <a href="{{ $urlNiveis }}" class="block px-3 py-2 hover:bg-gray-50">Níveis de Escolaridade</a>

                                    {{-- OMR - Calibração --}}
                                    @php
                                        $urlOmr = '/admin/config/omr/calibracao';
                                        foreach (['admin.config.omr.calibracao.index','admin.config.omr.index','admin.omr.calibracao.index'] as $rn) {
                                            if (Route::has($rn)) { $urlOmr = route($rn); break; }
                                        }
                                    @endphp
                                    <a href="{{ $urlOmr }}" class="block px-3 py-2 hover:bg-gray-50">OMR - Calibração</a>

                                    {{-- Configurações Gerais --}}
                                    @php
                                        $urlGerais = '/admin/config';
                                        foreach (['admin.config.index','admin.config.gerais.index','admin.configuracoes.index'] as $rn) {
                                            if (Route::has($rn)) { $urlGerais = route($rn); break; }
                                        }
                                    @endphp
                                    <a href="{{ $urlGerais }}" class="block px-3 py-2 hover:bg-gray-50">Configurações Gerais</a>

                                    {{-- ====== /ITENS ADICIONADOS ====== --}}
                                </div>
                            </div>
                        </details>
                    </nav>
                </div>

                {{-- Usuário / Logout (simples) --}}
                <div class="flex items-center gap-3">
                    <span class="hidden sm:inline text-sm text-gray-600">
                        {{ auth()->user()->name ?? 'Usuário' }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-600 hover:text-red-600">
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>
    @endunless

    {{-- ===== Conteúdo ===== --}}
    @if($isAuthScreen)
        {{-- Centraliza o formulário nas telas de autenticação --}}
        <main class="min-h-screen flex items-center justify-center px-4 py-8">
            <div class="w-full max-w-md">
                @if (session('status'))
                    <div class="alert alert-success mb-4">{{ session('status') }}</div>
                @endif

                @yield('content')
            </div>
        </main>
    @else
        <main class="gc-wrapper mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
            @if (session('status'))
                <div class="alert alert-success mb-4">{{ session('status') }}</div>
            @endif

            @yield('content')
        </main>
    @endif

    <script src="{{ asset('js/gc-theme.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
