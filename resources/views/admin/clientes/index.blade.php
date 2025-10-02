@extends('layouts.sigecon')
@section('title', 'Clientes - SIGECON')

@section('content')
@php
  /** @var \Illuminate\Pagination\LengthAwarePaginator|\App\Models\Client[] $clients */
  $clients = $clients ?? $clientes ?? collect();
  $q = $q ?? request('q','');

  $url_create = \Route::has('admin.clientes.create')
      ? route('admin.clientes.create')
      : url('/admin/clientes/create');
@endphp

<h1>Clientes</h1>
<p class="sub">Gerencie os clientes utilizados nos processos.</p>

<div class="toolbar" style="display:flex; gap:.5rem; align-items:center; margin-bottom:10px;">
  <form method="get" class="flex" style="gap:.5rem; flex:1;">
    <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por nome, cidade, UF…" style="flex:1">
    <button class="btn" type="submit">Buscar</button>
    <a class="btn" href="{{ route('admin.clientes.index') }}">Limpar</a>
  </form>

  <a class="btn primary" href="{{ $url_create }}">+ Novo Cliente</a>
</div>

@if (session('success'))
  <div class="mb-3 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">
    {{ session('success') }}
  </div>
@endif

<style>
  .tbl-min{ width:100%; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
  .tbl-min table{ width:100%; border-collapse:collapse; }
  .tbl-min thead th{ background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; text-align:left; font-weight:600; padding:10px 12px; }
  .tbl-min tbody td{ border-top:1px solid #f1f5f9; padding:10px 12px; vertical-align:middle; }
  .tbl-min .nowrap{ white-space:nowrap; }
  .btn.smol{ padding:.25rem .5rem; font-size:.85rem; }
  .btn.danger{ background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
  .btn.danger:hover{ background:#fecaca; }
  @media (max-width: 980px){
    .tbl-min table, .tbl-min thead, .tbl-min tbody, .tbl-min th, .tbl-min td, .tbl-min tr{ display:block; }
    .tbl-min thead{ display:none; }
    .tbl-min tbody td{ border:none; border-top:1px solid #f1f5f9; }
  }
</style>

<div class="tbl-min">
  <table>
    <thead>
      <tr>
        <th style="width:70px">ID</th>
        <th style="width:520px">Cliente</th>
        <th style="width:200px">Cidade/UF</th>
        <th style="width:220px" class="nowrap">Ações</th>
      </tr>
    </thead>
    <tbody>
      @forelse($clients as $c)
        <tr>
          <td>#{{ $c->id }}</td>
          <td>
            <div style="font-weight:600">{{ $c->cliente ?? '—' }}</div>
          </td>
          <td>{{ ($c->cidade ?? '—') . (isset($c->estado) ? '/'.$c->estado : '') }}</td>
          <td class="nowrap">
            <div class="toolbar" style="display:flex; gap:12px">
              <a class="btn smol" href="{{ route('admin.clientes.edit', $c) }}">Editar</a>

              <form method="POST"
                    action="{{ route('admin.clientes.destroy', $c) }}"
                    onsubmit="return confirm('Tem certeza que deseja excluir {{ addslashes($c->cliente ?? ('Cliente #'.$c->id)) }}?\nEsta ação não pode ser desfeita.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn smol danger">Excluir</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="4">Nenhum cliente encontrado.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="pagination" style="margin-top:12px;">
  {{ method_exists($clients,'links') ? $clients->links() : '' }}
</div>
@endsection
