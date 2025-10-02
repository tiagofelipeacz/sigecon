@section('content')
@php
  // Helper rápido pra marcar ativo
  $isActive = function(string $name) use ($active) {
      return ($active ?? '') === $name ? 'active' : '';
  };
@endphp

<nav class="list-group list-group-flush">
  <a class="list-group-item list-group-item-action d-flex align-items-center {{ $isActive('overview') }}"
     href="{{ route('admin.concursos.config', $concurso->id) }}">
    <span class="me-2">⚙️</span> Configurações
  </a>

  <a class="list-group-item list-group-item-action d-flex align-items-center {{ $isActive('vagas') }}"
     href="{{ route('admin.concursos.vagas.index', $concurso->id) }}">
    <span class="me-2">🧩</span> Vagas
  </a>

  <a class="list-group-item list-group-item-action d-flex align-items-center {{ $isActive('isencoes') }}"
     href="{{ route('admin.concursos.isencoes.index', $concurso->id) }}">
    <span class="me-2">🧾</span> Pedidos de Isenção
  </a>

  <a class="list-group-item list-group-item-action d-flex align-items-center {{ $isActive('impugnacoes') }}"
     href="{{ route('admin.concursos.impugnacoes.index', $concurso->id) }}">
    <span class="me-2">📄</span> Impugnações
  </a>
</nav>

<style>
  /* Ajuste leve para combinar com Bootstrap padrão */
  .list-group-item.active {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
    font-weight: 600;
  }
</style>

@endsection
