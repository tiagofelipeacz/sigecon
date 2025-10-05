@extends('layouts.sigecon')
@section('title', 'Cidades de Prova - SIGECON')

@php
  // Espera-se do controller: $concurso, $rows (Paginator), $q, $uf, $ufs
  $q  = $q  ?? request('q', '');
  $uf = $uf ?? request('uf', '');
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

  .muted{ color:#6b7280; }
  .w-uf{ width:80px; }
  .w-acoes{ width:160px; white-space:nowrap; }
</style>

<div class="gc-page">
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'cidades'
    ])
  </div>

  <div class="grid" style="gap:14px">
    <!-- Filtros -->
    <div class="gc-card">
      <div class="gc-body">
        <form method="get" action="{{ route('admin.concursos.cidades.index', $concurso) }}" class="toolbar">
          <div class="grow">
            <div style="position:relative">
              <i data-lucide="search" style="position:absolute; left:10px; top:8px; width:18px; height:18px; color:#6b7280"></i>
              <input type="text" name="q" class="input" placeholder="Buscar por cidade ou nome..."
                     value="{{ $q }}" style="padding-left:34px;">
            </div>
          </div>

          <div>
            <select name="uf" class="input">
              @foreach($ufs as $k=>$v)
                <option value="{{ $k }}" @selected($uf===$k)>{{ $v===''?'UF':$v }}</option>
              @endforeach
            </select>
          </div>

          <button class="btn" type="submit"><i data-lucide="sliders-horizontal"></i> Filtrar</button>

          <a class="btn primary" href="{{ route('admin.concursos.cidades.create', $concurso) }}">
            <i data-lucide="plus"></i> Nova cidade
          </a>
        </form>
      </div>
    </div>

    <!-- Tabela -->
    <div class="gc-card">
      <div class="gc-body x-scroll">
        <table>
          <thead>
          <tr>
            <th>Cidade</th>
            <th class="w-uf">UF</th>
            <th>Cargos vinculados</th>
            <th class="w-acoes">Ações</th>
          </tr>
          </thead>
          <tbody>
          @forelse($rows as $r)
            <tr>
              <td>{{ $r->cidade ?? '—' }}</td>
              <td>{{ $r->uf ?? '—' }}</td>
              <td class="muted">{{ $r->cargos_lista ?: '—' }}</td>
              <td class="w-acoes">
                <a class="btn" href="{{ route('admin.concursos.cidades.edit', [$concurso, $r->id]) }}">
                  <i data-lucide="edit-3"></i> Editar
                </a>
                <form method="post" action="{{ route('admin.concursos.cidades.destroy', [$concurso, $r->id]) }}" style="display:inline">
                  @csrf
                  @method('DELETE')
                  <button class="btn" type="submit" onclick="return confirm('Remover esta cidade?')">
                    <i data-lucide="trash-2"></i> Remover
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="muted" style="text-align:center; padding:24px">Nenhuma cidade cadastrada.</td>
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
