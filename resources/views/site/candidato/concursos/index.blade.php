@extends('layouts.site')
@section('title','Concursos disponíveis')

@section('content')
<div class="container" style="padding:24px 0">
  <h1>Concursos disponíveis</h1>

  @if($concursos->isEmpty())
    <p>Não há concursos disponíveis no momento.</p>
  @else
    <table class="table mt-3">
      <thead>
        <tr>
          <th>#</th>
          <th>Título</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        @foreach($concursos as $c)
          <tr>
            <td>{{ $c->id }}</td>
            <td>{{ $c->titulo ?? 'Concurso '.$c->id }}</td>
            <td>
              <a href="{{ route('candidato.concursos.show', $c->id) }}" class="btn btn-sm btn-primary">
                Ver detalhes / inscrever
              </a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    {{ $concursos->links() }}
  @endif
</div>
@endsection
