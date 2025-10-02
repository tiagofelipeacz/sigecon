{{-- resources/views/admin/concursos/visao_geral.blade.php --}}
@extends($layout)

@php
  // Garantias contra "undefined"
  $totais          = $totais          ?? ['total'=>0,'confirmadas'=>0,'pendentes'=>0,'porSituacao'=>[]];
  $series          = $series          ?? [];
  $porCargo        = $porCargo        ?? [];
  $porEscolaridade = $porEscolaridade ?? [];
  $porCidade       = $porCidade       ?? [];
  $pedidosIsencao  = $pedidosIsencao  ?? 0;

  // Para o menu lateral (usa o include do sidebar-min)
  // O partial aceita objeto simples; passamos id ao menos
  $concurso = (object) ['id' => $concursoId ?? null];
@endphp

@section('title', 'Visão Geral do Concurso')

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

  .gc-breadcrumb{ background:#fff7ed; border:1px solid #ffedd5; color:#92400e; border-radius:10px; padding:10px 12px; margin-bottom:10px; font-size:12px; }
  .text-muted{ color:#6b7280; }
  .mb-2{ margin-bottom:8px; }
  .mb-3{ margin-bottom:12px; }
</style>

<div class="gc-page">
  {{-- Lateral: menu do concurso --}}
  <div>
    @includeIf('admin.concursos.partials.sidebar-min', ['concurso' => $concurso])
  </div>

  {{-- Conteúdo principal --}}
  <div>
    {{-- faixa de aviso/breadcrumb simples --}}
    <div class="gc-breadcrumb">
      Você está em <b>Concurso #{{ $concursoId }}</b> &nbsp;•&nbsp;
      Esta visão é atualizada a cada poucos minutos.
    </div>

    {{-- KPI cards --}}
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

    {{-- Gráfico série + pizza situações --}}
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

    {{-- Tabelas: por cargo / por escolaridade --}}
    <div class="gc-row-3">
      <div class="gc-card"><div class="gc-body">
        <div class="mb-2" style="font-weight:600">Inscrições por Cargo</div>
        <table class="table table-sm">
          <thead><tr><th>Cargo</th><th style="width:120px">Qtd.</th></tr></thead>
          <tbody>
            @forelse($porCargo as $r)
              <tr><td>{{ $r['k'] }}</td><td>{{ number_format($r['v'],0,',','.') }}</td></tr>
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
              <tr><td>{{ $r['k'] }}</td><td>{{ number_format($r['v'],0,',','.') }}</td></tr>
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
            <tr><td>{{ $r['k'] }}</td><td>{{ number_format($r['v'],0,',','.') }}</td></tr>
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
  // Dados vindos do Controller
  const serieData   = @json($series);
  const sitLabels   = @json($situacaoLabels ?? array_keys($totais['porSituacao'] ?? []));
  const sitValues   = @json($situacaoValues ?? array_values($totais['porSituacao'] ?? []));

  // Série por data
  (function () {
    const el = document.getElementById('chartSerie');
    if (!el) return;
    const labels = serieData.map(r => r.d);
    const values = serieData.map(r => r.v);
    new Chart(el, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Inscrições',
          data: values,
          tension: .25,
          fill: true
        }]
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
    const labels = sitLabels;
    const values = sitValues;
    new Chart(el, {
      type: 'doughnut',
      data: { labels, datasets:[{ data: values }] },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
      }
    });
  })();
</script>
@endsection
