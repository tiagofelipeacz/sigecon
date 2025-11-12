{{-- resources/views/site/candidato/inscricoes/create.blade.php --}}
@extends('layouts.site')

@section('title', 'Nova inscrição')

@php
    use Illuminate\Support\Collection;

    $primary = $site['primary_color'] ?? $site['primary'] ?? '#0f172a';
    $accent  = $site['accent_color']  ?? $site['accent']  ?? '#111827';

    // Mapa vindo do controller:
    // $modalidadesPorCargo["concurso_id|cargo_id"] = [...]
    $modalidadesPorCargo = $modalidadesPorCargo ?? [];

    $formasPgLista       = $formasPagamento ?? [];
    $tiposIsencao        = $tiposIsencao ?? [];
    $temIsencao          = $temIsencao ?? (!empty($tiposIsencao));

    // Mapa de condições especiais por concurso (se o controller estiver enviando)
    $condicoesEspeciaisMap = $condicoesEspeciaisMap
        ?? $condicoesEspeciaisPorConcurso
        ?? $condicoesEspeciais
        ?? [];

    // Lista de concursos que o controller envia
    /** @var \Illuminate\Support\Collection|array $concursos */
    $concursos      = $concursos ?? collect();
    $concursosCount = $concursos instanceof Collection ? $concursos->count() : (is_array($concursos) ? count($concursos) : 0);

    // Concurso passado explicitamente (por ex. ao clicar em "INSCRIÇÃO ONLINE")
    $concursoFromCtrl = $concursoSelecionado->id ?? null;              // se o controller mandar um objeto
    $concursoFromReq  = request()->get('concurso_id')
                        ?? request()->get('concurso');                 // parâmetro da rota/query

    // ID efetivamente selecionado
    $selectedConcursoId = old('concurso_id')
        ?? $concursoFromCtrl
        ?? $concursoFromReq;

    // Se não veio nada e só tiver 1 concurso na lista, usa ele
    if (!$selectedConcursoId && $concursosCount === 1) {
        $first = $concursos instanceof Collection ? $concursos->first() : (is_array($concursos) ? reset($concursos) : null);
        if ($first && isset($first->id)) {
            $selectedConcursoId = $first->id;
        }
    }

    // Quando já temos um concurso definido, não mostramos dropdown
    $concursoTravado = (bool) $selectedConcursoId;

    // Nome do concurso travado (para exibir em modo leitura)
    $concursoNomeTravado = null;
    if ($concursoTravado && $concursosCount > 0) {
        $cObj = null;

        if ($concursos instanceof Collection) {
            $cObj = $concursos->firstWhere('id', $selectedConcursoId) ?? $concursos->first();
        } elseif (is_array($concursos)) {
            foreach ($concursos as $c) {
                if (isset($c->id) && (string)$c->id === (string)$selectedConcursoId) {
                    $cObj = $c;
                    break;
                }
            }
            if (!$cObj) {
                $cObj = reset($concursos);
            }
        }

        if ($cObj) {
            $concursoNomeTravado = $cObj->titulo ?? $cObj->nome ?? ('Concurso #'.$cObj->id);
        }
    }
@endphp

@section('content')
<style>
    :root{
        --c-primary: {{ $primary }};
        --c-accent:  {{ $accent }};
        --c-muted:   #6b7280;
        --c-border:  #e5e7eb;
        --c-bg:      #f3f4f6;
    }

    .c-page{
        min-height: calc(100vh - 140px);
        padding: 32px 16px 40px;
        background: radial-gradient(circle at top left, #ffffff 0, #eef2ff 35%, #f9fafb 100%);
        display:flex;
        align-items:flex-start;
        justify-content:center;
        font-size:15px;
    }
    .c-container{
        width:100%;
        max-width: 820px;
        margin:0 auto;
        display:flex;
        flex-direction:column;
        gap:18px;
    }

    .c-header{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:16px;
    }
    .c-title-wrap{
        max-width:70%;
    }
    .c-kicker{
        text-transform:uppercase;
        font-size:11px;
        letter-spacing:.12em;
        font-weight:700;
        color:var(--c-accent);
        margin-bottom:6px;
    }
    .c-title{
        font-size:22px;
        line-height:1.25;
        margin:0 0 6px;
        letter-spacing:-.03em;
        color:#0f172a;
    }
    .c-sub{
        font-size:13px;
        color:var(--c-muted);
        max-width:520px;
        margin:0;
    }

    .c-btn-back{
        border-radius:999px;
        padding:8px 14px;
        font-size:13px;
        font-weight:600;
        border:1px solid var(--c-border);
        background:#fff;
        color:#111827;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        gap:6px;
        cursor:pointer;
        transition:background-color .15s, border-color .15s, transform .05s, box-shadow .15s;
        box-shadow:0 4px 10px rgba(15,23,42,0.06);
    }
    .c-btn-back:hover{
        background:#f9fafb;
        border-color:var(--c-primary);
        transform:translateY(-1px);
        box-shadow:0 8px 18px rgba(15,23,42,0.10);
    }

    .c-card{
        background:#ffffff;
        border-radius:18px;
        border:1px solid var(--c-border);
        box-shadow:0 12px 30px rgba(15,23,42,0.06);
        padding:18px 18px 16px;
    }

    .c-card-title{
        font-size:15px;
        font-weight:700;
        margin:0 0 10px;
        letter-spacing:-.02em;
        color:#111827;
    }
    .c-card-sub{
        font-size:13px;
        color:var(--c-muted);
        margin:0 0 14px;
    }

    .c-form-grid{
        display:grid;
        grid-template-columns:1fr;
        gap:14px;
    }
    .c-field{
        display:flex;
        flex-direction:column;
        gap:4px;
        font-size:13px;
    }
    .c-label{
        font-weight:600;
        color:#111827;
    }
    .c-help{
        font-size:12px;
        color:var(--c-muted);
    }
    .c-input, .c-select, .c-textarea{
        border-radius:10px;
        border:1px solid var(--c-border);
        padding:8px 10px;
        font-size:14px;
        width:100%;
        background:#f9fafb;
        font-family:inherit;
    }
    .c-input--static{
        background:#f9fafb;
        border-style:dashed;
        cursor:default;
    }
    .c-input:focus, .c-select:focus, .c-textarea:focus{
        outline:none;
        border-color:var(--c-primary);
        background:#fff;
        box-shadow:0 0 0 1px rgba(37,99,235,0.15);
    }
    .c-textarea{
        min-height:70px;
        resize:vertical;
    }

    .c-error{
        color:#b91c1c;
        font-size:12px;
    }

    .c-submit-row{
        margin-top:12px;
        display:flex;
        justify-content:flex-end;
    }
    .c-btn-primary{
        border-radius:999px;
        padding:9px 18px;
        font-size:14px;
        font-weight:700;
        border:1px solid var(--c-primary);
        background:var(--c-primary);
        color:#fff;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        gap:6px;
        box-shadow:0 10px 25px rgba(15,23,42,0.20);
        transition: filter .15s, transform .05s, box-shadow .15s;
    }
    .c-btn-primary:hover{
        filter:brightness(1.05);
        transform:translateY(-1px);
        box-shadow:0 14px 28px rgba(15,23,42,0.24);
    }

    .c-checkbox-row{
        display:flex;
        align-items:flex-start;
        gap:8px;
        font-size:13px;
    }
    .c-checkbox-row input[type="checkbox"],
    .c-checkbox-row input[type="radio"]{
        margin-top:2px;
    }

    .c-radio-group{
        display:flex;
        gap:16px;
        margin:4px 0 4px;
    }

    .c-condicoes-opcoes{
        display:flex;
        flex-direction:column;
        gap:6px;
        margin-bottom:6px;
    }

    .c-note{
        font-size:12px;
        color:#0f172a;
        background:#f1f5f9;
        border:1px dashed var(--c-border);
        border-radius:10px;
        padding:8px 10px;
        display:none;
    }

    @media (max-width: 840px){
        .c-page{
            padding-top:20px;
        }
        .c-header{
            flex-direction:column;
            align-items:flex-start;
        }
        .c-title-wrap{
            max-width:100%;
        }
    }
</style>

<div class="c-page">
    <div class="c-container">

        <div class="c-header">
            <div class="c-title-wrap">
                <div class="c-kicker">Área do Candidato</div>
                <h1 class="c-title">Nova inscrição</h1>
                <p class="c-sub">
                    Selecione o cargo desejado, a cidade de prova e informe,
                    se for o caso, modalidade, condições especiais e isenção (quando disponíveis).
                </p>
            </div>

            <a href="{{ route('candidato.inscricoes.index') }}" class="c-btn-back">
                ← Voltar para minhas inscrições
            </a>
        </div>

        <div class="c-card">
            <h2 class="c-card-title">Dados da inscrição</h2>
            <p class="c-card-sub">
                Preencha os campos abaixo para realizar sua inscrição. Verifique com atenção o edital antes de confirmar.
            </p>

            {{-- Mensagem de erro geral (ex: já possui inscrição no concurso) --}}
            @if($errors->has('general'))
                <div class="c-error" style="margin-bottom:8px;">
                    {{ $errors->first('general') }}
                </div>
            @endif

            <form method="POST" action="{{ route('candidato.inscricoes.store') }}">
                @csrf

                <div class="c-form-grid">

                    {{-- Concurso --}}
                    <div class="c-field">
                        <label class="c-label" for="concurso_id">Concurso</label>

                        @if($concursoTravado && $selectedConcursoId && $concursoNomeTravado)
                            {{-- Exibição somente leitura (sem dropdown visual) --}}
                            <div class="c-input c-input--static">
                                {{ $concursoNomeTravado }}
                            </div>

                            {{-- Hidden REAL para o POST --}}
                            <input type="hidden" name="concurso_id" value="{{ $selectedConcursoId }}">

                            {{-- Select escondido só para o JS (change, fetch cargos, etc.) --}}
                            <select id="concurso_id" class="c-select" style="display:none;">
                                <option value="{{ $selectedConcursoId }}" selected>
                                    {{ $concursoNomeTravado }}
                                </option>
                            </select>

                            <div class="c-help">
                                Você está realizando a inscrição neste concurso.
                            </div>
                        @else
                            {{-- Modo “livre” (sem concurso pré-definido) --}}
                            <select name="concurso_id" id="concurso_id" class="c-select" required>
                                <option value="">Selecione...</option>
                                @foreach($concursos as $conc)
                                    <option value="{{ $conc->id }}"
                                        {{ (string)$selectedConcursoId === (string)$conc->id ? 'selected' : '' }}>
                                        {{ $conc->titulo ?? $conc->nome ?? ('Concurso #'.$conc->id) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="c-help">
                                Apenas concursos com inscrições online e período vigente são exibidos.
                            </div>
                        @endif

                        @error('concurso_id')
                        <div class="c-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Cargo --}}
                    <div class="c-field">
                        <label class="c-label" for="cargo_id">Cargo</label>
                        <select name="cargo_id" id="cargo_id" class="c-select" required>
                            <option value="">Selecione primeiro o concurso...</option>
                        </select>
                        <div class="c-help">
                            Os cargos serão carregados após a escolha do concurso.
                        </div>
                        @error('cargo_id')
                        <div class="c-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Localidade (Cidade / local de prova vinculado ao cargo) --}}
                    <div class="c-field" id="field_item">
                        <label class="c-label" for="item_id">Localidade</label>
                        <select name="item_id" id="item_id" class="c-select" required>
                            <option value="">Selecione o cargo...</option>
                        </select>
                        <div class="c-help">
                            Quando o cargo tiver localidades/cidades vinculadas,
                            é obrigatório selecionar uma delas.
                        </div>
                        @error('item_id')
                        <div class="c-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Cidade de prova (configurada no concurso, independente do cargo) --}}
                    <div class="c-field" id="field_cidade_prova" style="display:none;">
                        <label class="c-label" for="cidade_prova">Cidade de prova</label>
                        <select name="cidade_prova" id="cidade_prova" class="c-select">
                            <option value="">Selecione o concurso...</option>
                        </select>
                        <div class="c-help">
                            As cidades de prova são exibidas de acordo com a configuração deste concurso.
                        </div>
                        @error('cidade_prova')
                        <div class="c-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Modalidade (dinâmica a partir das vagas do concurso/cargo) --}}
                    <div class="c-field">
                        <label class="c-label" for="modalidade">Modalidade de concorrência</label>

                        <select name="modalidade" id="modalidade" class="c-select" required>
                            <option value="">
                                Selecione o concurso e o cargo...
                            </option>
                        </select>
                        <div class="c-help">
                            As modalidades (Ampla concorrência, PcD, cotas para negros, idosos, etc.)
                            são exibidas conforme as vagas especiais configuradas para aquele cargo no concurso.
                        </div>

                        @error('modalidade')
                        <div class="c-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Pergunta + Condições especiais (dinâmicas por concurso) --}}
                    <div class="c-field">
                        <label class="c-label">Desejo solicitar condições especiais</label>

                        <div class="c-radio-group" id="grupo_radio_condicoes">
                            @php
                                $oldQuer = old('quer_condicoes_especiais', null);
                                $querSim = (string)$oldQuer === '1';
                                $querNao = $oldQuer === null ? true : ((string)$oldQuer === '0');
                            @endphp
                            <label class="c-checkbox-row" for="quer_condicoes_0">
                                <input type="radio" name="quer_condicoes_especiais" id="quer_condicoes_0" value="0" {{ $querNao ? 'checked' : '' }}>
                                <span>Não</span>
                            </label>
                            <label class="c-checkbox-row" for="quer_condicoes_1">
                                <input type="radio" name="quer_condicoes_especiais" id="quer_condicoes_1" value="1" {{ $querSim ? 'checked' : '' }}>
                                <span>Sim</span>
                            </label>
                        </div>

                        {{-- Bloco que aparece apenas quando escolher "Sim" --}}
                        <div id="field_condicoes_especiais" style="display:none; margin-top:8px;">
                            <div id="condicoes_especiais_opcoes" class="c-condicoes-opcoes">
                                {{-- checkboxes gerados via JS --}}
                            </div>

                            <textarea
                                name="condicoes_especiais"
                                id="condicoes_especiais"
                                class="c-textarea"
                                placeholder="Descreva detalhes adicionais sobre o atendimento especial ou recursos de acessibilidade, se necessário."
                            >{{ old('condicoes_especiais') }}</textarea>

                            <div id="aviso_laudo" class="c-note" style="margin-top:8px;"></div>

                            <div class="c-help" style="margin-top:6px;">
                                Marque as condições especiais disponíveis para este concurso e, se precisar, complemente com uma descrição.
                            </div>

                            @error('condicoes_especiais')
                            <div class="c-error">{{ $message }}</div>
                            @enderror
                            @error('condicoes_especiais_opcoes')
                            <div class="c-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Isenção (só aparece se $temIsencao = true) --}}
                    @if($temIsencao)
                        <div class="c-field">
                            <div class="c-checkbox-row">
                                <input
                                    type="checkbox"
                                    id="solicitou_isencao"
                                    name="solicitou_isencao"
                                    value="1"
                                    {{ old('solicitou_isencao') ? 'checked' : '' }}
                                >
                                <label for="solicitou_isencao">
                                    Solicito isenção da taxa de inscrição, nos termos previstos no edital.
                                </label>
                            </div>

                            @if(!empty($tiposIsencao))
                                <div style="margin-top:6px;">
                                    <label class="c-label" for="tipo_isencao" style="font-weight:500;">Tipo de isenção</label>
                                    <select name="tipo_isencao" id="tipo_isencao" class="c-select">
                                        <option value="">Selecione...</option>
                                        @foreach($tiposIsencao as $tipo)
                                            @php
                                                $val = $tipo->codigo
                                                    ?? $tipo->id
                                                    ?? ($tipo['codigo'] ?? $tipo['id'] ?? null);
                                                $rot = $tipo->nome
                                                    ?? $tipo->descricao
                                                    ?? ($tipo['nome'] ?? $tipo['descricao'] ?? $val);
                                            @endphp
                                            @if($val)
                                                <option value="{{ $val }}" {{ old('tipo_isencao') == $val ? 'selected' : '' }}>
                                                    {{ $rot }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="c-help">
                                A concessão da isenção depende de análise e deferimento conforme as regras do edital.
                            </div>

                            @error('solicitou_isencao')
                            <div class="c-error">{{ $message }}</div>
                            @enderror
                            @error('tipo_isencao')
                            <div class="c-error">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    {{-- Forma de pagamento (só aparece se houver configuração) --}}
                    @if(!empty($formasPgLista) && count($formasPgLista))
                        <div class="c-field">
                            <label class="c-label" for="forma_pagamento">Forma de pagamento</label>
                            <select name="forma_pagamento" id="forma_pagamento" class="c-select">
                                <option value="">Selecionar...</option>
                                @foreach($formasPgLista as $fp)
                                    @php
                                        $val = $fp->codigo
                                            ?? $fp->slug
                                            ?? $fp->id
                                            ?? ($fp['codigo'] ?? $fp['slug'] ?? $fp['id'] ?? null);
                                        $rot = $fp->nome
                                            ?? $fp->descricao
                                            ?? ($fp['nome'] ?? $fp['descricao'] ?? $val);
                                    @endphp
                                    @if($val)
                                        <option value="{{ $val }}" {{ old('forma_pagamento') == $val ? 'selected' : '' }}>
                                            {{ $rot }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="c-help">
                                Escolha a forma de pagamento da taxa, conforme opções disponibilizadas no concurso.
                            </div>
                            @error('forma_pagamento')
                            <div class="c-error">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                </div>

                <div class="c-submit-row">
                    <button type="submit" class="c-btn-primary">
                        Confirmar inscrição
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

{{-- Scripts para carregar cargos, localidades, cidades de prova, condições especiais e modalidades via AJAX --}}
@push('scripts')
<script>
    (function(){
        const selectConcurso      = document.getElementById('concurso_id');
        const selectCargo         = document.getElementById('cargo_id');
        const selectItem          = document.getElementById('item_id');
        const selectModalidade    = document.getElementById('modalidade');
        const fieldItemWrapper    = document.getElementById('field_item');

        const fieldCidadeProva    = document.getElementById('field_cidade_prova');
        const selectCidadeProva   = document.getElementById('cidade_prova');

        // Radio SIM/NÃO para condições
        const radioNao            = document.getElementById('quer_condicoes_0');
        const radioSim            = document.getElementById('quer_condicoes_1');
        const fieldCondicoesEsp   = document.getElementById('field_condicoes_especiais');
        const wrapCondicoesOpcoes = document.getElementById('condicoes_especiais_opcoes');
        const txtCondicoes        = document.getElementById('condicoes_especiais');
        const avisoLaudo          = document.getElementById('aviso_laudo');

        const modalidadesPorCargo   = @json($modalidadesPorCargo);
        const condicoesEspeciaisMap = @json($condicoesEspeciaisMap);

        const oldModalidade       = @json(old('modalidade'));
        const oldCidadeProva      = @json(old('cidade_prova'));
        const oldItemId           = @json(old('item_id'));
        const oldCondicoesOpcoes  = @json(old('condicoes_especiais_opcoes', []));
        const oldQuerCond         = @json(old('quer_condicoes_especiais', '0'));
        const fixedConcursoId     = @json($selectedConcursoId);

        function clearSelect(select, placeholder){
            if (!select) return;
            select.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = placeholder;
            select.appendChild(opt);
        }

        function toggle(el, show){
            if (!el) return;
            el.style.display = show ? '' : 'none';
        }

        function setRequired(el, must){
            if (!el) return;
            if (must) el.setAttribute('required', 'required');
            else el.removeAttribute('required');
        }

        function preencherModalidades(concursoId, cargoId){
            if (!selectModalidade) return;

            const key = (concursoId && cargoId) ? (concursoId + '|' + cargoId) : null;

            let lista = key && modalidadesPorCargo[key]
                ? modalidadesPorCargo[key]
                : null;

            // Fallback: se não achar nada, usa "Ampla concorrência"
            if (!lista || Object.keys(lista).length === 0) {
                lista = { 'Ampla concorrência': 'Ampla concorrência' };
            }

            clearSelect(selectModalidade, 'Selecione...');

            Object.entries(lista).forEach(([value, label]) => {
                const opt = document.createElement('option');
                opt.value = value;
                opt.textContent = label;
                if (oldModalidade && oldModalidade === value) {
                    opt.selected = true;
                }
                selectModalidade.appendChild(opt);
            });
        }

        /**
         * Preenche as condições especiais de forma dinâmica a partir do mapa
         * condicoesEspeciaisMap[concursoId] = [{id, label, exibir_observacoes, precisa_laudo, laudo_obrigatorio}, ...]
         */
        function preencherCondicoesEspeciais(concursoId){
            if (!wrapCondicoesOpcoes) return;

            wrapCondicoesOpcoes.innerHTML = '';

            if (!concursoId || !condicoesEspeciaisMap || !condicoesEspeciaisMap[concursoId] || !condicoesEspeciaisMap[concursoId].length) {
                toggle(fieldCondicoesEsp, false);
                return;
            }

            const lista = condicoesEspeciaisMap[concursoId];

            lista.forEach((raw) => {
                const value = raw.value || raw.id || raw.codigo || raw.slug || raw;
                const label = raw.label || raw.nome || raw.descricao || String(value);

                // flags vindos do controller (fallback para 0)
                const precisaObs   = Number(raw.exibir_observacoes || raw.exibir_observacao || 0) ? 1 : 0;
                const precisaLaudo = Number(raw.precisa_laudo || raw.necessita_laudo || 0) ? 1 : 0;
                const laudoObr     = Number(raw.laudo_obrigatorio || raw.envio_laudo_obrigatorio || 0) ? 1 : 0;

                if (!value || !label) return;

                const inputId = 'cond_esp_' + concursoId + '_' + value;

                const wrapper = document.createElement('label');
                wrapper.className = 'c-checkbox-row';
                wrapper.setAttribute('for', inputId);

                const cb = document.createElement('input');
                cb.type  = 'checkbox';
                cb.id    = inputId;
                cb.name  = 'condicoes_especiais_opcoes[]';
                cb.value = label;

                cb.setAttribute('data-observacoes', precisaObs);
                cb.setAttribute('data-laudo', precisaLaudo);
                cb.setAttribute('data-laudo-obrigatorio', laudoObr);

                if (Array.isArray(oldCondicoesOpcoes) && oldCondicoesOpcoes.includes(label)) {
                    cb.checked = true;
                }

                cb.addEventListener('change', avaliarRequisitosCondicoes);

                const span = document.createElement('span');
                span.textContent = label;

                wrapper.appendChild(cb);
                wrapper.appendChild(span);

                wrapCondicoesOpcoes.appendChild(wrapper);
            });

            toggle(fieldCondicoesEsp, (radioSim && radioSim.checked));
            // Reavalia para setar textarea/aviso de laudo
            setTimeout(avaliarRequisitosCondicoes, 0);
        }

        function avaliarRequisitosCondicoes(){
            if (!wrapCondicoesOpcoes) return;

            const checks = wrapCondicoesOpcoes.querySelectorAll('input[type="checkbox"]');
            let exigeObs = false;
            let pedeLaudo = false;
            let laudoObrigatorio = false;

            checks.forEach(cb => {
                if (cb.checked) {
                    if (String(cb.getAttribute('data-observacoes')) === '1')  exigeObs = true;
                    if (String(cb.getAttribute('data-laudo')) === '1')        pedeLaudo = true;
                    if (String(cb.getAttribute('data-laudo-obrigatorio')) === '1') laudoObrigatorio = true;
                }
            });

            // Observações (texto)
            if (exigeObs) {
                toggle(txtCondicoes, true);
                setRequired(txtCondicoes, true);
            } else {
                setRequired(txtCondicoes, false);
                // mantém visível caso usuário queira detalhar mesmo sem ser obrigatório
                toggle(txtCondicoes, true);
            }

            // Aviso de laudo
            if (pedeLaudo || laudoObrigatorio) {
                avisoLaudo.style.display = '';
                avisoLaudo.innerText = laudoObrigatorio
                    ? 'Para pelo menos uma das condições marcadas, o envio de laudo médico é obrigatório conforme o edital.'
                    : 'Para pelo menos uma das condições marcadas, poderá ser solicitado laudo médico para análise.';
            } else {
                avisoLaudo.style.display = 'none';
                avisoLaudo.innerText = '';
            }
        }

        /**
         * Carrega cidades de prova dinâmicas via endpoint:
         *   GET candidato/inscricoes/cidades/{concursoId}/{cargoId?}
         */
        function carregarCidadesProva(concursoId, cargoId){
            if (!selectCidadeProva || !fieldCidadeProva) return;

            if (!concursoId) {
                clearSelect(selectCidadeProva, 'Selecione o concurso...');
                toggle(fieldCidadeProva, false);
                return;
            }

            let url = "{{ url('candidato/inscricoes/cidades') }}/" + concursoId;
            if (cargoId) {
                url += "/" + cargoId;
            }

            clearSelect(selectCidadeProva, 'Carregando cidades de prova...');

            fetch(url)
                .then(resp => resp.json())
                .then(data => {
                    if (!data || !data.length) {
                        clearSelect(selectCidadeProva, 'Nenhuma cidade de prova configurada para este concurso');
                        toggle(fieldCidadeProva, false);
                        return;
                    }

                    clearSelect(selectCidadeProva, 'Selecione...');
                    data.forEach(c => {
                        const label = c.label
                            || (c.cidade + (c.uf ? ' / ' + c.uf : ''))
                            || c.cidade
                            || '';

                        if (!label) return;

                        const value = label;

                        const opt = document.createElement('option');
                        opt.value = value;
                        opt.textContent = label;

                        if (oldCidadeProva && oldCidadeProva === value) {
                            opt.selected = true;
                        }

                        selectCidadeProva.appendChild(opt);
                    });

                    toggle(fieldCidadeProva, true);
                })
                .catch(() => {
                    clearSelect(selectCidadeProva, 'Erro ao carregar cidades de prova');
                    toggle(fieldCidadeProva, false);
                });
        }

        // SIM/NÃO – mostra/oculta bloco de condições
        function onToggleQuerCondicoes(){
            const show = radioSim && radioSim.checked;
            toggle(fieldCondicoesEsp, show);
            if (!show && wrapCondicoesOpcoes) {
                // limpa marcações e requisitos quando desabilita
                const checks = wrapCondicoesOpcoes.querySelectorAll('input[type="checkbox"]');
                checks.forEach(cb => cb.checked = false);
                avaliarRequisitosCondicoes();
            }
        }

        // Quando troca o concurso, carrega cargos, cidades de prova e condições especiais
        if (selectConcurso) {
            selectConcurso.addEventListener('change', function(){
                const concursoId = this.value;

                clearSelect(selectCargo, 'Carregando cargos...');
                clearSelect(selectItem, 'Selecione o cargo...');
                if (selectItem) setRequired(selectItem, false);

                clearSelect(selectModalidade, 'Selecione o concurso e o cargo...');
                clearSelect(selectCidadeProva, 'Selecione o concurso...');

                toggle(fieldItemWrapper, true);
                toggle(fieldCidadeProva, false);

                // limpa/oculta condições especiais
                preencherCondicoesEspeciais(null);

                if(!concursoId){
                    clearSelect(selectCargo, 'Selecione primeiro o concurso...');
                    return;
                }

                // Cargos
                fetch("{{ url('candidato/inscricoes/cargos') }}/" + concursoId)
                    .then(resp => resp.json())
                    .then(data => {
                        clearSelect(selectCargo, 'Selecione...');
                        data.forEach(c => {
                            const opt = document.createElement('option');
                            opt.value = c.id;
                            opt.textContent = c.nome || ('Cargo #' + c.id);
                            selectCargo.appendChild(opt);
                        });
                    })
                    .catch(() => {
                        clearSelect(selectCargo, 'Erro ao carregar cargos');
                    });

                // Cidades de prova (por concurso, sem filtro de cargo ainda)
                carregarCidadesProva(concursoId, null);

                // Condições especiais (por concurso)
                preencherCondicoesEspeciais(concursoId);
            });
        }

        // Quando troca o cargo, carrega localidades (itens), modalidades e refina cidades de prova
        if (selectCargo) {
            selectCargo.addEventListener('change', function(){
                const concursoId = selectConcurso ? selectConcurso.value : null;
                const cargoId    = this.value;

                clearSelect(selectItem, 'Carregando localidades...');
                if (selectItem) setRequired(selectItem, false);

                clearSelect(selectModalidade, 'Carregando modalidades...');
                toggle(fieldItemWrapper, true);

                if(!concursoId || !cargoId){
                    clearSelect(selectItem, 'Selecione o cargo...');
                    if (selectItem) setRequired(selectItem, false);

                    clearSelect(selectModalidade, 'Selecione o concurso e o cargo...');
                    toggle(fieldItemWrapper, false);
                    carregarCidadesProva(concursoId, null);
                    return;
                }

                // Localidades (itens)
                fetch("{{ url('candidato/inscricoes/localidades') }}/" + concursoId + "/" + cargoId)
                    .then(resp => resp.json())
                    .then(data => {
                        if(!data || !data.length){
                            clearSelect(selectItem, 'Não há localidades cadastradas para este cargo');
                            if (selectItem) setRequired(selectItem, false);
                            toggle(fieldItemWrapper, false);
                            return;
                        }

                        if (data.length > 1) {
                            // Mais de uma localidade: exibe o campo para o candidato escolher (OBRIGATÓRIO)
                            clearSelect(selectItem, 'Selecione...');
                            data.forEach(i => {
                                const opt = document.createElement('option');
                                opt.value = i.item_id;
                                opt.textContent = i.localidade_nome || ('Item #' + i.item_id);
                                if (oldItemId && String(oldItemId) === String(i.item_id)) {
                                    opt.selected = true;
                                }
                                selectItem.appendChild(opt);
                            });
                            if (selectItem) setRequired(selectItem, true);
                            toggle(fieldItemWrapper, true);
                        } else {
                            // Apenas uma localidade: seleciona automaticamente (continua obrigatório, mas já vem preenchido)
                            clearSelect(selectItem, 'Única localidade disponível');
                            const unico = data[0];
                            const opt = document.createElement('option');
                            opt.value = unico.item_id;
                            opt.textContent = unico.localidade_nome || ('Item #' + unico.item_id);
                            opt.selected = true;
                            selectItem.appendChild(opt);

                            if (selectItem) setRequired(selectItem, true);
                            // Pode ocultar o campo, já que há uma única opção
                            toggle(fieldItemWrapper, false);
                        }
                    })
                    .catch(() => {
                        clearSelect(selectItem, 'Erro ao carregar localidades');
                        if (selectItem) setRequired(selectItem, false);
                        toggle(fieldItemWrapper, false);
                    });

                // Modalidades para este (concurso, cargo)
                preencherModalidades(concursoId, cargoId);

                // Cidades de prova refinadas por cargo (quando houver vínculo em concursos_cidades_cargos)
                carregarCidadesProva(concursoId, cargoId);
            });
        }

        // Listeners de SIM/NÃO
        if (radioSim)  radioSim.addEventListener('change', onToggleQuerCondicoes);
        if (radioNao)  radioNao.addEventListener('change', onToggleQuerCondicoes);

        // Se veio old('concurso_id') ou um concurso fixo (INSCRIÇÃO ONLINE), dispara change inicial
        document.addEventListener('DOMContentLoaded', function () {
            const oldConcurso = @json(old('concurso_id'));
            const oldCargo    = @json(old('cargo_id'));

            const initialConcurso = oldConcurso || fixedConcursoId;

            // Estado inicial do bloco de condições
            onToggleQuerCondicoes();

            if (initialConcurso) {
                // Preenche condições especiais logo de cara, se já houver concurso definido
                preencherCondicoesEspeciais(initialConcurso);
            }

            if (selectConcurso && initialConcurso) {
                selectConcurso.value = initialConcurso;
                selectConcurso.dispatchEvent(new Event('change'));

                if (oldCargo) {
                    setTimeout(() => {
                        selectCargo.value = oldCargo;
                        selectCargo.dispatchEvent(new Event('change'));
                    }, 400);
                }
            }
        });

    })();
</script>
@endpush

@endsection
