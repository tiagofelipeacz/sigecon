@extends('layouts.auth')
@section('title', 'Entrar — SIGECON')

@section('content')
  <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="h-1 bg-gradient-to-r from-primary-500 to-cyan-400"></div>

    <div class="p-6">
      <div class="mb-5">
        <h1 class="text-xl font-semibold text-gray-800">Entrar</h1>
        <p class="text-sm text-gray-500">Acesse o painel administrativo do SIGECON</p>
      </div>

      @if ($errors->any())
        <div class="mb-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg p-3">
          <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('login') }}" class="space-y-4" novalidate>
        @csrf

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
          <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                 class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                 placeholder="voce@exemplo.com">
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label for="password" class="block text-sm font-medium text-gray-700">Senha</label>
            @if (Route::has('password.request'))
              <a href="{{ route('password.request') }}" class="text-xs text-primary-700 hover:underline">
                Esqueci minha senha
              </a>
            @endif
          </div>
          <div class="relative">
            <input id="password" name="password" type="password" required
                   class="w-full rounded-lg border-gray-300 pr-10 focus:border-primary-500 focus:ring-primary-500"
                   placeholder="••••••••">
            <button type="button" aria-label="Mostrar/ocultar senha"
                    class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700"
                    onclick="const i=document.getElementById('password'); i.type = i.type==='password' ? 'text' : 'password';">
              👁️
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <label class="inline-flex items-center text-sm text-gray-600">
            <input type="checkbox" name="remember" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
            <span class="ml-2">Lembrar de mim</span>
          </label>
        </div>

        <button type="submit"
                class="w-full inline-flex justify-center items-center rounded-lg px-4 py-2.5 font-medium
                       text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2
                       focus:ring-primary-500 transition">
          Entrar
        </button>
      </form>
    </div>
  </div>

  <p class="mt-6 text-center text-xs text-gray-500">
    © {{ date('Y') }} SIGECON - Sistema de Gestão de Concursos
  </p>
@endsection
