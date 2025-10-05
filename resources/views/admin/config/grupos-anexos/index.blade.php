@extends('layouts.sigecon')
@section('title', 'Grupo de Anexos - SIGECON')

@section('content')
<style>
  .gc-page{ display:grid; grid-template-columns: 1fr; gap:16px; }
  .gc-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body{ padding:14px; }
  .toolbar{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
  .toolbar .grow{ flex:1 1 280px; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:8px 11px; font-size:13px; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:7px 10px; border-radius:8px; background:#fff; cursor:pointer; text-decoration:none; font-size:13px; }
  .btn:hover{ background:#f9fafb; }
  .btn.primary{ background:#111827; border-color:#111827; color:#fff; }
  .btn.sm{ padding:5px 8px; font-size:12px; border-radius:7px; }
  .table{ width:100%; border-collapse:collapse; }
  .table th{ font-size:12px; color:#6b7280; text-align:left; padding:9px 10px; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
  .table td{ font-size:14px; padding:12px 10px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
</style>

<div class="gc-page">
  <div class="gc-card">
    <div class="gc-body">
      <form method="get" class="toolbar">
        <a href="{{ route('admin.config.grupos-anexos.create') }}" class="btn primary">+ Novo grupo</a>

        <div class="grow" style="margin-left:6px">
          <input type="text" class="input" name="q" placeholder="Buscar por nome"
                 value="{{ $q ?? '' }}">
        </div>

        <div>
          <select name="ativo" class="input">
            <option value="">Ativo: Todos</option>
            <option value="1" @selected(($ativo ?? '')==='1')>Somente ativos</option>
            <option value="0" @selected(($ativo ?? '')==='0')>Somente inativos</option>
          </select>
        </div>

        <button class="btn" type="submit">Filtrar</button>
      </form>
    </div>
  </div>

  <div class="gc-card">
    <div class="gc-body" style="overflow-x:auto">
      <table class="table">
        <thead>
          <tr>
            <th style="width:80px">ID</th>
            <th>Nome</th>
            <th style="width:90px">Ordem</th>
            <th style="width:100px">Ativo</th>
            <th style="width:110px">Usos</th>
            <th style="width:200px">Ações</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
          <tr>
            <td>{{ $r->id }}</td>
            <td>{{ $r->nome }}</td>
            <td>{{ (int)($r->ordem ?? 0) }}</td>
            <td>
              <form method="post" action="{{ route('admin.config.grupos-anexos.toggle', $r) }}">
                @csrf @method('patch')
                <button class="btn sm" type="submit" title="Alternar ativo">
                  {{ (int)$r->ativo === 1 ? 'Ativo' : 'Inativo' }}
                </button>
              </form>
            </td>
            <td>{{ (int)($r->usos ?? 0) }}</td>
            <td>
              <div style="display:flex; gap:6px; flex-wrap:wrap">
                <a class="btn sm" href="{{ route('admin.config.grupos-anexos.edit', $r) }}">Editar</a>
                <form method="post" action="{{ route('admin.config.grupos-anexos.destroy', $r) }}"
                      onsubmit="return confirm('Remover este grupo?')">
                  @csrf @method('delete')
                  <button class="btn sm" style="border-color:#fecaca; background:#fee2e2; color:#991b1b">
                    Remover
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align:center; padding:20px; color:#6b7280">
              Nenhum grupo encontrado.
            </td>
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
@endsection
