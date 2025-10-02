@section('content')
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
@foreach($concursos as $c)
  @php
    $cfg = (array) ($c->configs ?? []);
    $ini = $cfg['inscricoes_inicio'] ?? $c->inscricoes_inicio ?? null;
    $fim = $cfg['inscricoes_fim'] ?? $c->inscricoes_fim ?? null;
    $periodo = null;
    try {
      if ($ini && $fim) {
        $periodo = \Carbon\Carbon::parse($ini)->format('d/m/Y H:i') . ' a ' .
                   \Carbon\Carbon::parse($fim)->format('d/m/Y H:i');
      }
    } catch (\Throwable $e) {}
  @endphp
  <a href="{{ route('site.concursos.show', $c) }}"
     class="block rounded-lg border border-slate-200 bg-white p-4 hover:border-primary-300">
    <div class="text-sm text-slate-500">Concurso Público</div>
    <div class="font-semibold">{{ $c->titulo ?? ('Concurso #'.$c->id) }}</div>
    @if($periodo)
      <div class="mt-1 text-xs text-slate-600">Inscrições: {{ $periodo }}</div>
    @endif
    <div class="mt-3 text-primary-700 text-sm">Informações &raquo;</div>
  </a>
@endforeach
</div>

@endsection
