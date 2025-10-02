@extends('layouts.sigecon')
@section('title', 'Novo Tipo de Isenção')

@section('content')
  <h1>Novo Tipo de Isenção</h1>
  <p class="sub">Preencha as informações e salve para aplicar no sistema.</p>

  @if ($errors->any())
    <div class="mb-3 rounded border border-red-300 bg-red-50 p-3 text-red-800">
      <div class="font-semibold mb-1">Corrija os erros abaixo:</div>
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.config.tipos-isencao.store') }}">
    @csrf
    @include('admin.config.tipos-isencao._form', ['record' => $tipoIsencao ?? new \App\Models\TipoIsencao()])
  </form>
@endsection
