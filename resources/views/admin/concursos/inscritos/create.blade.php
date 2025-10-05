@extends('layouts.sigecon')
@section('title', 'Nova Inscrição - SIGECON')

@section('content')
<style>
  .gc-page{ display:grid; grid-template-columns:260px 1fr; gap:16px; }
  .gc-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body{ padding:14px; }
  .grid{ display:grid; grid-template-columns: 1fr 1fr; gap:12px; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px 10px; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; background:#fff; cursor:pointer; text-decoration:none;}
  .btn.primary{ background:#111827; border-color:#111827; color:#fff; }
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
      <div class="title mb-2" style="font-weight:700">Nova Inscrição</div>

      @if ($errors->any())
        <div class="mb-2" style="color:#991b1b">Verifique os campos abaixo.</div>
      @endif

      <form method="post" action="{{ route('admin.concursos.inscritos.store', $concurso) }}" class="grid">
        @csrf
        <div>
          <label style="font-size:12px; color:#6b7280">Usuário (candidato)</label>
          <select name="user_id" class="input" required>
            <option value="">— Selecione —</option>
            @foreach ($users as $u)
              <option value="{{ $u->id }}" @selected(old('user_id')==$u->id)>{{ $u->name }} — {{ $u->email }}</option>
            @endforeach
          </select>
          @error('user_id')<div style="color:#991b1b; font-size:12px">{{ $message }}</div>@enderror
        </div>

        <div>
          <label style="font-size:12px; color:#6b7280">Cargo</label>
          <select name="cargo_id" class="input" required>
            <option value="">— Selecione —</option>
            @foreach ($cargos as $c)
              <option value="{{ $c->id }}" @selected(old('cargo_id')==$c->id)>{{ $c->nome }}</option>
            @endforeach
          </select>
          @error('cargo_id')<div style="color:#991b1b; font-size:12px">{{ $message }}</div>@enderror
        </div>

        <div>
          <label style="font-size:12px; color:#6b7280">Modalidade</label>
          <select name="modalidade" class="input" required>
            @foreach ($MODALIDADES as $m)
              <option value="{{ $m }}" @selected(old('modalidade')==$m)>{{ strtoupper($m) }}</option>
            @endforeach
          </select>
          @error('modalidade')<div style="color:#991b1b; font-size:12px">{{ $message }}</div>@enderror
        </div>

        <div>
          <label style="font-size:12px; color:#6b7280">Status</label>
          <select name="status" class="input" required>
            @foreach ($STATUS as $s)
              <option value="{{ $s }}" @selected(old('status')==$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
          </select>
          @error('status')<div style="color:#991b1b; font-size:12px">{{ $message }}</div>@enderror
        </div>

        <div style="grid-column: 1 / -1; display:flex; gap:8px">
          <a class="btn" href="{{ route('admin.concursos.inscritos.index', $concurso) }}"><i data-lucide="chevron-left"></i> Voltar</a>
          <button class="btn primary"><i data-lucide="save"></i> Salvar</button>
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
