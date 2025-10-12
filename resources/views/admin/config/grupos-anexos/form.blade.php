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
  .switch{ display:inline-flex; align-items:center; gap:8px; cursor:pointer; user-select:none; }
  .switch input{ position:absolute; opacity:0; pointer-events:none; }
  .switch .knob{
    width:40px; height:22px; border-radius:999px; border:1px solid #e5e7eb; background:#f3f4f6; position:relative; transition:background .15s;
  }
  .switch .knob::after{
    content:''; position:absolute; top:2px; left:2px; width:18px; height:18px; background:#fff; border-radius:999px; box-shadow:0 1px 2px rgba(0,0,0,.08); transition:left .15s;
  }
  .switch input:checked + .knob{ background:#111827; border-color:#111827; }
  .switch input:checked + .knob::after{ left:20px; }
  .alert{ margin-bottom:10px; padding:10px 12px; border-radius:8px; }
  .alert.error{ background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
</style>

@php
  $action = $isEdit
    ? route('admin.config.grupos-anexos.update', $grupo)
    : route('admin.config.grupos-anexos.store');
@endphp

<div class="gc-page">
  <div class="gc-card">
    <div class="gc-body">
      @if ($errors->any())
        <div class="alert error">
          @foreach ($errors->all() as $err)
            <div>• {{ $err }}</div>
          @endforeach
        </div>
      @endif

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
            @if($isEdit)
              <div class="tag" style="margin-top:4px;color:#b45309">
                Atenção: se este grupo estiver em uso, ele <strong>não poderá</strong> ser renomeado.
              </div>
            @endif
          </div>
          <div>
            <label class="tag">Ordem</label>
            <input type="number" name="ordem" class="input" min="0"
                   value="{{ old('ordem', (int)($grupo->ordem ?? 0)) }}">
          </div>
        </div>

        <div style="margin-top:10px">
          <label class="tag" for="ativo">Ativo</label><br>
          {{-- flag/checkbox com fallback de 0 --}}
          <input type="hidden" name="ativo" value="0">
          <label class="switch">
            <input id="ativo" type="checkbox" name="ativo" value="1"
                   @checked((int)old('ativo', (int)($grupo->ativo ?? 1)) === 1)>
            <span class="knob" aria-hidden="true"></span>
            <span>{{ (int)old('ativo', (int)($grupo->ativo ?? 1)) === 1 ? 'Sim' : 'Não' }}</span>
          </label>
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
