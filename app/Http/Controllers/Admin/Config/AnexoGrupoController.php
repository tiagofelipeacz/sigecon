<?php

namespace App\Http\Controllers\Admin\Config;

use App\Http\Controllers\Controller;
use App\Models\AnexoGrupo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnexoGrupoController extends Controller
{
    public function index(Request $req)
    {
        $q     = trim((string)$req->get('q', ''));
        $ativo = (string)$req->get('ativo', ''); // '', '1', '0'

        // Subconsulta para contar uso em concursos_anexos.grupo (se existir)
        $usoSub = null;
        if (Schema::hasTable('concursos_anexos') && Schema::hasColumn('concursos_anexos', 'grupo')) {
            $usoSub = DB::table('concursos_anexos')
                ->select('grupo', DB::raw('COUNT(*) as qt'))
                ->whereNotNull('grupo')
                ->whereRaw("TRIM(grupo) <> ''")
                ->groupBy('grupo');
        }

        $qry = AnexoGrupo::query()
            ->when($q !== '', fn($w) => $w->where('nome', 'like', "%{$q}%"))
            ->when($ativo !== '' && in_array($ativo, ['0','1'], true), fn($w) => $w->where('ativo', (int)$ativo))
            ->orderBy('ordem')->orderBy('nome');

        // Junta a contagem
        if ($usoSub) {
            $qry->leftJoinSub($usoSub, 'u', 'u.grupo', '=', 'anexo_grupos.nome')
                ->addSelect('anexo_grupos.*', DB::raw('COALESCE(u.qt,0) as usos'));
        } else {
            $qry->addSelect('anexo_grupos.*', DB::raw('0 as usos'));
        }

        $rows = $qry->paginate(20)->withQueryString();

        return view('admin.config.grupos-anexos.index', [
            'rows'   => $rows,
            'q'      => $q,
            'ativo'  => $ativo,
        ]);
    }

    public function create()
    {
        $grupo = new AnexoGrupo(['ativo' => 1, 'ordem' => 0]);

        return view('admin.config.grupos-anexos.form', [
            'grupo'  => $grupo,
            'isEdit' => false,
        ]);
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'nome'  => ['required', 'string', 'max:190', 'unique:anexo_grupos,nome'],
            'ordem' => ['nullable', 'integer', 'min:0'],
            'ativo' => ['nullable', 'in:0,1'],
        ]);

        $data['ordem'] = (int)($data['ordem'] ?? 0);
        $data['ativo'] = (int)($data['ativo'] ?? 1);

        AnexoGrupo::create($data);

        return redirect()
            ->route('admin.config.grupos-anexos.index')
            ->with('status', 'Grupo criado com sucesso!');
    }

    public function edit(AnexoGrupo $grupo)
    {
        return view('admin.config.grupos-anexos.form', [
            'grupo'  => $grupo,
            'isEdit' => true,
        ]);
    }

    public function update(Request $req, AnexoGrupo $grupo)
    {
        $data = $req->validate([
            'nome'  => ['required', 'string', 'max:190', 'unique:anexo_grupos,nome,' . $grupo->id],
            'ordem' => ['nullable', 'integer', 'min:0'],
            'ativo' => ['nullable', 'in:0,1'],
        ]);

        $data['ordem'] = (int)($data['ordem'] ?? 0);
        $data['ativo'] = (int)($data['ativo'] ?? 1);

        $grupo->update($data);

        return redirect()
            ->route('admin.config.grupos-anexos.index')
            ->with('status', 'Grupo atualizado com sucesso!');
    }

    public function destroy(AnexoGrupo $grupo)
    {
        $grupo->delete();

        return redirect()
            ->route('admin.config.grupos-anexos.index')
            ->with('status', 'Grupo removido com sucesso!');
    }

    public function toggle(AnexoGrupo $grupo)
    {
        $grupo->ativo = (int)!$grupo->ativo;
        $grupo->save();

        return redirect()
            ->route('admin.config.grupos-anexos.index')
            ->with('status', 'Status do grupo atualizado.');
    }
}
