<?php

namespace App\Http\Controllers\Candidato;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('candidato.home');
    }
}