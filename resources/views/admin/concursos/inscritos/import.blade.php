{{-- resources/views/admin/concursos/inscritos/import.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Importar inscrições')

@section('content')
<style>
  .gc-page{ display:grid; grid-template-columns:260px 1fr; gap:16px; }
  .gc-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body{ padding:14px; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; text-decoration:none; cursor:pointer; }
  .btn.primary{ background:#111827; color:#fff; border-color:#111827; }
</style>

<div class="gc-page">
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'inscritos'
    ])
  </div>

  <div class="gc-card">
    <div class="gc-body">
      <div class="mb-2" style="font-weight:600">Importar inscrições</div>
      @if(session('ok'))
        <div class="chip" style="margin-bottom:10px">{{ session('ok') }}</div>
      @endif
      <form method="post" enctype="multipart/form-data" action="{{ route('admin.concursos.inscritos.import.handle', $concurso) }}">
        @csrf
        <input class="input" type="file" name="arquivo" accept=".csv,.txt,.xlsx" />
        <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:10px">
          <a class="btn" href="{{ route('admin.concursos.inscritos.index', $concurso) }}">Voltar</a>
          <button class="btn primary" type="submit"><i data-lucide="upload"></i> Enviar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
