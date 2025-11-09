{{-- resources/views/site/candidato/auth/register.blade.php --}}
@extends('layouts.site')

@section('title', 'Cadastro do Candidato')

@php
    // Cores vindas da config do site (fallback se não tiver)
    $primary = $site['primary_color'] ?? $site['primary'] ?? '#0f172a';
    $accent  = $site['accent_color']  ?? $site['accent']  ?? '#111827';
    $brand   = $site['brand'] ?? 'GestaoConcursos';

    // CPF pré-preenchido vindo da checagem (rota /candidato/registrar?cpf=...)
    $cpfPrefill = isset($prefillCpf) ? preg_replace('/\D+/', '', (string) $prefillCpf) : null;
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

    .auth-page{
        min-height: calc(100vh - 140px);
        padding: 32px 16px 40px;
        background: radial-gradient(circle at top left, #ffffff 0, #eef2ff 35%, #f9fafb 100%);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:15px;
    }
    .auth-container{
        width: 100%;
        max-width: 960px;
        margin: 0 auto;
        display:flex;
        justify-content:center;
    }

    .auth-card{
        background:#ffffff;
        border-radius:18px;
        border:1px solid var(--c-border);
        box-shadow:0 18px 40px rgba(15,23,42,0.08);
        padding:20px 22px 24px;
        width:100%;
    }
    .auth-card-header{
        margin-bottom:12px;
    }
    .auth-card-title{
        font-size:20px;
        font-weight:800;
        margin:0 0 4px;
        letter-spacing:-.02em;
        color:#0f172a;
    }
    .auth-card-sub{
        font-size:13px;
        color:var(--c-muted);
        margin:0;
    }

    .auth-section-title{
        font-size:14px;
        font-weight:700;
        color:#111827;
        margin:16px 0 6px;
        letter-spacing:.03em;
        text-transform:uppercase;
    }

    .auth-grid{
        display:grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap:12px 18px;
    }
    .auth-grid-3{
        display:grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap:12px 18px;
    }
    .auth-grid-4{
        display:grid;
        grid-template-columns: repeat(4, minmax(0,1fr));
        gap:12px 18px;
    }
    .auth-grid-1{
        display:grid;
        grid-template-columns: minmax(0,1fr);
        gap:12px;
    }

    .auth-form-group{
        margin-bottom:2px;
    }
    .auth-label{
        display:block;
        font-size:13px;
        font-weight:600;
        color:#374151;
        margin-bottom:4px;
    }
    .auth-label span.req{
        color:#b91c1c;
        margin-left:2px;
    }
    .auth-input,
    .auth-select{
        width:100%;
        border-radius:10px;
        border:1px solid var(--c-border);
        padding:8px 10px;
        font-size:14px;
        outline:none;
        background-color:#f9fafb;
        transition:border-color .15s, box-shadow .15s, background-color .15s;
    }
    .auth-input:focus,
    .auth-select:focus{
        border-color: var(--c-primary);
        box-shadow:0 0 0 1px color-mix(in srgb, var(--c-primary) 75%, transparent);
        background-color:#ffffff;
    }

    .auth-error{
        font-size:12px;
        color:#b91c1c;
        margin-top:2px;
    }

    .auth-status{
        font-size:13px;
        padding:8px 10px;
        border-radius:10px;
        background:#ecfdf3;
        color:#166534;
        border:1px solid #bbf7d0;
        margin-bottom:10px;
    }

    .auth-btn-row{
        margin-top:18px;
        display:flex;
        justify-content:flex-end;
        gap:10px;
        flex-wrap:wrap;
    }
    .auth-btn{
        border-radius:999px;
        padding:8px 18px;
        font-size:14px;
        font-weight:700;
        border:1px solid transparent;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:6px;
        transition: filter .15s, transform .05s, box-shadow .15s, background-color .15s, color .15s, border-color .15s;
    }
    .auth-btn-primary{
        background: var(--c-primary);
        color:#fff;
        box-shadow:0 10px 25px rgba(15,23,42,0.25);
    }
    .auth-btn-primary:hover{
        filter:brightness(1.05);
        transform:translateY(-1px);
        box-shadow:0 14px 28px rgba(15,23,42,0.27);
    }
    .auth-btn-primary:active{
        transform:translateY(0);
        box-shadow:0 8px 18px rgba(15,23,42,0.20);
    }
    .auth-btn-secondary{
        background:#f9fafb;
        color:#374151;
        border-color:var(--c-border);
    }
    .auth-btn-secondary:hover{
        background:#e5e7eb;
    }

    .auth-note{
        font-size:11px;
        color:var(--c-muted);
        margin-top:4px;
    }

    @media (max-width: 840px){
        .auth-page{
            padding-top:20px;
        }
        .auth-card{
            padding:16px 14px 20px;
        }
        .auth-grid,
        .auth-grid-3,
        .auth-grid-4{
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-card-header">
                <h2 class="auth-card-title">Cadastro do Candidato</h2>
                <p class="auth-card-sub">
                    Preencha seus dados para criar o acesso à área do candidato.
                </p>
            </div>

            @if(session('status'))
                <div class="auth-status">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('candidato.register.post') }}">
                @csrf

                {{-- DADOS PESSOAIS --}}
                <div class="auth-section-title">Dados Pessoais</div>

                <div class="auth-grid">
                    <div class="auth-form-group">
                        <label class="auth-label" for="nome">
                            Nome Completo <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            class="auth-input"
                            value="{{ old('nome') }}"
                            required
                        >
                        @error('nome')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="cpf">
                            CPF <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="cpf"
                            name="cpf"
                            class="auth-input"
                            value="{{ old('cpf', $cpfPrefill) }}"
                            placeholder="Somente números"
                            {{ $cpfPrefill ? 'readonly' : '' }}
                            required
                        >
                        @error('cpf')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="auth-grid">
                    <div class="auth-form-group">
                        <label class="auth-label" for="data_nascimento">
                            Data de Nascimento <span class="req">*</span>
                        </label>
                        <input
                            type="date"
                            id="data_nascimento"
                            name="data_nascimento"
                            class="auth-input"
                            value="{{ old('data_nascimento') }}"
                            required
                        >
                        @error('data_nascimento')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="sexo">
                            Gênero <span class="req">*</span>
                        </label>
                        <select id="sexo" name="sexo" class="auth-select" required>
                            <option value="">Selecione</option>
                            <option value="M" {{ old('sexo') === 'M' ? 'selected' : '' }}>Masculino</option>
                            <option value="F" {{ old('sexo') === 'F' ? 'selected' : '' }}>Feminino</option>
                            <option value="O" {{ old('sexo') === 'O' ? 'selected' : '' }}>Outro</option>
                        </select>
                        @error('sexo')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- ESTADO CIVIL --}}
                <div class="auth-grid">
                    <div class="auth-form-group">
                        <label class="auth-label" for="estado_civil">
                            Estado Civil <span class="req">*</span>
                        </label>
                        <select id="estado_civil" name="estado_civil" class="auth-select" required>
                            <option value="">Selecione</option>
                            <option value="Solteiro(a)"   {{ old('estado_civil') === 'Solteiro(a)'   ? 'selected' : '' }}>Solteiro(a)</option>
                            <option value="Casado(a)"     {{ old('estado_civil') === 'Casado(a)'     ? 'selected' : '' }}>Casado(a)</option>
                            <option value="Divorciado(a)" {{ old('estado_civil') === 'Divorciado(a)' ? 'selected' : '' }}>Divorciado(a)</option>
                            <option value="Viúvo(a)"      {{ old('estado_civil') === 'Viúvo(a)'      ? 'selected' : '' }}>Viúvo(a)</option>
                            <option value="União estável" {{ old('estado_civil') === 'União estável' ? 'selected' : '' }}>União estável</option>
                        </select>
                        @error('estado_civil')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="auth-grid-1">
                    <div class="auth-form-group">
                        <label class="auth-label" for="email">
                            E-mail <span class="req">*</span>
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="auth-input"
                            value="{{ old('email') }}"
                            required
                        >
                        @error('email')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- DOCUMENTO --}}
                <div class="auth-section-title">Documento</div>

                <div class="auth-grid-3">
                    <div class="auth-form-group">
                        <label class="auth-label" for="doc_tipo">
                            Tipo Documento <span class="req">*</span>
                        </label>
                        <select id="doc_tipo" name="doc_tipo" class="auth-select" required>
                            <option value="">Selecione</option>
                            <option value="RG"         {{ old('doc_tipo') === 'RG'         ? 'selected' : '' }}>RG</option>
                            <option value="CNH"        {{ old('doc_tipo') === 'CNH'        ? 'selected' : '' }}>CNH</option>
                            <option value="PASSAPORTE" {{ old('doc_tipo') === 'PASSAPORTE' ? 'selected' : '' }}>Passaporte</option>
                        </select>
                        @error('doc_tipo')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="doc_numero">
                            Número <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="doc_numero"
                            name="doc_numero"
                            class="auth-input"
                            value="{{ old('doc_numero') }}"
                            required
                        >
                        @error('doc_numero')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="doc_orgao">
                            Órgão <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="doc_orgao"
                            name="doc_orgao"
                            class="auth-input"
                            value="{{ old('doc_orgao') }}"
                            required
                        >
                        @error('doc_orgao')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="auth-grid-4">
                    <div class="auth-form-group">
                        <label class="auth-label" for="doc_uf">
                            UF <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="doc_uf"
                            name="doc_uf"
                            class="auth-input"
                            value="{{ old('doc_uf') }}"
                            placeholder="UF"
                            maxlength="2"
                            required
                        >
                        @error('doc_uf')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- DADOS ADICIONAIS --}}
                <div class="auth-section-title">Dados Adicionais</div>

                <div class="auth-grid">
                    <div class="auth-form-group">
                        <label class="auth-label" for="escolaridade">
                            Escolaridade <span class="req">*</span>
                        </label>
                        <select id="escolaridade" name="escolaridade" class="auth-select" required>
                            <option value="">Selecione</option>
                            <option value="Fundamental incompleto" {{ old('escolaridade') === 'Fundamental incompleto' ? 'selected' : '' }}>Fundamental incompleto</option>
                            <option value="Fundamental completo"  {{ old('escolaridade') === 'Fundamental completo'  ? 'selected' : '' }}>Fundamental completo</option>
                            <option value="Médio incompleto"       {{ old('escolaridade') === 'Médio incompleto'       ? 'selected' : '' }}>Médio incompleto</option>
                            <option value="Médio completo"         {{ old('escolaridade') === 'Médio completo'         ? 'selected' : '' }}>Médio completo</option>
                            <option value="Superior incompleto"    {{ old('escolaridade') === 'Superior incompleto'    ? 'selected' : '' }}>Superior incompleto</option>
                            <option value="Superior completo"      {{ old('escolaridade') === 'Superior completo'      ? 'selected' : '' }}>Superior completo</option>
                            <option value="Pós-graduação"          {{ old('escolaridade') === 'Pós-graduação'          ? 'selected' : '' }}>Pós-graduação</option>
                            <option value="Mestrado"               {{ old('escolaridade') === 'Mestrado'               ? 'selected' : '' }}>Mestrado</option>
                            <option value="Doutorado"              {{ old('escolaridade') === 'Doutorado'              ? 'selected' : '' }}>Doutorado</option>
                            <option value="Pós-doutorado"          {{ old('escolaridade') === 'Pós-doutorado'          ? 'selected' : '' }}>Pós-doutorado</option>
                        </select>
                        @error('escolaridade')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="nome_mae">
                            Nome da Mãe <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="nome_mae"
                            name="nome_mae"
                            class="auth-input"
                            value="{{ old('nome_mae') }}"
                            required
                        >
                        @error('nome_mae')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="auth-grid">
                    <div class="auth-form-group">
                        <label class="auth-label" for="nacionalidade">
                            Nacionalidade
                        </label>
                        <input
                            type="text"
                            id="nacionalidade"
                            name="nacionalidade"
                            class="auth-input"
                            value="{{ old('nacionalidade', 'Brasil') }}"
                        >
                        @error('nacionalidade')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="naturalidade_cidade">
                            Naturalidade - Cidade
                        </label>
                        <input
                            type="text"
                            id="naturalidade_cidade"
                            name="naturalidade_cidade"
                            class="auth-input"
                            value="{{ old('naturalidade_cidade') }}"
                        >
                        @error('naturalidade_cidade')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="auth-grid-4">
                    <div class="auth-form-group">
                        <label class="auth-label" for="naturalidade_uf">
                            Naturalidade - UF
                        </label>
                        <input
                            type="text"
                            id="naturalidade_uf"
                            name="naturalidade_uf"
                            class="auth-input"
                            value="{{ old('naturalidade_uf') }}"
                            maxlength="2"
                        >
                        @error('naturalidade_uf')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- ENDEREÇO E CONTATO --}}
                <div class="auth-section-title">Endereço e Contato</div>

                <div class="auth-grid">
                    {{-- CEP (usando campos do model: endereco_cep, etc) --}}
                    <div class="auth-form-group">
                        <label class="auth-label" for="endereco_cep">
                            CEP <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="endereco_cep"
                            name="endereco_cep"
                            class="auth-input"
                            value="{{ old('endereco_cep') }}"
                            placeholder="Somente números"
                            required
                        >
                        @error('endereco_cep')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                        <div id="cep-error-js" class="auth-error" style="display:none;"></div>
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="endereco_rua">
                            Endereço <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="endereco_rua"
                            name="endereco_rua"
                            class="auth-input"
                            value="{{ old('endereco_rua') }}"
                            required
                        >
                        @error('endereco_rua')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="auth-grid">
                    <div class="auth-form-group">
                        <label class="auth-label" for="endereco_numero">
                            Número <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="endereco_numero"
                            name="endereco_numero"
                            class="auth-input"
                            value="{{ old('endereco_numero') }}"
                            required
                        >
                        @error('endereco_numero')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="endereco_complemento">
                            Complemento
                        </label>
                        <input
                            type="text"
                            id="endereco_complemento"
                            name="endereco_complemento"
                            class="auth-input"
                            value="{{ old('endereco_complemento') }}"
                        >
                        @error('endereco_complemento')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="auth-grid">
                    <div class="auth-form-group">
                        <label class="auth-label" for="endereco_bairro">
                            Bairro <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="endereco_bairro"
                            name="endereco_bairro"
                            class="auth-input"
                            value="{{ old('endereco_bairro') }}"
                            required
                        >
                        @error('endereco_bairro')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="estado">
                            Estado (UF) <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="estado"
                            name="estado"
                            class="auth-input"
                            value="{{ old('estado') }}"
                            maxlength="2"
                            required
                        >
                        @error('estado')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="auth-grid">
                    <div class="auth-form-group">
                        <label class="auth-label" for="cidade">
                            Cidade <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="cidade"
                            name="cidade"
                            class="auth-input"
                            value="{{ old('cidade') }}"
                            required
                        >
                        @error('cidade')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="telefone">
                            Telefone
                        </label>
                        <input
                            type="text"
                            id="telefone"
                            name="telefone"
                            class="auth-input"
                            value="{{ old('telefone') }}"
                        >
                        @error('telefone')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="auth-grid-1">
                    <div class="auth-form-group">
                        <label class="auth-label" for="celular">
                            Celular <span class="req">*</span>
                        </label>
                        <input
                            type="text"
                            id="celular"
                            name="celular"
                            class="auth-input"
                            value="{{ old('celular') }}"
                            required
                        >
                        @error('celular')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- SENHA DE ACESSO --}}
                <div class="auth-section-title">Senha de Acesso</div>

                <div class="auth-grid">
                    <div class="auth-form-group">
                        <label class="auth-label" for="password">
                            Senha <span class="req">*</span>
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="auth-input"
                            required
                        >
                        @error('password')
                            <div class="auth-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-form-group">
                        <label class="auth-label" for="password_confirmation">
                            Repita a Senha <span class="req">*</span>
                        </label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="auth-input"
                            required
                        >
                    </div>
                </div>

                <div class="auth-note">
                    ATENÇÃO: Sua senha deve conter no mínimo 8 caracteres, incluindo uma letra maiúscula,
                    uma letra minúscula, um número e um dos seguintes caracteres especiais:
                    @$!%*#?&amp;&lt;+&gt;,.;
                </div>

                <div class="auth-btn-row">
                    <a href="{{ route('candidato.login') }}" class="auth-btn auth-btn-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="auth-btn auth-btn-primary">
                        Continuar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cepInput      = document.getElementById('endereco_cep');
    const ruaInput      = document.getElementById('endereco_rua');
    const bairroInput   = document.getElementById('endereco_bairro');
    const cidadeInput   = document.getElementById('cidade');
    const estadoInput   = document.getElementById('estado');
    const cepErrorJs    = document.getElementById('cep-error-js');

    if (!cepInput) return;

    function setCepError(message) {
        if (!cepErrorJs) return;
        if (!message) {
            cepErrorJs.style.display = 'none';
            cepErrorJs.textContent = '';
        } else {
            cepErrorJs.style.display = 'block';
            cepErrorJs.textContent = message;
        }
    }

    function clearAddressFields() {
        if (ruaInput)    ruaInput.value    = '';
        if (bairroInput) bairroInput.value = '';
        if (cidadeInput) cidadeInput.value = '';
        if (estadoInput) estadoInput.value = '';
    }

    cepInput.addEventListener('blur', function () {
        let cep = (cepInput.value || '').replace(/\D/g, '');

        setCepError('');

        if (cep.length === 0) {
            clearAddressFields();
            return;
        }

        if (cep.length !== 8) {
            setCepError('Informe um CEP válido com 8 dígitos.');
            clearAddressFields();
            return;
        }

        fetch('https://viacep.com.br/ws/' + cep + '/json/')
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.erro) {
                    setCepError('CEP não encontrado.');
                    clearAddressFields();
                    return;
                }

                // Preenche campos se estiverem vazios
                if (ruaInput && (!ruaInput.value || ruaInput.value.trim() === '')) {
                    ruaInput.value = data.logradouro || '';
                }
                if (bairroInput && (!bairroInput.value || bairroInput.value.trim() === '')) {
                    bairroInput.value = data.bairro || '';
                }
                if (cidadeInput && (!cidadeInput.value || cidadeInput.value.trim() === '')) {
                    cidadeInput.value = data.localidade || '';
                }
                if (estadoInput && (!estadoInput.value || estadoInput.value.trim() === '')) {
                    estadoInput.value = data.uf || '';
                }
            })
            .catch(function () {
                setCepError('Não foi possível consultar o CEP. Tente novamente.');
            });
    });
});
</script>
@endsection
