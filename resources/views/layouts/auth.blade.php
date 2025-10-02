<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', config('app.name', 'SIGECON'))</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    window.tailwind = window.tailwind || {};
    tailwind.config = {
      theme: { extend: {
        colors: { primary: {50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81'} }
      }}
    }
  </script>

  <!-- Seu CSS de compatibilidade -->
  <link href="{{ asset('css/gc-theme.css') }}" rel="stylesheet">
  @stack('styles')
</head>
<body class="bg-gray-50 text-gray-800 antialiased">
  <!-- Layout de autenticação: SEM topo -->
  <main class="min-h-screen flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-md">
      @if (session('status'))
        <div class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-3">
          {{ session('status') }}
        </div>
      @endif

      @yield('content')
    </div>
  </main>

  <script src="{{ asset('js/gc-theme.js') }}" defer></script>
  @stack('scripts')
</body>
</html>
