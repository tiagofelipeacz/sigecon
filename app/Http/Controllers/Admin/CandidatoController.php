<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Candidato;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CandidatoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $q = trim((string)$request->get('q', ''));
        $candidatos = Candidato::query()
            ->when($q !== '', function ($qry) use ($q) {
                $like = '%' . str_replace(' ', '%', $q) . '%';
                $digits = preg_replace('~\D+~', '', $q);
                $qry->where(function ($w) use ($like, $digits) {
                    $w->where('nome', 'like', $like)
                      ->orWhere('cpf', 'like', $digits ? "%{$digits}%" : $like)
                      ->orWhere('email', 'like', $like)
                      ->orWhere('telefone', 'like', $like)
                      ->orWhere('celular', 'like', $like)
                      ->orWhere('cidade', 'like', $like)
                      ->orWhere('estado', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.candidatos.index', compact('candidatos', 'q'));
    }

    public function create()
    {
        $candidato = new Candidato();
        $ufs = $this->ufs();
        return view('admin.candidatos.create', compact('candidato','ufs'));
    }

    protected function validateData(Request $request, ?Candidato $candidato = null): array
    {
        // --- pré-normalizações ---
        if ($request->has('cpf')) {
            $cpf = preg_replace('~\D+~', '', (string)$request->input('cpf'));
            $request->merge(['cpf' => $cpf]);
        }

        if ($request->filled('data_nascimento')) {
            $raw = trim((string)$request->input('data_nascimento'));
            if (preg_match('~^\d{2}/\d{2}/\d{4}$~', $raw)) {
                [$d,$m,$y] = explode('/', $raw);
                $request->merge(['data_nascimento' => "$y-$m-$d"]);
            }
        }

        foreach (['estado','naturalidade_uf','doc_uf'] as $ufField) {
            if ($request->filled($ufField)) {
                $request->merge([$ufField => strtoupper((string)$request->input($ufField))]);
            }
        }

        // Regra de unicidade do CPF respeitando SoftDeletes (deleted_at NULL)
        $cpfUniqueRule = Rule::unique('candidatos','cpf')
            ->whereNull('deleted_at');

        if ($candidato && $candidato->id) {
            $cpfUniqueRule = $cpfUniqueRule->ignore($candidato->id);
        }

        $rules = [
            'nome' => ['required','string','max:255'],
            'cpf'  => ['required','string','regex:/^\d{11}$/', $cpfUniqueRule],
            'email'=> ['nullable','email','max:150'],
            'telefone' => ['nullable','string','max:50'],
            'celular'  => ['nullable','string','max:50'],
            'data_nascimento' => ['nullable','date'],
            'sexo' => ['nullable','in:M,F,O'],
            'estado_civil' => ['nullable','in:solteiro,casado,separado,divorciado,viuvo,outro'],

            // Foto
            'foto' => ['nullable','image','mimes:jpeg,png,jpg,gif','max:4096'],

            // Endereço
            'endereco_cep' => ['nullable','string','max:12'],
            'endereco_rua' => ['nullable','string','max:255'],
            'endereco_numero' => ['nullable','string','max:20'],
            'endereco_complemento' => ['nullable','string','max:255'],
            'endereco_bairro' => ['nullable','string','max:255'],
            'cidade' => ['nullable','string','max:255'],
            'estado' => ['nullable','string','max:2'],

            // Familiares / dados pessoais
            'nome_mae' => ['nullable','string','max:255'],
            'nome_pai' => ['nullable','string','max:255'],
            'nacionalidade' => ['nullable','string','max:100'],
            'naturalidade_uf' => ['nullable','string','max:2'],
            'naturalidade_cidade' => ['nullable','string','max:255'],
            'nacionalidade_ano_chegada' => ['nullable','integer','min:0','max:3000'],
            'sistac_nis' => ['nullable','string','max:20'],
            'qt_filhos' => ['nullable','integer','min:0','max:50'],
            'cnh_categoria' => ['nullable','string','max:10'],
            'id_deficiencia' => ['nullable','integer','min:1'],
            'observacoes_internas' => ['nullable','string'],

            // Documentos extras
            'doc_tipo'        => ['nullable','in:rg,cnh,ctps'],
            'doc_numero'      => ['nullable','string','max:50'],
            'doc_orgao'       => ['nullable','string','max:50'],
            'doc_uf'          => ['nullable','string','max:2'],
            'doc_complemento' => ['nullable','string','max:100'],

            // Credenciais
            'password' => ['nullable','string','min:6','confirmed'],
            'status' => ['nullable','boolean'],
        ];

        $messages = [
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.regex'    => 'O CPF deve conter 11 dígitos (somente números).',
            'cpf.unique'   => 'Este CPF já está em uso por outro candidato ativo.',
            'data_nascimento.date' => 'Data de nascimento inválida.',
            'foto.image' => 'A foto deve ser uma imagem.',
            'foto.mimes' => 'A foto deve ser JPG, PNG ou GIF.',
            'foto.max'   => 'A foto não pode exceder 4 MB.',
        ];

        $data = $request->validate($rules, $messages);

        // normaliza vazios -> null
        $nullableKeys = [
            'email','telefone','celular','data_nascimento','sexo','estado_civil','foto_path',
            'endereco_cep','endereco_rua','endereco_numero','endereco_complemento','endereco_bairro',
            'cidade','estado','nome_mae','nome_pai','nacionalidade','naturalidade_uf','naturalidade_cidade',
            'nacionalidade_ano_chegada','sistac_nis','qt_filhos','cnh_categoria','id_deficiencia','observacoes_internas',
            'doc_tipo','doc_numero','doc_orgao','doc_uf','doc_complemento'
        ];
        foreach ($nullableKeys as $k) {
            if (array_key_exists($k, $data) && $data[$k] === '') {
                $data[$k] = null;
            }
        }

        return $data;
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request, null);

        // upload de foto
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $path = $request->file('foto')->store('candidatos', 'public');
            $data['foto_path'] = $path;
        }

        // status e senha
        $data['status'] = isset($data['status']) ? (int)!!$data['status'] : 1;
        if (!empty($data['password'])) { $data['password'] = bcrypt($data['password']); } else { unset($data['password']); }

        try {
            $candidato = Candidato::create($data);
        } catch (QueryException $e) {
            // Em geral validação já segura duplicidade; ainda assim tratamos
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                return back()->withInput()->withErrors(['cpf' => 'CPF já cadastrado para outro candidato.']);
            }
            return back()->withInput()->withErrors(['general' => 'Erro ao salvar: ' . $e->getMessage()]);
        }

        $action = (string)$request->input('action','save');
        return $this->redirectAfterAction($action, $candidato, false)->with('success', 'Candidato criado com sucesso.');
    }

    public function edit(Candidato $candidato)
    {
        $ufs = $this->ufs();
        return view('admin.candidatos.edit', compact('candidato','ufs'));
    }

    public function update(Request $request, Candidato $candidato)
    {
        $data = $this->validateData($request, $candidato);

        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            // não apaga já no update; apenas substitui o arquivo
            if ($candidato->foto_path) {
                Storage::disk('public')->delete($candidato->foto_path);
            }
            $path = $request->file('foto')->store('candidatos', 'public');
            $data['foto_path'] = $path;
        }

        $data['status'] = isset($data['status']) ? (int)!!$data['status'] : ($candidato->status ?? 1);
        if (!empty($data['password'])) { $data['password'] = bcrypt($data['password']); } else { unset($data['password']); }

        try {
            $candidato->update($data);
        } catch (QueryException $e) {
            if ((int)($e->errorInfo[1] ?? 0) === 1062) {
                return back()->withInput()->withErrors(['cpf' => 'CPF já cadastrado para outro candidato.']);
            }
            return back()->withInput()->withErrors(['general' => 'Erro ao salvar: ' . $e->getMessage()]);
        }

        $action = (string)$request->input('action','save');
        return $this->redirectAfterAction($action, $candidato, true)->with('success', 'Candidato atualizado com sucesso.');
    }

    /**
     * Soft delete: NÃO remove foto (permite restauração sem perda).
     */
    public function destroy(Candidato $candidato)
    {
        $candidato->delete();
        return redirect()->route('admin.candidatos.index')->with('success', 'Candidato arquivado com sucesso.');
    }

    /**
     * Restaura um candidato soft-deletado.
     */
    public function restore(int $id)
    {
        $cand = Candidato::onlyTrashed()->findOrFail($id);
        $cand->restore();
        return redirect()->route('admin.candidatos.edit', $cand)->with('success', 'Candidato restaurado com sucesso.');
    }

    /**
     * Exclusão definitiva: remove registro e apaga foto.
     */
    public function forceDestroy(int $id)
    {
        $cand = Candidato::onlyTrashed()->findOrFail($id);
        if ($cand->foto_path) {
            Storage::disk('public')->delete($cand->foto_path);
        }
        $cand->forceDelete();
        return redirect()->route('admin.candidatos.index')->with('success', 'Candidato excluído definitivamente.');
    }

    public function export(Request $request): StreamedResponse
    {
        $fileName = 'candidatos_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];
        $callback = function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','Nome','Telefone','Celular','Email','CPF','Data Nasc.','Cidade','UF'], ';');
            Candidato::orderBy('id')->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $c) {
                    $dn = '';
                    if (!empty($c->data_nascimento)) {
                        try { $dn = \Illuminate\Support\Carbon::parse($c->data_nascimento)->format('d/m/Y'); } catch (\Throwable $e) {}
                    }
                    fputcsv($out, [
                        $c->id,
                        $c->nome,
                        $c->telefone,
                        $c->celular,
                        $c->email,
                        $c->cpf,
                        $dn,
                        $c->cidade,
                        $c->estado,
                    ], ';');
                }
            });
            fclose($out);
        };
        return response()->stream($callback, 200, $headers);
    }

    // ======================================================
    // NOVAS AÇÕES
    // ======================================================

    /** Admin entra como o candidato (guard: candidato) */
    public function impersonate(Request $request, Candidato $candidato)
    {
        if (!app('config')->has('auth.guards.candidato')) {
            return back()->withErrors(['general' => 'Guard "candidato" não está configurado no auth.php.']);
        }

        if ((int)($candidato->status ?? 1) !== 1) {
            return back()->withErrors(['general' => 'Este candidato está inativo e não pode ser impersonado.']);
        }

        Auth::guard('candidato')->login($candidato, false);
        $request->session()->put('impersonating_candidato_id', $candidato->id);

        return redirect()->route('candidato.home')
            ->with('status', 'Agora você está visualizando como o candidato #'.$candidato->id.'.');
    }

    /** Admin sai do modo candidato */
    public function stopImpersonate(Request $request)
    {
        if (app('config')->has('auth.guards.candidato')) {
            Auth::guard('candidato')->logout();
        }
        $request->session()->forget('impersonating_candidato_id');

        $back = url()->previous();
        if (!$back || $back === url()->current()) {
            return redirect()->route('admin.candidatos.index')->with('success', 'Sessão do candidato encerrada.');
        }
        return redirect($back)->with('success', 'Sessão do candidato encerrada.');
    }

    /**
     * Inscrições do candidato para a view que espera $rows
     * Detecta dinamicamente a FK (concurso_id|edital_id) e oculta inscrições soft-deletadas.
     */
    public function inscricoes(Request $request, Candidato $candidato)
    {
        $limit = (int) $request->integer('limit', 50);

        // Descobre qual coluna a tabela 'inscricoes' usa para referenciar concursos
        $fkInsc = Schema::hasColumn('inscricoes', 'concurso_id') ? 'concurso_id' : 'edital_id';

        try {
            $rows = DB::table('inscricoes as i')
                ->leftJoin('concursos as co', 'co.id', '=', DB::raw("i.`{$fkInsc}`"))
                ->leftJoin('cargos as ca', 'ca.id', '=', 'i.cargo_id')
                ->where('i.candidato_id', $candidato->id)
                ->whereNull('i.deleted_at') // ignora inscrições soft-deletadas
                ->orderByDesc('i.created_at')
                ->limit($limit)
                ->get([
                    'i.id',
                    DB::raw('COALESCE(co.titulo, co.nome, "") as concurso'),
                    DB::raw('COALESCE(ca.nome, ca.titulo, "") as cargo'),
                    'i.status',
                    'i.created_at',
                ]);

        } catch (\Throwable $e) {
            // Fallback minimalista (sem joins)
            $rows = DB::table('inscricoes')
                ->where('candidato_id', $candidato->id)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get(['id','status','created_at']);

            // Garante chaves esperadas na view
            foreach ($rows as $r) {
                if (!isset($r->concurso)) $r->concurso = null;
                if (!isset($r->cargo))    $r->cargo    = null;
            }
        }

        return view('admin.candidatos.inscricoes', [
            'candidato' => $candidato,
            'rows'      => $rows,
        ]);
    }

    // ======================================================

    private function redirectAfterAction(string $action, Candidato $candidato, bool $updated)
    {
        switch ($action) {
            case 'save_close':
                return redirect()->route('admin.candidatos.index');
            case 'save':
            default:
                return redirect()->route('admin.candidatos.edit', $candidato);
        }
    }

    private function ufs(): array
    {
        return ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
    }
}
