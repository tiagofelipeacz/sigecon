@extends('layouts.sigecon')
@section('title', 'Inscrições do Candidato - SIGECON')

@section('content')
<h1>Inscrições de {{ $candidato->nome }}</h1>
<p class="sub">Últimas inscrições realizadas por este candidato.</p>

<style>
  .tbl-min{ width:100%; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
  .tbl-min table{ width:100%; border-collapse:collapse; }
  .tbl-min thead th{ background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; text-align:left; font-weight:600; padding:10px 12px; }
  .tbl-min tbody td{ border-top:1px solid #f1f5f9; padding:10px 12px; vertical-align:middle; }
  .pill{ display:inline-block; padding:.15rem .5rem; border-radius:999px; font-size:.8rem; border:1px solid transparent; }
  .pill.ok{ background:#e8faf0; color:#166534; border-color:#bbf7d0; }
  .pill.nok{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
</style>

<div class="tbl-min">
  <table>
    <thead>
      <tr>
        <th style="width:90px">#</th>
        <th>Concurso</th>
        <th>Cargo</th>
        <th style="width:160px">Status</th>
        <th style="width:180px">Criado em</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        @php
          $ok = in_array(strtolower((string)$r->status), ['pago','aprovado','confirmado','valida','válida']);
        @endphp
        <tr>
          <td>#{{ $r->id }}</td>
          <td>{{ $r->concurso ?? '—' }}</td>
          <td>{{ $r->cargo ?? '—' }}</td>
          <td>{!! $ok ? '<span class="pill ok">'.e($r->status).'</span>' : '<span class="pill nok">'.e($r->status).'</span>' !!}</td>
          <td>{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d/m/Y H:i') }}</td>
        </tr>
      @empty
        <tr><td colspan="5">Nenhuma inscrição encontrada.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="toolbar" style="margin-top:12px; display:flex; gap:.5rem;">
  <a class="btn" href="{{ route('admin.candidatos.index') }}">Voltar</a>
</div>
@endsection
