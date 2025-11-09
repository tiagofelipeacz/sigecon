@extends('layouts.site')
@section('title','Área do Candidato')

@section('content')
<div class="container" style="padding:24px 0">
  <h1>Olá, {{ $user->nome }}</h1>
  <p class="text-muted">Bem-vindo à sua área do candidato.</p>

  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <div class="mt-3">
    <a href="{{ route('candidato.concursos') }}" class="btn btn-primary me-2">Concursos disponíveis</a>
    <a href="{{ route('candidato.inscricoes') }}" class="btn btn-outline-primary">
      Minhas inscrições ({{ $totalInscricoes }})
    </a>
  </div>

  <form method="post" action="{{ route('candidato.logout') }}" class="mt-3">
    @csrf
    <button type="submit" class="btn btn-link text-danger p-0">Sair</button>
  </form>
</div>
@endsection
