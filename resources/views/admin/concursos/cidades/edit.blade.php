@extends('layouts.sigecon')
@section('title', 'Editar Cidade de Prova')

@section('content')
<div class="row">
  <div class="col-lg-3 mb-3">
    @includeFirst(
      ['admin.concursos._menu','admin.concursos._sidebar','admin.concursos.partials.menu','admin.concursos.menu'],
      ['concurso' => $concurso, 'active' => 'cidades']
    )
  </div>

  <div class="col-lg-9">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Editar Cidade de Prova</h5>

        <form method="post" action="{{ route('admin.concursos.cidades.update', [$concurso, $cidade->id]) }}">
          @csrf @method('PUT')

          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">* Cidade de Prova</label>
              <input type="text" name="cidade" class="form-control" value="{{ old('cidade', $cidade->cidade) }}" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Estado (UF)</label>
              <select name="uf" class="form-select" required>
                @foreach(($ufs ?? []) as $k=>$v)
                  <option value="{{ $k }}" @selected(old('uf', $cidade->uf)===$k)>{{ $v===''?'UF':$v }}</option>
                @endforeach
              </select>
            </div>
          </div>

          {{-- Dropdown de cargos (mesma implementação da create) --}}
          <div class="mt-3">
            <label class="form-label">Vagas Disponíveis (Cargos)</label>

            <div class="position-relative" id="cargos-wrapper">
              <button type="button" class="btn btn-outline-secondary" id="btnCargos">
                <span id="btnCargosText">Nenhum selecionado</span>
                <span class="ms-1">▾</span>
              </button>

              <div class="chk-menu card shadow-sm" id="menuCargos" style="display:none;position:absolute;z-index:20;min-width:420px;max-height:340px;overflow:auto;">
                <div class="list-group list-group-flush">
                  <label class="list-group-item">
                    <input type="checkbox" class="form-check-input me-2" id="chkTodos">
                    <strong>Todos</strong>
                  </label>

                  @php $selected = collect(old('cargos', $selecionados ?? []))->map(fn($v)=>(int)$v)->all(); @endphp

                  @forelse($cargos as $c)
                    <label class="list-group-item d-flex">
                      <input type="checkbox"
                             name="cargos[]"
                             value="{{ $c->id }}"
                             class="form-check-input me-2 chk-cargo"
                             @checked(in_array($c->id, $selected))>
                      <div>{{ $c->titulo }}</div>
                    </label>
                  @empty
                    <div class="list-group-item text-muted">— Sem cargos cadastrados para este concurso —</div>
                  @endforelse
                </div>
              </div>
            </div>

            <div class="form-text">
              Clique para abrir a lista; marque/desmarque. “Todos” seleciona/limpa tudo.
            </div>
          </div>

          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Salvar e Fechar</button>
            <a class="btn btn-light" href="{{ route('admin.concursos.cidades.index', $concurso) }}">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- mesmo JS da create --}}
<script>
document.addEventListener('click', function(e){
  const wrap = document.getElementById('cargos-wrapper');
  const btn  = document.getElementById('btnCargos');
  const menu = document.getElementById('menuCargos');
  const text = document.getElementById('btnCargosText');
  const all  = document.getElementById('chkTodos');

  if (!wrap || !btn || !menu) return;

  const updateText = () => {
    const n = wrap.querySelectorAll('.chk-cargo:checked').length;
    text.textContent = n ? `${n} selecionado(s)` : 'Nenhum selecionado';
    const total = wrap.querySelectorAll('.chk-cargo').length;
    if (all) all.checked = (n > 0 && n === total);
  };

  if (e.target === btn) {
    menu.style.display = (menu.style.display==='none' || !menu.style.display) ? 'block' : 'none';
    e.preventDefault();
    return;
  }

  if (menu.contains(e.target)) {
    if (e.target.id === 'chkTodos') {
      const flag = e.target.checked;
      wrap.querySelectorAll('.chk-cargo').forEach(i => i.checked = flag);
      updateText();
    } else if (e.target.classList.contains('chk-cargo')) {
      updateText();
    }
    return;
  }

  menu.style.display = 'none';
});

window.addEventListener('DOMContentLoaded', () => {
  const wrap = document.getElementById('cargos-wrapper');
  if (!wrap) return;
  const btn = document.getElementById('btnCargos');
  const textInit = () => {
    const n = wrap.querySelectorAll('.chk-cargo:checked').length;
    const text = document.getElementById('btnCargosText');
    if (text) text.textContent = n ? `${n} selecionado(s)` : 'Nenhum selecionado';
    const all  = document.getElementById('chkTodos');
    const total = wrap.querySelectorAll('.chk-cargo').length;
    if (all) all.checked = (n > 0 && n === total);
  };
  textInit();
});
</script>
@endsection
