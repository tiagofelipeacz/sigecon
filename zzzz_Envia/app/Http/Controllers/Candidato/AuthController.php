<?php

namespace App\Http\Controllers\Candidato;

use App\Http\Controllers\Controller;
use App\Models\Candidato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('candidato.auth.login');
    }

    public function login(Request $request)
    {
        // Validação usando CPF (não "username")
        $data = $request->validate(
            [
                'cpf'      => ['required'],
                'password' => ['required'],
                'remember' => ['nullable'],
            ],
            [
                'cpf.required'      => 'Informe seu CPF.',
                'password.required' => 'Informe sua senha.',
            ]
        );

        // Somente dígitos do CPF
        $cpfDigits = preg_replace('/\D+/', '', $data['cpf'] ?? '');

        // Busca sanitizando o CPF salvo (caso esteja com máscara no BD)
        $user = Candidato::query()
            ->whereRaw("REPLACE(REPLACE(REPLACE(cpf,'.',''),'-',''),'/','') = ?", [$cpfDigits])
            ->first();

        if (!$user) {
            return back()
                ->withErrors(['cpf' => 'CPF não encontrado.'])
                ->withInput(['cpf' => $request->input('cpf')]);
        }

        if (isset($user->status) && !$user->status) {
            return back()
                ->withErrors(['cpf' => 'Seu acesso está inativo.'])
                ->withInput(['cpf' => $request->input('cpf')]);
        }

        if (!Hash::check($data['password'], $user->password)) {
            return back()
                ->withErrors(['password' => 'Senha inválida.'])
                ->withInput(['cpf' => $request->input('cpf')]);
        }

        // Autentica no guard "candidato"
        Auth::guard('candidato')->login($user, (bool)$request->boolean('remember', false));

        // Atualiza último login (se existir a coluna)
        if ($user->isFillable('last_login_at') || \Schema::hasColumn($user->getTable(), 'last_login_at')) {
            $user->forceFill(['last_login_at' => now()])->save();
        }

        return redirect()->intended(route('candidato.home'));
    }

    public function logout(Request $request)
    {
        Auth::guard('candidato')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('candidato.login')->with('status', 'Você saiu da sua conta.');
    }

    /* ============================
     * ADIÇÕES: Cadastro de candidato
     * ============================ */

    public function showRegisterForm()
    {
        // Apenas exibe a view do formulário de cadastro
        return view('candidato.auth.register');
    }

    public function register(Request $request)
    {
        // Normaliza CPF (apenas dígitos)
        $cpf = preg_replace('/\D+/', '', (string) $request->input('cpf'));

        $validated = $request->validate(
            [
                'nome'     => ['required', 'string', 'max:120'],
                'email'    => ['required', 'email', 'max:160', 'unique:candidatos,email'],
                'cpf'      => ['required', 'digits:11', 'unique:candidatos,cpf'],
                'telefone' => ['nullable', 'string', 'max:20'],
                'celular'  => ['nullable', 'string', 'max:20'],
                'password' => ['required', 'string', 'min:6', 'max:100', 'confirmed'],
            ],
            [
                'cpf.digits'         => 'Informe um CPF válido com 11 dígitos.',
                'cpf.unique'         => 'Este CPF já está cadastrado.',
                'email.unique'       => 'Este e-mail já está cadastrado.',
                'password.confirmed' => 'A confirmação da senha não confere.',
            ]
        );

        $validated['cpf'] = $cpf;

        // Monta dados conforme seu modelo Candidato
        $data = [
            'nome'     => $validated['nome'],
            'email'    => $validated['email'],
            'cpf'      => $validated['cpf'],
            'telefone' => $validated['telefone'] ?? null,
            'celular'  => $validated['celular'] ?? null,
            'password' => Hash::make($validated['password']),
            'status'   => true,
            // Muitos sistemas usam CPF como login
            'login'    => $validated['cpf'],
        ];

        /** @var \App\Models\Candidato $candidato */
        $candidato = Candidato::create($data);

        // Login automático no guard candidato
        Auth::guard('candidato')->login($candidato);

        // Se o projeto usa verificação de e-mail e a notificação existe
        if (method_exists($candidato, 'sendEmailVerificationNotification')) {
            try { $candidato->sendEmailVerificationNotification(); } catch (\Throwable $e) {}
            return redirect()->route('candidato.verification.notice')
                ->with('status', 'Conta criada! Enviamos um e-mail para verificação.');
        }

        // Caso não use verificação, vai direto para a home
        return redirect()->intended(route('candidato.home'))
            ->with('status', 'Conta criada com sucesso!');
    }
}
