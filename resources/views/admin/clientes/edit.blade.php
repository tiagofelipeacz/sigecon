@extends('layouts.sigecon')
@section('title', 'Editar Cliente - SIGECON')

@section('content')
  <h1>Editar Cliente</h1>
  <p class="sub">Ajuste as informações e salve.</p>

  @if (session('success'))
    <div class="mb-3 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">
      {{ session('success') }}
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

  <form method="POST"
        action="{{ route('admin.clientes.update', ['clientes' => ($client->id ?? $cliente->id)]) }}"
        enctype="multipart/form-data">
    @csrf
    @method('PUT')

    @include('admin.clientes._form', [
      'client'  => $client ?? $cliente ?? new \App\Models\Client(),
      'cliente' => $client ?? $cliente ?? new \App\Models\Client(),
      'ufs'     => $ufs ?? [],
      'isEdit'  => true,
    ])

    <div class="toolbar" style="display:flex; gap:.5rem;">
      <button class="btn" type="submit" name="action" value="save">Salvar</button>
      <button class="btn" type="submit" name="action" value="save_close">Salvar e Fechar</button>
      <button class="btn" type="submit" name="action" value="save_new">Salvar e Novo</button>
      <a class="btn" href="{{ route('admin.clientes.index') }}">Cancelar</a>
    </div>
  </form>
@endsection
