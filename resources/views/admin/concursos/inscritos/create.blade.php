@extends('layouts.sigecon')

@section('title', 'Nova Inscrição - SIGECON')

@section('content')
@php
  $cpfRaw = old('cpf', request('cpf', $cpf ?? ''));
  $cpfFmt = (preg_match('/^\d{11}$/', $cpfRaw))
      ? (substr($cpfRaw,0,3).'.'.substr($cpfRaw,3,3).'.'.substr($cpfRaw,6,3).'-'.substr($cpfRaw,9,2))
      : $cpfRaw;

  $STATUS      = $STATUS      ?? ['rascunho','pendente_pagamento','confirmada','cancelada','importada'];
  $STATUS_LBL  = $STATUS_LBL  ?? ['rascunho'=>'Rascunho','pendente_pagamento'=>'Pendente','confirmada'=>'Confirmada','cancelada'=>'Cancelada','importada'=>'Importada'];
  $MODALIDADES = $MODALIDADES ?? ['ampla','pcd','negros','outras'];

  // garante coleções
  $cargos          = ($cargos ?? collect());
  $itensLocaisColl = isset($itensLocais) ? collect($itensLocais) : collect();
  $condicoesEspeciais = isset($condicoesEspeciais) ? collect($condicoesEspeciais) : collect();
@endphp

<style>
  /* === mesmo design das telas de Vagas === */
  .gc-page   { display:grid; grid-template-columns: 260px 1fr; gap:16px; }
  .gc-card   { background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.03); }
  .gc-body   { padding:14px; }
  .gc-row-2  { display:grid; grid-template-columns: 1fr; gap:14px; }
  .grid{ display:grid; gap:10px; }
  .g-2{ grid-template-columns: 1fr 1fr; }
  .g-3{ grid-template-columns: 1fr 1fr 1fr; }
  .g-4{ grid-template-columns: repeat(4, 1fr); }
  .mb-2{ margin-bottom:8px; }
  .mb-3{ margin-bottom:12px; }
  .mt-2{ margin-top:8px; }
  .hr{ height:1px; background:#f3f4f6; margin:10px 0; }
  .tag{ font-size:12px; color:#6b7280; display:block; margin-bottom:6px; }
  .muted{ color:#6b7280; }
  .input{ width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:8px; }
  .input-lg{ padding:10px 12px; font-size:15px; }
  .btn{ display:inline-flex; align-items:center; gap:6px; border:1px solid #e5e7eb; padding:8px 10px; border-radius:8px; text-decoration:none; cursor:pointer; }
  .btn:hover{ background:#f9fafb; }
  .btn.primary{ background:#111827; color:#fff; border-color:#111827; }
  .btn.link{ background:transparent; border-color:transparent; color:#111827; }
  .inline-help{ font-size:12px; color:#6b7280; }
  .x-scroll{ overflow-x:auto; }
</style>

<div class="gc-page">
  {{-- Menu lateral do concurso --}}
  <div>
    @include('admin.concursos.partials.right-menu', [
      'concurso'    => $concurso,
      'menu_active' => 'inscritos'
    ])
  </div>

  {{-- Conteúdo --}}
  <div class="gc-row-2">

    {{-- AVISO DE ERROS DE VALIDAÇÃO --}}
    @if($errors->any())
      <div class="gc-card">
        <div class="gc-body">
          <div class="mb-2" style="font-weight:600; color:#b91c1c">Erros de validação</div>
          <ul style="margin:0; padding-left:18px; color:#b91c1c">
            @foreach($errors->all() as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      </div>
    @endif

    <form id="form-inscricao" method="POST" action="{{ route('admin.concursos.inscritos.store', $concurso) }}">
      @csrf

      {{-- contexto --}}
      <input type="hidden" name="cpf" id="cpf" value="{{ $cpfRaw }}">
      <input type="hidden" name="candidato_id" id="candidato_id" value="{{ old('candidato_id', request('candidato_id', $candidato->id ?? '')) }}">
      <input type="hidden" name="nascimento" id="nascimento" value="{{ old('nascimento') }}">

      {{-- Opções Gerais --}}
      <div class="gc-card">
        <div class="gc-body">
          <div class="mb-2" style="font-weight:600">Opções Gerais</div>

          <div class="grid g-2">
            <div>
              <span class="tag">CPF</span>
              <div class="input" style="background:#f9fafb">{{ $cpfFmt ?: '-' }}</div>
            </div>
            <div>
              <label class="tag"><span class="text-danger">*</span> Nome Completo</label>
              <input type="text" name="nome_candidato" class="input input-lg" value="{{ old('nome_candidato', $candidato->nome ?? '') }}" required />
            </div>
          </div>

          <div class="grid g-3 mt-2">
            <div>
              <label class="tag"><span class="text-danger">*</span> Data de Nascimento</label>
              <input type="text" id="original-data_nascimento" name="original_data_nascimento" placeholder="DD/MM/AAAA" class="input" value="{{ old('original_data_nascimento', isset($candidato->data_nascimento) ? \Carbon\Carbon::parse($candidato->data_nascimento)->format('d/m/Y') : '') }}" required />
            </div>
            <div>
              <label class="tag">E-mail</label>
              <input type="email" name="email" class="input" value="{{ old('email', $candidato->email ?? '') }}" />
            </div>
            <div>
              <label class="tag">Sexo</label>
              <select name="sexo" class="input">
                <option value=""></option>
                <option value="M" @selected(old('sexo', $candidato->sexo ?? '')==='M')>Masculino</option>
                <option value="F" @selected(old('sexo', $candidato->sexo ?? '')==='F')>Feminino</option>
              </select>
            </div>
          </div>

          <div class="grid g-3 mt-2">
            <div>
              <label class="tag">Telefone</label>
              <input type="text" name="telefone" class="input" value="{{ old('telefone', $candidato->telefone ?? '') }}" />
            </div>
            <div>
              <label class="tag">Celular</label>
              <input type="text" name="celular" class="input" value="{{ old('celular', $candidato->celular ?? '') }}" />
            </div>
            <div></div>
          </div>
        </div>
      </div>

      {{-- RG --}}
      <div class="gc-card">
        <div class="gc-body">
          <div class="mb-2" style="font-weight:600">RG</div>
          <div class="grid g-3">
            <div>
              <label class="tag">Número</label>
              <input type="text" name="doc1" class="input" value="{{ old('doc1', $candidato->doc_numero ?? '') }}" />
            </div>
            <div>
              <label class="tag">Órgão (Ex.: SSP)</label>
              <input type="text" name="doc2" class="input" value="{{ old('doc2', $candidato->doc_orgao ?? '') }}" />
            </div>
            <div>
              <label class="tag">UF</label>
              <input type="text" name="doc3" class="input" value="{{ old('doc3', $candidato->doc_uf ?? '') }}" />
            </div>
          </div>
        </div>
      </div>

      {{-- Endereço --}}
      <div class="gc-card">
        <div class="gc-body">
          <div class="mb-2" style="font-weight:600">Endereço</div>
        <div class="grid g-3">
            <div>
              <label class="tag">Cep</label>
              <input type="text" name="endereco_cep" class="input" value="{{ old('endereco_cep', $candidato->endereco_cep ?? '') }}" />
            </div>
            <div class="g-col-2">
              <label class="tag">Endereço</label>
              <input type="text" name="endereco_rua" class="input" value="{{ old('endereco_rua', $candidato->endereco_rua ?? '') }}" />
            </div>
            <div>
              <label class="tag">Número</label>
              <input type="text" name="endereco_numero" class="input" value="{{ old('endereco_numero', $candidato->endereco_numero ?? '') }}" />
            </div>
            <div>
              <label class="tag">Complemento</label>
              <input type="text" name="endereco_complemento" class="input" value="{{ old('endereco_complemento', $candidato->endereco_complemento ?? '') }}" />
            </div>
            <div>
              <label class="tag">Bairro</label>
              <input type="text" name="endereco_bairro" class="input" value="{{ old('endereco_bairro', $candidato->endereco_bairro ?? '') }}" />
            </div>
            <div>
              <label class="tag">Cidade</label>
              <input type="text" name="cidade" class="input" value="{{ old('cidade', $candidato->cidade ?? '') }}" />
            </div>
            <div>
              <label class="tag">Estado</label>
              <select name="id_estado" class="input">
                <option value=""></option>
                @php($ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'])
                @foreach($ufs as $i => $uf)
                  <option value="{{ $i+1 }}" {{ old('id_estado', null) == ($i+1) || (isset($candidato->estado) && $candidato->estado == $uf) ? 'selected' : '' }}>{{ $uf }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>

      {{-- PcD --}}
      <div class="gc-card">
        <div class="gc-body">
          <div class="mb-2" style="font-weight:600">PcD - Pessoa com Deficiência</div>
          <div class="grid g-3">
            <div>
              <label class="tag">Deficiência</label>
              <select name="id_deficiencia" class="input">
                <option value=""></option>
                <option value="1" @selected(old('id_deficiencia', $candidato->id_deficiencia ?? '')=='1')>Auditiva</option>
                <option value="2" @selected(old('id_deficiencia', $candidato->id_deficiencia ?? '')=='2')>Visual</option>
                <option value="3" @selected(old('id_deficiencia', $candidato->id_deficiencia ?? '')=='3')>Intelectual</option>
                <option value="4" @selected(old('id_deficiencia', $candidato->id_deficiencia ?? '')=='4')>Física</option>
                <option value="5" @selected(old('id_deficiencia', $candidato->id_deficiencia ?? '')=='5')>Outra</option>
              </select>
            </div>
            <div class="g-col-2">
              <label class="tag">Obs. Deficiência</label>
              <textarea name="deficiencia_obs" rows="3" class="input">{{ old('deficiencia_obs') }}</textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- Inscrição --}}
      <div class="gc-card">
        <div class="gc-body">
          <div class="mb-2" style="font-weight:600">Inscrição</div>

          <div class="grid g-3">
            <div>
              <label class="tag"><span class="text-danger">*</span> Preenchimento da Inscrição</label>
              <select id="preenchimento" name="preenchimento" class="input" required>
                <option value="automatico" {{ old('preenchimento','automatico')==='automatico' ? 'selected' : '' }}>Automático</option>
                <option value="manual" {{ old('preenchimento')==='manual' ? 'selected' : '' }}>Manual</option>
              </select>
              <div class="inline-help">Automático usa a sequência configurada do concurso. Manual permite informar um número específico.</div>
            </div>

            <div id="wrap-numero" style="display:none">
              <label class="tag">Número da Inscrição (manual)</label>
              <input type="text" id="inscricao_numero" name="inscricao_numero" class="input" value="{{ old('inscricao_numero') }}" placeholder="Digite o número" />
            </div>

            <div>
              <label class="tag"><span class="text-danger">*</span> Situação da Inscrição</label>
              <select name="status" class="input" required>
                <option value="" disabled {{ old('status') ? '' : 'selected' }}>- selecione -</option>
                @foreach($STATUS as $st)
                  <option value="{{ $st }}" {{ old('status')===$st ? 'selected' : '' }}>{{ $STATUS_LBL[$st] ?? ucfirst($st) }}</option>
                @endforeach
              </select>
              @error('status')
                <div class="inline-help" style="color:#dc2626">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="grid g-2 mt-2">
            <div>
              <label class="tag"><span class="text-danger">*</span> Vaga</label>
              <select id="cargo_id" name="cargo_id" class="input" required>
                <option value=""></option>
                @foreach($cargos as $cargo)
                  <option value="{{ $cargo->id }}" {{ old('cargo_id')==$cargo->id ? 'selected' : '' }}>{{ $cargo->nome }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label class="tag">Modalidade (Vazio para "Ampla Concorrência")</label>
              <select id="modalidade" name="modalidade" class="input">
                <option value=""></option>
                @foreach($MODALIDADES as $m)
                  <option value="{{ $m }}" {{ old('modalidade')==$m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                @endforeach
              </select>
            </div>
          </div>

          {{-- Localidade por Vaga (quando houver) --}}
          <div class="mt-2" id="wrap-localidade" style="display:none">
            <label class="tag">
              Localidade da Vaga (quando houver)
              <span id="req-star" class="text-danger" style="display:none">*</span>
            </label>
            <select id="item_id" name="item_id" class="input" disabled>
              <option value="">- selecione a localidade -</option>
            </select>
            @error('item_id')
              <div class="inline-help" style="color:#dc2626">{{ $message }}</div>
            @enderror
            <div class="inline-help">Aparece somente para vagas que possuem itens/localidades cadastrados.</div>
          </div>

          {{-- Condições Especiais: se existirem --}}
          <div class="mt-2">
            <label class="tag">Condições Especiais</label>
            <select id="condicoes_especiais" name="condicoes_especiais[]" multiple class="input" {{ $condicoesEspeciais->isEmpty() ? 'disabled' : '' }}>
              @foreach($condicoesEspeciais as $ce)
                <option value="{{ $ce->id }}" {{ collect(old('condicoes_especiais', []))->contains($ce->id) ? 'selected' : '' }}>{{ $ce->nome }}</option>
              @endforeach
            </select>
            @if($condicoesEspeciais->isEmpty())
              <div class="inline-help">Nenhum selecionado</div>
            @endif
          </div>

          <div class="mt-2">
            <label class="tag">Observações</label>
            <textarea id="observacoes" name="observacoes" rows="3" class="input">{{ old('observacoes') }}</textarea>
          </div>
        </div>
      </div>

      <div style="display:flex; gap:10px; justify-content:flex-end">
        <a href="{{ route('admin.concursos.inscritos.index', $concurso) }}" class="btn link">Cancelar</a>
        <button class="btn primary" type="submit">Salvar inscrição</button>
      </div>

    </form>
  </div>
</div>

{{-- JS inline para não depender de @push/@stack --}}
<script>
  (function(){
    const form = document.getElementById('form-inscricao');
    const vis  = document.getElementById('original-data_nascimento');
    const hid  = document.getElementById('nascimento');

    function toggleNumero(){
      const select = document.getElementById('preenchimento');
      const wrap   = document.getElementById('wrap-numero');
      const inp    = document.getElementById('inscricao_numero');
      const manual = (select && select.value === 'manual');
      if (wrap) wrap.style.display = manual ? 'block' : 'none';
      if (inp){ inp.readOnly = !manual; if(!manual){ inp.value = ''; } }
    }

    const sel = document.getElementById('preenchimento');
    if (sel) sel.addEventListener('change', toggleNumero);
    toggleNumero(); // estado inicial

    if (form) form.addEventListener('submit', function(){
      const v = (vis ? vis.value : '').trim();
      const m = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
      if (m && hid) hid.value = m[3] + '-' + m[2] + '-' + m[1];
    });

    // ====== LOCALIDADES POR CARGO ======
    // mapa gerado no servidor: { "<cargo_id>": [{id, nome}, ...], ... }
    const ITENS_LOCAIS = @json(
      $itensLocaisColl
        ->groupBy('cargo_id')
        ->map(fn($g) => $g->map(fn($i) => ['id' => (int)$i->id, 'nome' => (string)$i->local_nome])->values())
        ->toArray()
    );

    const cargoSel   = document.getElementById('cargo_id');
    const wrapLoc    = document.getElementById('wrap-localidade');
    const locSel     = document.getElementById('item_id');
    const reqStar    = document.getElementById('req-star');
    const oldCargoId = "{{ old('cargo_id') }}";
    const oldItemId  = "{{ old('item_id') }}";

    function renderLocalidades() {
      if (!cargoSel || !locSel || !wrapLoc) return;

      const cid = String(cargoSel.value || '');
      const lista = ITENS_LOCAIS[cid] || [];

      // limpa
      locSel.innerHTML = '';
      const opt0 = document.createElement('option');
      opt0.value = '';
      opt0.textContent = '- selecione a localidade -';
      locSel.appendChild(opt0);

      if (lista.length) {
        lista.forEach(it => {
          const o = document.createElement('option');
          o.value = String(it.id);
          o.textContent = it.nome;
          if (String(oldItemId) && String(oldItemId) === String(it.id)) o.selected = true;
          locSel.appendChild(o);
        });
        wrapLoc.style.display = 'block';
        locSel.disabled = false;
        locSel.required = true;
        if (reqStar) reqStar.style.display = 'inline';
      } else {
        wrapLoc.style.display = 'none';
        locSel.disabled = true;
        locSel.required = false;
        if (reqStar) reqStar.style.display = 'none';
      }
    }

    if (cargoSel) {
      cargoSel.addEventListener('change', function(){
        // zera seleção antiga quando troca de cargo
        locSel.value = '';
        renderLocalidades();
      });
      // inicial
      renderLocalidades();
      // se veio old('cargo_id') e havia lista, já fica visível
      if (oldCargoId && ITENS_LOCAIS[String(oldCargoId)]) {
        wrapLoc.style.display = 'block';
        locSel.disabled = false;
        locSel.required = (ITENS_LOCAIS[String(oldCargoId)].length > 0);
        if (reqStar) reqStar.style.display = locSel.required ? 'inline' : 'none';
      }
    }
  })();
</script>
@endsection
