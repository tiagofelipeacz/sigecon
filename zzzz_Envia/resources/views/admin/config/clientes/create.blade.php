{{-- resources/views/admin/config/clientes/create.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Novo Cliente - SIGECON')

@section('content')
  <h1>Novo Cliente</h1>
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

  <form method="POST" action="{{ \Route::has('admin.config.clientes.store') ? route('admin.config.clientes.store') : url('/admin/config/clientes') }}">
    @csrf
    @include('admin.config.clientes._form')
  </form>
@endsection
