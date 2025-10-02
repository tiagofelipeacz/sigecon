<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $title = 'Gestão de Concursos';
        return view('welcome', compact('title'));
    }
}
