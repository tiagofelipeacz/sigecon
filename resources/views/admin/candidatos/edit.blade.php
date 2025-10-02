@extends('layouts.sigecon')
@section('title', 'Editar Candidato - SIGECON')

@section('content')
  <h1>Editar Candidato</h1>
  <p class="sub">Ajuste as informações e salve.</p>

  <form method="POST" action="{{ route('admin.candidatos.update', $candidato) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.candidatos._form', ['candidato' => $candidato, 'ufs' => $ufs])
  </form>
@endsection
