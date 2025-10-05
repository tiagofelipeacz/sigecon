@extends('layouts.sigecon')
@section('title', 'Inscritos - SIGECON')

@php
  // Esperado: $concurso, $inscricoes (LengthAwarePaginator ou Collection)
  use Illuminate\Support\Str;

  $lista = $inscricoes ?? collect();

  // Se vier Collection simples, simulamos dados mínimos pra não quebrar a view
  $isPaginator = $lista instanceof \Illuminate\Pagination\LengthAwarePaginator;
  $total   = $isPaginator ? $lista->total()     : $lista->count();
  $perPage = (int) request('per_page', $isPaginator ? $lista->perPage() : 10);
  $page    = (int) request('page', $isPaginator ? $lista->currentPage() : 1);
  $last    = (int) ($isPaginator ? $lista->lastPage() : ceil(max($total,1)/max($perPage,1)));
  $firstItem = $isPaginator ? $lista->firstItem() : (($page-1)*$perPage + 1);
  $lastItem  = $isPaginator ? $lista->lastItem()  : min($page*$perPage, $total);

  // Função helper pra pegar valores com fallback
  $get = function($row, $keys, $default = null) {
    foreach ((array)$keys as $k) {
      if (isset($row->{$k}) && $row->{$k} !== '' && $row->{$k} !== null) return $row->{$k};
    }
    return $default;
  };

  // Mantém querystring ao navegar
  $qs = request()->except(['page']);
  $gotoUrl = fn($p) => route('admin.concursos.inscritos.index', [$concurso]) . '?' . http_build_query(array_merge($qs, ['page'=>$p, 'per_page'=>$perPage]));

@endphp

@section('content')
<style>
  .gc-page   { display:grid; grid-template-columns: 260px 1fr; gap:16px; }
  .gc-card   { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body   { padding:14px; }
  .muted     { color:#6b7280; font-size:12px; }

  .table { width:100%; border-collapse: separate; border-spacing:0; }
  .table thead th{
    text-align:left; font-size:12px; color:#6b7280; padding:10px 12px; border-bottom:1px solid #e5e7eb;
  }
  .table tbody td{
    padding:12px; font-size:14px; vertical-align: top; border-bottom:1px solid #f3f4f6;
  }
  .table tbody tr:nth-child(odd){ background:#fafafa; }
  .table tbody tr:hover{ background:#f5f7fb; }

  .chip{ display:inline-flex; align-items:center; gap:6px; border-radius:999px; font-size:12px; padding:4px 10px; border:1px solid #e5e7eb; background:#fff; }
  .chip.ok{ background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
  .chip.warn{ background:#fff7ed; color:#9a3412; border-color:#fed7aa; }
  .chip.danger{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
  .chip.neutral{ background:#f3f4f6; color:#1f2937; border-color:#e5e7eb; }

  .actions{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; text-decoration:none; cursor:pointer; background:#fff; }
  .btn:hover{ background:#f9fafb; }
  .btn.icon{ padding:8px; }

  .form-row{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
  .input{ border:1px solid #e5e7eb; border-radius:8px; padding:8px 10px; }
  .input.w-80{ width:80px; }
  .input.w-140{ width:140px; }
  .input.w-200{ width:200px; }

  .subline{ color:#6b7280; font-size:12px; margin-top:2px; }

  .sticky-foot{ display:flex; justify-content:space-between; align-items:center; gap:12px; padding-top:10px; }
  .pager{ display:flex; align-items:center; gap:8px; }
</style>

<div class="gc-page">
  {{-- Lateral --}}
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'inscricoes'
    ])
  </div>

  {{-- Conteúdo --}}
  <div class="gc-card">
    <div class="gc-body">

      {{-- Filtros simples (opcional, não aparece no print, mas útil) --}}
      <form method="get" class="form-row" action="{{ route('admin.concursos.inscritos.index', [$concurso]) }}">
        <input type="text" name="q" class="input w-200" placeholder="Buscar por nome ou nº"
               value="{{ request('q','') }}">
        <input type="number" name="per_page" class="input w-80" min="5" step="5" value="{{ $perPage }}" title="Por página">
        <button class="btn" type="submit">Aplicar</button>
        @if(request()->hasAny(['q']))
          <a class="btn" href="{{ route('admin.concursos.inscritos.index', [$concurso]) }}">Limpar</a>
        @endif
      </form>

      <div style="height:10px"></div>

      {{-- Tabela --}}
      <table class="table">
        <thead>
          <tr>
            <th style="width:34px"><input id="chkAll" type="checkbox" onclick="toggleAll(this)"></th>
            <th style="width:120px">Inscrição</th>
            <th>Nome Inscrição</th>
            <th style="width:140px">Nascimento</th>
            <th>Vaga</th>
            <th style="width:180px">Data de Inscrição {!! request('sort')==='created_at_desc' ? '▼' : '' !!}</th>
            <th style="width:120px">Situação</th>
          </tr>
        </thead>
        <tbody>
          @forelse($lista as $row)
            @php
              $numero = $get($row, ['numero_inscricao','numero','inscricao','sequencial','id'], '—');
              $nome   = Str::upper($get($row, ['nome_inscricao','nome','candidato_nome','pessoa_nome'], '—'));
              $nasc   = optional($get($row, ['data_nascimento','nascimento']))?->format('d/m/Y') ?? '—';

              $vaga   = $get($row, ['cargo_nome','vaga','cargo'], '—');
              $local  = $get($row, ['local_nome','localidade','cidade'], null);

              $dt     = optional($get($row, ['data_inscricao','created_at']))?->format('d/m/Y H:i') ?? '—';

              // Badge / situação
              $sitRaw = Str::lower((string)$get($row, ['situacao','status','pagamento_status','pagto_situacao'], ''));
              $gratuita = (bool)$get($row, ['gratuita','isento','isencao'], false) || (float)$get($row, ['valor_pago','taxa'], 0) == 0.0;

              $badgeTxt = $gratuita ? 'Gratuita' :
                          ($sitRaw==='pago' || $sitRaw==='paga' ? 'Pago' :
                          ($sitRaw==='pendente' || $sitRaw==='' ? 'Pendente' : ucfirst($sitRaw)));

              $badgeCls = $gratuita ? 'ok' :
                          (in_array($sitRaw, ['pago','paga']) ? 'ok' :
                          (in_array($sitRaw, ['pendente','aguardando','em_aberto']) ? 'warn' :
                          (in_array($sitRaw, ['cancelado','recusado']) ? 'danger' : 'neutral')));
            @endphp
            <tr>
              <td><input type="checkbox" name="sel[]" value="{{ $numero }}"></td>
              <td>{{ $numero }}</td>
              <td>{{ $nome }}</td>
              <td>{{ $nasc }}</td>
              <td>
                <div>{{ $vaga }}</div>
                @if($local)<div class="subline">{{ $local }}</div>@endif
              </td>
              <td>{{ $dt }}</td>
              <td><span class="chip {{ $badgeCls }}">{{ $badgeTxt }}</span></td>
            </tr>
          @empty
            <tr><td colspan="7" class="muted">Nenhum inscrito encontrado.</td></tr>
          @endforelse
        </tbody>
      </table>

      {{-- Rodapé de paginação no mesmo formato do print --}}
      <div class="sticky-foot">
        <form method="get" class="form-row" action="{{ route('admin.concursos.inscritos.index', [$concurso]) }}">
          @foreach(request()->except(['per_page','page']) as $k=>$v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
          @endforeach
          <label class="muted">Por página:</label>
          <input type="number" name="per_page" class="input w-80" min="5" step="5" value="{{ $perPage }}">
          <button class="btn" type="submit">OK</button>
        </form>

        <div class="muted">
          {{ $total ? ($firstItem . ' - ' . $lastItem . ' de ' . $total) : '0 de 0' }}
          ({{ $last }} {{ $last>1?'páginas':'página' }})
        </div>

        <div class="pager">
          <a class="btn icon" href="{{ $page>1 ? $gotoUrl($page-1) : 'javascript:void(0)' }}"
             style="{{ $page>1 ? '' : 'pointer-events:none; opacity:.4' }}" aria-label="Anterior">
            &#8249;
          </a>

          <form method="get" action="{{ route('admin.concursos.inscritos.index', [$concurso]) }}" class="form-row">
            @foreach(request()->except(['page']) as $k=>$v)
              <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <label class="muted">Ir para página:</label>
            <input type="number" name="page" class="input w-80" value="{{ $page }}" min="1" max="{{ $last }}">
            <button class="btn" type="submit">Ir</button>
          </form>

          <a class="btn icon" href="{{ $page<$last ? $gotoUrl($page+1) : 'javascript:void(0)' }}"
             style="{{ $page<$last ? '' : 'pointer-events:none; opacity:.4' }}" aria-label="Próxima">
            &#8250;
          </a>
        </div>
      </div>

    </div>
  </div>
</div>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>
  document.addEventListener('DOMContentLoaded', () => {
    window.lucide?.createIcons();
  });

  function toggleAll(master){
    document.querySelectorAll('tbody input[type="checkbox"][name="sel[]"]').forEach(ch => ch.checked = master.checked);
  }
</script>
@endsection
