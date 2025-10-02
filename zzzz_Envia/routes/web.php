<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Controllers (público)
 */
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Site\ConcursoController as PublicConcursoController;

/**
 * Controllers (admin)
 */
use App\Http\Controllers\Admin\HomeController as AdminHomeController;   // <<< import correto do dashboard raiz
use App\Http\Controllers\Admin\InicioController;                        // página "Início" (lista estilizada)
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\ConcursoController;
use App\Http\Controllers\Admin\ImpugnacaoController;
use App\Http\Controllers\Admin\Concursos\IsencoesController;            // Inscrições -> Pedidos de Isenção
use App\Http\Controllers\Admin\Concursos\VagaController;                // Vagas
use App\Http\Controllers\Admin\Concursos\VisaoGeralController;          // Visão Geral do Concurso
use App\Http\Controllers\Admin\ConcursoAnexoController;                 // Anexos
use App\Http\Controllers\Admin\CandidatoController;                     // Base de Candidatos

/**
 * Controllers (admin -> configurações)
 */
use App\Http\Controllers\Admin\Config\PedidosIsencaoController;
use App\Http\Controllers\Admin\Config\TipoIsencaoController;
use App\Http\Controllers\Admin\Config\TipoCondicaoEspecialController;
use App\Http\Controllers\Admin\Config\NiveisEscolaridadeController;
use App\Http\Controllers\Admin\Config\TiposVagasEspeciaisController;

/**
 * Controllers (área do candidato)
 */
use App\Http\Controllers\Candidato\AuthController as CandidatoAuthController;
use App\Http\Controllers\Candidato\HomeController as CandidatoHomeController;
use App\Http\Controllers\Candidato\PasswordResetController as CandidatoPasswordResetController;
use App\Http\Controllers\Candidato\EmailVerificationController as CandidatoEmailVerificationController;
use App\Http\Controllers\Candidato\PerfilController as CandidatoPerfilController;
use App\Http\Controllers\Candidato\DocumentoController as CandidatoDocumentoController;
use App\Http\Controllers\Candidato\InscricaoController as CandidatoInscricaoController;

// --------------------------------------------------------------------------
// Web Routes
// --------------------------------------------------------------------------

// Grupo mínimo para Condições Especiais já existente
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::prefix('config')->name('config.')->group(function () {
        // ===== Tipos de Condições Especiais =====
        Route::get('condicoes-especiais', [TipoCondicaoEspecialController::class, 'index'])
            ->name('condicoes_especiais.index');
        Route::get('condicoes-especiais/criar', [TipoCondicaoEspecialController::class, 'create'])
            ->name('condicoes_especiais.create');
        Route::post('condicoes-especiais', [TipoCondicaoEspecialController::class, 'store'])
            ->name('condicoes_especiais.store');
        Route::get('condicoes-especiais/{tipo}/editar', [TipoCondicaoEspecialController::class, 'edit'])
            ->name('condicoes_especiais.edit');
        Route::put('condicoes-especiais/{tipo}', [TipoCondicaoEspecialController::class, 'update'])
            ->name('condicoes_especiais.update');
        Route::delete('condicoes-especiais/{tipo}', [TipoCondicaoEspecialController::class, 'destroy'])
            ->name('condicoes_especiais.destroy');
        Route::patch('condicoes-especiais/{tipo}/toggle-ativo', [TipoCondicaoEspecialController::class, 'toggleAtivo'])
            ->name('condicoes_especiais.toggle-ativo');
        // ===== /Tipos de Condições Especiais =====
    });
});

Route::resourceVerbs([
    'create' => 'criar',
    'edit'   => 'editar',
]);

// -------------------------
// Site (público)
// -------------------------
Route::redirect('/', '/concursos')->name('site.home');
Route::get('/concursos', [PublicConcursoController::class, 'index'])->name('site.concursos.index');
Route::get('/concursos/{concurso}', [PublicConcursoController::class, 'show'])->name('site.concursos.show');

// --------------------------------------------------------------------------
// DEV ONLY: servir /storage/* via Laravel quando o symlink public/storage
// não existe ou o servidor não segue o link (Windows/XAMPP).
// Em produção, prefira `php artisan storage:link`.
// --------------------------------------------------------------------------
if (!is_link(public_path('storage')) || !file_exists(public_path('storage'))) {
    Route::get('/storage/{path}', function (string $path) {
        $disk = Storage::disk('public');
        abort_if(!$disk->exists($path), 404);
        return $disk->response($path);
    })->where('path', '.*');
}

// -------------------------
// Autenticação (Admin)
// Mantém a view `resources/views/auth/login.blade.php` que criamos
// (o layout já oculta o topo nas rotas de auth).
// -------------------------
if (class_exists(LoginController::class)) {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');   // deve renderizar 'auth.login'
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
} else {
    Route::view('/login', 'auth.login')->name('login'); // fallback mantendo nossa view
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required'],
        ]);
        $remember = (bool) $request->boolean('remember', false);
        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.home'));
        }
        return back()->withErrors(['email' => 'Credenciais inválidas.']);
    })->name('login.post');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');
}

// -------------------------
// Admin (autenticado)
// -------------------------
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth'])
    ->group(function () {

        // Dashboard (raiz do admin) -> sempre envia ao "Início"
        Route::get('/', function () {
            return redirect()->route('admin.inicio');
        })->name('home');

        // NOVO: Página "Início" (lista no layout novo)
        Route::get('/inicio', [InicioController::class, 'index'])->name('inicio');

        // Clientes
        Route::resource('clientes', ClientController::class)
            ->parameters(['clientes' => 'clientes'])
            ->names('clientes');

        // Concursos (CRUD)
        Route::resource('concursos', ConcursoController::class)
            ->parameters(['concursos' => 'concurso'])
            ->names('concursos');

        // Config do concurso
        Route::get('concursos/{concurso}/config', [ConcursoController::class, 'config'])
            ->name('concursos.config');
        Route::put('concursos/{concurso}/config', [ConcursoController::class, 'updateConfig'])
            ->name('concursos.updateConfig');

        // Toggle "Ativo" na listagem de concursos
        Route::patch('concursos/{concurso}/toggle-ativo', [ConcursoController::class, 'toggleAtivo'])
            ->name('concursos.toggleAtivo');

        // ==============================
        // Rotas por concurso
        // ==============================
        Route::prefix('concursos/{concurso}')
            ->name('concursos.')
            ->group(function () {

                // VISÃO GERAL (DASHBOARD DO CONCURSO)
                Route::get('visao-geral', [VisaoGeralController::class, 'index'])
                    ->name('visao-geral');

                // Impugnações
                Route::get('impugnacoes', [ImpugnacaoController::class, 'index'])
                    ->name('impugnacoes.index');
                Route::get('impugnacoes/{impugnacao}/editar', [ImpugnacaoController::class, 'edit'])
                    ->name('impugnacoes.edit');
                Route::put('impugnacoes/{impugnacao}', [ImpugnacaoController::class, 'update'])
                    ->name('impugnacoes.update');

                // Inscrições -> Isenções
                Route::get('isencoes', [IsencoesController::class, 'index'])
                    ->name('isencoes.index');
                Route::get('isencoes/{pedido}/editar', [IsencoesController::class, 'edit'])
                    ->name('isencoes.edit');
                Route::put('isencoes/{pedido}', [IsencoesController::class, 'update'])
                    ->name('isencoes.update');
                Route::get('isencoes/{pedido}/arquivo', [IsencoesController::class, 'downloadArquivo'])
                    ->name('isencoes.arquivo.download');
                Route::delete('isencoes/{pedido}/arquivo', [IsencoesController::class, 'destroyArquivo'])
                    ->name('isencoes.arquivo.destroy');

                // ---------------------------------------------
                // Vagas (menu lateral)
                // ---------------------------------------------
                Route::get('vagas', [VagaController::class, 'index'])
                    ->name('vagas.index');

                // Tela separada de criação (form)
                Route::get('vagas/criar', [VagaController::class, 'create'])
                    ->name('vagas.create');

                // Salvar nova vaga (POST do form)
                Route::post('vagas', [VagaController::class, 'store'])
                    ->name('vagas.store');

                // Cargos (CRUD básico)
                Route::post('vagas/cargos', [VagaController::class, 'storeCargo'])
                    ->name('vagas.cargos.store');
                Route::delete('vagas/cargos/{cargo}', [VagaController::class, 'destroyCargo'])
                    ->name('vagas.cargos.destroy');

                // Localidades (inclusão)
                Route::post('vagas/localidades', [VagaController::class, 'storeLocalidade'])
                    ->name('vagas.localidades.store');

                // Itens (Localidade + quantidade por cargo)
                Route::post('vagas/itens', [VagaController::class, 'storeItem'])
                    ->name('vagas.itens.store');
                Route::put('vagas/itens/{item}', [VagaController::class, 'updateItem'])
                    ->name('vagas.itens.update');
                Route::delete('vagas/itens/{item}', [VagaController::class, 'destroyItem'])
                    ->name('vagas.itens.destroy');

                // ---------------------------------------------
                // ANEXOS
                // ---------------------------------------------
                Route::get('anexos', [ConcursoAnexoController::class, 'index'])
                    ->name('anexos.index');
                Route::get('anexos/criar', [ConcursoAnexoController::class, 'create'])
                    ->name('anexos.create');
                Route::post('anexos', [ConcursoAnexoController::class, 'store'])
                    ->name('anexos.store');
                Route::get('anexos/{anexo}/editar', [ConcursoAnexoController::class, 'edit'])
                    ->name('anexos.edit');
                Route::put('anexos/{anexo}', [ConcursoAnexoController::class, 'update'])
                    ->name('anexos.update');
                Route::delete('anexos/{anexo}', [ConcursoAnexoController::class, 'destroy'])
                    ->name('anexos.destroy');
            });

        // ==============================
        // Menu "Configurações"
        // ==============================
        Route::prefix('config')->name('config.')->group(function () {
            Route::get('/', function () {
                return view('admin.config.blank');
            })->name('index');

            // Pedidos de Isenção (listagem + CRUD básico)
            Route::get('pedidos-isencao', [PedidosIsencaoController::class, 'index'])
                ->name('pedidos-isencao.index');
            Route::get('pedidos-isencao/criar', [PedidosIsencaoController::class, 'create'])
                ->name('pedidos-isencao.create');
            Route::post('pedidos-isencao', [PedidosIsencaoController::class, 'store'])
                ->name('pedidos-isencao.store');
            Route::get('pedidos-isencao/{tipo}/editar', [PedidosIsencaoController::class, 'edit'])
                ->name('pedidos-isencao.edit');
            Route::put('pedidos-isencao/{tipo}', [PedidosIsencaoController::class, 'update'])
                ->name('pedidos-isencao.update');
            Route::delete('pedidos-isencao/{tipo}', [PedidosIsencaoController::class, 'destroy'])
                ->name('pedidos-isencao.destroy');
            Route::patch('pedidos-isencao/{tipo}/{field}/toggle', [PedidosIsencaoController::class, 'toggle'])
                ->name('pedidos-isencao.toggle');

            // Tipos de Isenção (resource + toggle)
            Route::resource('tipos-isencao', TipoIsencaoController::class)
                ->parameters(['tipos-isencao' => 'tipoIsencao'])
                ->names('tipos-isencao');
            Route::patch('tipos-isencao/{tipoIsencao}/toggle-ativo', [TipoIsencaoController::class, 'toggleAtivo'])
                ->name('tipos-isencao.toggle-ativo');

            // Níveis de Escolaridade (resource + toggle)
            Route::resource('niveis-escolaridade', NiveisEscolaridadeController::class)
                ->parameters(['niveis-escolaridade' => 'nivel'])
                ->names('niveis-escolaridade');
            Route::patch('niveis-escolaridade/{nivel}/toggle-ativo', [NiveisEscolaridadeController::class, 'toggleAtivo'])
                ->name('niveis-escolaridade.toggle-ativo');

            // Tipos de Vagas Especiais
            Route::get('tipos-vagas-especiais', [TiposVagasEspeciaisController::class, 'index'])
                ->name('tipos-vagas-especiais.index');
            Route::get('tipos-vagas-especiais/criar', [TiposVagasEspeciaisController::class, 'create'])
                ->name('tipos-vagas-especiais.create');
            Route::post('tipos-vagas-especiais', [TiposVagasEspeciaisController::class, 'store'])
                ->name('tipos-vagas-especiais.store');
            Route::get('tipos-vagas-especiais/{id}/editar', [TiposVagasEspeciaisController::class, 'edit'])
                ->name('tipos-vagas-especiais.edit');
            Route::put('tipos-vagas-especiais/{id}', [TiposVagasEspeciaisController::class, 'update'])
                ->name('tipos-vagas-especiais.update');
            Route::delete('tipos-vagas-especiais/{id}', [TiposVagasEspeciaisController::class, 'destroy'])
                ->name('tipos-vagas-especiais.destroy');
            Route::patch('tipos-vagas-especiais/{id}/toggle-ativo', [TiposVagasEspeciaisController::class, 'toggleAtivo'])
                ->name('tipos-vagas-especiais.toggle-ativo');
        });

        // ==============================
        // Candidatos (Base)
        // ==============================
        Route::get('candidatos/export', [CandidatoController::class, 'export'])
            ->name('candidatos.export');
        Route::resource('candidatos', CandidatoController::class)
            ->parameters(['candidatos' => 'candidato'])
            ->names('candidatos');
    });

// Redirect de compatibilidade: URL antiga -> nova página "Início"
Route::permanentRedirect('/admin/concursos-design', '/admin/inicio');

// ==============================================
// Área do Candidato (LOGIN + RESET + VERIFICAÇÃO + HOME + PERFIL + DOCS + INSCRIÇÕES)
// ==============================================
Route::prefix('candidato')->name('candidato.')->group(function () {
    // Login/Logout (público)
    Route::get('/login', [CandidatoAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [CandidatoAuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [CandidatoAuthController::class, 'logout'])->name('logout');

    /* ============================
     * Cadastro (público, somente guest:candidato)
     * ============================ */
    Route::middleware('guest:candidato')->group(function () {
        Route::get('/registrar', [CandidatoAuthController::class, 'showRegisterForm'])->name('register');
        Route::post('/registrar', [CandidatoAuthController::class, 'register'])->name('register.post');
    });

    // Esqueci minha senha (público)
    Route::get('/password/reset', [CandidatoPasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/password/email', [CandidatoPasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [CandidatoPasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [CandidatoPasswordResetController::class, 'reset'])->name('password.update');

    // Verificação de e-mail
    Route::get('/email/verify', [CandidatoEmailVerificationController::class, 'notice'])
        ->middleware('auth:candidato')->name('verification.notice');
    Route::post('/email/verification-notification', [CandidatoEmailVerificationController::class, 'send'])
        ->middleware(['auth:candidato','throttle:6,1'])->name('verification.send');
    Route::get('/email/verify/{id}/{hash}', [CandidatoEmailVerificationController::class, 'verify'])
        ->middleware('signed')->name('verification.verify');

    // Home do candidato (protegida)
    Route::get('/', [CandidatoHomeController::class, 'index'])
        ->middleware('auth:candidato')->name('home');

    // Meu Perfil + Documentos + Inscrições (protegidas)
    Route::middleware('auth:candidato')->group(function () {
        // Perfil
        Route::get('/perfil', [CandidatoPerfilController::class, 'edit'])->name('perfil.edit');
        Route::put('/perfil', [CandidatoPerfilController::class, 'update'])->name('perfil.update');
        Route::put('/perfil/senha', [CandidatoPerfilController::class, 'updatePassword'])->name('perfil.password');

        // Documentos
        Route::get('/documentos', [CandidatoDocumentoController::class, 'index'])->name('documentos.index');
        Route::get('/documentos/novo', [CandidatoDocumentoController::class, 'create'])->name('documentos.create');
        Route::post('/documentos', [CandidatoDocumentoController::class, 'store'])->name('documentos.store');
        Route::get('/documentos/{documento}/arquivo', [CandidatoDocumentoController::class, 'open'])->name('documentos.open');
        Route::delete('/documentos/{documento}', [CandidatoDocumentoController::class, 'destroy'])->name('documentos.destroy');

        // Inscrições
        Route::get('/inscricoes', [CandidatoInscricaoController::class, 'index'])->name('inscricoes.index');
        Route::get('/inscricoes/nova', [CandidatoInscricaoController::class, 'create'])->name('inscricoes.create');
        Route::get('/inscricoes/cargos/{concurso}', [CandidatoInscricaoController::class, 'cargos'])->name('inscricoes.cargos');
        Route::get('/inscricoes/localidades/{concurso}/{cargo}', [CandidatoInscricaoController::class, 'localidades'])->name('inscricoes.localidades');
        Route::post('/inscricoes', [CandidatoInscricaoController::class, 'store'])->name('inscricoes.store');
        Route::get('/inscricoes/{id}', [CandidatoInscricaoController::class, 'show'])->name('inscricoes.show');
        Route::get('/inscricoes/{id}/comprovante', [CandidatoInscricaoController::class, 'comprovante'])->name('inscricoes.comprovante');
    });
});
