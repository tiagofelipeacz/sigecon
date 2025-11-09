{{-- resources/views/site/candidato/auth/login.blade.php --}}
@extends('layouts.site')

@section('title', 'Área do Candidato')

@php
    use Illuminate\Support\Arr;

    // Cores vindas da config do site (fallback se não tiver)
    $primary = $site['primary_color'] ?? $site['primary'] ?? '#0f172a';
    $accent  = $site['accent_color']  ?? $site['accent']  ?? '#111827';
    $brand   = $site['brand'] ?? 'GestaoConcursos';

    // Se houver erro de senha, já começamos na etapa de senha
    $hasPasswordError = $errors->has('password');
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
        max-width: 980px;
        width: 100%;
        margin: 0 auto;
        display:grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
        gap: 32px;
        align-items:stretch;
    }

    .auth-hero{
        padding: 20px 8px 20px 0;
    }
    .auth-kicker{
        text-transform:uppercase;
        font-size:11px;
        letter-spacing:.12em;
        font-weight:700;
        color: var(--c-accent);
        margin-bottom:6px;
    }
    .auth-title{
        font-size: 26px;
        line-height: 1.25;
        margin: 0 0 10px;
        letter-spacing:-.03em;
        color:#0f172a;
    }
    .auth-subtitle{
        color: var(--c-muted);
        font-size:14px;
        max-width: 420px;
        margin-bottom: 18px;
    }
    .auth-list{
        margin: 10px 0 0;
        padding: 0;
        list-style:none;
        font-size:14px;
        color:#111827;
    }
    .auth-list li{
        display:flex;
        align-items:flex-start;
        gap:8px;
        margin-bottom:8px;
    }
    .auth-list .dot{
        margin-top:5px;
        width:6px;
        height:6px;
        border-radius:999px;
        background: var(--c-accent);
        flex-shrink:0;
    }

    .auth-card{
        background:#ffffff;
        border-radius:18px;
        border:1px solid var(--c-border);
        box-shadow:0 18px 40px rgba(15,23,42,0.08);
        padding:20px 20px 22px;
    }
    .auth-card-header{
        margin-bottom:10px;
    }
    .auth-card-title{
        font-size:18px;
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

    .auth-form-group{
        margin-bottom:12px;
    }
    .auth-label{
        display:block;
        font-size:13px;
        font-weight:600;
        color:#374151;
        margin-bottom:4px;
    }
    .auth-input{
        width:100%;
        border-radius:10px;
        border:1px solid var(--c-border);
        padding:9px 11px;
        font-size:14px;
        outline:none;
        transition:border-color .15s, box-shadow .15s, background-color .15s;
        background-color:#f9fafb;
    }
    .auth-input:focus{
        border-color: var(--c-primary);
        box-shadow:0 0 0 1px color-mix(in srgb, var(--c-primary) 75%, transparent);
        background-color:#ffffff;
    }

    .auth-check{
        display:flex;
        align-items:center;
        gap:6px;
        font-size:13px;
        color:#374151;
        margin-bottom:8px;
    }
    .auth-check input[type="checkbox"]{
        width:14px;
        height:14px;
        border-radius:4px;
    }

    .auth-error{
        font-size:12px;
        color:#b91c1c;
        margin-top:3px;
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

    .auth-btn-primary{
        width:100%;
        border:none;
        border-radius:999px;
        padding:9px 14px;
        font-size:14px;
        font-weight:700;
        background: var(--c-primary);
        color:#fff;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:6px;
        transition: filter .15s, transform .05s, box-shadow .15s;
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

    .auth-footer-links{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        margin-top:12px;
        font-size:13px;
    }
    .auth-link{
        color: var(--c-accent);
        text-decoration:none;
        font-weight:600;
    }
    .auth-link:hover{
        text-decoration:underline;
    }

    .auth-small{
        font-size:11px;
        color: var(--c-muted);
        margin-top:10px;
    }

    /* estados escondidos */
    .auth-hidden{
        display:none;
    }

    @media (max-width: 840px){
        .auth-page{
            padding-top:20px;
        }
        .auth-container{
            grid-template-columns: 1fr;
            gap: 18px;
        }
        .auth-hero{
            padding:0 0 4px;
        }
    }
</style>

<div class="auth-page">
    <div class="auth-container">
        {{-- Lado esquerdo: texto de boas-vindas / branding --}}
        <div class="auth-hero">
            <div class="auth-kicker">Área do Candidato</div>
            <h1 class="auth-title">
                Acesse suas inscrições e acompanhe o concurso.
            </h1>
            <p class="auth-subtitle">
                Informe o seu CPF. Se já existir cadastro, vamos pedir sua senha.
                Caso ainda não tenha, abriremos a tela de cadastro para você.
            </p>

            <ul class="auth-list">
                <li>
                    <span class="dot"></span>
                    <span>Inscreva-se on-line nos concursos disponíveis.</span>
                </li>
                <li>
                    <span class="dot"></span>
                    <span>Acompanhe publicações, cronograma e resultados.</span>
                </li>
                <li>
                    <span class="dot"></span>
                    <span>Atualize seus dados cadastrais quando necessário.</span>
                </li>
            </ul>
        </div>

        {{-- Lado direito: card de login "2 etapas" --}}
        <div class="auth-card">
            <div class="auth-card-header">
                <h2 class="auth-card-title">Identifique-se pelo CPF</h2>
                <p class="auth-card-sub">
                    Primeiro informe o CPF. Vamos verificar se você já possui cadastro.
                </p>
            </div>

            @if(session('status'))
                <div class="auth-status">
                    {{ session('status') }}
                </div>
            @endif

            <form id="candidato-login-form" method="POST" action="{{ route('candidato.login.post') }}">
                @csrf

                {{-- CPF --}}
                <div class="auth-form-group">
                    <label for="cpf" class="auth-label">CPF</label>
                    <input
                        type="text"
                        id="cpf"
                        name="cpf"
                        class="auth-input"
                        value="{{ old('cpf') }}"
                        placeholder="Somente números"
                        required
                    >
                    {{-- erro do back-end --}}
                    @error('cpf')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                    {{-- erro do JS / checagem --}}
                    <div id="cpf-error-js" class="auth-error" style="display:none;"></div>
                </div>

                {{-- Grupo da senha (escondido até confirmar CPF, exceto se houve erro de senha) --}}
                <div id="password-group" class="auth-form-group {{ $hasPasswordError ? '' : 'auth-hidden' }}">
                    <label for="password" class="auth-label">
                        Senha
                        <span id="cpf-ok-label" style="font-weight:400; font-size:12px; color:var(--c-muted);"></span>
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="auth-input"
                    >
                    @error('password')
                        <div class="auth-error">{{ $message }}</div>
                    @enderror
                </div>

                {{-- "Manter conectado" (escondido até mostrar senha ou erro de senha) --}}
                <div id="remember-row" class="auth-check {{ $hasPasswordError ? '' : 'auth-hidden' }}">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember">Manter conectado neste dispositivo</label>
                </div>

                <button type="submit" id="login-submit" class="auth-btn-primary">
                    {{ $hasPasswordError ? 'Entrar' : 'Continuar' }}
                </button>

                <div class="auth-footer-links">
                    <span id="link-nao-tenho" class="auth-small">
                        Ainda não sei se tenho cadastro? Digite o CPF e clique em "Continuar".
                    </span>

                    <a href="{{ route('candidato.password.request') }}" class="auth-link">
                        Esqueci minha senha
                    </a>
                </div>

                <div class="auth-small">
                    Em caso de dúvidas sobre o acesso, entre em contato com a banca organizadora.
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form           = document.getElementById('candidato-login-form');
    const cpfInput       = document.getElementById('cpf');
    const cpfErrorJs     = document.getElementById('cpf-error-js');
    const passwordGroup  = document.getElementById('password-group');
    const rememberRow    = document.getElementById('remember-row');
    const submitBtn      = document.getElementById('login-submit');
    const cpfOkLabel     = document.getElementById('cpf-ok-label');

    const checkCpfUrl    = "{{ route('candidato.login.checkCpf') }}";

    // Se houve erro de senha no back-end, já começamos na etapa de senha
    let etapaSenha = {{ $hasPasswordError ? 'true' : 'false' }};

    function setCpfError(message) {
        if (!message) {
            cpfErrorJs.style.display = 'none';
            cpfErrorJs.textContent = '';
        } else {
            cpfErrorJs.style.display = 'block';
            cpfErrorJs.textContent = message;
        }
    }

    function showSenha(nome) {
        etapaSenha = true;
        passwordGroup.classList.remove('auth-hidden');
        rememberRow.classList.remove('auth-hidden');
        submitBtn.textContent = 'Entrar';
        cpfOkLabel.textContent = nome ? (' - ' + nome) : ' - CPF localizado. Digite sua senha.';
        setCpfError('');
        const pwd = document.getElementById('password');
        if (pwd) pwd.focus();
    }

    // Se voltou da validação com erro de senha, já mostra a etapa de senha e foca o campo
    if (etapaSenha) {
        passwordGroup.classList.remove('auth-hidden');
        rememberRow.classList.remove('auth-hidden');
        submitBtn.textContent = 'Entrar';
        if (cpfOkLabel && !cpfOkLabel.textContent) {
            cpfOkLabel.textContent = ' - CPF localizado. Digite sua senha.';
        }
        const pwd = document.getElementById('password');
        if (pwd) pwd.focus();
    }

    form.addEventListener('submit', function (e) {
        // Se ainda não estamos na etapa da senha, primeiro checa CPF via AJAX
        if (!etapaSenha) {
            e.preventDefault();

            const cpf = cpfInput.value || '';

            setCpfError('');

            fetch(checkCpfUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ cpf: cpf })
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.ok && data.valid === false) {
                    setCpfError(data.message || 'CPF inválido.');
                    passwordGroup.classList.add('auth-hidden');
                    rememberRow.classList.add('auth-hidden');
                    etapaSenha = false;
                    return;
                }

                if (data.valid && data.exists) {
                    // CPF encontrado: mostra campo de senha na mesma tela
                    showSenha(data.name || '');
                    return;
                }

                if (data.valid && !data.exists && data.redirect) {
                    // CPF válido e não existe: redireciona para o cadastro
                    window.location.href = data.redirect;
                    return;
                }

                // fallback
                setCpfError('Não foi possível verificar o CPF. Tente novamente.');
            })
            .catch(function () {
                setCpfError('Não foi possível verificar o CPF. Tente novamente.');
            });

            return;
        }

        // Se já está na etapa senha, deixa o submit seguir para o login normal
    });
});
</script>
@endsection
