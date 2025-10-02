{{-- resources/views/admin/config/clientes/index.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Clientes - SIGECON')

@section('content')
  @php
    use Illuminate\Support\Str;
    $q            = $q ?? request('q','');
    $somenteAtivos= (string)($somenteAtivos ?? request('somente_ativos','')) === '1';

    $urlCreate = \Route::has('admin.config.clientes.create')
      ? route('admin.config.clientes.create')
      : url('/admin/config/clientes/create');
  @endphp

  <h1>Clientes</h1>
  <p class="sub">Cadastre e gerencie os clientes (órgãos/entidades) do sistema.</p>

  {{-- Flash / Erros --}}
  @if (session('success'))
    <div class="mb-3 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">
      {{ session('success') }}
    </div>
  @endif
  @if (session('ok'))
    <div class="mb-3 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">
      {{ session('ok') }}
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
      <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por nome, cidade, documento…" style="flex:1">

      <label class="btn" style="display:flex;align-items:center;gap:.5rem;">
        <input type="checkbox" name="somente_ativos" value="1" {{ $somenteAtivos ? 'checked':'' }}>
        <span>Somente ativos</span>
      </label>

      <button class="btn" type="submit">Buscar</button>
      <a class="btn" href="{{ route('admin.config.clientes.index') }}">Limpar</a>
    </form>

    <a class="btn primary" href="{{ $urlCreate }}">+ Novo</a>
  </div>

  <style>
    .tbl-min{ width:100%; background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
    .tbl-min table{ width:100%; border-collapse:collapse; }
    .tbl-min thead th{ background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; text-align:left; font-weight:600; padding:10px 12px; }
    .tbl-min tbody td{ border-top:1px solid #f1f5f9; padding:10px 12px; vertical-align:middle; }
    .pill{ display:inline-block; padding:.2rem .5rem; border-radius:999px; font-size:.8rem; border:1px solid transparent; }
    .pill.ok{  background:#e8faf0; color:#166534; border-color:#bbf7d0; }
    .pill.nok{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
    .title a{ font-weight:600; text-decoration:none; }
    .title a:hover{ text-decoration:underline; }
    .btn.smol{ padding:.25rem .5rem; font-size:.85rem; }
  </style>

  <div class="tbl-min">
    <table>
      <thead>
        <tr>
          <th style="width:90px;">ID</th>
          <th style="width:320px;">Nome</th>
          <th style="width:160px;">Documento</th>
          <th style="width:220px;">Cidade/UF</th>
          <th style="width:260px;">Contato</th>
          <th style="width:110px;">Ativo</th>
          <th style="width:260px;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($clientes as $c)
          @php
            // Fallbacks de campos comuns
            $nome = $c->nome ?? $c->name ?? ('Cliente #'.$c->id);
            $doc  = $c->documento ?? $c->cnpj ?? $c->cpf ?? '—';
            $cid  = $c->cidade ?? $c->municipio ?? $c->city ?? null;
            $uf   = $c->uf ?? $c->estado ?? $c->state ?? null;
            $contEmail = $c->email ?? $c->contato_email ?? null;
            $contTel   = $c->telefone ?? $c->fone ?? $c->contato_telefone ?? null;
            $ativo = (int)($c->ativo ?? 0) === 1;

            $urlEdit = \Route::has('admin.config.clientes.edit')
              ? route('admin.config.clientes.edit', $c->id)
              : url('/admin/config/clientes/'.$c->id.'/editar');

            $urlToggle = \Route::has('admin.config.clientes.toggle-ativo')
              ? route('admin.config.clientes.toggle-ativo', $c->id)
              : url('/admin/config/clientes/'.$c->id.'/toggle-ativo');

            $urlDestroy = \Route::has('admin.config.clientes.destroy')
              ? route('admin.config.clientes.destroy', $c->id)
              : url('/admin/config/clientes/'.$c->id);
          @endphp
          <tr>
            <td>#{{ $c->id }}</td>
            <td>
              <div class="title">{{ Str::limit($nome, 120) }}</div>
              @if(!empty($c->sigla))
                <div class="subtle" style="color:#6b7280;font-size:.85rem;">Sigla: {{ $c->sigla }}</div>
              @endif
            </td>
            <td>{{ $doc }}</td>
            <td>{{ $cid ? $cid : '—' }}{{ $uf ? ' / '.$uf : '' }}</td>
            <td>
              @if($contEmail) <div>{{ $contEmail }}</div> @endif
              @if($contTel)   <div>{{ $contTel }}</div>   @endif
              @unless($contEmail || $contTel) — @endunless
            </td>
            <td>
              {!! $ativo ? '<span class="pill ok">Sim</span>' : '<span class="pill nok">Não</span>' !!}
            </td>
            <td>
              <div class="toolbar" style="margin-top:4px; display:flex; gap:10px; flex-wrap:wrap;">
                <a class="btn smol" href="{{ $urlEdit }}">Editar</a>

                <form method="POST" action="{{ $urlToggle }}">
                  @csrf @method('PATCH')
                  <button class="btn smol" type="submit">
                    {{ $ativo ? 'Desativar' : 'Ativar' }}
                  </button>
                </form>

                @if(\Route::has('admin.config.clientes.destroy'))
                  <form method="POST" action="{{ $urlDestroy }}"
                        onsubmit="return confirm('Tem certeza que deseja excluir {{ addslashes($nome) }}?\nEsta ação não pode ser desfeita.');">
                    @csrf @method('DELETE')
                    <button class="btn smol" type="submit">Excluir</button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7">Nenhum cliente encontrado.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="pagination" style="margin-top:12px;">
    {{ method_exists($clientes, 'links') ? $clientes->links() : '' }}
  </div>
@endsection
