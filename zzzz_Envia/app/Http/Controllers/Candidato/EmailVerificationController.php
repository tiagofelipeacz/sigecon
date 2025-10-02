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
    public function notice(Request $request)
    {
        return view('candidato.auth.verify');
    }

    public function send(Request $request)
    {
        $user = Auth::guard('candidato')->user();
        if (!$user) {
            return redirect()->route('candidato.login');
        }
        if (!$user->email) {
            return back()->withErrors(['email' => 'Este candidato não possui e-mail cadastrado.']);
        }
        if ($user->email_verified_at) {
            return back()->with('status', 'E-mail já verificado.');
        }

        $user->notify(new CandidatoVerifyEmail());
        return back()->with('status', 'Link de verificação enviado para seu e-mail.');
    }

    public function verify(Request $request, $id, $hash)
    {
        $user = Candidato::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Assinatura inválida.');
        }

        if (! $request->hasValidSignature()) {
            abort(403, 'Link inválido ou expirado.');
        }

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
            event(new Verified($user));
        }

        return redirect()->route('candidato.home')->with('status', 'E-mail verificado com sucesso!');
    }
}