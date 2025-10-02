<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

class HomeController extends Controller
{
    public function index()
    {
        // Redireciona o /admin/home (ou /home se apontar pra cá) para a lista de concursos
        if (Route::has('admin.concursos.index')) {
            return redirect()->route('admin.concursos.index');
        }

        // Fallback direto (caso a rota nomeada não exista)
        return redirect('/admin/concursos');

        // Se você preferir manter a view quando necessário, comente os redirects acima
        // e descomente as linhas abaixo.
        //
        // $title = 'Admin | Gestão de Concursos';
        // return view('admin.home', compact('title'));
    }
}
