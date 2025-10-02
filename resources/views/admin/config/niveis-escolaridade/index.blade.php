@extends('layouts.sigecon')
@section('title', 'Níveis de Escolaridade')

@section('content')
  <h1>Níveis de Escolaridade</h1>
  <p class="sub">Gerencie os níveis utilizados nas inscrições/relatórios.</p>

  {{-- Flash / Erros --}}
  @if (session('status'))
    <div class="mb-3 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">
      {{ session('status') }}
    </div>
  @endif
  @if ($errors->any())
    <div class="mb-3 rounded border border-red-300 bg-red-50 p-3 text-red-800">
      <div class="font-semibold mb-1">Corrija os erros abaixo:</div>
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="toolbar" style="display:flex; gap:.5rem; align-items:center; margin-bottom:10px;">
    <form method="get" class="flex" style="gap:.5rem; flex:1;">
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nome…" style="flex:1">

      {{-- Filtro opcional: somente ativos (se o controller ignorar, não quebra) --}}
      <label class="btn" style="display:inline-flex; align-items:center; gap:.4rem;">
        <input type="checkbox" name="somente_ativos" value="1" {{ request('somente_ativos') == '1' ? 'checked' : '' }}>
        Somente ativos
      </label>

      <button class="btn" type="submit">Buscar</button>
      <a class="btn" href="{{ route('admin.config.niveis-escolaridade.index') }}">Limpar</a>
    </form>

    <a class="btn primary" href="{{ route('admin.config.niveis-escolaridade.create') }}">+ Novo</a>
  </div>

  <style>
    .tbl-min{ width:100%; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
    .tbl-min table{ width:100%; border-collapse:collapse; }
    .tbl-min thead th{ background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; text-align:left; font-weight:600; padding:10px 12px; }
    .tbl-min tbody td{ border-top:1px solid #f1f5f9; padding:10px 12px; vertical-align:middle; }
    .pill{ display:inline-block; padding:.2rem .5rem; border-radius:999px; font-size:.8rem; }
    .pill.ok{ background:#e8faf0; color:#166534; border:1px solid #bbf7d0; }
    .pill.nok{ background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
  </style>

  <div class="tbl-min">
    <table>
      <thead>
        <tr>
          <th style="width:90px;">ID</th>
          <th>Nome</th>
          <th style="width:120px;">Ordem</th>
          <th style="width:120px;">Ativo</th>
          <th style="width:280px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($niveis as $n)
          <tr>
            <td>#{{ $n->id }}</td>
            <td>{{ $n->nome ?? $n->titulo ?? '—' }}</td>
            <td>{{ $n->ordem ?? 0 }}</td>
            <td>
              @if(!empty($n->ativo))
                <span class="pill ok">Sim</span>
              @else
                <span class="pill nok">Não</span>
              @endif
            </td>
            <td>
              <div class="toolbar" style="display:flex; gap:12px; flex-wrap:wrap;">
                <a class="btn" href="{{ route('admin.config.niveis-escolaridade.edit', $n->id) }}">Editar</a>

                <form method="POST" action="{{ route('admin.config.niveis-escolaridade.toggle-ativo', $n->id) }}">
                  @csrf @method('PATCH')
                  <button class="btn" type="submit">
                    {{ !empty($n->ativo) ? 'Desativar' : 'Ativar' }}
                  </button>
                </form>

                @if(Route::has('admin.config.niveis-escolaridade.destroy'))
                  <form method="POST"
                        action="{{ route('admin.config.niveis-escolaridade.destroy', $n->id) }}"
                        onsubmit="return confirm('Tem certeza que deseja excluir {{ addslashes($n->nome ?? $n->titulo ?? 'este nível') }}?\nEsta ação não pode ser desfeita.');">
                    @csrf @method('DELETE')
                    <button class="btn" type="submit">Excluir</button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5">Nenhum registro encontrado.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="pagination" style="margin-top:12px;">
    {{ method_exists($niveis, 'links') ? $niveis->links() : '' }}
  </div>
@endsection
