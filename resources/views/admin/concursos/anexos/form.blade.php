{{-- resources/views/admin/concursos/anexos/form.blade.php --}}
@extends('layouts.sigecon')
@section('title', ($isEdit ?? (isset($anexo) && ($anexo->id ?? null))) ? 'Editar Anexo - SIGECON' : 'Novo Anexo - SIGECON')

@php
  // Alguns controladores podem enviar $isEdit; se não, deduzimos.
  $isEdit = ($isEdit ?? false) || (isset($anexo) && ($anexo->id ?? null));
@endphp

@section('content')
<style>
  .gc-page   { display:grid; grid-template-columns: 260px 1fr; gap:16px; }
  .gc-card   { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body   { padding:14px; }
  .gc-row-2  { display:grid; grid-template-columns: 1fr; gap:14px; }

  .gc-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
  .gc-head .title{ font-size: var(--fs-h1); line-height:1.2; font-weight:700; margin:0; }
  .gc-head .sub{ color:#6b7280; font-size: var(--fs-sm); margin-top:4px; }

  .tag{ font-size:12px; color:#6b7280; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; text-decoration:none; cursor:pointer; }
  .btn.primary{ background:#111827; color:white; border-color:#111827; }
  .btn.sm{ padding:6px 8px; font-size: var(--fs-sm); border-radius:6px; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px; }
  .grid{ display:grid; gap:10px; }
  .g-2{ grid-template-columns: 1fr 1fr; }
  .g-3{ grid-template-columns: 1fr 1fr 1fr; }
  .inline-help{ font-size:12px; color:#6b7280; }
</style>

<div class="gc-page">
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'anexos'
    ])
  </div>

  <div class="gc-row-2">

    <div class="gc-card">
      <div class="gc-body">

        <div class="gc-head">
          <div>
            <h1 class="title">{{ $isEdit ? 'Editar anexo' : 'Publicar anexo' }}</h1>
            <div class="sub">
              Use esta página para publicar arquivos ou links do concurso.
              Itens “restritos” aparecem apenas na área do candidato.
            </div>
          </div>
          <div>
            <a href="{{ route('admin.concursos.anexos.index', $concurso) }}" class="btn sm" title="Voltar para a lista de anexos">← Voltar</a>
          </div>
        </div>

        {{-- Formulário (partial) --}}
        @include('admin.concursos.anexos.form-fields', [
          'concurso' => $concurso,
          'anexo'    => $anexo ?? null,
          'cargos'   => $cargos ?? [],
          'grupos'   => $grupos ?? [],  {{-- << adicionado para sugerir grupos existentes --}}
        ])
        

      </div>
    </div>

  </div>
</div>
@endsection
