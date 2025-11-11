<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Para onde redirecionar quando o usuário NÃO está autenticado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {

            // 👉 URLs da área do candidato: começam com /candidato...
            if ($request->is('candidato') || $request->is('candidato/*')) {
                // usa a rota nomeada do login do candidato (routes/web.php)
                return route('candidato.login');
            }

            // 👉 URLs da área administrativa: /admin...
            if ($request->is('admin') || $request->is('admin/*')) {
                // login padrão de admin
                return route('login');
            }

            // 👉 Qualquer outra coisa cai no login padrão (admin)
            return route('login');
        }
    }
}
