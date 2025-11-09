<?php

namespace App\Http\Controllers\Candidato;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Verified;
use App\Models\Candidato;
use App\Notifications\CandidatoVerifyEmail;

class EmailVerificationController extends Controller
{
    /**
     * Tela pedindo para o candidato verificar o e-mail.
     */
    public function notice(Request $request)
    {
        // ATENÇÃO: caminho da view ajustado para "site.candidato.auth.verify"
        return view('site.candidato.auth.verify');
    }

    /**
     * Reenvio do e-mail de verificação.
     */
    public function send(Request $request)
    {
        $user = Auth::guard('candidato')->user();

        if (!$user) {
            return redirect()->route('candidato.login');
        }

        if (!$user->email) {
            return back()->withErrors([
                'email' => 'Este candidato não possui e-mail cadastrado.'
            ]);
        }

        if ($user->email_verified_at) {
            return back()->with('status', 'E-mail já verificado.');
        }

        try {
            $user->notify(new CandidatoVerifyEmail());
        } catch (\Throwable $e) {
            return back()->withErrors([
                'email' => 'Não foi possível enviar o e-mail de verificação. Tente novamente mais tarde.'
            ]);
        }

        return back()->with('status', 'Link de verificação enviado para seu e-mail.');
    }

    /**
     * Confirma o e-mail a partir do link enviado.
     */
    public function verify(Request $request, $id, $hash)
    {
        /** @var \App\Models\Candidato|null $user */
        $user = Candidato::find($id);

        if (!$user) {
            return redirect()
                ->route('candidato.login')
                ->withErrors(['email' => 'Link de verificação inválido ou expirado.']);
        }

        // Confere se o hash corresponde ao e-mail do candidato
        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            return redirect()
                ->route('candidato.login')
                ->withErrors(['email' => 'Link de verificação inválido ou expirado.']);
        }

        // Em teoria o middleware "signed" já valida, mas deixei aqui como proteção extra
        if (! $request->hasValidSignature()) {
            return redirect()
                ->route('candidato.login')
                ->withErrors(['email' => 'Link de verificação inválido ou expirado.']);
        }

        // Marca como verificado se ainda não foi
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
            event(new Verified($user));
        }

        // Faz login automático no guard "candidato"
        Auth::guard('candidato')->login($user);

        return redirect()
            ->route('candidato.home')
            ->with('status', 'E-mail verificado com sucesso!');
    }
}
