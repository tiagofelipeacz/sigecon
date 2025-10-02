@php
  /** Compat: aceita $nivel, $record ou $item */
  $nivel = $nivel ?? $record ?? $item ?? (object)[];
  $v = fn($k,$d=null)=> old($k, data_get($nivel,$k,$d));
  $vBool = fn($k,$d=0)=> (int) old($k, (int) data_get($nivel,$k,$d));
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
  .radio-row{ display:flex; gap:16px; align-items:center; }
  @media (max-width:920px){ .form-min{ grid-template-columns:1fr; } }
</style>

<div class="card-min">
  <div class="hd">Dados do Nível</div>
  <div class="form-min">
    <label>Nome do Nível *</label>
    <input type="text" name="nome" required
           value="{{ $v('nome', $v('titulo')) }}"
           placeholder="Ex.: Fundamental, Médio, Superior, Pós-Graduação…">

    <label>Ordem</label>
    <input type="number" name="ordem" min="0" max="65535"
           value="{{ $v('ordem', 0) }}" placeholder="0">

    <label>Ativo?</label>
    <div class="radio-row">
      <label><input type="radio" name="ativo" value="1" {{ $vBool('ativo',1)===1 ? 'checked':'' }}> Sim</label>
      <label><input type="radio" name="ativo" value="0" {{ $vBool('ativo',1)===0 ? 'checked':'' }}> Não</label>
    </div>

    {{-- Campo opcional de observações (se o controller ignorar, não quebra) --}}
    <label class="full">Observações</label>
    <textarea name="observacoes" rows="3" class="full"
              placeholder="Instruções/observações adicionais (opcional)">{{ $v('observacoes') }}</textarea>
  </div>
</div>

<div class="toolbar" style="display:flex; gap:.5rem; flex-wrap:wrap;">
  <button class="btn" type="submit" name="fechar" value="0">Salvar</button>
  <button class="btn" type="submit" name="fechar" value="1">Salvar e Fechar</button>
  <a class="btn" href="{{ route('admin.config.niveis-escolaridade.index') }}">Cancelar</a>
</div>
