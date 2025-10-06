<?php

// app/Http/Controllers/Admin/Concursos/InscritosImportController.php
namespace App\Http\Controllers\Admin\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use Illuminate\Http\Request;

class InscritosImportController extends Controller
{
    public function show(Concurso $concurso)
    {
        return view('admin.concursos.inscritos.import', ['concurso'=>$concurso]);
    }

    public function handle(Concurso $concurso, Request $req)
    {
        $req->validate(['arquivo'=>'required|file|mimes:csv,txt,xlsx']);
        // TODO: processar o arquivo
        return back()->with('ok','Arquivo recebido. Processamento em breve.');
    }
}
sucesso.');
    }
}
