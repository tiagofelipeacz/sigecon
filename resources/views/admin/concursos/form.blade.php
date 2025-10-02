@extends('layouts.sigecon')

@section('title', ($isEdit ? 'Editar' : 'Novo').' Anexo — '.$concurso->titulo)

@section('content')
<div class="flex gap-6">
  {{-- Sidebar do concurso --}}
  @include('admin.concursos.partials.sidebar-min', ['concurso' => $concurso])

  {{-- Conteúdo --}}
  <div class="flex-1 min-w-0">
    <div class="mb-4">
      <h1 class="text-xl font-semibold">{{ $isEdit ? 'Editar' : 'Novo' }} Anexo</h1>
      <p class="text-sm text-gray-500">Preencha as informações da publicação.</p>
    </div>

    @if ($errors->any())
      <div class="mb-4 rounded border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
        <strong>Corrija os campos abaixo:</strong>
        <ul class="list-disc ml-5">
          @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST"
          action="{{ $isEdit ? route('admin.concursos.anexos.update', [$concurso, $anexo]) : route('admin.concursos.anexos.store', $concurso) }}"
          enctype="multipart/form-data" class="space-y-6">
      @csrf
      @if ($isEdit)
        @method('PUT')
      @endif

      <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Título *</label>
          <input type="text" name="titulo" class="w-full border rounded-md px-3 py-2"
                 value="{{ old('titulo', $anexo->titulo) }}" required>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Tipo *</label>
          <select name="tipo" id="tipo" class="w-full border rounded-md px-3 py-2">
            @php $tipo = old('tipo', $anexo->tipo ?? 'arquivo'); @endphp
            <option value="arquivo" @selected($tipo==='arquivo')>Arquivo</option>
            <option value="link" @selected($tipo==='link')>Link</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Grupo</label>
          <input type="text" name="grupo" class="w-full border rounded-md px-3 py-2"
                 value="{{ old('grupo', $anexo->grupo) }}" placeholder="ex.: Editais, Comunicados...">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Posição</label>
          <input type="number" min="0" name="posicao" class="w-full border rounded-md px-3 py-2"
                 value="{{ old('posicao', $anexo->posicao ?? 0) }}">
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium mb-1">Legenda (opcional)</label>
          <input type="text" name="legenda" class="w-full border rounded-md px-3 py-2"
                 value="{{ old('legenda', $anexo->legenda) }}">
        </div>

        {{-- Campo de arquivo (quando tipo=arquivo) --}}
        <div id="campo-arquivo" class="{{ old('tipo', $anexo->tipo ?? 'arquivo')==='arquivo' ? '' : 'hidden' }}">
          <label class="block text-sm font-medium mb-1">Arquivo</label>
          <input type="file" name="arquivo" class="w-full border rounded-md px-3 py-2">
          @if ($isEdit && $anexo->arquivo_path)
            <p class="text-xs text-gray-500 mt-1">Arquivo atual: {{ $anexo->arquivo_path }}</p>
          @endif
        </div>

        {{-- Campo de link (quando tipo=link) --}}
        <div id="campo-link" class="{{ old('tipo', $anexo->tipo ?? 'arquivo')==='link' ? '' : 'hidden' }}">
          <label class="block text-sm font-medium mb-1">URL</label>
          <input type="url" name="link_url" class="w-full border rounded-md px-3 py-2"
                 value="{{ old('link_url', $anexo->link_url) }}" placeholder="https://...">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Publicado em</label>
          <input type="datetime-local" name="publicado_em" class="w-full border rounded-md px-3 py-2"
                 value="{{ old('publicado_em', optional($anexo->publicado_em)->format('Y-m-d\TH:i')) }}">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Tempo indeterminado?</label>
          <select name="tempo_indeterminado" id="tempo_indeterminado" class="w-full border rounded-md px-3 py-2">
            @php $ti = filter_var(old('tempo_indeterminado', $anexo->tempo_indeterminado ?? false), FILTER_VALIDATE_BOOLEAN); @endphp
            <option value="0" @selected(!$ti)>Não</option>
            <option value="1" @selected($ti)>Sim</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Visível de</label>
          <input type="datetime-local" name="visivel_de" class="w-full border rounded-md px-3 py-2"
                 value="{{ old('visivel_de', optional($anexo->visivel_de)->format('Y-m-d\TH:i')) }}">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Visível até</label>
          <input type="datetime-local" name="visivel_ate" class="w-full border rounded-md px-3 py-2"
                 value="{{ old('visivel_ate', optional($anexo->visivel_ate)->format('Y-m-d\TH:i')) }}">
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Ativo</label>
          @php $ativo = filter_var(old('ativo', $anexo->ativo ?? true), FILTER_VALIDATE_BOOLEAN); @endphp
          <select name="ativo" class="w-full border rounded-md px-3 py-2">
            <option value="1" @selected($ativo)>Sim</option>
            <option value="0" @selected(!$ativo)>Não</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Restrito</label>
          @php $restrito = filter_var(old('restrito', $anexo->restrito ?? false), FILTER_VALIDATE_BOOLEAN); @endphp
          <select name="restrito" id="restrito" class="w-full border rounded-md px-3 py-2">
            <option value="0" @selected(!$restrito)>Não</option>
            <option value="1" @selected($restrito)>Sim</option>
          </select>
        </div>

        {{-- Cargos (se restrito) --}}
        <div id="restrito-cargos" class="md:col-span-2 {{ $restrito ? '' : 'hidden' }}">
          <label class="block text-sm font-medium mb-1">Cargos permitidos (opcional)</label>

          @if(!empty($cargos))
            <div class="grid gap-2 md:grid-cols-2">
              @foreach($cargos as $c)
                @php
                  $sel = collect(old('restrito_cargos', $anexo->restrito_cargos ?: []))->contains($c->id);
                @endphp
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" name="restrito_cargos[]" value="{{ $c->id }}" @checked($sel)>
                  <span>{{ $c->nome }}</span>
                </label>
              @endforeach
            </div>
            <p class="text-xs text-gray-500 mt-1">Se nada for marcado, o anexo ficará restrito genericamente (sem amarrar a cargos específicos).</p>
          @else
            <p class="text-xs text-gray-500">Nenhuma lista de cargos disponível.</p>
          @endif
        </div>
      </div>

      <div class="pt-2 flex items-center gap-3">
        <button class="px-4 py-2 rounded-md bg-primary-700 text-white">Salvar</button>
        <a href="{{ route('admin.concursos.anexos.index', $concurso) }}" class="text-gray-700 hover:underline">Cancelar</a>
      </div>
    </form>
  </div>
</div>

{{-- JS básico para alternar campos --}}
<script>
  (function(){
    const tipoSel = document.getElementById('tipo');
    const campoArq = document.getElementById('campo-arquivo');
    const campoLink = document.getElementById('campo-link');
    const restritoSel = document.getElementById('restrito');
    const boxCargos = document.getElementById('restrito-cargos');

    function toggleTipo() {
      if (tipoSel.value === 'arquivo') {
        campoArq.classList.remove('hidden');
        campoLink.classList.add('hidden');
      } else {
        campoLink.classList.remove('hidden');
        campoArq.classList.add('hidden');
      }
    }

    function toggleRestrito() {
      if (restritoSel.value === '1') {
        boxCargos.classList.remove('hidden');
      } else {
        boxCargos.classList.add('hidden');
      }
    }

    tipoSel && tipoSel.addEventListener('change', toggleTipo);
    restritoSel && restritoSel.addEventListener('change', toggleRestrito);
  })();
</script>
@endsection
