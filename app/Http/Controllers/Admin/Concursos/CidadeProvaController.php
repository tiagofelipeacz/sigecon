<?php

namespace App\Http\Controllers\Admin\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;

class CidadeProvaController extends Controller
{
    public function index(Concurso $concurso, Request $req)
    {
        $q   = trim((string)$req->get('q',''));
        $uf  = trim((string)$req->get('uf',''));
        $per = 20;

        $tbl = $this->tblCidades();
        if (!$tbl) {
            // Sem tabela ainda → entrega listagem vazia para a view não quebrar
            $rows = new LengthAwarePaginator([], 0, $per, 1, [
                'path'  => $req->url(),
                'query' => $req->query(),
            ]);
            return view('admin.concursos.cidades.index', compact('concurso','rows','q','uf'));
        }

        $query = DB::table($tbl)->where('concurso_id', $concurso->id);

        if ($q !== '') {
            $query->where(function($w) use ($q){
                $w->where('cidade', 'like', "%{$q}%")
                  ->orWhere('nome', 'like', "%{$q}%");
            });
        }
        if ($uf !== '') {
            $query->where('uf', $uf);
        }

        $rows = $query->orderBy('cidade')->orderBy('nome')
            ->paginate($per)
            ->through(function($r){
                $r = (object)$r;
                $r->cidade = $r->cidade ?? $r->nome ?? null;
                $r->cargos_lista = $this->cargosDaCidadeLista((int)$r->id);
                return $r;
            });

        return view('admin.concursos.cidades.index', compact('concurso','rows','q','uf'));
    }

    public function create(Concurso $concurso)
    {
        $cidade = null;
        $cargos = $this->listarCargos($concurso->id);
        return view('admin.concursos.cidades.create', compact('concurso','cidade','cargos'));
    }

    public function edit(Concurso $concurso, int $cidade)
    {
        $tbl = $this->tblCidades() ?? abort(500, 'Tabela de cidades de prova não encontrada.');
        $row = DB::table($tbl)->where('id',$cidade)->where('concurso_id',$concurso->id)->first() ?? abort(404);

        $row = (object)$row;
        $row->cargos_ids = $this->idsCargosDaCidade($cidade);

        $cargos = $this->listarCargos($concurso->id);

        return view('admin.concursos.cidades.edit', [
            'concurso' => $concurso,
            'cidade'   => $row,
            'cargos'   => $cargos,
        ]);
    }

    public function store(Concurso $concurso, Request $req)
    {
        $data = $this->validated($req);
        $tbl  = $this->tblCidades() ?? abort(500, 'Tabela de cidades de prova não encontrada.');

        $id = DB::table($tbl)->insertGetId([
            'concurso_id' => $concurso->id,
            'cidade'      => $data['cidade'],
            'uf'          => $data['uf'],
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->syncPivot($id, $data['cargos'] ?? []);

        return redirect()
            ->route('admin.concursos.cidades.index', $concurso)
            ->with('status', 'Cidade de prova criada.');
    }

    public function update(Concurso $concurso, int $cidade, Request $req)
    {
        $data = $this->validated($req);
        $tbl  = $this->tblCidades() ?? abort(500, 'Tabela de cidades de prova não encontrada.');

        DB::table($tbl)
            ->where('id', $cidade)
            ->where('concurso_id', $concurso->id)
            ->update([
                'cidade'     => $data['cidade'],
                'uf'         => $data['uf'],
                'updated_at' => now(),
            ]);

        $this->syncPivot($cidade, $data['cargos'] ?? []);

        return redirect()
            ->route('admin.concursos.cidades.index', $concurso)
            ->with('status', 'Cidade de prova atualizada.');
    }

    private function validated(Request $req): array
    {
        $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

        return $req->validate([
            'cidade'   => ['required','string','max:120'],
            'uf'       => ['required','in:'.implode(',',$ufs)],
            'cargos'   => ['nullable','array'],
            'cargos.*' => ['integer'],
        ]);
    }

    // =====================
    // Helpers de infraestrutura
    // =====================

    private function tblCidades(): ?string
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
        return null;
    }

    private function tblPivot(): ?string
    {
        foreach ([
            'concursos_cidades_cargos',
            'cidades_prova_cargos',
            'cidade_prova_cargo',
            'cidades_de_prova_cargos',
        ] as $t) {
            if (Schema::hasTable($t)) return $t;
        }
        return null;
    }

    /**
     * Retorna [tabela, idCol, nomeCol] para cargos.
     */
    private function tblCargos(): ?array
    {
        $cands = [
            ['concursos_cargos','id','nome'],
            ['concursos_cargos','id','titulo'],
            ['cargos','id','nome'],
            ['concursos_vagas_cargos','id','nome'],
            ['vagas_cargos','id','nome'],
        ];
        foreach ($cands as [$t,$id,$nm]) {
            if (Schema::hasTable($t) && Schema::hasColumn($t,$id) && Schema::hasColumn($t,$nm)) {
                return [$t,$id,$nm];
            }
        }
        return null;
    }

    private function listarCargos(int $concursoId): array
    {
        $meta = $this->tblCargos();
        if (!$meta) return [];
        [$tbl,$id,$nm] = $meta;

        $q = DB::table($tbl)->select([$id.' as id',$nm.' as nome']);
        if (Schema::hasColumn($tbl,'concurso_id')) {
            $q->where('concurso_id',$concursoId);
        }
        return $q->orderBy($nm)->get()->map(fn($r)=>(object)$r)->all();
    }

    private function pivotCidadeCol(string $pivot): string
    {
        foreach (['cidade_id','concursos_cidade_id','cidade_prova_id','concursos_cidades_id'] as $c) {
            if (Schema::hasColumn($pivot,$c)) return $c;
        }
        return 'cidade_id';
    }

    private function pivotCargoCol(string $pivot): string
    {
        foreach (['cargo_id','concursos_cargo_id','vaga_cargo_id'] as $c) {
            if (Schema::hasColumn($pivot,$c)) return $c;
        }
        return 'cargo_id';
    }

    private function syncPivot(int $cidadeId, array $ids): void
    {
        $pivot = $this->tblPivot();
        if (!$pivot) return;

        $colCidade = $this->pivotCidadeCol($pivot);
        $colCargo  = $this->pivotCargoCol($pivot);

        DB::table($pivot)->where($colCidade, $cidadeId)->delete();

        $rows = [];
        foreach (array_unique(array_map('intval',$ids)) as $cg) {
            $rows[] = [$colCidade => $cidadeId, $colCargo => $cg];
        }
        if ($rows) DB::table($pivot)->insert($rows);
    }

    private function idsCargosDaCidade(int $cidadeId): array
    {
        $pivot = $this->tblPivot();
        if (!$pivot) return [];
        $colCidade = $this->pivotCidadeCol($pivot);
        $colCargo  = $this->pivotCargoCol($pivot);

        return DB::table($pivot)->where($colCidade,$cidadeId)->pluck($colCargo)->map(fn($v)=>(int)$v)->all();
    }

    private function cargosDaCidadeLista(int $cidadeId): string
    {
        $meta  = $this->tblCargos();
        $pivot = $this->tblPivot();
        if (!$meta || !$pivot) return '';

        [$tbl,$id,$nm] = $meta;
        $colCidade = $this->pivotCidadeCol($pivot);
        $colCargo  = $this->pivotCargoCol($pivot);

        $ids = DB::table($pivot)->where($colCidade,$cidadeId)->pluck($colCargo)->all();
        if (!$ids) return '';

        return DB::table($tbl)->whereIn($id,$ids)->orderBy($nm)->pluck($nm)->join(', ');
    }
}
