@extends('layouts.sigecon')
@section('title', $isEdit ? 'Editar Grupo de Anexos - SIGECON' : 'Novo Grupo de Anexos - SIGECON')

@section('content')
<style>
  .gc-page{ display:grid; grid-template-columns: 1fr; gap:16px; }
  .gc-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body{ padding:14px; }
  .grid{ display:grid; gap:10px; }
  .g-2{ grid-template-columns: 1fr 200px; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; background:#fff; cursor:pointer; text-decoration:none; }
  .btn.primary{ background:#111827; border-color:#111827; color:#fff; }
  .tag{ font-size:12px; color:#6b7280; }
</style>

@php
  $action = $isEdit
    ? route('admin.config.grupos-anexos.update', $grupo)
    : route('admin.config.grupos-anexos.store');
@endphp

<div class="gc-page">
  <div class="gc-card">
    <div class="gc-body">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px">
        <div style="font-weight:700; font-size:var(--fs-h1)">{{ $isEdit ? 'Editar grupo' : 'Novo grupo' }}</div>
        <a class="btn" href="{{ route('admin.config.grupos-anexos.index') }}">← Voltar</a>
      </div>

      <form method="post" action="{{ $action }}">
        @csrf
        @if($isEdit) @method('put') @endif

        <div class="grid g-2">
          <div>
            <label class="tag">Nome *</label>
            <input type="text" name="nome" class="input" required maxlength="190"
                   value="{{ old('nome', $grupo->nome ?? '') }}">
          </div>
          <div>
            <label class="tag">Ordem</label>
            <input type="number" name="ordem" class="input" min="0"
                   value="{{ old('ordem', (int)($grupo->ordem ?? 0)) }}">
          </div>
        </div>

        <div style="margin-top:8px">
          <label class="tag">Ativo</label>
          <select name="ativo" class="input" style="max-width:200px">
            @php $ativo = (int) old('ativo', (int)($grupo->ativo ?? 1)); @endphp
            <option value="1" @selected($ativo===1)>Sim</option>
            <option value="0" @selected($ativo===0)>Não</option>
          </select>
        </div>

        <div style="margin-top:14px">
          <button class="btn primary" type="submit">
            {{ $isEdit ? 'Salvar alterações' : 'Criar grupo' }}
          </button>
          <a class="btn" href="{{ route('admin.config.grupos-anexos.index') }}">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
