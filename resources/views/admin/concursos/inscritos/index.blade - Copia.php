@extends('layouts.sigecon')
@section('title', 'Inscritos - SIGECON')

@php
  $STATUS_LBL = [
    'rascunho' => 'Rascunho',
    'pendente_pagamento' => 'Pendente Pagamento',
    'confirmada' => 'Confirmada',
    'cancelada' => 'Cancelada',
    'importada' => 'Importada',
  ];
@endphp

@section('content')
<style>
  .gc-page{ display:grid; grid-template-columns:260px 1fr; gap:16px; }
  .gc-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body{ padding:14px; }
  .title{ font-weight:700; }
  .filters{ display:grid; grid-template-columns: 1fr 160px 160px 1fr; gap:10px; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px 10px; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; background:#fff; cursor:pointer; text-decoration:none;}
  .btn.primary{ background:#111827; border-color:#111827; color:#fff; }
  .table{ width:100%; border-collapse:collapse; }
  .table th{ text-align:left; font-size:12px; color:#6b7280; padding:8px; border-bottom:1px solid #e5e7eb; }
  .table td{ padding:8px; border-bottom:1px solid #f3f4f6; font-size:14px; }
  .chip{ display:inline-flex; align-items:center; gap:6px; border-radius:999px; font-size:12px; padding:2px 10px; border:1px solid #e5e7eb; background:#fff; }
  .chips{ display:flex; gap:8px; flex-wrap:wrap; }
</style>

<div class="gc-page">
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'inscricoes'
    ])
  </div>

  <div class="gc-card">
    <div class="gc-body">
      <div class="title mb-2">Inscritos</div>

      <div class="chips mb-2">
        @foreach ($STATUS as $st)
          <span class="chip">
            {{ $STATUS_LBL[$st] ?? ucfirst(str_replace('_',' ',$st)) }}:
            <strong>{{ (int)($statusCounts[$st] ?? 0) }}</strong>
          </span>
        @endforeach
      </div>

      <form method="get" class="filters mb-2">
        <input type="text" name="q" value="{{ $q }}" class="input" placeholder="Buscar por nome ou e-mail" />
        <select name="status" class="input">
          <option value="">— Status —</option>
          @foreach ($STATUS as $st)
            <option value="{{ $st }}" @selected($status===$st)>{{ $STATUS_LBL[$st] ?? ucfirst(str_replace('_',' ',$st)) }}</option>
          @endforeach
        </select>
        <select name="modalidade" class="input">
          <option value="">— Modalidade —</option>
          @foreach ($MODALIDADES as $md)
            <option value="{{ $md }}" @selected($modalidade===$md)>{{ strtoupper($md) }}</option>
          @endforeach
        </select>
        <div style="display:flex; gap:8px; justify-content:flex-end">
          <a class="btn" href="{{ route('admin.concursos.inscritos.import', $concurso) }}"><i data-lucide="upload"></i> Importar</a>
          <a class="btn" href="{{ route('admin.concursos.inscritos.create', $concurso) }}"><i data-lucide="plus"></i> Nova</a>
          <button class="btn primary" type="submit"><i data-lucide="search"></i> Filtrar</button>
        </div>
      </form>

      @if (session('ok'))
        <div class="chip" style="margin-bottom:10px">{{ session('ok') }}</div>
      @endif
      @if (session('import_errors'))
        <div class="chip" style="margin-bottom:10px; background:#fff7ed; border-color:#fed7aa">
          {{ count(session('import_errors')) }} aviso(s) — ver console/laravel.log para detalhes.
        </div>
      @endif

      <div class="x-scroll">
        <table class="table">
          <thead>
            <tr>
              <th style="width:80px">#</th>
              <th>Candidato</th>
              <th>Cargo</th>
              <th>Modalidade</th>
              <th>Status</th>
              <th style="width:160px">Criada em</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($inscricoes as $insc)
              <tr>
                <td>{{ $insc->id }}</td>
                <td>
                  <div style="font-weight:600">{{ $insc->candidato_nome ?? '—' }}</div>
                  <div style="font-size:12px; color:#6b7280">{{ $insc->candidato_email ?? '—' }}</div>
                </td>
                <td>{{ $insc->cargo_nome ?? '—' }}</td>
                <td>{{ strtoupper($insc->modalidade ?? '-') }}</td>
                <td>{{ $STATUS_LBL[$insc->status] ?? ucfirst(str_replace('_',' ',$insc->status ?? '-')) }}</td>
                <td>{{ optional($insc->created_at)->format('d/m/Y H:i') }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="muted">Nenhuma inscrição.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-2">
        {{ $inscricoes->links() }}
      </div>
    </div>
  </div>
</div>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>document.addEventListener('DOMContentLoaded',()=>window.lucide?.createIcons())</script>
@endsection
