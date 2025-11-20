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
    /**
     * Tela de login (etapa CPF + etapa senha).
     * Aceita ?redirect=/alguma/url para voltar após login/cadastro.
     */
    public function showLogin(Request $r)
    {
        // URL para onde o candidato deve voltar depois de logar/cadastrar
        $redirect = $r->query('redirect', $r->session()->get('candidato.redirect'));

        if ($redirect) {
            $r->session()->put('candidato.redirect', $redirect);
        }

        return view('site.candidato.auth.login', [
            'redirect' => $redirect,
        ]);
    }

    /**
     * POST do login (senha).
     * Aqui o CPF já foi digitado e validado na etapa anterior.
     * Rota sugerida: candidato.login.post
     */
    public function login(Request $r)
    {
        $data = $r->validate([
            'cpf'      => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $cpf      = preg_replace('/\D+/', '', $data['cpf']);
        $password = $data['password'];

        $remember = $r->boolean('remember');

        if (Auth::guard('candidato')->attempt(['cpf' => $cpf, 'password' => $password], $remember)) {
            $r->session()->regenerate();

            // Tenta pegar a URL de retorno
            $redirect = $r->input('redirect') ?: $r->session()->pull('candidato.redirect');

            if ($redirect) {
                return redirect()->to($redirect);
            }

            return redirect()->route('candidato.dashboard');
        }

        return back()
            ->withErrors(['password' => 'Senha inválida.'])
            ->withInput($r->except('password'));
    }

    /**
     * AJAX: checa CPF enviado na primeira etapa do login.
     * Rota sugerida: POST candidato.login.checkCpf
     *
     * Retornos:
     * - CPF inválido: { ok:false, valid:false, message:'...' }
     * - CPF existe:   { ok:true, valid:true, exists:true, name:'Fulano' }
     * - CPF não existe:
     *      { ok:true, valid:true, exists:false, redirect:'URL do cadastro' }
     */
    public function checkCpf(Request $r)
    {
        $cpf      = preg_replace('/\D+/', '', (string) $r->input('cpf', ''));
        $redirect = $r->input('redirect');

        if ($redirect) {
            $r->session()->put('candidato.redirect', $redirect);
        }

        if ($cpf === '' || strlen($cpf) !== 11) {
            return response()->json([
                'ok'      => false,
                'valid'   => false,
                'exists'  => false,
                'message' => 'Informe um CPF válido com 11 dígitos.',
            ]);
        }

        $candidato = Candidato::where('cpf', $cpf)->first();

        // CPF já existe no banco: volta para a mesma tela, pedindo a senha
        if ($candidato) {
            return response()->json([
                'ok'     => true,
                'valid'  => true,
                'exists' => true,
                'name'   => $candidato->nome,
            ]);
        }

        // CPF é válido, mas ainda não existe: manda para o cadastro
        $params = ['cpf' => $cpf];

        if ($redirect) {
            $params['redirect'] = $redirect;
        } elseif ($sessRedirect = $r->session()->get('candidato.redirect')) {
            $params['redirect'] = $sessRedirect;
        }

        // Ajuste o name da rota para o que você usa no cadastro do candidato
        $registerUrl = route('candidato.register', $params);

        return response()->json([
            'ok'       => true,
            'valid'    => true,
            'exists'   => false,
            'redirect' => $registerUrl,
        ]);
    }

    /**
     * Tela de cadastro do candidato.
     * Aceita ?cpf=... e ?redirect=... para preencher e manter o fluxo.
     */
    public function showRegister(Request $r)
    {
        $redirect = $r->query('redirect', $r->session()->get('candidato.redirect'));
        if ($redirect) {
            $r->session()->put('candidato.redirect', $redirect);
        }

        $cpf = preg_replace('/\D+/', '', (string) $r->query('cpf', ''));

        return view('site.candidato.auth.register', [
            'cpf'      => $cpf,
            'redirect' => $redirect,
        ]);
    }

    /**
     * POST de cadastro.
     * Depois de criar o candidato, já faz login e redireciona.
     */
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

        $data['cpf']      = preg_replace('/\D+/', '', $data['cpf']);
        $data['password'] = Hash::make($data['password']);

        $candidato = Candidato::create($data);

        Auth::guard('candidato')->login($candidato);
        $r->session()->regenerate();

        $redirect = $r->input('redirect') ?: $r->session()->pull('candidato.redirect');

        if ($redirect) {
            return redirect()->to($redirect);
        }

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
    public function showRecover(Request $r)
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

        // Se tiver um redirect guardado (vindo da inscrição), reaproveita
        $redirect = $r->session()->get('candidato.redirect');

        return redirect()
            ->route('candidato.login', $redirect ? ['redirect' => $redirect] : [])
            ->with('status', 'Senha atualizada com sucesso! Faça login novamente.');
    }
}
