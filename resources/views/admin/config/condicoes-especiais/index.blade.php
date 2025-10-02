@extends('layouts.sigecon')
@section('title', 'Tipos de Condições Especiais')

@section('content')
  <h1>Tipos de Condições Especiais</h1>
  <p class="sub">Gerencie as opções exibidas ao candidato.</p>

  {{-- Flash / Erros --}}
  @if (session('success'))
    <div class="mb-3 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">
      {{ session('success') }}
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
      <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por título…" style="flex:1">
      <button class="btn" type="submit">Buscar</button>
      <a class="btn" href="{{ route('admin.config.condicoes_especiais.index') }}">Limpar</a>
    </form>

    <a class="btn primary" href="{{ route('admin.config.condicoes_especiais.create') }}">+ Novo</a>
  </div>

  <style>
    .tbl-min{ width:100%; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
    .tbl-min table{ width:100%; border-collapse:collapse; }
    .tbl-min thead th{ background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; text-align:left; font-weight:600; padding:10px 12px; }
    .tbl-min tbody td{ border-top:1px solid #f1f5f9; padding:10px 12px; vertical-align:middle; }
    .tbl-min .actions{ display:flex; gap:.4rem; flex-wrap:wrap; }
    .pill{ display:inline-block; padding:.2rem .5rem; border-radius:999px; font-size:.8rem; }
    .pill.ok{ background:#e8faf0; color:#166534; border:1px solid #bbf7d0; }
    .pill.nok{ background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
  </style>

  <div class="tbl-min">
    <table>
      <thead>
        <tr>
          <th style="width:90px;">ID</th>
          <th>Título</th>
          <th style="min-width:160px;">Grupo</th>
          <th style="min-width:190px;">Necessita Arquivo (Outros)</th>
          <th style="width:120px;">Ativo</th>
          <th style="width:320px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($tipos as $t)
          @php
            // Grupo: tenta diversos campos comuns
            $grupoTxt = $t->grupo
              ?? $t->grupo_nome
              ?? data_get($t, 'grupo.titulo')
              ?? data_get($t, 'grupo.nome')
              ?? data_get($t, 'grupo_descricao')
              ?? '-';

            // Necessita Arquivo (Outros): tenta bandeiras comuns
            $needOutros = (int) (
                $t->necessita_arquivo_outros
                ?? $t->necessita_arquivo
                ?? $t->precisa_arquivo
                ?? $t->flag_arquivo_outros
                ?? 0
            );
          @endphp
          <tr>
            <td>#{{ $t->id }}</td>
            <td>{{ $t->titulo }}</td>
            <td>{{ $grupoTxt }}</td>
            <td>
              @if($needOutros)
                <span class="pill ok">Sim</span>
              @else
                <span class="pill nok">Não</span>
              @endif
            </td>
            <td>
              @if($t->ativo)
                <span class="pill ok">Sim</span>
              @else
                <span class="pill nok">Não</span>
              @endif
            </td>
            <td>
              <div class="toolbar" style="display:flex; gap:12px; align-items:center;">
                <a class="btn" href="{{ route('admin.config.condicoes_especiais.edit', $t->id) }}">Editar</a>

                <form method="POST" action="{{ route('admin.config.condicoes_especiais.toggle-ativo', $t->id) }}">
                  @csrf @method('PATCH')
                  <button class="btn" type="submit">
                    {{ $t->ativo ? 'Desativar' : 'Ativar' }}
                  </button>
                </form>

                @if(Route::has('admin.config.condicoes_especiais.destroy'))
                  <form method="POST"
                        action="{{ route('admin.config.condicoes_especiais.destroy', $t->id) }}"
                        onsubmit="return confirm('Tem certeza que deseja excluir ' + {{ json_encode($t->titulo) }} + '?\nEsta ação não pode ser desfeita.');">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit">Excluir</button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6">Nenhum registro encontrado.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="pagination" style="margin-top:12px;">
    {{ method_exists($tipos, 'links') ? $tipos->links() : '' }}
  </div>
@endsection
