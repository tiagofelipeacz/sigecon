<?php

namespace App\Http\Controllers\Admin\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NiveisEscolaridadeController extends Controller
{
    public function index(Request $req)
    {
        $q = trim($req->get('q', ''));
        $niveis = DB::table('niveis_escolaridade')
            ->when($q, fn ($qb) => $qb->where('nome', 'like', "%{$q}%"))
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get();

        return view('admin.config.niveis-escolaridade.index', compact('niveis', 'q'));
    }

    public function create()
    {
        return view('admin.config.niveis-escolaridade.create');
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'nome'  => ['required', 'string', 'max:120'],
            'ordem' => ['nullable', 'integer', 'min:0'],
            'ativo' => ['nullable', 'integer', 'in:0,1'],
        ]);

        $id = DB::table('niveis_escolaridade')->insertGetId($data + [
            'ativo'      => (int)($data['ativo'] ?? 1),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($req->input('_after') === 'close') {
            $return = $this->resolveReturnUrl($req);
            return redirect()->to($return)->with('ok', 'Nível criado.');
        }

        return redirect()
            ->route('admin.config.niveis-escolaridade.edit', ['nivel' => $id])
            ->with('ok', 'Nível criado.');
    }

    public function edit($nivel)
    {
        $nivel = DB::table('niveis_escolaridade')->find($nivel);
        abort_if(!$nivel, 404);

        return view('admin.config.niveis-escolaridade.edit', compact('nivel'));
    }

    public function update(Request $req, $nivel)
    {
        $data = $req->validate([
            'nome'  => ['required', 'string', 'max:120'],
            'ordem' => ['nullable', 'integer', 'min:0'],
            'ativo' => ['nullable', 'integer', 'in:0,1'],
        ]);

        DB::table('niveis_escolaridade')
            ->where('id', $nivel)
            ->update($data + ['updated_at' => now()]);

        if ($req->input('_after') === 'close') {
            $return = $this->resolveReturnUrl($req);
            return redirect()->to($return)->with('ok', 'Nível atualizado.');
        }

        return redirect()
            ->route('admin.config.niveis-escolaridade.edit', ['nivel' => $nivel])
            ->with('ok', 'Nível atualizado.');
    }

    public function destroy($nivel)
    {
        DB::table('niveis_escolaridade')->where('id', $nivel)->delete();
        return back()->with('ok', 'Excluído.');
    }

    public function toggleAtivo($nivel)
    {
        $row = DB::table('niveis_escolaridade')->select('ativo')->find($nivel);
        abort_if(!$row, 404);

        DB::table('niveis_escolaridade')
            ->where('id', $nivel)
            ->update([
                'ativo'      => (int)!$row->ativo,
                'updated_at' => now(),
            ]);

        return back();
    }

    /**
     * Resolve uma URL segura para voltar à listagem preservando filtros/paginação.
     */
    private function resolveReturnUrl(Request $req): string
    {
        $fallback = route('admin.config.niveis-escolaridade.index');
        $ret = (string) $req->input('_return', '');

        if ($ret === '') return $fallback;

        $path = parse_url($ret, PHP_URL_PATH) ?: '';
        // Aceita apenas URLs da própria listagem (evita redirecionar para /editar, /create, etc.)
        if (Str::startsWith($path, '/admin/config/niveis-escolaridade')
            && !Str::contains($path, ['/editar', '/create'])) {
            return $ret;
        }

        return $fallback;
    }
}
