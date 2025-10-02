@extends('layouts.site')
@section('title', 'Concursos')

@section('content')
  {{-- Hero com busca simples --}}
  <section class="mb-6 rounded-lg border border-slate-200 bg-white p-5">
    <h1 class="text-xl font-semibold mb-2">Encontre seu concurso</h1>
    <form method="GET" action="{{ route('site.concursos.index') }}" class="flex gap-2">
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Pesquisar por título, órgão..."
             class="flex-1 rounded-md border-slate-300">
      <button class="rounded-md bg-primary-600 px-4 py-2 text-white hover:bg-primary-700">Buscar</button>
      <a href="{{ url('/candidato/login') }}" class="px-4 py-2 rounded-md border">Já me inscrevi</a>
    </form>
  </section>

  {{-- Agrupamentos úteis (Abertos / Em andamento / Encerrados) --}}
  @php
    $agora = now();
    $cards = function($lista) {
      return view()->make('site.concursos.partials.cards', ['concursos' => $lista])->render();
    };
  @endphp

  @if(isset($abertos) && $abertos->count())
  <section class="mb-8">
    <h2 class="text-lg font-semibold mb-3">Inscrições abertas</h2>
    {!! $cards($abertos) !!}
  </section>
  @endif

  @if(isset($andamento) && $andamento->count())
  <section class="mb-8">
    <h2 class="text-lg font-semibold mb-3">Em andamento</h2>
    {!! $cards($andamento) !!}
  </section>
  @endif

  @if(isset($encerrados) && $encerrados->count())
  <section>
    <h2 class="text-lg font-semibold mb-3">Encerrados / Homologados</h2>
    {!! $cards($encerrados) !!}
  </section>
  @endif

  @if( (isset($abertos) && !$abertos->count()) && (isset($andamento) && !$andamento->count()) && (isset($encerrados) && !$encerrados->count()) )
    <div class="rounded border bg-white p-5">Nenhum concurso encontrado.</div>
  @endif
@endsection
