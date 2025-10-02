@extends('layouts.sigecon')
@section('title', 'Novo Nível de Escolaridade')

@section('content')
  <h1>Novo Nível de Escolaridade</h1>
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

  <form method="POST" action="{{ route('admin.config.niveis-escolaridade.store') }}">
    @csrf
    @include('admin.config.niveis-escolaridade._form', ['nivel' => (object)[]])
  </form>
@endsection
