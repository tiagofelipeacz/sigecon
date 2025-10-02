@extends('layouts.sigecon')
@section('title', 'Candidatos - SIGECON')

@section('content')
@php
  /** @var \Illuminate\Pagination\LengthAwarePaginator|\App\Models\Candidato[] $candidatos */
  $q = $q ?? request('q','');
  $url_create = route('admin.candidatos.create');
@endphp

<h1>Candidatos</h1>
<p class="sub">Base de todos os candidatos cadastrados no sistema.</p>

<div class="toolbar" style="display:flex; gap:.5rem; align-items:center; margin-bottom:10px;">
  <form method="get" class="flex" style="gap:.5rem; flex:1;">
    <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por nome, CPF, e-mail, cidade, UF…" style="flex:1">
    <button class="btn" type="submit">Buscar</button>
    <a class="btn" href="{{ route('admin.candidatos.index') }}">Limpar</a>
  </form>

  <a class="btn" href="{{ route('admin.candidatos.export') }}">Exportar CSV</a>
  <a class="btn primary" href="{{ $url_create }}">+ Novo Candidato</a>
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
  .pill{ display:inline-block; padding:.15rem .5rem; border-radius:999px; font-size:.8rem; border:1px solid transparent; }
  .pill.ok{ background:#e8faf0; color:#166534; border-color:#bbf7d0; }
  .pill.nok{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
  .btn.smol{ padding:.25rem .5rem; font-size:.85rem; }
  .btn.danger{ background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
  .btn.danger:hover{ background:#fecaca; }
  @media (max-width:980px){
    .tbl-min table, .tbl-min thead, .tbl-min tbody, .tbl-min th, .tbl-min td, .tbl-min tr{ display:block; }
    .tbl-min thead{ display:none; }
    .tbl-min tbody td{ border:none; border-top:1px solid #f1f5f9; }
  }
</style>

<div class="tbl-min">
  <table>
    <thead>
      <tr>
        <th style="width:80px">ID</th>
        <th style="width:380px">Nome</th>
        <th style="width:160px">CPF</th>
        <th style="width:220px">Cidade/UF</th>
        <th style="width:110px">Status</th>
        <th style="width:220px" class="nowrap">Ações</th>
      </tr>
    </thead>
    <tbody>
      @forelse($candidatos as $c)
        @php
          $status = (int)($c->status ?? 1) === 1;
        @endphp
        <tr>
          <td>#{{ $c->id }}</td>
          <td style="font-weight:600">{{ $c->nome }}</td>
          <td>{{ $c->cpf }}</td>
          <td>{{ trim(($c->cidade ?? '').(isset($c->estado) ? '/'.$c->estado : ''), '/') ?: '—' }}</td>
          <td>{!! $status ? '<span class="pill ok">Ativo</span>' : '<span class="pill nok">Inativo</span>' !!}</td>
          <td class="nowrap">
            <div class="toolbar" style="display:flex; gap:12px">
              <a class="btn smol" href="{{ route('admin.candidatos.edit', $c) }}">Editar</a>
              <form method="POST" action="{{ route('admin.candidatos.destroy', $c) }}"
                    onsubmit="return confirm('Excluir {{ addslashes($c->nome) }}? Esta ação não pode ser desfeita.');">
                @csrf @method('DELETE')
                <button type="submit" class="btn smol danger">Excluir</button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="6">Nenhum candidato encontrado.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="pagination" style="margin-top:12px;">
  {{ $candidatos->links() }}
</div>
@endsection
