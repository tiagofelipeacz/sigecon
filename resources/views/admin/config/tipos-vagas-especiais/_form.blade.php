@php
    // Aceita várias chaves de model vindas do controller
    $tipo = $tipo ?? $tipoVagaEspecial ?? $tipoVaga ?? $item ?? $record ?? (object)[];

    // Helper: old() -> model -> default, com fallbacks e normalização
    $val = function (string $key, $default = null) use ($tipo) {
        $raw = old($key, data_get($tipo, $key, $default));

        // titulo <-> nome
        if (($raw === null || $raw === '') && $key === 'titulo') {
            $raw = old('nome', data_get($tipo, 'nome', $default));
        }
        if (($raw === null || $raw === '') && $key === 'nome') {
            $raw = old('titulo', data_get($tipo, 'titulo', $default));
        }

        // necessita_laudo_medico <-> necessita_laudo
        if (($raw === null || $raw === '') && $key === 'necessita_laudo_medico') {
            $raw = old('necessita_laudo', data_get($tipo, 'necessita_laudo', $default));
        }

        // exige_arquivo_outros <-> envio_arquivo (sim/nao)
        if (($raw === null || $raw === '') && $key === 'exige_arquivo_outros') {
            $ea = old('envio_arquivo', data_get($tipo, 'envio_arquivo', $default));
            if ($ea === 'sim')  $raw = 1;
            if ($ea === 'nao')  $raw = 0;
        }

        // Normaliza bools
        if (in_array($key, [
            'ativo','necessita_laudo_medico','laudo_obrigatorio',
            'informar_tipo_deficiencia','autodeclaracao','exige_arquivo_outros',
            'necessita_laudo'
        ], true)) {
            return (int) (!!$raw);
        }

        return $raw;
    };

    $isEdit = (bool) data_get($tipo, 'id');
@endphp

<style>
  .card-min{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin-bottom:16px; }
  .card-min .hd{ padding:10px 14px; font-weight:600; background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; }
  .form-min{ padding:14px; display:grid; grid-template-columns: 220px 1fr; gap:12px; align-items:center; }
  .form-min label{ font-weight:600; }
  .form-min input[type="text"], .form-min input[type="number"], .form-min textarea, .form-min select{
    width:100%; background:#fff; border:1px solid #d1d5db; border-radius:8px; padding:9px 10px;
  }
  .radio-row{ display:flex; gap:16px; align-items:center; }
  .muted{ color:#6b7280; font-size:12px; }
  .full{ grid-column: 1 / -1; }
  @media (max-width:920px){ .form-min{ grid-template-columns: 1fr; } }
</style>

<div class="card-min">
  <div class="hd">{{ $isEdit ? 'Editar' : 'Novo' }} - Tipo de Vaga Especial</div>
  <div class="form-min">

    {{-- Grupo --}}
    <label>Grupo</label>
    <input type="text" name="grupo" value="{{ $val('grupo') }}" placeholder="Ex.: PcD, Ampla, Outras…">

    {{-- Título (UI) + nome (hidden legacy) --}}
    <label>Título *</label>
    <input type="text" name="titulo" required value="{{ $val('titulo') }}" placeholder="Ex.: Pessoa com Deficiência, Afrodescendente, etc.">
    <input type="hidden" name="nome" id="hidden-nome" value="{{ $val('nome') }}">

    {{-- Necessita de Laudo Médico (UI) + hidden necessita_laudo --}}
    <label>Necessita de Laudo Médico?</label>
    <div class="radio-row">
      <label><input type="radio" name="necessita_laudo_medico" value="1" {{ $val('necessita_laudo_medico', 0) === 1 ? 'checked':'' }}> Sim</label>
      <label><input type="radio" name="necessita_laudo_medico" value="0" {{ $val('necessita_laudo_medico', 0) === 0 ? 'checked':'' }}> Não</label>
    </div>
    <input type="hidden" name="necessita_laudo" id="hidden-necessita-laudo" value="{{ $val('necessita_laudo_medico', 0) }}">

    {{-- Laudo obrigatório (UI COM OUTRO NAME) + hidden real --}}
    <label>Envio de Laudo Médico Obrigatório?</label>
    <div class="radio-row">
      <label><input type="radio" name="laudo_obrigatorio_choice" value="1" {{ $val('laudo_obrigatorio', 0) === 1 ? 'checked':'' }}> Sim</label>
      <label><input type="radio" name="laudo_obrigatorio_choice" value="0" {{ $val('laudo_obrigatorio', 0) === 0 ? 'checked':'' }}> Não</label>
    </div>
    {{-- Sempre enviar algo, mesmo se os radios ficarem desabilitados --}}
    <input type="hidden" name="laudo_obrigatorio" id="hidden-laudo-obrigatorio" value="{{ $val('laudo_obrigatorio', 0) }}">
    <div class="muted full">Se <strong>Necessita de Laudo Médico</strong> = “Não”, este campo é desativado e enviado como “Não”.</div>

    {{-- Informar Tipo de Deficiência --}}
    <label>Informar Tipo de Deficiência?</label>
    <div class="radio-row">
      <label><input type="radio" name="informar_tipo_deficiencia" value="1" {{ $val('informar_tipo_deficiencia', 0) === 1 ? 'checked':'' }}> Sim</label>
      <label><input type="radio" name="informar_tipo_deficiencia" value="0" {{ $val('informar_tipo_deficiencia', 0) === 0 ? 'checked':'' }}> Não</label>
    </div>

    {{-- Autodeclaração --}}
    <label>Autodeclaração?</label>
    <div class="radio-row">
      <label><input type="radio" name="autodeclaracao" value="1" {{ $val('autodeclaracao', 0) === 1 ? 'checked':'' }}> Sim</label>
      <label><input type="radio" name="autodeclaracao" value="0" {{ $val('autodeclaracao', 0) === 0 ? 'checked':'' }}> Não</label>
    </div>

    {{-- Envio de Arquivo (UI 0/1) + hidden legado sim/nao --}}
    <label>Envio de Arquivo (Outros/Genérico)?</label>
    <div class="radio-row">
      <label><input type="radio" name="exige_arquivo_outros" value="1" {{ $val('exige_arquivo_outros', 0) === 1 ? 'checked':'' }}> Sim</label>
      <label><input type="radio" name="exige_arquivo_outros" value="0" {{ $val('exige_arquivo_outros', 0) === 0 ? 'checked':'' }}> Não</label>
    </div>
    <input type="hidden" name="envio_arquivo" id="hidden-envio-arquivo" value="{{ $val('exige_arquivo_outros', 0) ? 'sim' : 'nao' }}">

    {{-- Ativo --}}
    <label>Ativo?</label>
    <div class="radio-row">
      <label><input type="radio" name="ativo" value="1" {{ $val('ativo', 1) === 1 ? 'checked':'' }}> Sim</label>
      <label><input type="radio" name="ativo" value="0" {{ $val('ativo', 1) === 0 ? 'checked':'' }}> Não</label>
    </div>

    {{-- Ordem --}}
    <label>Ordem</label>
    <input type="number" name="ordem" inputmode="numeric" min="0" step="1" value="{{ $val('ordem', 0) }}">

    {{-- Observações --}}
    <label class="full">Observações</label>
    <textarea name="observacoes" rows="4" class="full" placeholder="Instruções/observações adicionais…">{{ $val('observacoes') }}</textarea>

    {{-- sistac (hidden, padrão 0) --}}
    <input type="hidden" name="sistac" id="hidden-sistac" value="{{ (int) old('sistac', (int) data_get($tipo, 'sistac', 0)) }}">
  </div>
</div>

<div class="toolbar" style="display:flex; gap:.5rem;">
  <button class="btn" type="submit" name="__action" value="save">Salvar</button>
  <button class="btn" type="submit" name="__action" value="save-close">Salvar e Fechar</button>
  <a class="btn" href="{{ route('admin.config.tipos-vagas-especiais.index') }}">Cancelar</a>
</div>

<script>
(function(){
  const form = document.currentScript.closest('form') || document.querySelector('form');
  if(!form) return;

  // Espelha 'titulo' -> hidden 'nome'
  const inputTitulo = form.querySelector('input[name="titulo"]');
  const inputNome   = form.querySelector('#hidden-nome');
  if (inputTitulo && inputNome) {
    inputTitulo.addEventListener('input', () => { inputNome.value = inputTitulo.value; });
  }

  // Helpers
  const $ = (sel) => form.querySelector(sel);
  const $$ = (sel) => Array.from(form.querySelectorAll(sel));

  function radioVal(name){
    const r = form.querySelector(`input[name="${name}"]:checked`);
    return r ? r.value : null;
  }

  function laudoNecessario(){
    return radioVal('necessita_laudo_medico') === '1';
  }

  // Sync campos legados (sempre enviados)
  function syncHidden(){
    // necessita_laudo
    const nl = radioVal('necessita_laudo_medico') || '0';
    $('#hidden-necessita-laudo').value = nl;

    // laudo_obrigatorio (do grupo visual *_choice)
    const loChoice = radioVal('laudo_obrigatorio_choice');
    $('#hidden-laudo-obrigatorio').value = (loChoice ?? '0');

    // envio_arquivo sim/nao (a partir de exige_arquivo_outros 0/1)
    const ea = radioVal('exige_arquivo_outros') || '0';
    $('#hidden-envio-arquivo').value = (ea === '1') ? 'sim' : 'nao';
  }

  function setObrigatorioEnabled(on){
    $$('#hidden-laudo-obrigatorio'); // garante existência
    $$('#hidden-laudo-obrigatorio').forEach(()=>{});
    // Habilita/Desabilita os radios visuais
    $$('input[name="laudo_obrigatorio_choice"]').forEach(el => el.disabled = !on);
    if(!on){
      // força "Não" visualmente
      const no = $('input[name="laudo_obrigatorio_choice"][value="0"]');
      if(no) no.checked = true;
    }
  }

  // Inicial
  setObrigatorioEnabled(laudoNecessario());
  syncHidden();

  // Eventos
  form.addEventListener('change', (e) => {
    if (!e.target) return;
    if (e.target.name === 'necessita_laudo_medico') {
      setObrigatorioEnabled(laudoNecessario());
    }
    // Qualquer mudança de radio relevante ressincroniza
    if (['necessita_laudo_medico','laudo_obrigatorio_choice','exige_arquivo_outros'].includes(e.target.name)) {
      syncHidden();
    }
  });
})();
</script>
