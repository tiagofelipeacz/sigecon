@extends('layouts.sigecon')
@section('title', 'Nova Cidade de Prova - SIGECON')

@section('content')
<div class="container-fluid">
  <div class="row">
    <!-- Lateral -->
    <div class="col-12 col-md-3 col-xl-2 mb-3">
      @include('admin.concursos._menu', ['concurso'=>$concurso, 'active'=>'cidades'])
    </div>

    <!-- Conteúdo -->
    <div class="col-12 col-md-9 col-xl-10">
      <div class="card">
        <div class="card-header fw-semibold">Nova Cidade de Prova</div>
        <div class="card-body">

          <form method="post" action="{{ route('admin.concursos.cidades.store', $concurso) }}">
            @csrf

            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label">* Cidade de Prova</label>
                <input type="text" name="cidade" class="form-control" value="{{ old('cidade') }}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Estado (UF)</label>
                <select name="uf" class="form-select" required>
                  @foreach($ufs as $k => $v)
                    <option value="{{ $k }}" @selected(old('uf')===$k)>{{ $v===''?'UF':$v }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div class="mt-4">
              <label class="form-label d-block">Vagas Disponíveis (Cargos)</label>

              {{-- Botão dropdown + contador --}}
              <div class="dropdown d-inline-block me-2">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                        id="btnCargos" data-bs-toggle="dropdown" aria-expanded="false">
                  <span id="btnCargosText">Nenhum selecionado</span>
                </button>

                <div class="dropdown-menu p-2" aria-labelledby="btnCargos"
                     style="min-width:420px; max-height:300px; overflow:auto">
                  <div class="form-check ps-2 border-bottom pb-2 mb-2">
                    <input class="form-check-input" type="checkbox" id="chkAll">
                    <label class="form-check-label fw-semibold" for="chkAll">Todos</label>
                  </div>

                  @forelse($cargos as $c)
                    <label class="dropdown-item d-flex align-items-center gap-2">
                      <input class="form-check-input me-2 chk-cargo" type="checkbox"
                             name="cargos[]" value="{{ $c->id }}">
                      <span>{{ $c->titulo }}</span>
                    </label>
                  @empty
                    <div class="text-muted px-2">— Sem cargos cadastrados para este concurso —</div>
                  @endforelse
                </div>
              </div>

              <small class="text-muted d-block mt-2">
                Clique para abrir a lista; marque/desmarque. “Todos” seleciona/limpa tudo.
              </small>
            </div>

            <div class="mt-4 d-flex gap-2">
              <button class="btn btn-primary" type="submit">Salvar e Fechar</button>
              <a class="btn btn-outline-secondary" href="{{ route('admin.concursos.cidades.index', $concurso) }}">Cancelar</a>
            </div>
          </form>

        </div>
      </div>
    </div>
  </div>
</div>

{{-- scripts --}}
@push('scripts')
<script>
(function(){
  const text = document.getElementById('btnCargosText');
  const all  = document.getElementById('chkAll');
  const boxes= Array.from(document.querySelectorAll('.chk-cargo'));

  function update(){
    const n = boxes.filter(b => b.checked).length;
    text.textContent = n ? (n === 1 ? '1 selecionado' : n+' selecionados') : 'Nenhum selecionado';
    if (boxes.length) {
      all.checked = n === boxes.length;
      all.indeterminate = n > 0 && n < boxes.length;
    }
  }
  if (all){
    all.addEventListener('change', () => {
      const v = all.checked;
      boxes.forEach(b => b.checked = v);
      update();
    });
  }
  boxes.forEach(b => b.addEventListener('change', update));
  update();
})();
</script>
@endpush
@endsection
