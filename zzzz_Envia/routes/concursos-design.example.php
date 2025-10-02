<?php
// Cole UMA das opções abaixo no seu routes/web.php (ou simplesmente 'require' este arquivo).

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ConcursosDesignController;

// (a) Rota dedicada fora de grupos
Route::get('/admin/concursos-design', [ConcursosDesignController::class, 'index'])
    ->middleware(['web'])
    ->name('admin.concursos.design');

// (b) Opcional — dentro de um grupo com prefixo 'admin', se você usa:
/*
Route::prefix('admin')->middleware(['web'])->group(function () {
    Route::get('/concursos-design', [ConcursosDesignController::class, 'index'])
        ->name('admin.concursos.design');
});
*/
