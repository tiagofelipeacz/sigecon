<form method="post" action="{{ $action }}">
  @csrf
  @if($method !== 'POST') @method($method) @endif

  @include('admin.concursos.cidades.form-fields', [
    'concurso'     => $concurso,
    'cidade'       => $cidade ?? null,
    'ufs'          => $ufs ?? [],
    'cargos'       => $cargos ?? [],
    'selecionados' => $selecionados ?? [],
  ])

  <div class="mt-3" style="display:flex;gap:.5rem;flex-wrap:wrap">
    <button class="btn btn-primary" type="submit">Salvar e Fechar</button>
    <a class="btn btn-light" href="{{ route('admin.concursos.cidades.index', $concurso) }}">Cancelar</a>
  </div>
</form>
