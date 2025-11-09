@extends('layouts.site')
@section('title','Concurso #'.$concurso->id)

@section('content')
<div class="container" style="padding:24px 0;max-width:900px">
  <h1>{{ $concurso->titulo ?? 'Concurso '.$concurso->id }}</h1>

  <p class="text-muted">
    Edital: {{ $concurso->edital_numero ?? '-' }}
  </p>

  {{-- Se quiser, pode colocar aqui um link para a página pública do concurso --}}
  <p>
    <a href="{{ url('/concursos/'.$concurso->id) }}" target="_blank">
      Ver página completa do concurso (pública)
    </a>
  </p>

  @if($jaInscrito)
    <div class="alert alert-success mt-3">
      Você já está inscrito neste concurso.
    </div>
  @else
    <form method="post" action="{{ route('candidato.concursos.inscrever', $concurso->id) }}" class="mt-3">
      @csrf
      <button type="submit" class="btn btn-primary">
        Fazer inscrição neste concurso
      </button>
    </form>
  @endif
</div>
@endsection
