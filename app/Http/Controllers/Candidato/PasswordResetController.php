<?php

namespace App\Http\Controllers\Candidato;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Candidato;

class PasswordResetController extends Controller
{
    public function requestForm()
    {
        return view('candidato.auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => ['required','email']]);
        $email = $request->input('email');

        // opcional: verifica se existe candidato ativo com esse e-mail
        $exists = Candidato::where('email', $email)->where('status', 1)->exists();
        if (!$exists) {
            return back()->withInput()->withErrors(['email' => 'Não encontramos um candidato ativo com este e-mail.']);
        }

        $status = Password::broker('candidatos')->sendResetLink(['email' => $email]);

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput()->withErrors(['email' => __($status)]);
    }

    public function showResetForm(Request $request, $token = null)
    {
        return view('candidato.auth.passwords.reset', [
            'token' => $token,
            'email' => $request->query('email')
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required','email'],
            'password' => ['required','confirmed','min:6'],
        ]);

        $status = Password::broker('candidatos')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // Redireciona para login com sucesso
            return redirect()->route('candidato.login')->with('status', __($status));
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}