<?php

namespace App\Http\Controllers\Admin\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use App\Models\Inscricao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InscritosController extends Controller
{
    // status do seu enum mais comuns
    private const STATUS = [
        'rascunho', 'pendente_pagamento', 'confirmada', 'cancelada', 'importada'
    ];

    private const MODALIDADES = ['ampla','pcd','negros','outras'];

    public function index(Request $request, Concurso $concurso)
    {
        $q          = (string) $request->string('q');
        $status     = (string) $request->string('status');
        $modalidade = (string) $request->string('modalidade');

        $query = DB::table('inscricoes as i')
            ->leftJoin('users as u', 'u.id', '=', 'i.user_id')
            ->leftJoin('concursos_vagas_cargos as cvc', 'cvc.id', '=', 'i.cargo_id')
            ->where('i.edital_id', $concurso->id);

        if ($q !== '') {
            $query->where(function($w) use ($q) {
                $w->where('u.name', 'like', "%{$q}%")
                  ->orWhere('u.email', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $query->where('i.status', $status);
        }

        if ($modalidade !== '') {
            $query->where('i.modalidade', $modalidade);
        }

        $inscricoes = $query
            ->select([
                'i.id', 'i.status', 'i.modalidade', 'i.created_at',
                'u.name as candidato_nome', 'u.email as candidato_email',
                'cvc.nome as cargo_nome'
            ])
            ->orderByDesc('i.id')
            ->paginate(20)
            ->withQueryString();

        // contagens por status (chips)
        $statusCounts = DB::table('inscricoes')
            ->select('status', DB::raw('count(*) as total'))
            ->where('edital_id', $concurso->id)
            ->groupBy('status')
            ->pluck('total','status')
            ->toArray();

        return view('admin.concursos.inscritos.index', [
            'concurso'      => $concurso,
            'inscricoes'    => $inscricoes,
            'statusCounts'  => $statusCounts,
            'q'             => $q,
            'status'        => $status,
            'modalidade'    => $modalidade,
            'STATUS'        => self::STATUS,
            'MODALIDADES'   => self::MODALIDADES,
        ]);
    }

    public function create(Concurso $concurso, Request $request)
    {
        // Cargos do concurso
        $cargos = DB::table('concursos_vagas_cargos')
            ->where('concurso_id', $concurso->id)
            ->orderBy('nome')->get(['id','nome']);

        // Para não listar milhares de usuários sem filtro:
        $users = DB::table('users')
            ->orderBy('name')
            ->limit(50)
            ->get(['id','name','email']);

        return view('admin.concursos.inscritos.create', [
            'concurso'    => $concurso,
            'cargos'      => $cargos,
            'users'       => $users,
            'STATUS'      => self::STATUS,
            'MODALIDADES' => self::MODALIDADES,
        ]);
    }

    public function store(Concurso $concurso, Request $request)
    {
        $data = $request->validate([
            'user_id'    => ['required','integer','exists:users,id'],
            'cargo_id'   => ['required','integer','exists:concursos_vagas_cargos,id'],
            'modalidade' => ['required', Rule::in(self::MODALIDADES)],
            'status'     => ['required', Rule::in(self::STATUS)],
        ]);

        $data['edital_id'] = $concurso->id;

        Inscricao::create($data);

        return redirect()
            ->route('admin.concursos.inscritos.index', $concurso)
            ->with('ok', 'Inscrição criada com sucesso.');
    }

    public function importForm(Concurso $concurso)
    {
        // exemplo de CSV simples
        $exemplo = "user_email,cargo_id,modalidade,status\ncandidato@exemplo.com,12,ampla,confirmada";
        return view('admin.concursos.inscritos.import', [
            'concurso' => $concurso,
            'exemplo'  => $exemplo,
            'MODALIDADES' => self::MODALIDADES,
            'STATUS'      => self::STATUS,
        ]);
    }

    public function importStore(Concurso $concurso, Request $request)
    {
        $request->validate([
            'arquivo' => ['required','file','mimes:csv,txt'],
        ]);

        $file   = $request->file('arquivo')->getRealPath();
        $handle = fopen($file, 'r');
        if (!$handle) {
            return back()->withErrors(['arquivo' => 'Não foi possível ler o arquivo.']);
        }

        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            fclose($handle);
            return back()->withErrors(['arquivo' => 'CSV vazio.']);
        }

        // Normaliza nomes das colunas
        $header = array_map(fn($h)=>strtolower(trim($h)), $header);

        $colUserEmail = array_search('user_email', $header);
        $colUserId    = array_search('user_id', $header);
        $colCargoId   = array_search('cargo_id', $header);
        $colModal     = array_search('modalidade', $header);
        $colStatus    = array_search('status', $header);

        if ($colCargoId === false || ($colUserEmail === false && $colUserId === false)) {
            fclose($handle);
            return back()->withErrors(['arquivo' => 'Cabeçalho precisa conter "cargo_id" e (user_email OU user_id).']);
        }

        $ok = 0; $fail = 0; $errors = [];
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (count($row) === 1 && trim($row[0]) === '') continue;

                $userId = null;
                if ($colUserId !== false && !empty($row[$colUserId])) {
                    $userId = (int) $row[$colUserId];
                    $exists = DB::table('users')->where('id',$userId)->exists();
                    if (!$exists) {
                        $errors[] = "user_id {$userId} não encontrado";
                        $fail++; continue;
                    }
                } elseif ($colUserEmail !== false) {
                    $email = trim((string)($row[$colUserEmail] ?? ''));
                    if ($email === '') { $errors[] = "user_email vazio"; $fail++; continue; }
                    $userId = (int) DB::table('users')->where('email',$email)->value('id');
                    if (!$userId) { $errors[] = "user_email {$email} não encontrado"; $fail++; continue; }
                }

                $cargoId = (int) ($row[$colCargoId] ?? 0);
                $cargoOk = DB::table('concursos_vagas_cargos')
                    ->where('id', $cargoId)->where('concurso_id', $concurso->id)->exists();
                if (!$cargoOk) { $errors[] = "cargo_id {$cargoId} inválido para este concurso"; $fail++; continue; }

                $modalidade = $colModal !== false ? strtolower(trim((string)($row[$colModal] ?? 'ampla'))) : 'ampla';
                if (!in_array($modalidade, self::MODALIDADES, true)) $modalidade = 'ampla';

                $status = $colStatus !== false ? strtolower(trim((string)($row[$colStatus] ?? 'confirmada'))) : 'confirmada';
                if (!in_array($status, self::STATUS, true)) $status = 'confirmada';

                DB::table('inscricoes')->insert([
                    'edital_id'  => $concurso->id,
                    'user_id'    => $userId,
                    'cargo_id'   => $cargoId,
                    'modalidade' => $modalidade,
                    'status'     => $status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $ok++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return back()->withErrors(['arquivo' => 'Falha ao importar: '.$e->getMessage()]);
        }
        fclose($handle);

        $msg = "{$ok} linha(s) importadas";
        if ($fail) $msg .= " — {$fail} falha(s)";
        return redirect()->route('admin.concursos.inscritos.index', $concurso)
            ->with('ok', $msg)
            ->with('import_errors', $errors);
    }

    public function dadosExtras(Concurso $concurso)
    {
        // Apenas informa como habilitar (sem quebrar nada se não tiver as tabelas extras)
        $temCampos = DB::getSchemaBuilder()->hasTable('inscricoes_campos');
        $campos = [];
        if ($temCampos) {
            $campos = DB::table('inscricoes_campos')
                ->where('concurso_id', $concurso->id)
                ->orderBy('ordem')->orderBy('id')
                ->get();
        }

        return view('admin.concursos.inscritos.dados-extras', [
            'concurso'  => $concurso,
            'temCampos' => $temCampos,
            'campos'    => $campos,
        ]);
    }
}
