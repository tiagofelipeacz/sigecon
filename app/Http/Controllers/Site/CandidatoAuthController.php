<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Candidato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;

class CandidatoAuthController extends Controller
{
    public function showLogin()
    {
        return view('site.candidato.auth.login');
    }

    public function login(Request $r)
    {
        $data = $r->validate([
            'login'    => ['required', 'string'], // pode ser CPF ou e-mail
            'password' => ['required', 'string'],
        ]);

        $login    = trim($data['login']);
        $password = $data['password'];

        // Decide se é e-mail ou CPF
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $cred = ['email' => $login, 'password' => $password];
        } else {
            $cpf  = preg_replace('/\D+/', '', $login);
            $cred = ['cpf' => $cpf, 'password' => $password];
        }

        if (Auth::guard('candidato')->attempt($cred, $r->boolean('remember'))) {
            $r->session()->regenerate();
            return redirect()->intended(route('candidato.dashboard'));
        }

        return back()
            ->withErrors(['login' => 'Login ou senha inválidos.'])
            ->onlyInput('login');
    }

    public function showRegister()
    {
        return view('site.candidato.auth.register');
    }

    public function register(Request $r)
    {
        $data = $r->validate([
            'nome'            => ['required','string','max:255'],
            'email'           => ['required','string','email','max:255','unique:candidatos,email'],
            'cpf'             => ['required','string','max:14','unique:candidatos,cpf'],
            'data_nascimento' => ['required','date'],
            'telefone'        => ['nullable','string','max:20'],
            'password'        => ['required','confirmed', Password::min(8)],
        ]);

        $data['password'] = Hash::make($data['password']);

        $candidato = Candidato::create($data);

        Auth::guard('candidato')->login($candidato);

        return redirect()->route('candidato.dashboard');
    }

    public function logout(Request $r)
    {
        Auth::guard('candidato')->logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('candidato.login');
    }

    /**
     * FORM de recuperação de senha:
     * CPF + data de nascimento + e-mail + nova senha
     */
    public function showRecover()
    {
        return view('site.candidato.auth.recover');
    }

    public function recover(Request $r)
    {
        $data = $r->validate([
            'cpf'             => ['required','string','max:14'],
            'data_nascimento' => ['required','date'],
            'email'           => ['required','email'],
            'password'        => ['required','confirmed', Password::min(8)],
        ]);

        $cpf   = preg_replace('/\D+/', '', $data['cpf']);
        $dataN = Carbon::parse($data['data_nascimento'])->format('Y-m-d');
        $email = $data['email'];

        $candidato = Candidato::where('cpf', $cpf)
            ->whereDate('data_nascimento', $dataN)
            ->where('email', $email)
            ->first();

        if (!$candidato) {
            return back()
                ->withErrors(['cpf' => 'Dados não conferem com nenhum cadastro.'])
                ->withInput($r->except('password','password_confirmation'));
        }

        $candidato->password = Hash::make($data['password']);
        $candidato->save();

        return redirect()
            ->route('candidato.login')
            ->with('status', 'Senha atualizada com sucesso! Faça login novamente.');
    }
}
