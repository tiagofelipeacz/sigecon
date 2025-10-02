{{-- resources/views/admin/config/clientes/_form.blade.php --}}
@php
  // Compatibilidade de variáveis
  $cliente = $cliente ?? $client ?? $item ?? $record ?? (object)[];
  $v = fn($k,$d=null)=> old($k, data_get($cliente,$k,$d));
  $vBool = fn($k,$d=1)=> (int) old($k, (int) data_get($cliente,$k,$d));
@endphp

<style>
  .card-min{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin-bottom:16px; }
  .card-min .hd{ padding:10px 14px; font-weight:600; background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; }
  .form-min{ padding:14px; display:grid; grid-template-columns: 220px 1fr; gap:12px; align-items:center; }
  .form-min label{ font-weight:600; }
  .form-min input[type="text"],
  .form-min input[type="email"],
  .form-min input[type="url"],
  .form-min input[type="tel"],
  .form-min input[type="number"],
  .form-min textarea{
    width:100%; background:#fff; border:1px solid #d1d5db; border-radius:8px; padding:9px 10px;
  }
  .radio-row{ display:flex; gap:16px; align-items:center; }
  .full{ grid-column:1 / -1; }
  @media (max-width:920px){ .form-min{ grid-template-columns:1fr; } }
</style>

<div class="card-min">
  <div class="hd">Dados do Cliente</div>
  <div class="form-min">
    <label>Nome *</label>
    <input type="text" name="nome" required
           value="{{ $v('nome', $v('name')) }}"
           placeholder="Ex.: Prefeitura Municipal de Exemplo">

    <label>Sigla</label>
    <input type="text" name="sigla"
           value="{{ $v('sigla') }}"
           placeholder="Ex.: PMX">

    <label>Documento (CNPJ/CPF)</label>
    <input type="text" name="documento"
           value="{{ $v('documento', $v('cnpj', $v('cpf'))) }}"
           placeholder="Somente números (opcional)">

    <label>E-mail</label>
    <input type="email" name="email"
           value="{{ $v('email', $v('contato_email')) }}"
           placeholder="contato@exemplo.gov.br">

    <label>Telefone</label>
    <input type="tel" name="telefone"
           value="{{ $v('telefone', $v('fone', $v('contato_telefone'))) }}"
           placeholder="(00) 0000-0000">

    <label>Site</label>
    <input type="url" name="site"
           value="{{ $v('site', $v('url')) }}"
           placeholder="https://www.exemplo.gov.br">

    <label>CEP</label>
    <input type="text" name="cep"
           value="{{ $v('cep') }}"
           placeholder="00000-000">

    <label>Logradouro</label>
    <input type="text" name="logradouro"
           value="{{ $v('logradouro', $v('endereco'))) }}"
           placeholder="Rua/Av., número e complemento">

    <label>Bairro</label>
    <input type="text" name="bairro"
           value="{{ $v('bairro') }}"
           placeholder="Bairro">

    <label>Cidade</label>
    <input type="text" name="cidade"
           value="{{ $v('cidade', $v('municipio', $v('city'))) }}"
           placeholder="Cidade/Município">

    <label>UF</label>
    <input type="text" name="uf" maxlength="2"
           value="{{ $v('uf', $v('estado', $v('state'))) }}"
           placeholder="UF">

    <label>Ativo?</label>
    <div class="radio-row">
      <label><input type="radio" name="ativo" value="1" {{ $vBool('ativo',1)===1 ? 'checked':'' }}> Sim</label>
      <label><input type="radio" name="ativo" value="0" {{ $vBool('ativo',1)===0 ? 'checked':'' }}> Não</label>
    </div>

    <label class="full">Observações</label>
    <textarea name="observacoes" rows="3" class="full"
              placeholder="Informações adicionais internas (opcional)">{{ $v('observacoes', $v('nota')) }}</textarea>
  </div>
</div>

<div class="toolbar" style="display:flex; gap:.5rem;">
  <button class="btn" type="submit" formaction="{{ request()->url() }}">Salvar</button>

  {{-- botão "Salvar e Fechar": define input hidden fechar=1 e envia para a mesma rota --}}
  <button class="btn" type="submit" onclick="this.closest('form').querySelector('input[name=fechar]')?.setAttribute('value','1')">Salvar e Fechar</button>

  <a class="btn" href="{{ \Route::has('admin.config.clientes.index') ? route('admin.config.clientes.index') : url('/admin/config/clientes') }}">Cancelar</a>
</div>

<input type="hidden" name="fechar" value="0">
