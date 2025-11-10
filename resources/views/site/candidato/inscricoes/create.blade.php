{{-- resources/views/site/candidato/inscricoes/create.blade.php --}}
@extends('layouts.site')

@section('title', 'Nova inscrição')

@php
    $primary = $site['primary_color'] ?? $site['primary'] ?? '#0f172a';
    $accent  = $site['accent_color']  ?? $site['accent']  ?? '#111827';

    // Convenções esperadas do controller:
    // $modalidadesDisponiveis ou $modalidades  -> lista de modalidades da vaga/concurso (opcional)
    // $formasPagamento                         -> formas de pagamento configuradas (opcional)
    // $temIsencao / $tiposIsencao              -> se há algum tipo de isenção no concurso (opcional)

    $modalidadesLista = $modalidadesDisponiveis ?? $modalidades ?? [];
    $formasPgLista    = $formasPagamento ?? [];
    $tiposIsencao     = $tiposIsencao ?? [];
    $temIsencao       = $temIsencao ?? (!empty($tiposIsencao));
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
    .c-checkbox-row input[type="checkbox"]{
        margin-top:2px;
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
                    Selecione o concurso, o cargo desejado, a cidade de prova e informe,
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
                        <select name="concurso_id" id="concurso_id" class="c-select" required>
                            <option value="">Selecione...</option>
                            @foreach($concursos as $conc)
                                <option value="{{ $conc->id }}" {{ old('concurso_id') == $conc->id ? 'selected' : '' }}>
                                    {{ $conc->titulo ?? $conc->nome ?? ('Concurso #'.$conc->id) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="c-help">
                            Apenas concursos com inscrições online e período vigente são exibidos.
                        </div>
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

                    {{-- Localidade (Cidade de prova) --}}
                    <div class="c-field">
                        <label class="c-label" for="item_id">Cidade / local de prova (quando houver)</label>
                        <select name="item_id" id="item_id" class="c-select">
                            <option value="">Selecione o cargo...</option>
                        </select>
                        <div class="c-help">
                            Quando o concurso tiver cidades de prova, elas aparecerão aqui.
                        </div>
                        @error('item_id')
                        <div class="c-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Modalidade (dinâmica a partir da configuração do concurso / vaga) --}}
                    <div class="c-field">
                        <label class="c-label" for="modalidade">Modalidade de concorrência</label>

                        @if(!empty($modalidadesLista) && count($modalidadesLista))
                            <select name="modalidade" id="modalidade" class="c-select">
                                <option value="">Selecione...</option>
                                @foreach($modalidadesLista as $mod)
                                    @php
                                        // Tenta descobrir valor e rótulo com nomes genéricos
                                        $value = $mod->codigo
                                            ?? $mod->slug
                                            ?? $mod->id
                                            ?? ($mod['codigo'] ?? $mod['slug'] ?? $mod['id'] ?? null);

                                        $label = $mod->nome
                                            ?? $mod->descricao
                                            ?? ($mod['nome'] ?? $mod['descricao'] ?? $value);
                                    @endphp
                                    @if($value)
                                        <option value="{{ $value }}" {{ old('modalidade') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="c-help">
                                As modalidades acima foram configuradas para este concurso/cargo.
                                Se tiver dúvidas, consulte o edital.
                            </div>
                        @else
                            {{-- Sem modalidades configuradas: padrão ampla concorrência --}}
                            <input type="hidden" name="modalidade" value="{{ old('modalidade', 'ampla') }}">
                            <div class="c-help">
                                Este concurso não possui modalidades diferenciadas configuradas.
                                A inscrição será considerada em <strong>ampla concorrência</strong>.
                            </div>
                        @endif

                        @error('modalidade')
                        <div class="c-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Condições especiais --}}
                    <div class="c-field">
                        <label class="c-label" for="condicoes_especiais">Condições especiais</label>
                        <textarea
                            name="condicoes_especiais"
                            id="condicoes_especiais"
                            class="c-textarea"
                            placeholder="Descreva se precisa de atendimento especial, recursos de acessibilidade ou outras condições previstas em edital."
                        >{{ old('condicoes_especiais') }}</textarea>
                        @error('condicoes_especiais')
                        <div class="c-error">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Isenção (só aparece se o concurso tiver tipo de isenção configurado) --}}
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

{{-- Scripts para carregar cargos e localidades via AJAX --}}
@push('scripts')
<script>
    (function(){
        const selectConcurso   = document.getElementById('concurso_id');
        const selectCargo      = document.getElementById('cargo_id');
        const selectItem       = document.getElementById('item_id');

        function clearSelect(select, placeholder){
            select.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = placeholder;
            select.appendChild(opt);
        }

        // Quando troca o concurso, carrega cargos
        selectConcurso.addEventListener('change', function(){
            const concursoId = this.value;
            clearSelect(selectCargo, 'Carregando cargos...');
            clearSelect(selectItem, 'Selecione o cargo...');

            if(!concursoId){
                clearSelect(selectCargo, 'Selecione primeiro o concurso...');
                return;
            }

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
        });

        // Quando troca o cargo, carrega localidades (itens)
        selectCargo.addEventListener('change', function(){
            const concursoId = selectConcurso.value;
            const cargoId    = this.value;
            clearSelect(selectItem, 'Carregando localidades...');

            if(!concursoId || !cargoId){
                clearSelect(selectItem, 'Selecione o cargo...');
                return;
            }

            fetch("{{ url('candidato/inscricoes/localidades') }}/" + concursoId + "/" + cargoId)
                .then(resp => resp.json())
                .then(data => {
                    clearSelect(selectItem, 'Selecione (opcional)...');
                    if(!data || !data.length){
                        selectItem.options[0].textContent = 'Não há cidades específicas para este cargo';
                        return;
                    }
                    data.forEach(i => {
                        const opt = document.createElement('option');
                        opt.value = i.item_id;
                        opt.textContent = i.localidade_nome || ('Item #' + i.item_id);
                        selectItem.appendChild(opt);
                    });
                })
                .catch(() => {
                    clearSelect(selectItem, 'Erro ao carregar localidades');
                });
        });
    })();
</script>
@endpush

@endsection
