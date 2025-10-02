<?php

namespace App\Http\Controllers\Candidato;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class PerfilController extends Controller
{
    public function edit()
    {
        $user = Auth::guard('candidato')->user();
        return view('candidato.perfil.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::guard('candidato')->user();

        $data = $request->validate([
            'nome' => ['required','string','max:255'],
            'email'=> ['nullable','email','max:150'],
            'telefone' => ['nullable','string','max:50'],
            'celular'  => ['nullable','string','max:50'],
            'data_nascimento' => ['nullable','date'],
            'estado_civil' => ['nullable','in:solteiro,casado,separado,divorciado,viuvo,outro'],
            'sexo' => ['nullable','in:M,F,O'],
            'cidade' => ['nullable','string','max:255'],
            'estado' => ['nullable','string','max:2'],
            'foto'   => ['nullable','image','mimes:jpeg,png,jpg,gif','max:4096'],
        ]);

        if ($request->hasFile('foto')) {
            if ($user->foto_path) {
                Storage::disk('public')->delete($user->foto_path);
            }
            $data['foto_path'] = $request->file('foto')->store('candidatos', 'public');
        }

        $user->update($data);

        return back()->with('success', 'Perfil atualizado com sucesso.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::guard('candidato')->user();

        $data = $request->validate([
            'password_current' => ['required','string'],
            'password' => ['required','string','min:6','confirmed'],
        ]);

        if (!Hash::check($data['password_current'], $user->password)) {
            return back()->withErrors(['password_current' => 'Senha atual não confere.']);
        }

        $user->password = bcrypt($data['password']);
        $user->save();

        return back()->with('success', 'Senha alterada com sucesso.');
    }
}
