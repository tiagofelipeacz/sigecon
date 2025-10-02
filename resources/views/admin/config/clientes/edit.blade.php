{{-- resources/views/admin/config/clientes/edit.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Editar Cliente - SIGECON')

@section('content')
  <h1>Editar Cliente</h1>
  <p class="sub">Ajuste as informações e salve para aplicar no sistema.</p>

  @if (session('success'))
    <div class="mb-3 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">
      {{ session('success') }}
    </div>
  @endif
  @if (session('ok'))
    <div class="mb-3 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">
      {{ session('ok') }}
    </div>
  @endif
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
    // aceita $cliente, $client, $item, $record
    $cliente = $cliente ?? $client ?? $item ?? $record ?? null;
    $id = is_object($cliente) ? ($cliente->id ?? null) : $cliente;
    $urlUpdate = \Route::has('admin.config.clientes.update')
      ? route('admin.config.clientes.update', $id)
      : url('/admin/config/clientes/'.$id);
  @endphp

  <form method="POST" action="{{ $urlUpdate }}">
    @csrf
    @method('PUT')
    @include('admin.config.clientes._form', ['cliente' => $cliente])
  </form>
@endsection
