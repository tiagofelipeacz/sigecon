@extends('layouts.sigecon')
@section('title', 'Novo Candidato - SIGECON')

@section('content')
  <h1>Novo Candidato</h1>
  <p class="sub">Preencha as informações e salve.</p>

  <form method="POST" action="{{ route('admin.candidatos.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.candidatos._form', ['candidato' => $candidato, 'ufs' => $ufs])
  </form>
@endsection
