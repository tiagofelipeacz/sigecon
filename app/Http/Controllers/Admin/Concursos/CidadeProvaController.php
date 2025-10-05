<?php

namespace App\Http\Controllers\Admin\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CidadeProvaController extends Controller
{
    /** LISTA */
    public function index(Concurso $concurso, Request $request)
    {
        $q  = trim((string) $request->input('q', ''));
        $uf = (string) $request->input('uf', '');
        if ($uf === 'null') { $uf = ''; }

        $per = 20;
        $tbl = $this->pickCitiesTable();

        $query = DB::table($tbl)->where('concurso_id', $concurso->id);

        if ($q !== '') {
            $query->where(function ($w) use ($q, $tbl) {
                $hasCidade = Schema::hasColumn($tbl, 'cidade');
                $hasNome   = Schema::hasColumn($tbl, 'nome');

                if ($hasCidade && $hasNome) {
                    $w->where('cidade', 'like', "%{$q}%")
                      ->orWhere('nome', 'like', "%{$q}%");
                } elseif ($hasCidade) {
                    $w->where('cidade', 'like', "%{$q}%");
                } elseif ($hasNome) {
                    $w->where('nome', 'like', "%{$q}%");
                }
            });
        }

        if ($uf !== '') {
            foreach (['uf', 'estado', 'sigla_uf'] as $c) {
                if (Schema::hasColumn($tbl, $c)) {
                    $query->where($c, $uf);
                    break;
                }
            }
        }

        $orderCol = Schema::hasColumn($tbl, 'cidade') ? 'cidade'
                   : (Schema::hasColumn($tbl, 'nome') ? 'nome' : 'id');

        if (Schema::hasColumn($tbl, 'uf')) {
            $query->orderBy('uf');
        } elseif (Schema::hasColumn($tbl, 'estado')) {
            $query->orderBy('estado');
        }

        $rows = $query->orderBy($orderCol)
            ->paginate($per)
            ->through(function ($r) use ($concurso) {
                $r = (object) $r;
                $r->cidade = $r->cidade ?? $r->nome ?? null;
                $r->uf     = $r->uf ?? ($r->estado ?? ($r->sigla_uf ?? ''));
                $r->cargos_lista = $this->cargosDaCidadeLista((int) $r->id, (int) $concurso->id);
                return $r;
            });

        $ufs = $this->ufs();

        return view('admin.concursos.cidades.index', compact('concurso','rows','q','uf','ufs'));
    }

    /** FORM CRIAR */
    public function create(Concurso $concurso)
    {
        $cidade       = null;
        $ufs          = $this->ufs();
        $cargos       = $this->loadCargosForConcurso($concurso->id);
        $selecionados = [];

        return view('admin.concursos.cidades.create', compact('concurso','cidade','ufs','cargos','selecionados'));
    }

    /** SALVAR NOVO */
    public function store(Concurso $concurso, Request $request)
    {
        $tbl = $this->pickCitiesTable();

        $request->validate([
            'cidade' => ['required','string','max:255'],
            'uf'     => ['required','string','max:2'],
        ]);

        $data = ['concurso_id' => $concurso->id];

        // cidade/nome
        if (Schema::hasColumn($tbl, 'cidade')) {
            $data['cidade'] = $request->cidade;
        } elseif (Schema::hasColumn($tbl, 'nome')) {
            $data['nome'] = $request->cidade;
        }

        // UF/estado
        if (Schema::hasColumn($tbl, 'uf')) {
            $data['uf'] = strtoupper($request->uf);
        } elseif (Schema::hasColumn($tbl, 'estado')) {
            $data['estado'] = strtoupper($request->uf);
        } elseif (Schema::hasColumn($tbl, 'sigla_uf')) {
            $data['sigla_uf'] = strtoupper($request->uf);
        }

        foreach (['ativo','disponivel'] as $maybe) {
            if (Schema::hasColumn($tbl, $maybe)) {
                $data[$maybe] = 1;
            }
        }

        $cidadeId = DB::table($tbl)->insertGetId($data);

        $cargoIds = (array) $request->input('cargos', []);
        $this->syncPivotCargos($concurso->id, $cidadeId, $cargoIds);

        return redirect()
            ->route('admin.concursos.cidades.index', $concurso)
            ->with('ok', 'Cidade de prova cadastrada com sucesso.');
    }

    /** FORM EDITAR */
    public function edit(Concurso $concurso, $id)
    {
        $tbl = $this->pickCitiesTable();
        $cidade = (array) DB::table($tbl)->where('id', $id)->where('concurso_id',$concurso->id)->first();
        abort_if(empty($cidade), 404);

        $cidade = (object) $cidade;
        $cidade->cidade = $cidade->cidade ?? ($cidade->nome ?? '');
        $cidade->uf     = $cidade->uf ?? ($cidade->estado ?? ($cidade->sigla_uf ?? ''));

        $ufs          = $this->ufs();
        $cargos       = $this->loadCargosForConcurso($concurso->id);
        $selecionados = $this->cidadeCargosIds((int)$id, (int)$concurso->id);

        return view('admin.concursos.cidades.edit', compact('concurso','cidade','ufs','cargos','selecionados'));
    }

    /** ATUALIZAR */
    public function update(Concurso $concurso, Request $request, $id)
    {
        $tbl = $this->pickCitiesTable();

        $request->validate([
            'cidade' => ['required','string','max:255'],
            'uf'     => ['required','string','max:2'],
        ]);

        $data = [];

        if (Schema::hasColumn($tbl, 'cidade')) {
            $data['cidade'] = $request->cidade;
        } elseif (Schema::hasColumn($tbl, 'nome')) {
            $data['nome'] = $request->cidade;
        }

        if (Schema::hasColumn($tbl, 'uf')) {
            $data['uf'] = strtoupper($request->uf);
        } elseif (Schema::hasColumn($tbl, 'estado')) {
            $data['estado'] = strtoupper($request->uf);
        } elseif (Schema::hasColumn($tbl, 'sigla_uf')) {
            $data['sigla_uf'] = strtoupper($request->uf);
        }

        DB::table($tbl)
            ->where('id', $id)
            ->where('concurso_id', $concurso->id)
            ->update($data);

        $cargoIds = (array) $request->input('cargos', []);
        $this->syncPivotCargos($concurso->id, (int)$id, $cargoIds);

        return redirect()
            ->route('admin.concursos.cidades.index', $concurso)
            ->with('ok', 'Cidade de prova atualizada com sucesso.');
    }

    /** REMOVER */
    public function destroy(Concurso $concurso, $id)
    {
        $pivot = $this->pickPivotTable();
        DB::table($pivot)->where('cidade_id', $id)->delete();

        $tbl = $this->pickCitiesTable();
        DB::table($tbl)
            ->where('id', $id)
            ->where('concurso_id', $concurso->id)
            ->delete();

        return back()->with('ok', 'Cidade de prova removida.');
    }

    /* ============================== HELPERS ============================== */

    protected function pickCitiesTable(): string
    {
        foreach ([
            'concursos_cidades',
            'cidades_prova',
            'cidades_de_prova',
            'concursos_cidades_prova',
            'concursos_cidade_prova',
        ] as $t) {
            if (Schema::hasTable($t)) return $t;
        }
        return 'concursos_cidades';
    }

    protected function pickPivotTable(): string
    {
        foreach ([
            'concursos_cidades_cargos',
            'cidades_prova_cargos',
            'concursos_cidade_prova_cargos',
            'concursos_cargos_cidades',
        ] as $t) {
            if (Schema::hasTable($t)) return $t;
        }
        return 'concursos_cidades_cargos';
    }

    /** UFs para select */
    protected function ufs(): array
    {
        return [
            ''=>'UF','AC'=>'AC','AL'=>'AL','AM'=>'AM','AP'=>'AP','BA'=>'BA','CE'=>'CE','DF'=>'DF','ES'=>'ES',
            'GO'=>'GO','MA'=>'MA','MG'=>'MG','MS'=>'MS','MT'=>'MT','PA'=>'PA','PB'=>'PB','PE'=>'PE',
            'PI'=>'PI','PR'=>'PR','RJ'=>'RJ','RN'=>'RN','RO'=>'RO','RR'=>'RR','RS'=>'RS','SC'=>'SC',
            'SE'=>'SE','SP'=>'SP','TO'=>'TO',
        ];
    }

    /**
     * Carrega cargos do concurso.
     * 1) concursos_vagas_cargos (nome)
     * 2) fallback: distinct dos itens (quando existir só item/cota)
     */
    protected function loadCargosForConcurso(int $concursoId): array
    {
        if (Schema::hasTable('concursos_vagas_cargos')
            && Schema::hasColumn('concursos_vagas_cargos','concurso_id')
            && Schema::hasColumn('concursos_vagas_cargos','nome')) {

            $rows = DB::table('concursos_vagas_cargos as c')
                ->where('c.concurso_id', $concursoId)
                ->orderBy('c.nome')
                ->get(['c.id','c.nome']);

            if ($rows->isNotEmpty()) {
                return $rows->map(fn($r) => (object)['id'=>(int)$r->id,'titulo'=>(string)$r->nome])->all();
            }
        }

        // fallback (deriva dos itens)
        if (Schema::hasTable('concursos_vagas_itens') && Schema::hasTable('concursos_vagas_cargos')) {
            $rows = DB::table('concursos_vagas_itens as i')
                ->join('concursos_vagas_cargos as c', 'c.id', '=', 'i.cargo_id')
                ->where('i.concurso_id', $concursoId)
                ->distinct()
                ->orderBy('c.nome')
                ->get(['c.id','c.nome']);

            return $rows->map(fn($r) => (object)['id'=>(int)$r->id,'titulo'=>(string)$r->nome])->all();
        }

        return [];
    }

    /** IDs dos cargos já vinculados à cidade */
    protected function cidadeCargosIds(int $cidadeId, int $concursoId): array
    {
        $pivot = $this->pickPivotTable();
        if (!Schema::hasTable($pivot)) return [];

        return DB::table($pivot)
            ->where('cidade_id', $cidadeId)
            ->pluck('cargo_id')->map(fn($v)=>(int)$v)->all();
    }

    /** Lista nomes dos cargos vinculados (texto) */
    protected function cargosDaCidadeLista(int $cidadeId, int $concursoId): string
    {
        $ids = $this->cidadeCargosIds($cidadeId, $concursoId);
        if (empty($ids)) return '';

        $nomes = DB::table('concursos_vagas_cargos')
            ->where('concurso_id', $concursoId)
            ->whereIn('id', $ids)
            ->pluck('nome')
            ->map(fn($s)=>trim((string)$s))
            ->filter()
            ->all();

        return implode(', ', $nomes);
    }

    /** Sincroniza pivot cidade x cargos */
    protected function syncPivotCargos(int $concursoId, int $cidadeId, array $cargoIds): void
    {
        $pivot = $this->pickPivotTable();
        if (!Schema::hasTable($pivot)) return;

        DB::table($pivot)->where('cidade_id', $cidadeId)->delete();

        $rows = [];
        foreach ($cargoIds as $cid) {
            $cid = (int) $cid;
            if ($cid > 0) {
                $rows[] = [
                    'cidade_id'   => $cidadeId,
                    'cargo_id'    => $cid,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }
        if (!empty($rows)) DB::table($pivot)->insert($rows);
    }
}
