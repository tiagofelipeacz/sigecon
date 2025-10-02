@php
  // Normaliza variáveis
  $concurso  = $concurso  ?? (object)[];
  $clientesC = ($clientes ?? ($clients ?? collect()));

  // Converte para array simples id => nome, aceitando vários formatos
  if ($clientesC instanceof \Illuminate\Support\Collection) $clientesC = $clientesC->toArray();
  $clientesLista = [];
  foreach ((array)$clientesC as $k => $v) {
      $id   = is_array($v) ? ($v['id'] ?? $k) : (is_object($v) ? ($v->id ?? $k) : $k);
      $nome = is_array($v)
                ? ($v['cliente'] ?? $v['name'] ?? $v['razao_social'] ?? $v['nome'] ?? (string)$v)
                : (is_object($v) ? ($v->cliente ?? $v->name ?? $v->razao_social ?? $v->nome ?? "Cliente #$id")
                                 : (string)$v);
      $clientesLista[$id] = $nome;
  }

  // valores atuais
  $vTipo      = old('tipo',      $concurso->tipo      ?? '');
  $vClienteId = old('client_id', $concurso->client_id ?? '');
  $vNumEd     = old('numero_edital', $concurso->numero_edital ?? '');
  $vSit       = old('situacao',  $concurso->situacao  ?? '');
  $vTitulo    = old('titulo',    $concurso->titulo    ?? '');
  $vDescCurta = old('descricao_curta', $concurso->descricao_curta ?? '');
  $vAtivo     = (string) old('ativo', isset($concurso->ativo) ? (int)$concurso->ativo : 1);
  $vOcultar   = (string) old('ocultar_site', isset($concurso->ocultar_site) ? (int)$concurso->ocultar_site : 0);
@endphp

<style>
  /* card único "Opções Gerais" no padrão do layout novo */
  .card-min {
    background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;
  }
  .card-min .hd {
    padding:10px 14px; font-weight:600; background:linear-gradient(#f6f7f8,#eef0f3);
    border-bottom:1px solid #e5e7eb;
  }
  .form-min {
    padding:14px;
    display:grid; grid-template-columns: 220px 1fr; gap:12px; align-items:center;
  }
  .form-min label { font-weight:600; }
  .form-min input[type="text"],
  .form-min textarea,
  .form-min select {
    width:100%; background:#fff; border:1px solid #d1d5db; border-radius:8px; padding:9px 10px;
  }
  .form-min .full { grid-column:1 / -1; }
  .muted { color:#6b7280; font-size:12px; }
  @media (max-width:920px){ .form-min{ grid-template-columns:1fr; } }
</style>

<div class="card-min">
  <div class="hd">Opções Gerais</div>
  <div class="form-min">
    {{-- Tipo --}}
    <label for="tipo">* Tipo:</label>
    <select id="tipo" name="tipo" required>
      <option value="">Selecione…</option>
      <option value="Concurso"          @selected($vTipo==='Concurso')>Concurso</option>
      <option value="Processo Seletivo" @selected($vTipo==='Processo Seletivo')>Processo Seletivo</option>
      <option value="Vestibular"        @selected($vTipo==='Vestibular')>Vestibular</option>
    </select>

    {{-- Cliente (select simples; se quiser, trocamos por um autocomplete depois) --}}
    <label for="client_id">* Cliente:</label>
    <select id="client_id" name="client_id" required>
      <option value="">Buscar</option>
      @foreach($clientesLista as $id => $nome)
        <option value="{{ $id }}" @selected((string)$vClienteId===(string)$id)>{{ $nome }}</option>
      @endforeach
    </select>

    {{-- Nº do Edital --}}
    <label for="numero_edital">Nº do Edital:</label>
    <input id="numero_edital" type="text" name="numero_edital" value="{{ $vNumEd }}" placeholder="Ex.: 01/2025">

    {{-- Situação (do concurso) --}}
    <label for="situacao">* Situação:</label>
    <select id="situacao" name="situacao" required>
      <option value="">Selecione…</option>
      {{-- use os rótulos que você já utiliza no sistema; esses são comuns --}}
      <option value="Em Breve"            @selected($vSit==='Em Breve')>Em Breve</option>
      <option value="Em Andamento"  @selected($vSit==='Em Andamento')>Em Andamento</option>
      <option value="Encerrado"           @selected($vSit==='Encerrado')>Encerrado</option>
      <option value="Publicado"           @selected($vSit==='Publicado')>Publicado</option>
      <option value="Rascunho"            @selected($vSit==='Rascunho')>Rascunho</option>
    </select>

    {{-- Título (auto) --}}
    <label for="titulo">* Título:</label>
    <input id="titulo" type="text" name="titulo" required value="{{ $vTitulo }}"
           placeholder="Ex.: Concurso - Prefeitura X - Edital 01/2025 - Em Breve"
           data-autotitle="1">

    {{-- Descrição curta --}}
    <label for="descricao_curta">Descrição curta:</label>
    <textarea id="descricao_curta" name="descricao_curta" rows="2"
              placeholder="Resumo do certame (opcional)">{{ $vDescCurta }}</textarea>

    {{-- Ativo --}}
    <label>Ativo:</label>
    <div>
      <label style="margin-right:16px;"><input type="radio" name="ativo" value="1" @checked($vAtivo==='1')> Sim</label>
      <label><input type="radio" name="ativo" value="0" @checked($vAtivo==='0')> Não</label>
    </div>

    {{-- Ocultar no site --}}
    <label>Ocultar no site:</label>
    <div>
      <label style="margin-right:16px;"><input type="radio" name="ocultar_site" value="1" @checked($vOcultar==='1')> Sim</label>
      <label><input type="radio" name="ocultar_site" value="0" @checked($vOcultar==='0')> Não</label>
    </div>


  </div>
</div>

{{-- Auto-preenchimento do Título (sem situação) --}}
<script>
(function(){
  const elTipo   = document.getElementById('tipo');
  const elCli    = document.getElementById('client_id');
  const elNum    = document.getElementById('numero_edital');
  const elTitulo = document.getElementById('titulo');

  // Marca "manual" quando o usuário altera o título
  let tituloManual = false;
  elTitulo?.addEventListener('input', () => {
    tituloManual = elTitulo.value.trim().length > 0;
  });

  function getClienteText() {
    const opt = elCli?.options?.[elCli.selectedIndex];
    return opt && opt.value ? opt.text.trim() : '';
  }

  function buildTitulo() {
    const partes = [];
    const tipo = elTipo?.value?.trim();
    const cli  = getClienteText();
    const num  = elNum?.value?.trim();

    if (tipo) partes.push(tipo);
    if (cli)  partes.push(cli);
    if (num)  partes.push('Edital ' + num);

    return partes.join(' - ');
  }

  function refreshTitulo() {
    if (!elTitulo || tituloManual) return; // não sobrescreve se usuário já digitou
    elTitulo.value = buildTitulo();
  }

  [elTipo, elCli, elNum].forEach(el => el && el.addEventListener('change', refreshTitulo));
  elNum?.addEventListener('input', refreshTitulo);

  // primeira carga: só preenche se vazio
  if (elTitulo && !elTitulo.value.trim()) refreshTitulo();
})();
</script>

