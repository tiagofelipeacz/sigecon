@php
  $ufs = $ufs ?: [
    ''=>'UF','AC'=>'AC','AL'=>'AL','AM'=>'AM','AP'=>'AP','BA'=>'BA','CE'=>'CE','DF'=>'DF','ES'=>'ES',
    'GO'=>'GO','MA'=>'MA','MG'=>'MG','MS'=>'MS','MT'=>'MT','PA'=>'PA','PB'=>'PB','PE'=>'PE',
    'PI'=>'PI','PR'=>'PR','RJ'=>'RJ','RN'=>'RN','RO'=>'RO','RR'=>'RR','RS'=>'RS','SC'=>'SC',
    'SE'=>'SE','SP'=>'SP','TO'=>'TO',
  ];
  $oldUf = old('uf', $cidade->uf ?? '');
  $pre   = array_map('intval', old('cargos', $selecionados ?? []));
@endphp

<style>
  /* dropdown independente de bootstrap.js */
  .chk-dropdown{position:relative;display:inline-block}
  .chk-btn{display:inline-flex;align-items:center;gap:.5rem}
  .chk-menu{display:none;position:absolute;z-index:1030;min-width:320px;max-height:320px;overflow:auto;
            background:#fff;border:1px solid #dee2e6;border-radius:.5rem;box-shadow:0 10px 20px rgba(0,0,0,.08)}
  .chk-item{padding:.5rem .75rem;cursor:pointer;display:flex;align-items:center;gap:.5rem}
  .chk-item:hover{background:#f8f9fa}
  .chk-sep{border-top:1px solid #eee;margin:.25rem 0}
</style>

<div class="row g-3">
  <div class="col-md-8">
    <label class="form-label">* Cidade de Prova</label>
    <input type="text" name="cidade" class="form-control"
           value="{{ old('cidade', $cidade->cidade ?? '') }}" required>
  </div>

  <div class="col-md-4">
    <label class="form-label">Estado (UF)</label>
    <select name="uf" class="form-select" required>
      @foreach($ufs as $k => $v)
        <option value="{{ $k }}" @selected($oldUf === $k)>{{ $v === '' ? 'UF' : $v }}</option>
      @endforeach
    </select>
  </div>

  <div class="col-12">
    <label class="form-label">Vagas Disponíveis (Cargos)</label>

    <div class="chk-dropdown" id="cg-dd">
      <button type="button" class="btn btn-outline-secondary chk-btn" id="cg-btn">
        <span id="cg-text">Nenhum selecionado</span>
        <span class="ms-1">▾</span>
      </button>

      <div class="chk-menu" id="cg-menu" role="listbox" aria-labelledby="cg-btn">
        <label class="chk-item">
          <input type="checkbox" id="cg-all">
          <strong>Todos</strong>
        </label>
        <div class="chk-sep"></div>

        @forelse($cargos as $c)
          <label class="chk-item">
            <input type="checkbox" class="cg-opt" value="{{ $c->id }}"
              {{ in_array($c->id, $pre, true) ? 'checked' : '' }}>
            <span>{{ $c->titulo }}</span>
          </label>
        @empty
          <div class="chk-item text-muted">— Sem cargos cadastrados para este concurso —</div>
        @endforelse
      </div>
    </div>

    {{-- inputs reais enviados no submit --}}
    <div id="cg-hidden"></div>
    <div class="form-text">Clique para abrir a lista; marque/desmarque. “Todos” seleciona/limpa tudo.</div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const dd = document.getElementById('cg-dd');
  const btn = document.getElementById('cg-btn');
  const box = document.getElementById('cg-menu');
  const text= document.getElementById('cg-text');
  const all = document.getElementById('cg-all');
  const hid = document.getElementById('cg-hidden');

  const opts = () => Array.from(box.querySelectorAll('.cg-opt'));

  function sync() {
    hid.innerHTML = '';
    const sel = opts().filter(o => o.checked).map(o => o.value);
    sel.forEach(id => {
      const i = document.createElement('input');
      i.type='hidden'; i.name='cargos[]'; i.value=id;
      hid.appendChild(i);
    });

    const total = opts().length;
    const n = sel.length;
    text.textContent = n ? (n === total ? `Todos selecionados (${n})` : `Selecionados (${n})`) : 'Nenhum selecionado';
    all.checked = (n && n === total);
    all.indeterminate = (n > 0 && n < total);
  }

  function toggle(open) {
    box.style.display = open ? 'block' : 'none';
  }

  btn.addEventListener('click', e => {
    e.preventDefault();
    toggle(box.style.display !== 'block');
  });

  document.addEventListener('click', e => {
    if (!dd.contains(e.target)) toggle(false);
  });

  all.addEventListener('change', () => {
    opts().forEach(o => o.checked = all.checked);
    sync();
  });

  opts().forEach(o => o.addEventListener('change', sync));
  sync();
})();
</script>
@endpush
