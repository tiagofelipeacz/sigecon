<?php

namespace App\Http\Controllers\Candidato;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\CandidatoDocumento;

class DocumentoController extends Controller
{
    public function index()
    {
        $user = Auth::guard('candidato')->user();
        $docs = CandidatoDocumento::where('candidato_id', $user->id)->orderByDesc('id')->get();
        return view('candidato.documentos.index', compact('docs'));
    }

    public function create()
    {
        return view('candidato.documentos.create');
    }

    public function store(Request $request)
    {
        $user = Auth::guard('candidato')->user();
        $data = $request->validate([
            'tipo' => ['required','string','max:80'],
            'numero' => ['nullable','string','max:100'],
            'validade' => ['nullable','date'],
            'arquivo' => ['required','file','max:8192'],
        ]);

        $path = $request->file('arquivo')->store('candidatos/'.$user->id.'/docs', 'public');

        CandidatoDocumento::create([
            'candidato_id' => $user->id,
            'tipo' => $data['tipo'],
            'numero' => $data['numero'] ?? null,
            'validade' => $data['validade'] ?? null,
            'arquivo_path' => $path,
            'status' => 'pendente',
        ]);

        return redirect()->route('candidato.documentos.index')->with('success', 'Documento enviado e aguardando análise.');
    }

    public function destroy(CandidatoDocumento $documento)
    {
        $user = Auth::guard('candidato')->user();
        abort_unless($documento->candidato_id === $user->id, 403);

        Storage::disk('public')->delete($documento->arquivo_path);
        $documento->delete();

        return back()->with('success', 'Documento removido.');
    }
}
