@php
  /** @var \App\Models\TipoIsencao $record */
  $record = $record ?? new \App\Models\TipoIsencao();

  // Helpers curtos
  $oldb = fn($k,$d=false)=> (bool) old($k, (int) data_get($record,$k,$d));
  $oldv = fn($k,$d=null)=> old($k, data_get($record,$k,$d));

  // anexo_policy default seguro
  $anexoPolicy = $oldv('anexo_policy', $record->anexo_policy ?? 'nao'); // 'nao' | 'opcional' | 'obrigatorio'
@endphp

<style>
  .card-min{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin-bottom:16px; }
  .card-min .hd{ padding:10px 14px; font-weight:600; background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; }
  .form-min{ padding:14px; display:grid; grid-template-columns: 220px 1fr; gap:12px; align-items:center; }
  .form-min label{ font-weight:600; }
  .form-min input[type="text"],
  .form-min input[type="number"],
  .form-min textarea,
  .form-min select{
    width:100%; background:#fff; border:1px solid #d1d5db; border-radius:8px; padding:9px 10px;
  }
  .muted{ color:#6b7280; font-size:12px; }
  .check-row{ display:flex; gap:12px; align-items:center; }
  .radio-row{ display:flex; gap:16px; align-items:center; }
  .full{ grid-column:1 / -1; }
  @media (max-width:920px){ .form-min{ grid-template-columns:1fr; } }
</style>

<div class="card-min">
  <div class="hd">Dados do Tipo de Isenção</div>
  <div class="form-min">
    {{-- Nome / Título --}}
    <label>Nome *</label>
    <input type="text" name="nome" required
           value="{{ $oldv('nome', $record->nome) }}"
           placeholder="Ex.: CadÚnico, Doação de Medula, Baixa Renda...">

    <label>Título (opcional)</label>
    <input type="text" name="titulo"
           value="{{ $oldv('titulo', $record->titulo) }}"
           placeholder="Se vazio, usa o Nome">

    {{-- CadÚnico --}}
    <label>Exige número do CadÚnico?</label>
    <div class="check-row">
      <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="exigir_cadunico" value="1" {{ $oldb('exigir_cadunico', $record->exigir_cadunico ?? false) ? 'checked' : '' }}>
        <span>Sim</span>
      </label>
    </div>
    {{-- Compat: se seu backend ainda lê "exige_cadunico" --}}
    <input type="hidden" name="exige_cadunico" id="mirror-exige-cadunico" value="{{ $oldb('exigir_cadunico', $record->exigir_cadunico ?? false) ? 1 : 0 }}">

    {{-- Integrações / Validações externas --}}
    <label>Validar via SISTAC?</label>
    <div class="check-row">
      <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="sistac" value="1" {{ $oldb('sistac', $record->sistac ?? false) ? 'checked' : '' }}>
        <span>Sim</span>
      </label>
    </div>

    <label>Validar via REDOME?</label>
    <div class="check-row">
      <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="redome" value="1" {{ $oldb('redome', $record->redome ?? false) ? 'checked' : '' }}>
        <span>Sim</span>
      </label>
    </div>

    {{-- Campo extra opcional (ex.: código, número do protocolo, etc.) --}}
    <label>Solicitar campo extra?</label>
    <div class="check-row">
      <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="has_extra_field" id="has-extra-field" value="1" {{ $oldb('has_extra_field', $record->has_extra_field ?? false) ? 'checked' : '' }}>
        <span>Sim</span>
      </label>
      <span class="muted">Se marcado, informe o rótulo abaixo.</span>
    </div>

    <label>Rótulo do campo extra</label>
    <input type="text" name="campo_extra_label" id="campo-extra-label"
           value="{{ $oldv('campo_extra_label', $record->campo_extra_label) }}"
           placeholder="Ex.: N° do benefício / Protocolo">

    {{-- Política de Anexo (Outros/Genérico) --}}
    <label>Anexo (Outros/Genérico)</label>
    <div class="radio-row">
      <label><input type="radio" name="anexo_policy" value="nao" {{ $anexoPolicy==='nao' ? 'checked' : '' }}> Não permite</label>
      <label><input type="radio" name="anexo_policy" value="opcional" {{ $anexoPolicy==='opcional' ? 'checked' : '' }}> Opcional</label>
      <label><input type="radio" name="anexo_policy" value="obrigatorio" {{ $anexoPolicy==='obrigatorio' ? 'checked' : '' }}> Obrigatório</label>
    </div>
    {{-- Compat: se seu backend ainda lê "exige_arquivo" e/ou "permite_anexo" --}}
    <input type="hidden" name="exige_arquivo" id="mirror-exige-arquivo" value="{{ $anexoPolicy==='obrigatorio' ? 1 : 0 }}">
    <input type="hidden" name="permite_anexo" id="mirror-permite-anexo" value="{{ $anexoPolicy!=='nao' ? 1 : 0 }}">

    {{-- Ativo / Ordem --}}
    <label>Ativo</label>
    <div class="check-row">
      <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="ativo" value="1" {{ $oldb('ativo', ($record->ativo ?? true)) ? 'checked' : '' }}>
        <span>Deixar disponível para uso</span>
      </label>
    </div>

    <label>Ordem</label>
    <input type="number" name="ordem" min="0" max="65535"
           value="{{ $oldv('ordem', $record->ordem ?? 0) }}">

    {{-- Orientações / Observações --}}
    <label class="full">Observações</label>
    <textarea name="observacoes" rows="4" class="full"
              placeholder="Instruções/observações adicionais...">{{ $oldv('observacoes', $record->descricao) }}</textarea>

    <label class="full">Orientações ao candidato (HTML opcional)</label>
    <textarea name="orientacoes_html" rows="5" class="full"
              placeholder="Conteúdo rico/HTML exibido ao candidato...">{{ $oldv('orientacoes_html', $record->orientacoes_html) }}</textarea>
  </div>
</div>

<div class="toolbar" style="display:flex; gap:.5rem;">
  <button class="btn" type="submit">Salvar</button>
  <a class="btn" href="{{ route('admin.config.tipos-isencao.index') }}">Cancelar</a>
</div>

<script>
  (function(){
    const fm = document.currentScript.closest('form');
    if(!fm) return;

    // CadÚnico: espelha exigir_cadunico -> exige_cadunico (compat)
    const chkCad = fm.querySelector('input[name="exigir_cadunico"]');
    const hidCad = fm.querySelector('#mirror-exige-cadunico');
    if (chkCad && hidCad) {
      const syncCad = () => hidCad.value = chkCad.checked ? 1 : 0;
      chkCad.addEventListener('change', syncCad); syncCad();
    }

    // Campo extra: habilita/disable rótulo
    const chkExtra = fm.querySelector('#has-extra-field');
    const lblExtra = fm.querySelector('#campo-extra-label');
    const toggleExtra = () => {
      if (!chkExtra || !lblExtra) return;
      lblExtra.disabled = !chkExtra.checked;
      lblExtra.style.opacity = chkExtra.checked ? '1' : '0.6';
    };
    if (chkExtra && lblExtra) {
      chkExtra.addEventListener('change', toggleExtra);
      toggleExtra();
    }

    // Anexo policy: espelha para exige_arquivo / permite_anexo (compat)
    const radios = fm.querySelectorAll('input[name="anexo_policy"]');
    const hidExige = fm.querySelector('#mirror-exige-arquivo');
    const hidPerm  = fm.querySelector('#mirror-permite-anexo');
    const syncAnexo = () => {
      const sel = Array.from(radios).find(r => r.checked)?.value || 'nao';
      if (hidExige) hidExige.value = (sel === 'obrigatorio') ? 1 : 0;
      if (hidPerm)  hidPerm.value  = (sel !== 'nao') ? 1 : 0;
    };
    radios.forEach(r => r.addEventListener('change', syncAnexo));
    syncAnexo();
  })();
</script>
