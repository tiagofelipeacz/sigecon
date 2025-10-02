{{-- resources/views/admin/config/condicoes-especiais/form.blade.php --}}
@extends('layouts.sigecon')
@section('title', ($tipo->exists ? 'Editar' : 'Novo') . ' - Condição Especial - SIGECON')

@section('content')
  @php
    // Valores padrão (garante 0/1 inteiros p/ os radios)
    $v = fn($k,$d=0)=> (int) old($k, (int) data_get($tipo,$k,$d));
  @endphp

  <h1>{{ $tipo->exists ? 'Editar Condição Especial' : 'Nova Condição Especial' }}</h1>
  <p class="sub">Preencha as informações e salve para aplicar no sistema.</p>

  {{-- Flash / Erros --}}
  @if (session('success'))
    <div class="mb-3 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">
      {{ session('success') }}
    </div>
  @endif
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

  <style>
    /* Mesmo estilo usado nas telas de configuração/listagem */
    .card-min{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin-bottom:16px; }
    .card-min .hd{ padding:10px 14px; font-weight:600; background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; }
    .form-min{ padding:14px; display:grid; grid-template-columns: 220px 1fr; gap:12px; align-items:center; }
    .form-min label{ font-weight:600; }
    .form-min input[type="text"],
    .form-min textarea,
    .form-min select{
      width:100%; background:#fff; border:1px solid #d1d5db; border-radius:8px; padding:9px 10px;
    }
    .muted{ color:#6b7280; font-size:12px; }
    .radio-row{ display:flex; gap:16px; align-items:center; }
    @media (max-width:920px){ .form-min{ grid-template-columns:1fr; } }
  </style>

  <form method="POST"
        action="{{ $tipo->exists ? route('admin.config.condicoes_especiais.update', $tipo) : route('admin.config.condicoes_especiais.store') }}">
    @csrf
    @if($tipo->exists) @method('PUT') @endif

    <div class="card-min">
      <div class="hd">Dados da Condição Especial</div>
      <div class="form-min">
        <label>Grupo</label>
        <input type="text" name="grupo"
               value="{{ old('grupo', $tipo->grupo) }}"
               placeholder="Ex.: Grupo A">

        <label>Condição Especial *</label>
        <input type="text" name="titulo" required
               value="{{ old('titulo', $tipo->titulo) }}"
               placeholder="Ex.: Prova Ampliada">

        <label>Exibir campo Observações?</label>
        <div class="radio-row">
          <label><input type="radio" name="exibir_observacoes" value="1" {{ $v('exibir_observacoes')===1 ? 'checked':'' }}> Sim</label>
          <label><input type="radio" name="exibir_observacoes" value="0" {{ $v('exibir_observacoes')===0 ? 'checked':'' }}> Não</label>
        </div>

        <label>Necessita Laudo Médico?</label>
        <div class="radio-row">
          <label><input type="radio" name="necessita_laudo_medico" value="1" {{ $v('necessita_laudo_medico')===1 ? 'checked':'' }}> Sim</label>
          <label><input type="radio" name="necessita_laudo_medico" value="0" {{ $v('necessita_laudo_medico')===0 ? 'checked':'' }}> Não</label>
        </div>

        <label>Envio de Laudo Médico Obrigatório?</label>
        <div class="radio-row">
          <label><input type="radio" name="laudo_obrigatorio" value="1" {{ $v('laudo_obrigatorio')===1 ? 'checked':'' }}> Sim</label>
          <label><input type="radio" name="laudo_obrigatorio" value="0" {{ $v('laudo_obrigatorio')===0 ? 'checked':'' }}> Não</label>
        </div>
        <div class="muted" style="grid-column:1/-1;">
          Só faz sentido se <strong>Necessita Laudo Médico</strong> for “Sim”. (O campo abaixo é desativado automaticamente quando for “Não”.)
        </div>

        <label>Necessita Arquivo (Outros/Genérico)?</label>
        <div class="radio-row">
          <label><input type="radio" name="exige_arquivo_outros" value="1" {{ $v('exige_arquivo_outros')===1 ? 'checked':'' }}> Sim</label>
          <label><input type="radio" name="exige_arquivo_outros" value="0" {{ $v('exige_arquivo_outros')===0 ? 'checked':'' }}> Não</label>
        </div>

        <label>Tamanho da Fonte Especial</label>
        <input type="text" name="tamanho_fonte_especial"
               value="{{ old('tamanho_fonte_especial', $tipo->tamanho_fonte_especial) }}"
               inputmode="numeric" pattern="[0-9]*" placeholder="Ex.: 24">

        <label>Ativo?</label>
        <div class="radio-row">
          <label><input type="radio" name="ativo" value="1" {{ $v('ativo',1)===1 ? 'checked':'' }}> Sim</label>
          <label><input type="radio" name="ativo" value="0" {{ $v('ativo',1)===0 ? 'checked':'' }}> Não</label>
        </div>

        <label>Impressão duplicada?</label>
        <div class="radio-row">
          <label><input type="radio" name="impressao_duplicada" value="1" {{ $v('impressao_duplicada')===1 ? 'checked':'' }}> Sim</label>
          <label><input type="radio" name="impressao_duplicada" value="0" {{ $v('impressao_duplicada')===0 ? 'checked':'' }}> Não</label>
        </div>

        <label class="full">Informações ao Candidato</label>
        <textarea name="info_candidato" rows="4" class="full"
                  placeholder="Orientações específicas, documentos aceitos, prazos, etc.">{{ old('info_candidato', $tipo->info_candidato) }}</textarea>
      </div>
    </div>

    {{-- Barra de ações: igual aos Tipos de Vagas Especiais --}}
    <div class="toolbar" style="display:flex; gap:.5rem;">
      @if($tipo->exists)
        <button class="btn" type="submit">Salvar</button>
        <button class="btn" type="submit" name="fechar" value="1">Salvar e Fechar</button>
      @else
        <button class="btn" type="submit" name="fechar" value="1">Salvar e Fechar</button>
      @endif
      <a class="btn" href="{{ route('admin.config.condicoes_especiais.index') }}">Cancelar</a>
    </div>
  </form>

  <script>
    (function(){
      const fm = document.currentScript.closest('form');
      if (!fm) return;
      const need = () => fm.querySelector('input[name="necessita_laudo_medico"]:checked')?.value === '1';
      const setLaudoObrig = (enabled) => {
        fm.querySelectorAll('input[name="laudo_obrigatorio"]').forEach(el => {
          el.disabled = !enabled;
        });
        if (!enabled) {
          // força "Não" quando não precisa de laudo
          const no = fm.querySelector('input[name="laudo_obrigatorio"][value="0"]');
          if (no) no.checked = true;
        }
      };

      setLaudoObrig(need());
      fm.addEventListener('change', (e) => {
        if (e.target && e.target.name === 'necessita_laudo_medico') {
          setLaudoObrig(need());
        }
      });
    })();
  </script>
@endsection
