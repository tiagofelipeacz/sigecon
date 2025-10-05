@extends('layouts.sigecon')
@section('title', 'Impugnações do Edital - SIGECON')

@php
  use Illuminate\Support\Carbon;

  // Espera-se que o controller envie:
  // $concurso, $rows (Paginator de impugnações), $q (busca), $situacao (filtro)
  $q        = $q        ?? request('q', '');
  $situacao = $situacao ?? request('situacao', '');
  $opts = [
    ''           => 'Todas',
    'pendente'   => 'Pendente',
    'deferido'   => 'Deferido',
    'indeferido' => 'Indeferido',
  ];
@endphp

@section('content')
<style>
  .gc-page{ display:grid; grid-template-columns:260px 1fr; gap:16px; }
  .gc-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body{ padding:14px; }

  .toolbar{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
  .toolbar .grow{ flex:1 1 320px; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:8px 11px; font-size:13px; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:7px 10px; border-radius:8px; background:#fff; cursor:pointer; text-decoration:none; font-size:13px; }
  .btn.primary{ background:#111827; border-color:#111827; color:#fff; }
  .btn.icon{ padding:8px; width:36px; justify-content:center; }

  .x-scroll{ overflow-x:auto; }
  table{ width:100%; border-collapse:collapse; }
  th{ font-size:12px; color:#6b7280; text-align:left; padding:9px 10px; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
  td{ font-size:14px; padding:12px 10px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
  tr:hover td{ background:#fcfcfd; }

  .chip{ display:inline-flex; align-items:center; gap:6px; border-radius:999px; font-size:12px; padding:2px 10px; border:1px solid #e5e7eb; background:#fff; }
  .chip.pendente{ background:#fff7ed; color:#9a3412; border-color:#fed7aa; }
  .chip.deferido{ background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
  .chip.indeferido{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }

  .w-id{ width:72px; }
  .w-data{ width:170px; }
  .w-sit{ width:120px; }
  .w-acoes{ width:120px; }
</style>

<div class="gc-page">
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'impugnacoes'
    ])
  </div>

  <div class="grid" style="gap:14px">

    <!-- Filtros -->
    <div class="gc-card">
      <div class="gc-body">
        <form method="get" action="{{ route('admin.concursos.impugnacoes.index', $concurso) }}" class="toolbar">
          <div class="grow">
            <div style="position:relative">
              <i data-lucide="search" style="position:absolute; left:10px; top:8px; width:18px; height:18px; color:#6b7280"></i>
              <input type="text" name="q" class="input" placeholder="Buscar por nome, CPF, e-mail..."
                     value="{{ $q }}" style="padding-left:34px;">
            </div>
          </div>

          <div>
            <select name="situacao" class="input">
              @foreach($opts as $k=>$lbl)
                <option value="{{ $k }}" @selected($situacao===$k)>{{ $lbl }}</option>
              @endforeach
            </select>
          </div>

          <button class="btn" type="submit"><i data-lucide="sliders-horizontal"></i> Filtrar</button>
        </form>
      </div>
    </div>

    <!-- Listagem -->
    <div class="gc-card">
      <div class="gc-body x-scroll">
        <table>
          <thead>
          <tr>
            <th class="w-id">ID</th>
            <th>Candidato/Requerente</th>
            <th class="w-data">Data Envio</th>
            <th class="w-sit">Situação</th>
            <th class="w-data">Data Resposta</th>
            <th class="w-acoes">Ações</th>
          </tr>
          </thead>
          <tbody>
          @forelse($rows as $r)
            @php
              $sit = strtolower((string)($r->situacao ?? $r->status ?? 'pendente'));
              $chipClass = in_array($sit, ['deferido','indeferido','pendente']) ? $sit : 'pendente';
              $enviadoEm = $r->created_at ?? $r->data_envio ?? null;
              $respEm    = $r->respondido_em ?? $r->data_resposta ?? $r->updated_at ?? null;
            @endphp
            <tr>
              <td>{{ $r->id }}</td>
              <td>
                <a href="{{ route('admin.concursos.impugnacoes.edit', [$concurso, $r->id]) }}"
                   style="font-weight:600; text-decoration:underline; text-underline-offset:2px">
                  {{ $r->nome ?? $r->candidato_nome ?? $r->requerente ?? '—' }}
                </a>
                @if(($r->cpf ?? '') !== '')
                  <div class="muted" style="font-size:12px">{{ $r->cpf }}</div>
                @endif
              </td>
              <td>{{ $enviadoEm ? \Illuminate\Support\Carbon::parse($enviadoEm)->format('d/m/Y H:i') : '—' }}</td>
              <td>
                <span class="chip {{ $chipClass }}">
                  {{ ucfirst($sit) }}
                </span>
              </td>
              <td>{{ $respEm ? \Illuminate\Support\Carbon::parse($respEm)->format('d/m/Y H:i') : '—' }}</td>
              <td>
                <a class="btn" href="{{ route('admin.concursos.impugnacoes.edit', [$concurso, $r->id]) }}">
                  <i data-lucide="edit-3"></i> Editar
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="muted" style="text-align:center; padding:24px">Nenhuma impugnação encontrada.</td>
            </tr>
          @endforelse
          </tbody>
        </table>

        <div style="margin-top:12px">
          {{ $rows->withQueryString()->onEachSide(2)->links() }}
        </div>
      </div>
    </div>

  </div>
</div>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>document.addEventListener('DOMContentLoaded',()=>window.lucide?.createIcons())</script>
@endsection
