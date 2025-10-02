@extends('layouts.sigecon')
@section('title', 'Editar Tipo de Vaga Especial')

@section('content')
  <h1>Editar Tipo de Vaga Especial</h1>
  <p class="sub">Ajuste as informações e salve para aplicar no sistema.</p>

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

  {{-- model via route-model-binding: $tipoVagaEspecial, $tipoVaga ou $tipo --}}
  @php $tipo = $tipo ?? $tipoVaga ?? $tipoVagaEspecial ?? null; @endphp

  <form method="POST" action="{{ route('admin.config.tipos-vagas-especiais.update', $tipo) }}">
    @csrf
    @method('PUT')
    @include('admin.config.tipos-vagas-especiais._form', ['tipo' => $tipo])
  </form>
@endsection
