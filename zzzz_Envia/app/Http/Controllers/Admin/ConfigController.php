<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ConfigController extends Controller
{
    public function index()
    {
        // página de atalho para todos os cadastros auxiliares
        return view('admin.config.index');
    }
}