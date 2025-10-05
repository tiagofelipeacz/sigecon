{{-- resources/views/admin/concursos/anexos/edit.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Editar Anexo - SIGECON')

@section('content')
<style>
  .gc-page   { display:grid; grid-template-columns: 260px 1fr; gap:16px; }
  .gc-card   { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body   { padding:14px; }
  .gc-row-2  { display:grid; grid-template-columns: 1fr; gap:14px; }
  .tag{ font-size:12px; color:#6b7280; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; text-decoration:none; cursor:pointer; }
  .btn.primary{ background:#111827; color:white; border-color:#111827; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px; }
  .grid{ display:grid; gap:10px; }
  .g-2{ grid-template-columns: 1fr 1fr; }
  .g-3{ grid-template-columns: 1 fr 1fr 1fr; }
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
        <div class="mb-2" style="font-weight:600">Editar anexo</div>

        {{-- Formulário --}}
        @include('admin.concursos.anexos.form-fields', [
          'concurso' => $concurso,
          'anexo'    => $anexo ?? null,
          'cargos'   => $cargos ?? [],
          'grupos'   => $grupos ?? [],  {{-- << passa os grupos para o partial --}}
        ])
      </div>
    </div>
  </div>
</div>
@endsection
