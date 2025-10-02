<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{ $concurso->titulo ?? $concurso->nome ?? ('Concurso #'.$concurso->id) }}</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="{{ asset('css/gc-theme.css') }}">
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

  <nav class="border-b bg-white">
    <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
      <a href="{{ route('site.concursos.index') }}" class="font-semibold">&larr; Concursos</a>
      <span class="text-primary-700 font-medium">Detalhes</span>
    </div>
  </nav>

  <main class="max-w-7xl mx-auto px-4 py-6">
    @php
      $titulo  = $concurso->titulo ?? $concurso->nome ?? ('Concurso #'.$concurso->id);
      $cliente = optional($concurso->client)->name ?? optional($concurso->client)->nome ?? '—';
      $st      = $concurso->status ?? $concurso->situacao ?? '—';
      $ini     = $concurso->inscricao_inicio ?? $concurso->inscricoes_inicio ?? null;
      $fim     = $concurso->inscricao_fim ?? $concurso->inscricoes_fim ?? null;
      $periodo = ($ini && $fim) ? \Illuminate\Support\Carbon::parse($ini)->format('d/m/Y').' — '.\Illuminate\Support\Carbon::parse($fim)->format('d/m/Y') : '—';
    @endphp

    <div class="grid gap-4 md:grid-cols-3">
      <div class="md:col-span-2">
        <div class="card p-5">
          <h1 class="text-2xl font-semibold mb-1">{{ $titulo }}</h1>
          <p class="text-muted mb-4">{{ $cliente }}</p>

          <div class="grid gap-3 md:grid-cols-3">
            <div>
              <div class="text-xs text-muted uppercase">Situação</div>
              <div class="mt-1">
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
              </div>
            </div>
            <div>
              <div class="text-xs text-muted uppercase">Inscrições</div>
              <div class="mt-1">{{ $periodo }}</div>
            </div>
            <div>
              <div class="text-xs text-muted uppercase">Edital</div>
              <div class="mt-1">
                {{-- Se houver campo/URL do edital no banco, troque abaixo --}}
                @if(!empty($concurso->edital_url))
                  <a href="{{ $concurso->edital_url }}" target="_blank" class="link">Abrir edital</a>
                @else
                  —
                @endif
              </div>
            </div>
          </div>

          @if(!empty($concurso->descricao))
            <hr class="my-5">
            <div class="prose max-w-none">
              {!! nl2br(e($concurso->descricao)) !!}
            </div>
          @endif
        </div>

        {{-- ====== NOVA SEÇÃO: VAGAS (pública) ====== --}}
        @if(!empty($vagasPorCargo))
          <div class="card p-5 mt-4">
            <h2 class="text-lg font-semibold mb-3">Vagas</h2>

            @foreach($vagasPorCargo as $cargo)
              <div class="mb-5">
                <div class="flex items-center justify-between flex-wrap gap-2">
                  <div class="text-sm">
                    <strong class="uppercase">{{ $cargo['cargo'] }}</strong>
                    @if(!empty($cargo['nivel']))
                      <span class="text-muted"> • Nível: {{ $cargo['nivel'] }}</span>
                    @endif
                    @if(!empty($cargo['codigo']))
                      <span class="text-muted"> • Cód.: {{ $cargo['codigo'] }}</span>
                    @endif
                  </div>
                  <div class="text-sm">
                    @php $valor = $cargo['valor'] ?? 0; @endphp
                    <span class="badge bg-light text-gray-700">
                      Taxa: {{ $valor > 0 ? ('R$ '.number_format($valor, 2, ',', '.')) : 'Gratuito' }}
                    </span>
                  </div>
                </div>

                <div class="table-responsive mt-3">
                  <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="min-width:180px;">Localidade</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Ampla</th>
                        @foreach($tiposVaga as $t)
                          <th class="text-right">{{ $t->nome }}</th>
                        @endforeach
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($cargo['locais'] as $loc)
                        <tr>
                          <td>{{ $loc['nome'] }}</td>
                          <td class="text-right">{{ (int) $loc['total'] }}</td>
                          <td class="text-right">{{ (int) $loc['ampla'] }}</td>
                          @foreach($tiposVaga as $t)
                            <td class="text-right">{{ (int)($loc['cotas'][$t->id] ?? 0) }}</td>
                          @endforeach
                        </tr>
                      @endforeach
                    </tbody>
                    <tfoot>
                      <tr class="font-semibold">
                        <td class="text-right">Totais do cargo</td>
                        <td class="text-right">{{ (int) $cargo['total'] }}</td>
                        <td class="text-right">{{ (int) $cargo['ampla'] }}</td>
                        @foreach($tiposVaga as $t)
                          <td class="text-right">{{ (int)($cargo['cotas'][$t->id] ?? 0) }}</td>
                        @endforeach
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

              @if(!$loop->last)
                <hr class="my-3">
              @endif
            @endforeach
          </div>
        @endif
        {{-- ====== FIM: VAGAS ====== --}}

      </div>

      <aside class="md:col-span-1">
        <div class="card p-5">
          <h2 class="text-lg font-semibold mb-3">Ações</h2>
          <a href="{{ route('site.concursos.index') }}" class="btn btn-outline-secondary w-full">Voltar</a>
        </div>
      </aside>
    </div>
  </main>

  <script src="{{ asset('js/gc-theme.js') }}" defer></script>
</body>
</html>
