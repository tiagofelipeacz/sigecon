<?php

namespace App\Http\Controllers\Candidato;

use App\Http\Controllers\Controller;

class RecursoController extends Controller
{
    public function index()
    {
        // aqui depois você lista os concursos/fases que permitem recurso
        return view('site.candidato.recursos.index');
    }
}
