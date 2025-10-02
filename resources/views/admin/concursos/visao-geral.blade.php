{{-- resources/views/admin/concursos/visao-geral.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Visão Geral do Concurso - SIGECON')

@php
  // Garantias contra "undefined"
  $totais          = $totais          ?? ['total'=>0,'confirmadas'=>0,'pendentes'=>0,'porSituacao'=>[]];
  $series          = $series          ?? [];
  $porCargo        = $porCargo        ?? [];
  $porEscolaridade = $porEscolaridade ?? [];
  $porCidade       = $porCidade       ?? [];
  $pedidosIsencao  = $pedidosIsencao  ?? 0;

  // Objeto concurso mínimo para o menu
  $concurso = $concurso ?? (object) [];
  $cid = $concursoId ?? ($concurso->id ?? null);

  // Situações: usa $totais['porSituacao'] se vier, senão combina labels/values do controller
  $sitObj = $totais['porSituacao'] ?? [];
  if (empty($sitObj) && !empty($situacaoLabels ?? []) && !empty($situacaoValues ?? [])) {
      try { $sitObj = array_combine($situacaoLabels, $situacaoValues) ?: []; } catch (\Throwable $e) { $sitObj = []; }
  }

  // Helper para imprimir k/v independentemente do formato (array/obj)
  $resolveKV = function($row, $fallbackK = '—') {
      // Valor (qtd)
      $v = 0;
      if (is_array($row)) {
          $v = $row['v'] ?? ($row['total'] ?? 0);
      } elseif (is_object($row)) {
          $v = $row->total ?? 0;
      }

      // Chave (rótulo)
      $k = $fallbackK;
      if (is_array($row)) {
          $k = $row['k'] ?? ($row['cargo'] ?? ($row['nivel'] ?? ($row['cidade'] ?? $fallbackK)));
      } elseif (is_object($row)) {
          if (isset($row->cargo))        $k = $row->cargo;
          elseif (isset($row->nivel))    $k = $row->nivel;
          elseif (isset($row->cidade))   $k = isset($row->uf) ? ($row->cidade.' - '.$row->uf) : $row->cidade;
          elseif (isset($row->codigo))   $k = $row->codigo;
      }

      return [$k, (int)$v];
  };
@endphp

@section('content')
<style>
  .gc-page   { display:grid; grid-template-columns: 260px 1fr; gap:16px; }
  .gc-card   { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body   { padding:14px; }
  .gc-kpis   { display:grid; grid-template-columns: repeat(4, 1fr); gap:14px; }
  .gc-kpi .label{ color:#6b7280; font-size:12px; }
  .gc-kpi .val  { font-size:26px; font-weight:700; }

  .gc-row-2 { display:grid; grid-template-columns: 2fr 1fr; gap:14px; margin-top:14px; }
  .gc-row-3 { display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-top:14px; }

  .table { width:100%; border-collapse: collapse; }
  .table thead th{ text-align:left; font-size:12px; color:#6b7280; padding:8px; border-bottom:1px solid #e5e7eb; }
  .table tbody td{ padding:8px; border-bottom:1px solid #f3f4f6; font-size:14px; }
  .table-sm thead th, .table-sm tbody td{ padding:6px 8px; font-size:13px; }

  .text-muted{ color:#6b7280; }
  .mb-2{ margin-bottom:8px; }
</style>

<div class="gc-page">
  {{-- Lateral: teu menu (forçando "Visão Geral" ativa) --}}
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'root',
    ])
  </div>

  {{-- Conteúdo principal --}}
  <div>
    {{-- (Sem a faixa "Você está em...") --}}

    {{-- KPIs --}}
    <div class="gc-kpis">
      <div class="gc-card gc-kpi"><div class="gc-body">
        <div class="label">Total de Inscrições</div>
        <div class="val">{{ number_format($totais['total'] ?? 0, 0, ',', '.') }}</div>
      </div></div>

      <div class="gc-card gc-kpi"><div class="gc-body">
        <div class="label">Confirmadas</div>
        <div class="val">{{ number_format($totais['confirmadas'] ?? 0, 0, ',', '.') }}</div>
      </div></div>

      <div class="gc-card gc-kpi"><div class="gc-body">
        <div class="label">Pendentes</div>
        <div class="val">{{ number_format($totais['pendentes'] ?? 0, 0, ',', '.') }}</div>
      </div></div>

      <div class="gc-card gc-kpi"><div class="gc-body">
        <div class="label">Pedidos de Isenção</div>
        <div class="val">{{ number_format($pedidosIsencao, 0, ',', '.') }}</div>
      </div></div>
    </div>

    {{-- Gráficos --}}
    <div class="gc-row-2">
      <div class="gc-card"><div class="gc-body">
        <div class="mb-2" style="font-weight:600">Inscrições por Data</div>
        <canvas id="chartSerie" height="120"></canvas>
      </div></div>

      <div class="gc-card"><div class="gc-body">
        <div class="mb-2" style="font-weight:600">Inscrições por Situação</div>
        <canvas id="chartSituacao" height="120"></canvas>
      </div></div>
    </div>

    {{-- Tabelas: por cargo / escolaridade --}}
    <div class="gc-row-3">
      <div class="gc-card"><div class="gc-body">
        <div class="mb-2" style="font-weight:600">Inscrições por Cargo</div>
        <table class="table table-sm">
          <thead><tr><th>Cargo</th><th style="width:120px">Qtd.</th></tr></thead>
          <tbody>
            @forelse($porCargo as $r)
              @php $kv = $resolveKV($r, 'Cargo'); @endphp
              <tr><td>{{ $kv[0] }}</td><td>{{ number_format($kv[1],0,',','.') }}</td></tr>
            @empty
              <tr><td colspan="2" class="text-muted">Sem dados</td></tr>
            @endforelse
          </tbody>
        </table>
      </div></div>

      <div class="gc-card"><div class="gc-body">
        <div class="mb-2" style="font-weight:600">Inscrições por Nível de Escolaridade</div>
        <table class="table table-sm">
          <thead><tr><th>Nível</th><th style="width:120px">Qtd.</th></tr></thead>
          <tbody>
            @forelse($porEscolaridade as $r)
              @php $kv = $resolveKV($r, 'Nível'); @endphp
              <tr><td>{{ $kv[0] }}</td><td>{{ number_format($kv[1],0,',','.') }}</td></tr>
            @empty
              <tr><td colspan="2" class="text-muted">Sem dados</td></tr>
            @endforelse
          </tbody>
        </table>
      </div></div>
    </div>

    {{-- Tabela: por cidade --}}
    <div class="gc-card" style="margin-top:14px"><div class="gc-body">
      <div class="mb-2" style="font-weight:600">Inscrições por Cidade</div>
      <table class="table table-sm">
        <thead><tr><th>Cidade</th><th style="width:120px">Qtd.</th></tr></thead>
        <tbody>
          @forelse($porCidade as $r)
            @php $kv = $resolveKV($r, 'Cidade'); @endphp
            <tr><td>{{ $kv[0] }}</td><td>{{ number_format($kv[1],0,',','.') }}</td></tr>
          @empty
            <tr><td colspan="2" class="text-muted">Sem dados</td></tr>
          @endforelse
        </tbody>
      </table>
    </div></div>
  </div>
</div>

{{-- Chart.js (CDN) --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  // Dados do controller
  const serieData  = @json($series);
  // Garantia de objeto para evitar parse de '{}' errado
  const sitDataObj = @json((object)$sitObj);

  // Série por data (aceita 'date/total' ou 'd/v')
  (function () {
    const el = document.getElementById('chartSerie');
    if (!el) return;
    const labels = (serieData || []).map(r => r.date ?? r.d);
    const values = (serieData || []).map(r => (r.total ?? r.v ?? 0));
    new Chart(el, {
      type: 'line',
      data: {
        labels,
        datasets: [{ label: 'Inscrições', data: values, tension: .25, fill: true }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: {
          x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
          y: { beginAtZero: true }
        }
      }
    });
  })();

  // Pizza por situação
  (function () {
    const el = document.getElementById('chartSituacao');
    if (!el) return;
    const labels = Object.keys(sitDataObj);
    const values = Object.values(sitDataObj);
    new Chart(el, {
      type: 'doughnut',
      data: { labels, datasets:[{ data: values }] },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
  })();
</script>
@endsection
