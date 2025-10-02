@extends('layouts.sigecon')
@section('title', 'Novo Concurso - SIGECON')

@section('content')
@php
  // Garante variáveis mesmo que o controller não envie
  $concurso = $concurso ?? (object)[];
  // Em alguns controllers você chama de $clients (id => nome)
  // Em outros, de $clientes. Aqui tratamos ambos.
  $clientes = $clientes ?? ($clients ?? collect());
@endphp

  <h1>Novo Concurso</h1>
  <p class="sub">Cadastre um novo certame</p>

  @if ($errors->any())
    <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-red-800">
      <div class="font-semibold mb-1">Corrija os erros abaixo:</div>
      <ul class="list-disc pl-5">
        @foreach ($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form id="form-concurso" method="POST" action="{{ route('admin.concursos.store') }}">
    @csrf

    @includeFirst(
      ['admin.concursos._form', 'admin.concursos.form', 'concursos._form', 'concursos.form'],
      ['concurso' => $concurso, 'clientes' => $clientes]
    )

    {{-- Bridges para os nomes que o controller espera (sem alterar seu form) --}}
    <input type="hidden" name="cliente_id" id="mirror_cliente_id" value="">
    <input type="hidden" name="oculto" id="mirror_oculto" value="">

    <script>
    (function(){
      const form = document.getElementById('form-concurso');

      // Seleciona o client_id (por id ou name) -> preenche cliente_id (hidden)
      const selCli = form.querySelector('#client_id') || form.querySelector('select[name="client_id"]');
      const hidCli = form.querySelector('#mirror_cliente_id');
      function syncCliente(){ if (hidCli) hidCli.value = selCli ? (selCli.value || '') : ''; }
      selCli && selCli.addEventListener('change', syncCliente);

      // Radios ocultar_site -> preenche oculto (hidden)
      const radiosOcultar = form.querySelectorAll('input[name="ocultar_site"]');
      const hidOculto = form.querySelector('#mirror_oculto');
      function syncOculto(){
        const checked = Array.from(radiosOcultar).find(r => r.checked);
        if (hidOculto) hidOculto.value = checked ? checked.value : '';
      }
      radiosOcultar.forEach(r => r.addEventListener('change', syncOculto));

      // Sincroniza na carga (garante valores mesmo sem interação)
      syncCliente();
      syncOculto();
    })();
    </script>

   <div class="toolbar" style="margin-top:16px; display:flex; gap:12px">
  <a href="#" class="btn"
     onclick="document.getElementById('form-concurso').requestSubmit(); return false;">
    Salvar
  </a>

  <a href="{{ url('/admin/inicio') }}" class="btn">Voltar</a>
</div>

  </form>
@endsection
