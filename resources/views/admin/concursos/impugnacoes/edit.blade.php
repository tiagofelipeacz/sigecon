{{-- resources/views/admin/impugnacoes/edit.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Impugnação #'.$impugnacao->id.' - SIGECON')

@php
  $dtResp = data_get($impugnacao,'respondido_em') ?? data_get($impugnacao,'responded_at');
  $sit    = strtolower((string)($impugnacao->situacao ?: 'pendente'));
@endphp

@section('content')
<style>
  .gc-page   { display:grid; grid-template-columns: 260px 1fr; gap:16px; }
  .gc-card   { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body   { padding:14px; }
  .gc-main   { display:grid; gap:16px; }
  @media (min-width: 1024px){ .gc-main{ grid-template-columns: 1.2fr .8fr; } }
  .muted     { color:#6b7280; }
  .title     { font-size:18px; font-weight:600; }
  .hr        { height:1px; background:#f3f4f6; margin:10px 0; }
  .grid      { display:grid; gap:10px; }
  .g-2       { grid-template-columns: 1fr 1fr; }
  .tag       { font-size:12px; color:#6b7280; }
  .chip      { display:inline-block; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600; }
  .chip.pendente  { background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; }
  .chip.deferido  { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
  .chip.indeferido{ background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
  .input     { width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px; }
  .input[readonly]{ background:#f9fafb; color:#6b7280; }
  .btn       { display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; text-decoration:none; cursor:pointer; }
  .btn:hover { background:#f9fafb; }
  .btn.primary{ background:#111827; color:#fff; border-color:#111827; }
  .btn.ghost { background:transparent; }
  .sticky-top{ position:sticky; top:14px; }
  .pre-wrap  { white-space:pre-wrap; line-height:1.5; }
  .kv        { display:grid; grid-template-columns: 140px 1fr; gap:4px 10px; font-size:14px; }
  .kv dt     { color:#6b7280; }
  .kv dd     { color:#111827; font-weight:500; }
</style>

@if(session('success'))
  <div class="gc-card" style="border-color:#bbf7d0; background:#ecfdf5">
    <div class="gc-body" style="color:#065f46">{{ session('success') }}</div>
  </div>
@endif

<div class="gc-page">
  {{-- Lateral: menu (marca "impugnacoes" ativo) --}}
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'impugnacoes'
    ])
  </div>

  {{-- Conteúdo --}}
  <div class="gc-main">
    {{-- COLUNA ESQUERDA: dados + argumentos --}}
    <div class="grid">
      {{-- Cabeçalho/Resumo --}}
      <div class="gc-card">
        <div class="gc-body">
          <div class="title">Impugnação #{{ $impugnacao->id }}</div>
          <div class="muted" style="margin-top:2px">
            Concurso #{{ $concurso->id }}
            @if($concurso->titulo)
              — {{ $concurso->titulo }}
            @endif
          </div>

          <div class="hr"></div>

          <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap">
            <span class="chip {{ in_array($sit,['pendente','deferido','indeferido']) ? $sit : 'pendente' }}">
              {{ ucfirst($impugnacao->situacao ?: 'pendente') }}
            </span>
            <div class="muted">Enviado em: <strong>{{ optional($impugnacao->created_at)->format('d/m/Y H:i:s') }}</strong></div>
            <div class="muted">Respondido em:
              <strong>{{ $dtResp ? \Carbon\Carbon::parse($dtResp)->format('d/m/Y H:i:s') : '—' }}</strong>
            </div>
          </div>
        </div>
      </div>

      {{-- Requerente --}}
      <div class="gc-card">
        <div class="gc-body">
          <div class="title">Dados do requerente</div>
          <div class="hr"></div>
          <dl class="kv">
            <dt>Nome</dt><dd>{{ $impugnacao->nome ?: '—' }}</dd>
            <dt>E-mail</dt><dd>{{ $impugnacao->email ?: '—' }}</dd>
            <dt>CPF</dt><dd>{{ $impugnacao->cpf ?: '—' }}</dd>
            <dt>Telefone</dt><dd>{{ $impugnacao->telefone ?: '—' }}</dd>
            @if($impugnacao->endereco)
              <dt>Endereço</dt><dd>{{ $impugnacao->endereco }}</dd>
            @endif
          </dl>

          @if($impugnacao->anexo_path)
            <div class="hr"></div>
            <a class="btn" target="_blank" href="{{ asset($impugnacao->anexo_path) }}">
              <i data-lucide="paperclip"></i> Baixar anexo enviado
            </a>
          @endif
        </div>
      </div>

      {{-- Argumentos --}}
      <div class="gc-card">
        <div class="gc-body">
          <div class="title">Argumentos apresentados</div>
          <div class="hr"></div>
          <div class="pre-wrap">{{ $impugnacao->texto }}</div>
        </div>
      </div>
    </div>

    {{-- COLUNA DIREITA: análise / resposta (fixo) --}}
    <div class="grid">
      <form method="POST"
            action="{{ route('admin.concursos.impugnacoes.update', [$concurso, $impugnacao]) }}"
            class="gc-card sticky-top">
        @csrf
        @method('PUT')

        <div class="gc-body">
          <div class="title">Analisar e responder</div>
          <div class="hr"></div>

          <div class="grid g-2">
            <div>
              <label class="tag">Situação</label>
              @php $sel = old('situacao', $impugnacao->situacao ?: 'pendente'); @endphp
              <select name="situacao" class="input" required>
                <option value="pendente"   @selected($sel==='pendente')>Pendente</option>
                <option value="deferido"   @selected($sel==='deferido')>Deferido</option>
                <option value="indeferido" @selected($sel==='indeferido')>Indeferido</option>
              </select>
            </div>
            <div>
              <label class="tag">Data da resposta</label>
              <input class="input" readonly
                     value="{{ $dtResp ? \Carbon\Carbon::parse($dtResp)->format('d/m/Y H:i:s') : '—' }}">
            </div>
          </div>

          <div class="hr"></div>

          <div>
            <label class="tag">Resposta (decisão fundamentada)</label>
            <textarea name="resposta" rows="10" class="input"
              placeholder="Escreva aqui a decisão fundamentada…">{{ old('resposta', $impugnacao->resposta_html ?? $impugnacao->resposta_texto) }}</textarea>
            <div class="tag" style="margin-top:6px">
              Texto simples. (Se o layout já tiver WYSIWYG, ele se aplica aqui.)
            </div>
          </div>

          <div class="hr"></div>

          <div style="display:flex; gap:8px; flex-wrap:wrap">
            <button class="btn primary" type="submit">
              <i data-lucide="save"></i> Salvar
            </button>
            <a href="{{ route('admin.concursos.impugnacoes.index', $concurso) }}" class="btn ghost">
              <i data-lucide="arrow-left"></i> Voltar
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>
  document.addEventListener('DOMContentLoaded', () => {
    window.lucide?.createIcons();
  });
</script>
@endsection
