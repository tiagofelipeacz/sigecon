<?php

namespace App\Http\Controllers\Candidato;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        // Usa o mesmo layout "site" das telas de login/cadastro
        return view('site.candidato.home');
    }
}
