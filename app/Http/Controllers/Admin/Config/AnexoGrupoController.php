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
        $data['ativo'] = (int)$req->boolean('ativo');

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
        $data['ativo'] = (int)$req->boolean('ativo');

        // Protege renomeação se houver usos
        if ($data['nome'] !== $grupo->nome) {
            $usos = $this->countUsosByNome($grupo->nome);
            if ($usos > 0) {
                return back()
                    ->withErrors("Não é possível renomear o grupo enquanto houver {$usos} anexo(s) usando o nome atual.")
                    ->withInput();
            }
        }

        $grupo->update($data);

        return redirect()
            ->route('admin.config.grupos-anexos.index')
            ->with('status', 'Grupo atualizado com sucesso!');
    }

    public function destroy(AnexoGrupo $grupo)
    {
        $usos = $this->countUsosByNome($grupo->nome);
        if ($usos > 0) {
            return back()
                ->withErrors("Não é possível remover: há {$usos} anexo(s) usando este grupo.")
                ->withInput();
        }

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

    /**
     * Reordenar:
     * - Se vierem "ids[]" e "ordens[]" do front, fazemos uma PERMUTA local:
     *   aplicamos as ordens antigas do bloco na nova ordem visual.
     * - Senão, usamos fallback com "start" (offset 1-based da página).
     * Também normalizamos (uma vez) quando todos têm ordem 0/null.
     */
    public function reorder(Request $req)
    {
        $data = $req->validate([
            'ids'       => ['required', 'array', 'min:1'],
            'ids.*'     => ['integer'],
            'ordens'    => ['nullable', 'array'],
            'ordens.*'  => ['integer'],
            'start'     => ['nullable', 'integer', 'min:1'],
            '_return'   => ['nullable', 'string'],
        ]);

        $ids    = array_values(array_unique(array_map('intval', $data['ids'])));
        $ordens = isset($data['ordens']) ? array_map('intval', $data['ordens']) : null;

        // Normalização: 1ª vez que todos estão 0/null -> ordem = id
        $total    = (int) DB::table('anexo_grupos')->count();
        $semOrdem = (int) DB::table('anexo_grupos')
            ->where(function ($q) {
                $q->whereNull('ordem')->orWhere('ordem', 0);
            })->count();

        if ($total > 0 && $semOrdem === $total) {
            DB::transaction(function () {
                $todos = DB::table('anexo_grupos')->orderBy('id')->pluck('id');
                foreach ($todos as $i => $id) {
                    DB::table('anexo_grupos')->where('id', $id)->update([
                        'ordem'      => $i + 1,
                        'updated_at' => now(),
                    ]);
                }
            });
        }

        if (is_array($ordens) && count($ordens) === count($ids)) {
            // PERMUTA LOCAL: ordena as ordens originais e as aplica na nova ordem visual
            sort($ordens, SORT_NUMERIC);

            DB::transaction(function () use ($ids, $ordens) {
                foreach ($ids as $i => $id) {
                    DB::table('anexo_grupos')
                        ->where('id', $id)
                        ->update([
                            'ordem'      => $ordens[$i],
                            'updated_at' => now(),
                        ]);
                }
            });
        } else {
            // Fallback com "start" (para quando não vierem ordens[])
            $start = max(1, (int)($data['start'] ?? 1));
            $base  = $start - 1;

            DB::transaction(function () use ($ids, $base) {
                foreach ($ids as $i => $id) {
                    DB::table('anexo_grupos')
                        ->where('id', $id)
                        ->update([
                            'ordem'      => $base + $i + 1,
                            'updated_at' => now(),
                        ]);
                }
            });
        }

        $return = $req->input('_return') ?: route('admin.config.grupos-anexos.index');

        return redirect()->to($return)->with('status', 'Ordem atualizada com sucesso.');
    }

    private function countUsosByNome(string $nome): int
    {
        if (!Schema::hasTable('concursos_anexos') || !Schema::hasColumn('concursos_anexos', 'grupo')) {
            return 0;
        }

        return (int) DB::table('concursos_anexos')
            ->whereNotNull('grupo')
            ->whereRaw("TRIM(grupo) <> ''")
            ->where('grupo', $nome)
            ->count();
    }
}
