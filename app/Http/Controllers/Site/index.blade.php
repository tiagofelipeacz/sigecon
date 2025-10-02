<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Concursos</title>

  <!-- Tailwind + seu tema (mesmo stack já usado no admin) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="{{ asset('css/gc-theme.css') }}">
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

  <nav class="border-b bg-white">
    <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
      <a href="{{ url('/') }}" class="font-semibold">Início</a>
      <a href="{{ route('site.concursos.index') }}" class="text-primary-700 font-medium">Concursos</a>
    </div>
  </nav>

  <main class="max-w-7xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-semibold">Concursos</h1>
    </div>

    <form method="GET" class="grid gap-3 md:grid-cols-12 mb-4">
      <div class="md:col-span-6">
        <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por título, cliente, etc.">
      </div>
      <div class="md:col-span-3">
        @php $statusAtual = $status ?? 'todos'; @endphp
        <select name="status" class="form-select">
          <option value="todos" {{ $statusAtual==='todos'?'selected':'' }}>Todos</option>
          <option value="rascunho" {{ $statusAtual==='rascunho'?'selected':'' }}>Rascunho</option>
          <option value="em-andamento" {{ $statusAtual==='em-andamento'?'selected':'' }}>Em andamento</option>
          <option value="encerrado" {{ $statusAtual==='encerrado'?'selected':'' }}>Encerrado</option>
          <option value="arquivado" {{ $statusAtual==='arquivado'?'selected':'' }}>Arquivado</option>
        </select>
      </div>
      <div class="md:col-span-3">
        <button class="btn btn-outline-secondary w-full">Aplicar</button>
      </div>
    </form>

    <div class="card overflow-hidden">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Concurso</th>
              <th>Cliente</th>
              <th>Status</th>
              <th>Período</th>
              <th class="text-right">Ações</th>
            </tr>
          </thead>
          <tbody>
          @forelse($concursos as $concurso)
            @php
              $titulo = $concurso->titulo ?? $concurso->nome ?? ('Concurso #'.$concurso->id);
              $cliente = optional($concurso->client)->name ?? optional($concurso->client)->nome ?? '—';
              $st = $concurso->status ?? $concurso->situacao ?? '—';
              $ini = $concurso->inscricao_inicio ?? $concurso->inscricoes_inicio ?? null;
              $fim = $concurso->inscricao_fim ?? $concurso->inscricoes_fim ?? null;
              $periodo = ($ini && $fim) ? \Illuminate\Support\Carbon::parse($ini)->format('d/m/Y').' — '.\Illuminate\Support\Carbon::parse($fim)->format('d/m/Y') : '—';
            @endphp
            <tr>
              <td class="align-top">
                <div class="font-semibold">{{ $titulo }}</div>
                <div class="text-muted text-sm">#{{ $concurso->id }}</div>
              </td>
              <td class="align-top">
                <div>{{ $cliente }}</div>
                <div class="text-muted text-sm">Processo Seletivo</div>
              </td>
              <td class="align-top">
                @if($st === 'em-andamento')
                  <span class="badge bg-info">EM ANDAMENTO</span>
                @elseif($st === 'encerrado')
                  <span class="badge bg-secondary">ENCERRADO</span>
                @elseif($st === 'arquivado')
                  <span class="badge bg-dark">ARQUIVADO</span>
                @elseif($st === 'rascunho')
                  <span class="badge bg-warning">RASCUNHO</span>
                @else
                  <span class="badge bg-light text-gray-700">{{ strtoupper($st) }}</span>
                @endif
              </td>
              <td class="align-top">
                <div>{{ $periodo }}</div>
                <div class="text-muted text-sm">Horário local</div>
              </td>
              <td class="text-right">
                <a href="{{ route('site.concursos.show', $concurso) }}" class="btn btn-sm btn-outline-primary">Mais informações</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-8 text-muted">Nenhum concurso encontrado.</td>
            </tr>
          @endforelse
          </tbody>
        </table>
      </div>

      @if(method_exists($concursos, 'links'))
        <div class="card-footer bg-white">
          {!! $concursos->onEachSide(1)->links('pagination::bootstrap-4') !!}
        </div>
      @endif
    </div>
  </main>

  <script src="{{ asset('js/gc-theme.js') }}" defer></script>
</body>
</html>
