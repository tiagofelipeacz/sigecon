{{-- resources/views/admin/config/niveis-escolaridade/edit.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Editar Nível de Escolaridade')

@section('content')
  <h1>Editar Nível de Escolaridade</h1>
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

  @php
    // Garante que o route() receba o ID (stdClass não implementa UrlRoutable)
    $nivelId = is_object($nivel) ? ($nivel->id ?? null) : $nivel;
  @endphp

  <form method="POST" action="{{ route('admin.config.niveis-escolaridade.update', ['nivel' => $nivelId]) }}">
    @csrf
    @method('PUT')
    @include('admin.config.niveis-escolaridade._form', ['nivel' => $nivel])
  </form>
@endsection
