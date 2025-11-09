@extends('layouts.site')
@section('title','Recuperar senha')

@section('content')
<div class="container" style="max-width:540px;padding:24px 0">
  <h1 style="margin-bottom:16px">Recuperar senha</h1>
  <p class="text-muted">Informe os dados do seu cadastro e defina uma nova senha.</p>

  <form method="post" action="{{ route('candidato.password.recover.post') }}">
    @csrf

    <div class="mb-3">
      <label class="form-label">CPF</label>
      <input type="text" name="cpf" class="form-control" value="{{ old('cpf') }}" required>
      @error('cpf') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Data de Nascimento</label>
      <input type="date" name="data_nascimento" class="form-control"
             value="{{ old('data_nascimento') }}" required>
      @error('data_nascimento') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
      <label class="form-label">E-mail cadastrado</label>
      <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
      @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Nova senha</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Confirmar nova senha</label>
      <input type="password" name="password_confirmation" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary w-100">Atualizar senha</button>
  </form>
</div>
@endsection
