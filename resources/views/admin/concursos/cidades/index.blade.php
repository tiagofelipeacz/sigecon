@extends('layouts.sigecon')
@section('title', 'Cidades de Prova - SIGECON')

@php
  $ufs = $ufs ?? [
    ''=>'UF','AC'=>'AC','AL'=>'AL','AM'=>'AM','AP'=>'AP','BA'=>'BA','CE'=>'CE','DF'=>'DF','ES'=>'ES',
    'GO'=>'GO','MA'=>'MA','MG'=>'MG','MS'=>'MS','MT'=>'MT','PA'=>'PA','PB'=>'PB','PE'=>'PE',
    'PI'=>'PI','PR'=>'PR','RJ'=>'RJ','RN'=>'RN','RO'=>'RO','RR'=>'RR','RS'=>'RS','SC'=>'SC',
    'SE'=>'SE','SP'=>'SP','TO'=>'TO',
  ];
@endphp

@section('content')
<style>
  .gc-page{display:grid;grid-template-columns:260px 1fr;gap:16px}
  .gc-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.03)}
  .gc-body{padding:14px}
  .muted{color:#6b7280}
  .btn{display:inline-flex;align-items:center;gap:6px;border:1px solid #e5e7eb;padding:8px 10px;border-radius:8px;text-decoration:none;cursor:pointer}
  .btn:hover{background:#f9fafb}.btn.primary{background:#111827;color:#fff;border-color:#111827}
  .table{width:100%;border-collapse:collapse}
  .table thead th{ text-align:left; font-size:12px; color:#6b7280; padding:8px; border-bottom:1px solid #e5e7eb; }
  .table tbody td{ padding:8px; border-bottom:1px solid #f3f4f6; font-size:14px; vertical-align: top; }
  .chip{ background:#eef2ff; color:#3730a3; padding:3px 8px; border-radius:999px; font-size:12px; }
  .input{width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:8px}
</style>

<div class="gc-page">
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'cidades'
    ])
  </div>

  <div class="gc-card">
    <div class="gc-body">
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:end">
        <div style="font-weight:600">Cidades de Prova</div>
        <a class="btn primary" href="{{ route('admin.concursos.cidades.create', $concurso) }}">
          <i data-lucide="plus"></i> Nova Cidade
        </a>
      </div>

      <form class="mt-2" method="get" action="{{ route('admin.concursos.cidades.index', $concurso) }}">
        <div style="display:grid;grid-template-columns:1fr 120px 100px;gap:10px">
          <input class="input" type="text" name="q" placeholder="Buscar por cidade"
                 value="{{ $q }}">
          <select class="input" name="uf">
            @foreach($ufs as $k=>$v)
              <option value="{{ $k }}" @selected($uf===$k)>{{ $v===''?'UF':$v }}</option>
            @endforeach
          </select>
          <button class="btn" type="submit"><i data-lucide="search"></i> Filtrar</button>
        </div>
      </form>

      <div class="mt-2" style="overflow-x:auto">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Cidade</th>
              <th style="width:100px">UF</th>
              <th>Cargos vinculados</th>
              <th style="width:160px">Ações</th>
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $r)
              <tr>
                <td>{{ $r->cidade ?? '-' }}</td>
                <td>{{ $r->uf ?: '-' }}</td>
                <td>
                  @if($r->cargos_lista)
                    {!! collect(explode(',', $r->cargos_lista))->map(fn($t)=>'<span class="chip">'.trim($t).'</span>')->implode(' ') !!}
                  @else
                    <span class="muted">-</span>
                  @endif
                </td>
                <td>
                  <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <a class="btn" href="{{ route('admin.concursos.cidades.edit', [$concurso, $r->id]) }}">
                      <i data-lucide="pencil"></i> Editar
                    </a>
                    <form method="post" action="{{ route('admin.concursos.cidades.destroy', [$concurso, $r->id]) }}"
                          onsubmit="return confirm('Remover esta cidade e seus vínculos de cargos?')">
                      @csrf @method('delete')
                      <button class="btn" type="submit"><i data-lucide="trash-2"></i> Remover</button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="muted">Nenhuma cidade cadastrada.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-2">
        {{ $rows->withQueryString()->links() }}
      </div>
    </div>
  </div>
</div>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>document.addEventListener('DOMContentLoaded',()=>window.lucide?.createIcons());</script>
@endsection
