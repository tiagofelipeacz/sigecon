<?php

namespace App\Http\Controllers\Admin\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NiveisEscolaridadeController extends Controller
{
    public function index(Request $req)
    {
        $q = trim($req->get('q',''));
        $niveis = DB::table('niveis_escolaridade')
            ->when($q, fn($qb)=>$qb->where('nome','like',"%{$q}%"))
            ->orderBy('ordem')->orderBy('nome')->get();

        return view('admin.config.niveis-escolaridade.index', compact('niveis','q'));
    }

    public function create()
    {
        return view('admin.config.niveis-escolaridade.create');
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'nome'  => ['required','string','max:120'],
            'ordem' => ['nullable','integer','min:0'],
            'ativo' => ['nullable','integer','in:0,1'],
        ]);
        DB::table('niveis_escolaridade')->insert($data + [
            'ativo' => (int)($data['ativo'] ?? 1),
            'created_at'=>now(),'updated_at'=>now()
        ]);
        return redirect()->route('admin.config.niveis-escolaridade.index')->with('ok','Nível criado.');
    }

    public function edit($nivel)
    {
        $nivel = DB::table('niveis_escolaridade')->find($nivel);
        abort_if(!$nivel, 404);
        return view('admin.config.niveis-escolaridade.edit', compact('nivel'));
    }

    public function update(Request $req, $nivel)
    {
        $data = $req->validate([
            'nome'  => ['required','string','max:120'],
            'ordem' => ['nullable','integer','min:0'],
            'ativo' => ['nullable','integer','in:0,1'],
        ]);
        DB::table('niveis_escolaridade')->where('id',$nivel)->update($data + ['updated_at'=>now()]);
        return back()->with('ok','Salvo.');
    }

    public function destroy($nivel)
    {
        DB::table('niveis_escolaridade')->where('id',$nivel)->delete();
        return back()->with('ok','Excluído.');
    }

    public function toggleAtivo($nivel)
    {
        $row = DB::table('niveis_escolaridade')->select('ativo')->find($nivel);
        abort_if(!$row, 404);
        DB::table('niveis_escolaridade')->where('id',$nivel)->update([
            'ativo' => (int)!$row->ativo,
            'updated_at'=>now(),
        ]);
        return back();
    }
}
