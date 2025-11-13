@extends('layouts.sigecon')
@section('title', 'Configurações do Concurso - SIGECON')

@section('content')
@php
    // Normaliza objeto
    $concurso = $concurso ?? (object)[];
@endphp

{{-- Flash --}}
@if (session('success'))
  <div class="mb-4 rounded border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">
    {{ session('success') }}
  </div>
@endif

{{-- Erros --}}
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


<style>
  /* Cards e formulários no visual "clean" do seu layout */
  .cfg-wrap{ display:flex; gap:20px; align-items:flex-start; }
  .cfg-main{ flex:1; min-width:0; }

  .card-min{ background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin-bottom:16px; }
  .card-min .hd{ padding:10px 14px; font-weight:600; background:linear-gradient(#f6f7f8,#eef0f3); border-bottom:1px solid #e5e7eb; }
  .form-min{ padding:14px; display:grid; grid-template-columns: 220px 1fr; gap:12px; align-items:center; }
  .form-min label{ font-weight:600; }
  .form-min input[type="text"],
  .form-min textarea,
  .form-min select{
    width:100%; background:#fff; border:1px solid #d1d5db; border-radius:8px; padding:9px 10px;
  }
  .form-min .readonly{ background:#f8fafc; }
  .form-min .full{ grid-column:1 / -1; }
  .muted{ color:#6b7280; font-size:12px; }

  @media (max-width:920px){
    .cfg-wrap{flex-direction:column;}
    .form-min{ grid-template-columns:1fr; }
  }
</style>

<div class="cfg-wrap">

  {{-- MENU ESQUERDO (parcial reutilizável) --}}
 @include('admin.concursos.partials.right-menu', [
  'concurso'    => $concurso,
  'menu_active' => 'config'   // <- só “Configurações” ativo nesta página
])

  {{-- CONTEÚDO PRINCIPAL --}}
  <div class="cfg-main">
    <form id="form-config" method="POST" action="{{ route('admin.concursos.updateConfig', $concurso) }}">
      @csrf
      @method('PUT')

      {{-- ===== Campos ocultos herdados (mantidos) ===== --}}
      <input type="hidden" name="ativo" value="{{ old('ativo', ($concurso->ativo ?? false) ? 1 : 0) }}">
      <input type="hidden" name="oculto" value="{{ old('oculto', ($concurso->oculto ?? $concurso->ocultar_site ?? false) ? 1 : 0) }}">
      <input type="hidden" name="legenda_interna" value="{{ old('legenda_interna', $concurso->legenda_interna ?? '') }}">

      {{-- ===== Opções Gerais ===== --}}
      @php
        $situacaoAtual = old('situacao', $concurso->situacao ?? $concurso->status ?? 'rascunho');
        $situacoes = [
          'rascunho'     => 'Rascunho',
          'em_andamento' => 'Em andamento',
          'homologado'   => 'Homologado',
          'finalizado'   => 'Finalizado',
          'suspenso'     => 'Suspenso',
          'cancelado'    => 'Cancelado',
          'arquivado'    => 'Arquivado',
        ];
        $tipo = old('configs.tipo', data_get($concurso, 'configs.tipo', $concurso->tipo ?? 'concurso'));
        $inscOnline = (int) old('configs.inscricoes_online', data_get($concurso, 'configs.inscricoes_online', (int)($concurso->inscricoes_online ?? 1)));
        // Resolve nome do cliente (somente leitura):
        $fkId = $concurso->fk_cliente_id ?? null;
        $nomeCliente = $concurso->cliente_nome
            ?? optional($concurso->client)->cliente
            ?? optional($concurso->clientLegacy)->cliente
            ?? optional($concurso->clientAlt)->cliente
            ?? optional($concurso->clientPlural)->cliente
            ?? optional($concurso->client)->razao_social
            ?? optional($concurso->clientLegacy)->razao_social
            ?? optional($concurso->clientAlt)->razao_social
            ?? optional($concurso->clientPlural)->razao_social
            ?? null;
        $labelCliente = $nomeCliente ?: ($fkId ? "Cliente #{$fkId}" : 'Cliente não definido');
        $ocultaDh = (int) old('configs.flag_ocultar_datahora_no_site', data_get($concurso, 'configs.flag_ocultar_datahora_no_site', 0));
      @endphp

      <div class="card-min">
        <div class="hd">Opções Gerais</div>
        <div class="form-min">
          <label>ID</label>
          <div class="readonly" style="border:1px solid #e5e7eb;border-radius:8px;padding:9px 10px;">{{ $concurso->id }}</div>

          <label>Tipo</label>
          <select name="configs[tipo]">
            <option value="concurso"          {{ $tipo === 'concurso' ? 'selected' : '' }}>Concurso Público</option>
            <option value="processo_seletivo" {{ $tipo === 'processo_seletivo' ? 'selected' : '' }}>Processo Seletivo</option>
            <option value="vestibular"        {{ $tipo === 'vestibular' ? 'selected' : '' }}>Vestibular</option>
          </select>

          <label class="full">Título do concurso</label>
          <input class="full" type="text" name="titulo"
                 value="{{ old('titulo', $concurso->titulo) }}"
                 placeholder="Ex.: Concurso Público - Edital 01/2025 - Prefeitura Municipal de ...">

          <label class="full">Situação</label>
          <select class="full" name="situacao">
            @foreach($situacoes as $key => $label)
              <option value="{{ $key }}" {{ (string)$situacaoAtual === (string)$key ? 'selected' : '' }}>
                {{ $label }}
              </option>
            @endforeach
          </select>

          <label class="full">Cliente</label>
          <input class="full readonly" type="text" value="{{ $labelCliente }}" readonly>

          <label>Nº do Edital</label>
          <input type="text" name="configs[numero_edital]"
                 value="{{ old('configs.numero_edital', data_get($concurso, 'configs.numero_edital') ?? $concurso->numero_edital) }}"
                 placeholder="Ex.: 01/2025">

          <label>Data do Edital</label>
          <input type="text" name="configs[data_edital]"
                 value="{{ old('configs.data_edital', optional($concurso->edital_data)->format('d/m/Y') ?? data_get($concurso, 'configs.data_edital')) }}"
                 class="js-mask-date" inputmode="numeric" placeholder="dd/mm/aaaa">

          <label>Inscrições Online</label>
          <div>
            <label style="margin-right:16px;"><input type="radio" name="configs[inscricoes_online]" value="1" {{ (int)$inscOnline === 1 ? 'checked' : '' }}> Sim</label>
            <label><input type="radio" name="configs[inscricoes_online]" value="0" {{ (int)$inscOnline === 0 ? 'checked' : '' }}> Não</label>
          </div>

          <label>Nº sequencial de inscrição</label>
          <div class="full" style="display:grid; grid-template-columns:1fr; gap:8px;">
            <input type="text" name="configs[sequence_inscricao]"
                   value="{{ old('configs.sequence_inscricao', $concurso->sequence_inscricao ?? data_get($concurso, 'configs.sequence_inscricao', 1)) }}"
                   inputmode="numeric" pattern="[0-9]*" placeholder="Ex.: 1" style="max-width:240px;">
            <div class="muted">Defina o número inicial (mínimo 1). Você pode iniciar em qualquer faixa numérica.</div>
          </div>

          <label>Inscrições - Início</label>
          <input type="text" name="configs[inscricoes_inicio]"
                 value="{{ old('configs.inscricoes_inicio', optional($concurso->inscricoes_inicio)->format('d/m/Y H:i') ?? data_get($concurso, 'configs.inscricoes_inicio')) }}"
                 class="js-mask-datetime" inputmode="numeric" placeholder="dd/mm/aaaa hh:mm">

          <label>Inscrições - Fim</label>
          <input type="text" name="configs[inscricoes_fim]"
                 value="{{ old('configs.inscricoes_fim', optional($concurso->inscricoes_fim)->format('d/m/Y H:i') ?? data_get($concurso, 'configs.inscricoes_fim')) }}"
                 class="js-mask-datetime" inputmode="numeric" placeholder="dd/mm/aaaa hh:mm">

          <label class="full">Ocultar data/hora nas informações do site</label>
          <div class="full">
            <label style="margin-right:16px;"><input type="radio" name="configs[flag_ocultar_datahora_no_site]" value="1" {{ $ocultaDh === 1 ? 'checked' : '' }}> Sim</label>
            <label><input type="radio" name="configs[flag_ocultar_datahora_no_site]" value="0" {{ $ocultaDh === 0 ? 'checked' : '' }}> Não</label>
          </div>
        </div>
      </div>

      {{-- ===== Impugnação do Edital ===== --}}
      @php $flagImp = (int) old('configs.flag_impugnacao', data_get($concurso, 'configs.flag_impugnacao', 0)); @endphp
      <div class="card-min">
        <div class="hd">Impugnação do Edital</div>
        <div class="form-min">
          <label>Habilitar</label>
          <div>
            <label style="margin-right:16px;">
              <input type="radio" name="configs[flag_impugnacao]" value="1" {{ $flagImp === 1 ? 'checked' : '' }}> Sim
            </label>
            <label>
              <input type="radio" name="configs[flag_impugnacao]" value="0" {{ $flagImp === 0 ? 'checked' : '' }}> Não
            </label>
          </div>

          {{-- Datas (mostra só quando habilitado) --}}
          <div id="impugnacao-datas" style="display: {{ $flagImp === 1 ? 'contents' : 'none' }};">
            <label>Início</label>
            <input type="text" name="configs[data_impugnacao_inicio]"
                   value="{{ old('configs.data_impugnacao_inicio', data_get($concurso, 'configs.data_impugnacao_inicio')) }}"
                   class="js-mask-datetime" inputmode="numeric" placeholder="dd/mm/aaaa hh:mm"
                   {{ $flagImp === 1 ? '' : 'disabled' }}>

            <label>Fim</label>
            <input type="text" name="configs[data_impugnacao_fim]"
                   value="{{ old('configs.data_impugnacao_fim', data_get($concurso, 'configs.data_impugnacao_fim')) }}"
                   class="js-mask-datetime" inputmode="numeric" placeholder="dd/mm/aaaa hh:mm"
                   {{ $flagImp === 1 ? '' : 'disabled' }}>
          </div>
        </div>
      </div>

      {{-- ===== Forma de Pagamento ===== --}}
      @php $tpVenc = old('configs.tipo_vencimento', data_get($concurso, 'configs.tipo_vencimento', '1')); @endphp
      <div class="card-min">
        <div class="hd">Forma de Pagamento</div>
        <div class="form-min">
          <label>Vencimento</label>
          <select name="configs[tipo_vencimento]">
            <option value="">- selecione -</option>
            <option value="1" {{ $tpVenc == '1' ? 'selected' : '' }}>Em Dias</option>
            <option value="2" {{ $tpVenc == '2' ? 'selected' : '' }}>Data Fixa</option>
          </select>

          <label>Data (se Data Fixa)</label>
          <input type="text" name="configs[vencimento_data]"
                 value="{{ old('configs.vencimento_data', data_get($concurso, 'configs.vencimento_data')) }}"
                 class="js-mask-date" inputmode="numeric" placeholder="dd/mm/aaaa">

          <label>Qtde. de dias (se Em Dias)</label>
          <input type="text" name="configs[vencimento_dias]"
                 value="{{ old('configs.vencimento_dias', data_get($concurso, 'configs.vencimento_dias', '5')) }}"
                 inputmode="numeric" pattern="[0-9]*" placeholder="Ex.: 5">
        </div>
      </div>

      {{-- ===== Pedidos de Isenção — dropdown ===== --}}
      @php
        $flagIsenc = (int) old('configs.flag_isencao', data_get($concurso, 'configs.flag_isencao', 0));

        $valIsencIni = old('configs.data_isencao_inicio', (function() use ($concurso) {
            $v = data_get($concurso, 'configs.data_isencao_inicio') ?? data_get($concurso, 'data_isencao_inicio');
            if (!$v) return '';
            try { return \Carbon\Carbon::parse($v)->format('d/m/Y H:i'); } catch (\Throwable $e) { return $v; }
        })());

        $valIsencFim = old('configs.data_isencao_fim', (function() use ($concurso) {
            $v = data_get($concurso, 'configs.data_isencao_fim') ?? data_get($concurso, 'data_isencao_fim');
            if (!$v) return '';
            try { return \Carbon\Carbon::parse($v)->format('d/m/Y H:i'); } catch (\Throwable $e) { return $v; }
        })());

        $tiposIsenc = \App\Models\TipoIsencao::where('ativo', 1)->orderBy('titulo')->get(['id','titulo']);
        $selecionadosIsenc = \DB::table('concurso_tipo_isencao')
            ->where('concurso_id', $concurso->id)
            ->pluck('tipo_isencao_id')->toArray();
      @endphp

      <div class="card-min">
        <div class="hd">Pedidos de Isenção</div>
        <div class="form-min">
          <label>Ativar pedidos de isenção</label>
          <div>
            <label style="margin-right:16px;">
              <input type="radio" name="configs[flag_isencao]" value="1" {{ $flagIsenc === 1 ? 'checked' : '' }}> Sim
            </label>
            <label>
              <input type="radio" name="configs[flag_isencao]" value="0" {{ $flagIsenc === 0 ? 'checked' : '' }}> Não
            </label>
          </div>

          {{-- Campos (só aparecem quando habilitado) --}}
          <div id="isencao-fields" style="display: {{ $flagIsenc === 1 ? 'contents' : 'none' }};">
            <label>Isenção - Início</label>
            <input type="text" name="configs[data_isencao_inicio]"
                   value="{{ $valIsencIni }}"
                   class="js-mask-datetime" inputmode="numeric" placeholder="dd/mm/aaaa hh:mm"
                   {{ $flagIsenc === 1 ? '' : 'disabled' }}>

            <label>Isenção - Fim</label>
            <input type="text" name="configs[data_isencao_fim]"
                   value="{{ $valIsencFim }}"
                   class="js-mask-datetime" inputmode="numeric" placeholder="dd/mm/aaaa hh:mm"
                   {{ $flagIsenc === 1 ? '' : 'disabled' }}>

            {{-- Dropdown multi-seleção de tipos --}}
            <label class="full">Tipos de isenção permitidos</label>
            <select id="select-tipos-isencao" name="tipos_isencao[]" multiple class="full" {{ $flagIsenc === 1 ? '' : 'disabled' }}>
              @foreach($tiposIsenc as $t)
                <option value="{{ $t->id }}" {{ in_array($t->id, $selecionadosIsenc, true) ? 'selected' : '' }}>
                  {{ $t->titulo }}
                </option>
              @endforeach
            </select>
            <div class="muted full">Selecione um ou mais tipos no dropdown.</div>
          </div>
        </div>
      </div>

      {{-- ===== Pedidos de condições especiais (hide/show) ===== --}}
      @php
        $flagCond = (int) old('configs.flag_condicoesespeciais', data_get($concurso, 'configs.flag_condicoesespeciais', 0));
        $tiposCE = \App\Models\TipoCondicaoEspecial::where('ativo', 1)->orderBy('titulo')->get(['id','titulo']);
        $selecionadosCE = \DB::table('concurso_tipo_condicao_especial')
            ->where('concurso_id', $concurso->id)
            ->pluck('tipo_condicao_especial_id')->toArray();
      @endphp
      <div class="card-min">
        <div class="hd">Pedidos de condições especiais</div>
        <div class="form-min">
          <label>Permitir solicitar condições especiais</label>
          <div>
            <label style="margin-right:16px;"><input type="radio" name="configs[flag_condicoesespeciais]" value="1" {{ $flagCond === 1 ? 'checked' : '' }}> Sim</label>
            <label><input type="radio" name="configs[flag_condicoesespeciais]" value="0" {{ $flagCond === 0 ? 'checked' : '' }}> Não</label>
          </div>

          <div id="ce-fields" style="display: {{ $flagCond === 1 ? 'contents' : 'none' }};">
            <label class="full">Condições Especiais a serem listadas</label>
            <select id="select-condicoes-especiais" name="condicoes_especiais[]" multiple class="full" {{ $flagCond === 1 ? '' : 'disabled' }}>
              @foreach($tiposCE as $t)
                <option value="{{ $t->id }}" {{ in_array($t->id, $selecionadosCE, true) ? 'selected' : '' }}>
                  {{ $t->titulo }}
                </option>
              @endforeach
            </select>
            <div class="muted full">Selecione um ou mais tipos que o candidato poderá escolher.</div>

            <label class="full">Observações ao candidato</label>
            <textarea class="full" name="configs[condicoes_especiais_observacoes]" rows="3" {{ $flagCond === 1 ? '' : 'disabled' }}
                      placeholder="Instruções e observações...">{{ old('configs.condicoes_especiais_observacoes', data_get($concurso, 'configs.condicoes_especiais_observacoes')) }}</textarea>
          </div>
        </div>
      </div>

      {{-- ===== Configurações de Inscrição ===== --}}
      @php
        $termos = (int) old('configs.exibir_termos', data_get($concurso, 'configs.exibir_termos', 0)); // padrão Não
        $permCanc = old('configs.flag_permitir_cancelar_inscricao', data_get($concurso, 'configs.flag_permitir_cancelar_inscricao', '0')); // padrão Não
        $sitCanc = old('configs.situacoes_cancelar_inscricao', data_get($concurso, 'configs.situacoes_cancelar_inscricao', '1'));

        // NOVOS CAMPOS
        $limiteCpf = (int) old(
            'configs.limite_inscricoes_por_cpf',
            data_get($concurso, 'configs.limite_inscricoes_por_cpf', 1) // default 1 como regra antiga
        );
        if ($limiteCpf < 0) $limiteCpf = 0;

        $bloqMesmoCargo = (int) old(
            'configs.bloquear_multiplas_inscricoes_mesmo_cargo',
            data_get($concurso, 'configs.bloquear_multiplas_inscricoes_mesmo_cargo', 1) // default Sim
        );
      @endphp
      <div class="card-min">
        <div class="hd">Configurações de Inscrição</div>
        <div class="form-min">

          {{-- Quantidade de inscrições por CPF --}}
          <label>Quantidade de inscrições permitidas por CPF</label>
          <div>
            <select name="configs[limite_inscricoes_por_cpf]" style="max-width:220px;">
              <option value="0" {{ $limiteCpf === 0 ? 'selected' : '' }}>Ilimitada</option>
              @for($i = 1; $i <= 5; $i++)
                <option value="{{ $i }}" {{ $limiteCpf === $i ? 'selected' : '' }}>
                  {{ $i }} inscrição{{ $i > 1 ? 's' : '' }}
                </option>
              @endfor
            </select>
            <div class="muted" style="margin-top:4px;">
              Limite global por CPF neste concurso. Use "Ilimitada" para permitir mais de {{ 5 }} inscrições.
            </div>
          </div>

          {{-- Bloquear múltiplas inscrições no mesmo cargo --}}
          <label>Bloquear múltiplas inscrições no mesmo cargo</label>
          <div>
            <label style="margin-right:16px;">
              <input type="radio" name="configs[bloquear_multiplas_inscricoes_mesmo_cargo]" value="1" {{ $bloqMesmoCargo === 1 ? 'checked' : '' }}>
              Sim
            </label>
            <label>
              <input type="radio" name="configs[bloquear_multiplas_inscricoes_mesmo_cargo]" value="0" {{ $bloqMesmoCargo === 0 ? 'checked' : '' }}>
              Não
            </label>
            <div class="muted" style="margin-top:4px;">
              Quando "Sim", o mesmo CPF não poderá fazer duas inscrições para o mesmo cargo neste concurso.
            </div>
          </div>

          {{-- Aceite dos termos --}}
          <label class="full">Exibir aceite dos termos do edital antes da inscrição</label>
          <div class="full">
            <label style="margin-right:16px;"><input type="radio" name="configs[exibir_termos]" value="1" {{ $termos === 1 ? 'checked' : '' }}> Sim</label>
            <label><input type="radio" name="configs[exibir_termos]" value="0" {{ $termos === 0 ? 'checked' : '' }}> Não</label>
          </div>

          {{-- Termo (aparece somente se exibir_termos = 1) --}}
          <div id="insc-termos" style="display: {{ $termos === 1 ? 'contents' : 'none' }};">
            <label class="full">Termo (texto exibido ao candidato)</label>
            <textarea class="full" name="configs[termo_texto]" rows="6" {{ $termos === 1 ? '' : 'disabled' }}
                      placeholder="Cole aqui o texto dos termos...">{{ old('configs.termo_texto', data_get($concurso, 'configs.termo_texto')) }}</textarea>
          </div>

          {{-- Cancelamento --}}
          <label>Permitir cancelar inscrição</label>
          <select id="select-cancelar" name="configs[flag_permitir_cancelar_inscricao]">
            <option value="0" {{ $permCanc == '0' ? 'selected' : '' }}>Não permitir</option>
            <option value="1" {{ $permCanc == '1' ? 'selected' : '' }}>Sim, durante o período de inscrições</option>
            <option value="2" {{ $permCanc == '2' ? 'selected' : '' }}>Sim, no período definido abaixo</option>
          </select>

          <div id="cancelamento-fields" style="display: {{ $permCanc != '0' ? 'contents' : 'none' }};">
            <label>Situações que podem cancelar inscrição</label>
            <select name="configs[situacoes_cancelar_inscricao]" {{ $permCanc != '0' ? '' : 'disabled' }}>
              <option value="1" {{ $sitCanc == '1' ? 'selected' : '' }}>Apenas inscrições em aberto</option>
              <option value="2" {{ $sitCanc == '2' ? 'selected' : '' }}>Qualquer situação</option>
            </select>

            {{-- Datas do cancelamento (apenas quando opção = 2) --}}
            <div id="cancelamento-datas" style="display: {{ $permCanc == '2' ? 'contents' : 'none' }};">
              <label>Cancelamento - Início</label>
              <input type="text" name="configs[data_cancelar_inicio]"
                     value="{{ old('configs.data_cancelar_inicio', data_get($concurso, 'configs.data_cancelar_inicio')) }}"
                     class="js-mask-datetime" inputmode="numeric" placeholder="dd/mm/aaaa hh:mm"
                     {{ $permCanc == '2' ? '' : 'disabled' }}>

              <label>Cancelamento - Fim</label>
              <input type="text" name="configs[data_cancelar_fim]"
                     value="{{ old('configs.data_cancelar_fim', data_get($concurso, 'configs.data_cancelar_fim')) }}"
                     class="js-mask-datetime" inputmode="numeric" placeholder="dd/mm/aaaa hh:mm"
                     {{ $permCanc == '2' ? '' : 'disabled' }}>
            </div>

            {{-- Informações sobre o cancelamento (sempre que permitir cancelar) --}}
            <label class="full">Informações sobre o cancelamento</label>
            <textarea class="full" name="configs[texto_cancelar_inscricao]" rows="3" {{ $permCanc != '0' ? '' : 'disabled' }}
                      placeholder="Passos para o candidato cancelar a inscrição...">{{ old('configs.texto_cancelar_inscricao', data_get($concurso, 'configs.texto_cancelar_inscricao')) }}</textarea>
          </div>
        </div>
      </div>

      {{-- ===== Configurações de Layout ===== --}}
      @php $exib = old('configs.exibicao_anexos', data_get($concurso, 'configs.exibicao_anexos', '1')); @endphp
      <div class="card-min">
        <div class="hd">Configurações de Layout</div>
        <div class="form-min">
          <label>Modo de exibição das publicações</label>
          <select name="configs[exibicao_anexos]">
            <option value="1" {{ $exib == '1' ? 'selected' : '' }}>Padrão</option>
            <option value="2" {{ $exib == '2' ? 'selected' : '' }}>Dividido em Abas</option>
          </select>

          <label>Texto alerta (listagem e página)</label>
          <input type="text" name="configs[texto_alerta]"
                 value="{{ old('configs.texto_alerta', data_get($concurso, 'configs.texto_alerta')) }}"
                 placeholder="Ex.: Atenção candidatos: inscrições prorrogadas!">
        </div>
      </div>

      {{-- ===== Ações (mesmo estilo do botão "Voltar") ===== --}}
      <div class="toolbar" style="margin-top:16px; display:flex; gap:12px;">
        <a href="#" class="btn" onclick="document.getElementById('form-config').requestSubmit(); return false;">
          Salvar configurações
        </a>
        <a href="{{ route('admin.concursos.index') }}" class="btn">Cancelar</a>
      </div>
    </form>
  </div>
</div>

{{-- ===== Máscaras de Data e Data/Hora ===== --}}
<script>
(function(){
  function maskDate(el){
      let v = el.value.replace(/\D/g,'').slice(0,8);
      if (v.length >= 5) el.value = v.slice(0,2) + '/' + v.slice(2,4) + '/' + v.slice(4,8);
      else if (v.length >= 3) el.value = v.slice(0,2) + '/' + v.slice(2,4);
      else el.value = v;
  }
  function maskDateTime(el){
      let v = el.value.replace(/\D/g,'').slice(0,12);
      if (v.length >= 11) el.value = v.slice(0,2)+'/'+v.slice(2,4)+'/'+v.slice(4,8)+' '+v.slice(8,10)+':'+v.slice(10,12);
      else if (v.length >= 9) el.value = v.slice(0,2)+'/'+v.slice(2,4)+'/'+v.slice(4,8)+' '+v.slice(8,10);
      else if (v.length >= 5) el.value = v.slice(0,2)+'/'+v.slice(2,4)+'/'+v.slice(4,8);
      else if (v.length >= 3) el.value = v.slice(0,2)+'/'+v.slice(2,4);
      else el.value = v;
  }
  document.addEventListener('input', function(e){
      if (e.target.classList.contains('js-mask-date')) maskDate(e.target);
      if (e.target.classList.contains('js-mask-datetime')) maskDateTime(e.target);
  });

  // Reforço no submit
  document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('form-config');
      if (!form) return;

      form.addEventListener('submit', function () {
          const titulo = form.querySelector('input[name="titulo"]');
          if (titulo) titulo.value = (titulo.value || '').trim();

          const seq = form.querySelector('input[name="configs[sequence_inscricao]"]');
          if (seq) {
              const cleaned = (seq.value || '').toString().replace(/\D+/g,'');
              let n = cleaned === '' ? NaN : parseInt(cleaned, 10);
              if (!Number.isFinite(n) || n < 1) n = 1;
              seq.value = String(n);
          }

          function sanitizeMasked(selector, expectLen) {
              const el = form.querySelector(selector);
              if (!el) return;
              const v = (el.value || '').trim();
              const onlyDigits = v.replace(/\D/g,'');
              if (onlyDigits.length < expectLen) el.value = '';
          }
          // dd/mm/aaaa => 8 dígitos
          sanitizeMasked('input[name="configs[data_edital]"]', 8);
          sanitizeMasked('input[name="configs[vencimento_data]"]', 8);
          // dd/mm/aaaa hh:mm => 12 dígitos
          [
              'input[name="configs[inscricoes_inicio]"]',
              'input[name="configs[inscricoes_fim]"]',
              'input[name="configs[data_impugnacao_inicio]"]',
              'input[name="configs[data_impugnacao_fim]"]',
              'input[name="configs[data_cancelar_inicio]"]',
              'input[name="configs[data_cancelar_fim]"]',
              'input[name="configs[data_isencao_inicio]"]',
              'input[name="configs[data_isencao_fim]"]'
          ].forEach(sel => sanitizeMasked(sel, 12));
      });
  });
})();
</script>

{{-- Tom Select (para os multi-selects) --}}
@once
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
@endonce

<script>
(function () {
  let tsIsenc = null, tsCE = null;

  function mountIsencao(){
    const el = document.getElementById('select-tipos-isencao');
    if (!el) return;
    if (tsIsenc) { tsIsenc.destroy(); tsIsenc = null; }
    if (window.TomSelect) {
      tsIsenc = new TomSelect(el, {
        plugins:['remove_button'],
        maxItems:null,
        create:false,
        persist:false,
        placeholder:'Selecione ...'
      });
    }
  }
  function toggleIsencao(){
    const on  = document.querySelector('input[name="configs[flag_isencao]"]:checked')?.value === '1';
    const box = document.getElementById('isencao-fields');
    if (!box) return;
    box.style.display = on ? 'contents' : 'none';
    box.querySelectorAll('input, select, textarea').forEach(el => el.disabled = !on);
    if (tsIsenc) { on ? tsIsenc.enable() : tsIsenc.disable(); }
  }

  function mountCE(){
    const el = document.getElementById('select-condicoes-especiais');
    if (!el) return;
    if (tsCE) { tsCE.destroy(); tsCE = null; }
    if (window.TomSelect) {
      tsCE = new TomSelect(el, {
        plugins:['remove_button'],
        maxItems:null,
        create:false,
        persist:false,
        placeholder:'Selecione ...'
      });
    }
  }
  function toggleCE(){
    const on  = document.querySelector('input[name="configs[flag_condicoesespeciais]"]:checked')?.value === '1';
    const box = document.getElementById('ce-fields');
    if (!box) return;
    box.style.display = on ? 'contents' : 'none';
    box.querySelectorAll('input, select, textarea').forEach(el => el.disabled = !on);
    if (tsCE) { on ? tsCE.enable() : tsCE.disable(); }
  }

  function toggleImpugnacao(){
    const on = document.querySelector('input[name="configs[flag_impugnacao]"]:checked')?.value === '1';
    const wrap = document.getElementById('impugnacao-datas');
    if (!wrap) return;
    wrap.style.display = on ? 'contents' : 'none';
    wrap.querySelectorAll('input').forEach(inp => inp.disabled = !on);
  }

  function toggleTermos(){
    const on = document.querySelector('input[name="configs[exibir_termos]"]:checked')?.value === '1';
    const box = document.getElementById('insc-termos');
    if (!box) return;
    box.style.display = on ? 'contents' : 'none';
    box.querySelectorAll('textarea').forEach(t => t.disabled = !on);
  }

  function toggleCancelamento(){
    const sel = document.getElementById('select-cancelar');
    if (!sel) return;
    const v = sel.value;
    const box = document.getElementById('cancelamento-fields');
    const datas = document.getElementById('cancelamento-datas');

    if (!box || !datas) return;

    const permitir = (v !== '0');
    box.style.display = permitir ? 'contents' : 'none';

    // habilita/desabilita todos os campos do bloco principal
    box.querySelectorAll('input, select, textarea').forEach(el => el.disabled = !permitir);

    // Datas só quando v == '2'
    const usarDatas = (v === '2');
    datas.style.display = usarDatas ? 'contents' : 'none';
    datas.querySelectorAll('input').forEach(el => el.disabled = !usarDatas);
  }

  document.addEventListener('DOMContentLoaded', function(){
    mountIsencao();
    mountCE();
    toggleIsencao();
    toggleCE();
    toggleImpugnacao();
    toggleTermos();
    toggleCancelamento();

    document.addEventListener('change', function(e){
      if (e.target?.name === 'configs[flag_isencao]') toggleIsencao();
      if (e.target?.name === 'configs[flag_condicoesespeciais]') toggleCE();
      if (e.target?.name === 'configs[flag_impugnacao]') toggleImpugnacao();
      if (e.target?.name === 'configs[exibir_termos]') toggleTermos();
      if (e.target?.name === 'configs[flag_permitir_cancelar_inscricao]') toggleCancelamento();
      if (e.target?.id   === 'select-cancelar') toggleCancelamento();
    });
  });
})();
</script>
@endsection
