@extends('layouts.sigecon')
@section('title', 'Grupo de Anexos - SIGECON')

@section('content')
<style>
  .gc-page{ display:grid; grid-template-columns: 1fr; gap:16px; }
  .gc-card{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body{ padding:14px; }
  .toolbar{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
  .toolbar .grow{ flex:1 1 280px; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:10px; padding:8px 11px; font-size:13px; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:7px 10px; border-radius:8px; background:#fff; cursor:pointer; text-decoration:none; font-size:13px; }
  .btn:hover{ background:#f9fafb; }
  .btn.primary{ background:#111827; border-color:#111827; color:#fff; }
  .btn.sm{ padding:5px 8px; font-size:12px; border-radius:7px; }
  .table{ width:100%; border-collapse:collapse; }
  .table th{ font-size:12px; color:#6b7280; text-align:left; padding:9px 10px; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
  .table td{ font-size:14px; padding:12px 10px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
  .alert{ margin-bottom:10px; padding:10px 12px; border-radius:8px; }
  .alert.error{ background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
  .muted{ color:#6b7280; font-size:12px; }

  /* Reordenar */
  .drag-col{ width:34px; }
  .drag-handle{
    cursor:grab; user-select:none; font-size:18px; line-height:1; color:#9ca3af;
    -webkit-user-drag: element; /* ajuda Safari */
  }
  .reorder-on .drag-handle{ color:#374151; }
  .dragging{ opacity:.6; }
  .drag-over{ outline:2px dashed #60a5fa; outline-offset:-4px; background:#f0f9ff; }
  .reorder-toolbar{ display:none; gap:8px; }
  .reorder-on .reorder-toolbar{ display:flex; }
</style>

<div class="gc-page">
  @if ($errors->any())
    <div class="gc-card">
      <div class="gc-body">
        <div class="alert error">
          @foreach ($errors->all() as $err)
            <div>• {{ $err }}</div>
          @endforeach
        </div>
      </div>
    </div>
  @endif

  @if (session('status'))
    <div class="gc-card">
      <div class="gc-body">
        <div class="alert" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;">
          {{ session('status') }}
        </div>
      </div>
    </div>
  @endif

  <div class="gc-card">
    <div class="gc-body">
      <form method="get" class="toolbar" id="filter-form">
        <a href="{{ route('admin.config.grupos-anexos.create') }}" class="btn primary">+ Novo grupo</a>

        <div class="grow" style="margin-left:6px">
          <input type="text" class="input" name="q" placeholder="Buscar por nome"
                 value="{{ $q ?? '' }}">
        </div>

        <div>
          <select name="ativo" class="input">
            <option value="">Ativo: Todos</option>
            <option value="1" @selected(($ativo ?? '')==='1')>Somente ativos</option>
            <option value="0" @selected(($ativo ?? '')==='0')>Somente inativos</option>
          </select>
        </div>

        <button class="btn" type="submit">Filtrar</button>

        {{-- Botões de reordenação (mostrados quando ativo) --}}
        <div class="reorder-toolbar" id="reorder-toolbar">
          <button class="btn" type="button" id="save-order">Salvar ordem</button>
          <button class="btn" type="button" id="cancel-reorder">Cancelar</button>
        </div>

        <button class="btn" type="button" id="toggle-reorder" title="Reordenar por arrastar e soltar">Reordenar</button>
      </form>

      <div class="muted" style="margin-top:6px">
        Dica: ative <strong>Reordenar</strong> para arrastar as linhas. Depois clique em <em>Salvar ordem</em>.
        (A listagem continua ordenada por <code>ordem</code> e, em seguida, por <code>nome</code>.)
      </div>
    </div>
  </div>

  <div class="gc-card">
    <div class="gc-body" style="overflow-x:auto">
      <table class="table" id="grupo-table">
        <thead>
          <tr>
            <th class="drag-col"></th>
            <th style="width:80px">ID</th>
            <th>Nome</th>
            <th style="width:90px">Ordem</th>
            <th style="width:100px">Ativo</th>
            <th style="width:110px">Usos</th>
            <th style="width:260px">Ações</th>
          </tr>
        </thead>
        <tbody>
        @forelse($rows as $r)
          @php $usos = (int)($r->usos ?? 0); @endphp
          <tr data-id="{{ $r->id }}" data-ordem="{{ (int)($r->ordem ?? 0) }}">
            <td class="drag-col"><span class="drag-handle" title="Arraste para reordenar">⋮⋮</span></td>
            <td>{{ $r->id }}</td>
            <td>
              {{ $r->nome }}
              @if($usos > 0)
                <span class="muted">— em uso</span>
              @endif
            </td>
            <td>{{ (int)($r->ordem ?? 0) }}</td>
            <td>
              <form method="post" action="{{ route('admin.config.grupos-anexos.toggle', $r) }}">
                @csrf @method('patch')
                <button class="btn sm" type="submit" title="Alternar ativo">
                  {{ (int)$r->ativo === 1 ? 'Ativo' : 'Inativo' }}
                </button>
              </form>
            </td>
            <td>{{ $usos }}</td>
            <td>
              <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center">
                <a class="btn sm" href="{{ route('admin.config.grupos-anexos.edit', $r) }}"
                   @if($usos>0) title="Atenção: renomear quebra a referência por nome nos anexos." @endif>
                  Editar
                </a>

                @if($usos > 0)
                  <button class="btn sm" disabled
                          title="Não é possível remover: há {{ $usos }} anexo(s) usando este grupo.">
                    Remover
                  </button>
                @else
                  <form method="post" action="{{ route('admin.config.grupos-anexos.destroy', $r) }}"
                        onsubmit="return confirm('Remover este grupo?')">
                    @csrf @method('delete')
                    <button class="btn sm" style="border-color:#fecaca; background:#fee2e2; color:#991b1b">
                      Remover
                    </button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align:center; padding:20px; color:#6b7280">
              Nenhum grupo encontrado.
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>

      <div style="margin-top:12px">
        {{ $rows->withQueryString()->onEachSide(2)->links() }}
      </div>
    </div>
  </div>
</div>

{{-- Form oculto para salvar ordem --}}
<form id="reorder-form" method="post" action="{{ route('admin.config.grupos-anexos.reorder') }}" style="display:none">
  @csrf
  <input type="hidden" name="_return" value="{{ request()->fullUrl() }}">
  <input type="hidden" name="start" value="{{ method_exists($rows,'firstItem') ? ($rows->firstItem() ?? 1) : 1 }}">
</form>

<script>
  (function() {
    const page   = document.querySelector('.gc-page');
    const tbody  = document.querySelector('#grupo-table tbody');
    const toggle = document.getElementById('toggle-reorder');
    const save   = document.getElementById('save-order');
    const cancel = document.getElementById('cancel-reorder');
    const toolbar= document.getElementById('reorder-toolbar');
    const form   = document.getElementById('reorder-form');

    if (!tbody || !toggle || !form) return;

    let dragging = null;

    const setMode = (on) => {
      page.classList.toggle('reorder-on', on);
      // O draggable precisa estar no HANDLE
      tbody.querySelectorAll('.drag-handle').forEach(h => {
        if (on) h.setAttribute('draggable', 'true');
        else h.removeAttribute('draggable');
      });
      toolbar.style.display = on ? 'flex' : 'none';
    };

    // Ativa/desativa modo
    toggle.addEventListener('click', () => {
      const isOn = page.classList.contains('reorder-on');
      setMode(!isOn);
    });

    // Cancelar
    cancel?.addEventListener('click', () => {
      setMode(false);
      window.location.reload();
    });

    // Início do drag — TEM QUE começar no .drag-handle
    tbody.addEventListener('dragstart', (e) => {
      const handle = e.target.closest('.drag-handle');
      if (!handle) return; // ignorar drags fora do handle
      dragging = handle.closest('tr');
      if (!dragging) return;

      dragging.classList.add('dragging');
      try {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragging.dataset.id || 'row');
      } catch(_) {}
    });

    tbody.addEventListener('dragend', () => {
      if (dragging) dragging.classList.remove('dragging');
      tbody.querySelectorAll('tr').forEach(r => r.classList.remove('drag-over'));
      dragging = null;
    });

    // Onde passar / soltar
    tbody.addEventListener('dragover', (e) => {
      if (!dragging) return;
      e.preventDefault();
      const overTr = e.target.closest('tr');
      if (!overTr || overTr === dragging) return;

      overTr.classList.add('drag-over');
      const rect = overTr.getBoundingClientRect();
      const after = (e.clientY - rect.top) / rect.height > 0.5;
      tbody.insertBefore(dragging, after ? overTr.nextSibling : overTr);
    });

    tbody.addEventListener('dragleave', (e) => {
      const overTr = e.target.closest('tr');
      if (overTr) overTr.classList.remove('drag-over');
    });

    tbody.addEventListener('drop', (e) => {
      if (!dragging) return;
      e.preventDefault();
      const overTr = e.target.closest('tr');
      if (overTr) overTr.classList.remove('drag-over');
    });

    // Salvar ordem (ids + ordens originais do bloco)
    save?.addEventListener('click', () => {
      form.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
      form.querySelectorAll('input[name="ordens[]"]').forEach(i => i.remove());

      const trs = Array.from(tbody.querySelectorAll('tr[data-id]'));
      const ids = trs.map(tr => tr.dataset.id);
      const ordens = trs.map(tr => parseInt(tr.dataset.ordem || '0', 10))
                        .sort((a,b) => a - b);

      if (!ids.length) return;

      ids.forEach(id => {
        const i = document.createElement('input');
        i.type = 'hidden';
        i.name = 'ids[]';
        i.value = id;
        form.appendChild(i);
      });

      ordens.forEach(o => {
        const i = document.createElement('input');
        i.type = 'hidden';
        i.name = 'ordens[]';
        i.value = o;
        form.appendChild(i);
      });

      form.submit();
    });
  })();
</script>
@endsection
