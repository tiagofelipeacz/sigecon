@extends('layouts.sigecon')
@section('title', 'Nova Cidade de Prova - SIGECON')

@php
  // Espera-se: $concurso, $ufs, $cargos (id, titulo)
@endphp

@section('content')
<style>
  .gc-page{ display:grid; grid-template-columns:260px 1fr; gap:16px; }
  .gc-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body{ padding:14px; }

  .grid2{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .label{ display:block; font-size:12px; color:#6b7280; margin-bottom:6px; }
  .input, .select{ width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; font-size:14px; }

  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:9px 13px; border-radius:8px; background:#fff; cursor:pointer; text-decoration:none; font-size:14px; }
  .btn.primary{ background:#111827; border-color:#111827; color:#fff; }
  .btn.muted{ background:#f9fafb; }
</style>

<div class="gc-page">
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'cidades'
    ])
  </div>

  <div class="grid" style="gap:14px">
    <div class="gc-card">
      <div class="gc-body">
        <h5 style="margin:0 0 10px; font-weight:700">Nova Cidade de Prova</h5>

        <form method="post" action="{{ route('admin.concursos.cidades.store', $concurso) }}">
          @csrf

          <div class="grid2">
            <div>
              <label class="label">* Cidade de Prova</label>
              <input type="text" name="cidade" class="input" value="{{ old('cidade') }}" required>
            </div>

            <div>
              <label class="label">* Estado (UF)</label>
              <select name="uf" class="select" required>
                @foreach($ufs as $k=>$v)
                  <option value="{{ $k }}" @selected(old('uf')===$k)>{{ $v===''?'UF':$v }}</option>
                @endforeach
              </select>
            </div>
          </div>

          {{-- Cargos (mantido e com check-all) --}}
          <div style="margin-top:18px">
            <label class="label">Vincular cargos (opcional)</label>

            <div style="display:flex; align-items:center; gap:10px; margin:6px 0 12px">
              <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                <input type="checkbox" id="check-all-cargos">
                <span>Selecionar todos</span>
              </label>
              <button type="button" class="btn" id="clear-cargos">Limpar</button>
            </div>

            <div style="display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px">
              @forelse ($cargos as $c)
                <label style="display:flex; gap:8px; align-items:center; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px">
                  <input type="checkbox" name="cargos[]" value="{{ $c->id }}"
                         @checked(in_array($c->id, old('cargos', [])))>
                  <span>{{ $c->titulo }}</span>
                </label>
              @empty
                <div class="muted">Nenhum cargo disponível para este concurso.</div>
              @endforelse
            </div>
          </div>

          <div style="display:flex; gap:8px; margin-top:18px">
            <button class="btn primary" type="submit">
              <i data-lucide="save"></i> Salvar
            </button>
            <a class="btn muted" href="{{ route('admin.concursos.cidades.index', $concurso) }}">
              Cancelar
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@once
  <script src="https://unpkg.com/lucide@latest"></script>
@endonce
<script>
document.addEventListener('DOMContentLoaded', () => {
  window.lucide?.createIcons?.();

  const master   = document.getElementById('check-all-cargos');
  const clearBtn = document.getElementById('clear-cargos');
  const boxes    = Array.from(document.querySelectorAll('input[name="cargos[]"]'));

  const refreshMaster = () => {
    const total = boxes.length;
    const marcados = boxes.filter(b => b.checked).length;
    master.checked = marcados === total && total > 0;
    master.indeterminate = marcados > 0 && marcados < total;
  };

  master?.addEventListener('change', (e) => {
    boxes.forEach(b => { if (!b.disabled) b.checked = e.target.checked; });
    refreshMaster();
  });

  clearBtn?.addEventListener('click', () => {
    boxes.forEach(b => b.checked = false);
    refreshMaster();
  });

  boxes.forEach(b => b.addEventListener('change', refreshMaster));
  refreshMaster();
});
</script>
@endsection
