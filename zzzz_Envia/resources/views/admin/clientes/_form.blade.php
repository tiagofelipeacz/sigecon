@php
  /** Compat: aceita $client, $cliente */
  /** @var \App\Models\Client $model */
  $model = $client ?? $cliente ?? new \App\Models\Client();
  $isEdit = (bool) ($isEdit ?? ($model->id ?? false));
  $ufs = $ufs ?? ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

  $v = fn($k,$d=null)=> old($k, data_get($model, $k, $d));
  $logoUrl = $model->logo_path ? asset('storage/'.$model->logo_path) : null;
@endphp

<style>
  .card-min{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin-bottom:16px; }
  .card-min .hd{ padding:10px 14px; font-weight:600; background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; }
  .form-min{ padding:14px; display:grid; grid-template-columns: 220px 1fr; gap:12px; align-items:center; }
  .form-min label{ font-weight:600; }
  .form-min input[type="text"],
  .form-min input[type="email"],
  .form-min input[type="url"],
  .form-min input[type="number"],
  .form-min input[type="file"],
  .form-min textarea,
  .form-min select{
    width:100%; background:#fff; border:1px solid #d1d5db; border-radius:8px; padding:9px 10px;
  }
  .muted{ color:#6b7280; font-size:12px; }
  .full{ grid-column: 1 / -1; }
  .logo-prev{ width:96px; height:96px; border:1px solid #e5e7eb; background:#f8fafc; border-radius:8px; object-fit:contain; }
  @media (max-width:920px){ .form-min{ grid-template-columns:1fr; } }
</style>

<div class="card-min">
  <div class="hd">Dados do Cliente</div>
  <div class="form-min">
    {{-- Identificação --}}
    <label>Cliente *</label>
    <input type="text" name="cliente" required
           value="{{ $v('cliente') }}"
           placeholder="Ex.: Prefeitura Municipal de Exemplo">

    <label>Razão Social</label>
    <input type="text" name="razao_social"
           value="{{ $v('razao_social') }}"
           placeholder="Ex.: Razão Social completa">

    <label>CNPJ</label>
    <input type="text" name="cnpj"
           value="{{ $v('cnpj') }}"
           placeholder="00.000.000/0000-00">

    <label>Website</label>
    <input type="url" name="website"
           value="{{ $v('website') }}"
           placeholder="https://www.exemplo.gov.br">

    <label>E-mail</label>
    <input type="email" name="email"
           value="{{ $v('email') }}"
           placeholder="contato@exemplo.gov.br">

    {{-- Logo --}}
    <label>Logo (opcional)</label>
    <div class="full" style="display:flex; align-items:center; gap:12px;">
      @if($isEdit && $logoUrl)
        <img class="logo-prev" src="{{ $logoUrl }}" alt="Logo atual">
      @else
        <div class="logo-prev" title="Sem logo"></div>
      @endif
      <input type="file" name="logo" accept="image/*" style="max-width:360px">
    </div>

    {{-- Endereço --}}
    <label>CEP</label>
    <input type="text" name="cep" value="{{ $v('cep') }}" placeholder="00000-000">

    <label>Endereço</label>
    <input type="text" name="endereco" value="{{ $v('endereco') }}" placeholder="Rua, avenida...">

    <label>Número</label>
    <input type="text" name="numero" value="{{ $v('numero') }}" placeholder="Ex.: 123">

    <label>Complemento</label>
    <input type="text" name="complemento" value="{{ $v('complemento') }}" placeholder="Sala, bloco, andar...">

    <label>Bairro</label>
    <input type="text" name="bairro" value="{{ $v('bairro') }}" placeholder="Bairro">

    <label>Cidade</label>
    <input type="text" name="cidade" value="{{ $v('cidade') }}" placeholder="Cidade">

    <label>UF</label>
    <select name="estado">
      <option value="">Selecione</option>
      @foreach($ufs as $uf)
        <option value="{{ $uf }}" @selected($v('estado')===$uf)>{{ $uf }}</option>
      @endforeach
    </select>

    {{-- Observações --}}
    <label class="full">Observações</label>
    <textarea name="observacoes" rows="4" class="full"
              placeholder="Informações complementares…">{{ $v('observacoes') }}</textarea>
  </div>
</div>
