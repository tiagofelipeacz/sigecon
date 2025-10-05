{{-- resources/views/admin/concursos/impugnacoes/index.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Impugnações - SIGECON')

@php
  // Esperado do controller:
  // $concurso (Model), $impugnacoes (LengthAwarePaginator), $q (string), $situacao (string)
  $q = $q ?? '';
  $situacao = $situacao ?? '';
@endphp

@section('content')
<style>
  .gc-page   { display:grid; grid-template-columns: 260px 1fr; gap:16px; }
  .gc-card   { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body   { padding:14px; }
  .x-scroll  { overflow-x:auto; }
  .table { width:100%; border-collapse: collapse; }
  .table thead th{ text-align:left; font-size:12px; color:#6b7280; padding:8px; border-bottom:1px solid #e5e7eb; }
  .table tbody td{ padding:8px; border-bottom:1px solid #f3f4f6; font-size:14px; vertical-align: top; }
  .muted{ color:#6b7280; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; text-decoration:none; cursor:pointer; }
  .btn:hover{ background:#f9fafb; }
  .btn.primary{ background:#111827; color:white; border-color:#111827; }
  .filters{ display:grid; grid-template-columns: 1fr 220px 120px; gap:10px; margin-bottom:12px; }
  @media (max-width: 768px){ .filters{ grid-template-columns: 1fr; } }
  .chip{ background:#eef2ff; color:#3730a3; padding:3px 8px; border-radius:999px; font-size:12px; }
</style>

@if(session('success'))
  <div class="mb-3" style="border:1px solid #a7f3d0; background:#ecfdf5; color:#065f46; padding:8px; border-radius:8px">
    {{ session('success') }}
  </div>
@endif

<div class="gc-page">
  {{-- Lateral: menu (marca "impugnacoes" ativo) --}}
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'impugnacoes'
    ])
  </div>

  {{-- Conteúdo --}}
  <div class="gc-card">
    <div class="gc-body">
      <div style="font-weight:600; margin-bottom:8px">
        Impugnações do Concurso #{{ $concurso->id }}
        @if($concurso->titulo) — <span class="muted">{{ $concurso->titulo }}</span>@endif
      </div>

      <form method="GET" class="filters">
        <input class="input" type="text" name="q"
               placeholder="Buscar por nome, e-mail, CPF, texto…"
               value="{{ $q }}">
        <select name="situacao" class="input">
          <option value="">Todas as situações</option>
          <option value="pendente"  @selected($situacao==='pendente')>Pendente</option>
          <option value="deferido"  @selected($situacao==='deferido')>Deferido</option>
          <option value="indeferido"@selected($situacao==='indeferido')>Indeferido</option>
        </select>
        <button class="btn primary" type="submit">Filtrar</button>
      </form>

      <div class="x-scroll">
        <table class="table">
          <thead>
            <tr>
              <th class="w-id">ID</th>
              <th>Candidato/Requerente</th>
              <th>Data Envio</th>
              <th>Situação</th>
              <th>Data Resposta</th>
              <th style="width:120px">Ações</th>
            </tr>
          </thead>
          <tbody>
            @forelse($impugnacoes as $i)
              @php
                $sit    = strtolower((string)($i->situacao ?? 'pendente'));
                $dtResp = data_get($i,'respondido_em') ?? data_get($i,'responded_at');
              @endphp
              <tr>
                <td>{{ $i->id }}</td>
                <td>
                  <a href="{{ route('admin.concursos.impugnacoes.edit', [$concurso, $i->id]) }}"
                     style="font-weight:600; text-decoration:underline; text-underline-offset:2px">
                    {{ $i->nome ?? '—' }}
                  </a>
                  <div class="muted" style="font-size:12px">
                    {{ $i->email ?? '' }} @if(!empty($i->cpf)) • CPF: {{ $i->cpf }} @endif
                  </div>
                </td>
                <td>{{ optional($i->created_at)->format('d/m/Y H:i') }}</td>
                <td class="capitalize">
                  <span class="chip">{{ $sit ?: 'pendente' }}</span>
                </td>
                <td>
                  {{ $dtResp ? \Carbon\Carbon::parse($dtResp)->format('d/m/Y H:i') : '—' }}
                </td>
                <td>
                  <a class="btn" href="{{ route('admin.concursos.impugnacoes.edit', [$concurso, $i->id]) }}">
                    Analisar
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="muted">Nenhuma impugnação encontrada.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div style="margin-top:12px">
        {{ $impugnacoes->appends(['q'=>$q,'situacao'=>$situacao])->links() }}
      </div>
    </div>
  </div>
</div>
@endsection
