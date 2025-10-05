@extends('layouts.sigecon')
@section('title', 'Importar Inscrições - SIGECON')

@section('content')
<style>
  .gc-page{ display:grid; grid-template-columns:260px 1fr; gap:16px; }
  .gc-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body{ padding:14px; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px 10px; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; background:#fff; cursor:pointer; text-decoration:none;}
  .btn.primary{ background:#111827; border-color:#111827; color:#fff; }
  .code{ background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; padding:10px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
</style>

<div class="gc-page">
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'inscricoes'
    ])
  </div>

  <div class="gc-card">
    <div class="gc-body">
      <div class="title mb-2" style="font-weight:700">Importar Inscrições (CSV)</div>

      @if ($errors->any())
        <div class="mb-2" style="color:#991b1b">Verifique o arquivo enviado.</div>
      @endif

      <form method="post" action="{{ route('admin.concursos.inscritos.import.store', $concurso) }}" enctype="multipart/form-data" style="display:grid; gap:12px">
        @csrf
        <div>
          <input class="input" type="file" name="arquivo" accept=".csv,.txt" required />
          @error('arquivo')<div style="color:#991b1b; font-size:12px">{{ $message }}</div>@enderror
        </div>

        <div class="code">
          Cabeçalho obrigatório: <strong>cargo_id</strong> e (<strong>user_email</strong> ou <strong>user_id</strong>)<br>
          Colunas opcionais: <code>modalidade</code> ({{ implode(', ', $MODALIDADES) }}) e <code>status</code> ({{ implode(', ', $STATUS) }}).<br><br>
          Exemplo:<br>
<pre style="margin:0">{{ $exemplo }}</pre>
        </div>

        <div style="display:flex; gap:8px">
          <a class="btn" href="{{ route('admin.concursos.inscritos.index', $concurso) }}"><i data-lucide="chevron-left"></i> Voltar</a>
          <button class="btn primary"><i data-lucide="upload"></i> Importar</button>
        </div>
      </form>
    </div>
  </div>
</div>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>document.addEventListener('DOMContentLoaded',()=>window.lucide?.createIcons())</script>
@endsection
