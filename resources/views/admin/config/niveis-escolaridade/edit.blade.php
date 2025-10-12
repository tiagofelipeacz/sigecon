{{-- resources/views/admin/config/niveis-escolaridade/edit.blade.php --}}
@extends('layouts.sigecon')
@section('title', 'Editar Nível de Escolaridade')

@section('content')
  <h1>Editar Nível de Escolaridade</h1>
  <p class="sub">Ajuste as informações e salve para aplicar no sistema.</p>

  @if ($errors->any())
    <div class="mb-3 rounded border border-red-300 bg-red-50 p-3 text-red-800">
      <div class="font-semibold mb-1">Corrija os erros abaixo:</div>
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @php
    use Illuminate\Support\Str;

    // ID para rota
    $nivelId = is_object($nivel) ? ($nivel->id ?? null) : $nivel;

    // URL base da listagem
    $indexUrl  = \Route::has('admin.config.niveis-escolaridade.index')
      ? route('admin.config.niveis-escolaridade.index')
      : url('/admin/config/niveis-escolaridade');

    // URL anterior (com filtros/paginação)
    $prev = url()->previous() ?? '';
    $path = parse_url($prev, PHP_URL_PATH) ?: '';

    // Considera "anterior" apenas se for a listagem (evita editar/create)
    $returnTo = ($prev
                 && Str::startsWith($path, '/admin/config/niveis-escolaridade')
                 && !Str::contains($path, ['/editar','/create']))
                ? $prev
                : $indexUrl;
  @endphp

  <form id="nivel-form"
        method="POST"
        action="{{ route('admin.config.niveis-escolaridade.update', $nivelId) }}">
    @csrf
    @method('PUT')

    {{-- para o controller saber para onde voltar ao fechar --}}
    <input type="hidden" name="_return" value="{{ $returnTo }}">

    {{-- Campos + botões (Salvar, Salvar e fechar, Cancelar) vêm do parcial --}}
    @include('admin.config.niveis-escolaridade._form', ['nivel' => $nivel])
  </form>

  <script>
    (function () {
      const form = document.getElementById('nivel-form');
      if (!form) return;

      const indexUrl = @json($returnTo);

      // Normalizador de texto p/ comparação
      const norm = (s) => (s || '').toLowerCase().replace(/\s+/g, ' ').trim();

      // Testes de rótulo/atributos
      const isCancel = (el) => {
        if (!el) return false;
        if (el.dataset?.action === 'cancel') return true;
        const t = norm(el.textContent || el.value);
        return t === 'cancelar' || t === 'voltar' || t === 'cancel';
      };
      const isSaveClose = (el) => {
        if (!el) return false;
        if (el.dataset?.after === 'close' || el.dataset?.action === 'close') return true;
        if (el.name === '_after' && el.value === 'close') return true;
        const t = norm(el.textContent || el.value);
        return /salvar\s*(e|&|\/)\s*fechar/.test(t);
      };

      // Ao submeter, decide o que fazer com base no botão que disparou
      form.addEventListener('submit', function (e) {
        // `e.submitter` identifica exatamente quem disparou o submit (botão, input, etc)
        const btn = e.submitter || document.activeElement;

        // Se for "Cancelar" e estiver como submit por engano, cancela e vai à listagem
        if (isCancel(btn)) {
          e.preventDefault();
          window.location.href = indexUrl;
          return;
        }

        // Se for "Salvar e fechar", garante _after=close e (por redundância) ?after=close
        if (isSaveClose(btn)) {
          let h = form.querySelector('input[name="_after"][value="close"]');
          if (!h) {
            h = document.createElement('input');
            h.type = 'hidden';
            h.name = '_after';
            h.value = 'close';
            form.appendChild(h);
          }
          // redundância: acrescenta ?after=close no action se ainda não houver
          try {
            const url = new URL(form.action, window.location.origin);
            if (!url.searchParams.has('after')) {
              url.searchParams.set('after', 'close');
              form.action = url.toString();
            }
          } catch (_) {}
        }

        // Anti duplo-submit
        setTimeout(() => {
          form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((b) => b.disabled = true);
        }, 0);
      });
    })();
  </script>
@endsection
