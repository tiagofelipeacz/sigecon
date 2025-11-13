<?php

namespace App\Http\Controllers\Candidato;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Models\CandidatoInscricao;
use App\Models\Candidato;

class InscricaoController extends Controller
{
    private array $colMetaCache = [];
    private array $enumCache = [];
    private array $fkCache = [];

    private function concursoKeyOnInscricoes(): string
    {
        $tbl = (new CandidatoInscricao)->getTable();
        if (Schema::hasColumn($tbl, 'edital_id')) return 'edital_id';
        if (Schema::hasColumn($tbl, 'concurso_id')) return 'concurso_id';
        return 'edital_id';
    }

    private function decodeConfigs($concurso): array
    {
        if (!$concurso) return [];
        $raw = $concurso->configs ?? null;
        if (is_array($raw)) return $raw;
        if (is_object($raw)) return (array) $raw;
        if (is_string($raw) && $raw !== '') {
            $arr = json_decode($raw, true);
            return (json_last_error() === JSON_ERROR_NONE && is_array($arr)) ? $arr : [];
        }
        return [];
    }

    private function columnMeta(string $table, string $column): ?array
    {
        $key = $table.'|'.$column;
        if (isset($this->colMetaCache[$key])) return $this->colMetaCache[$key];

        try {
            $row = DB::table('information_schema.columns')
                ->select('data_type', 'character_maximum_length', 'extra', 'generation_expression', 'column_type')
                ->where('table_schema', DB::raw('DATABASE()'))
                ->where('table_name', $table)
                ->where('column_name', $column)
                ->first();

            if (!$row) return $this->colMetaCache[$key] = null;

            $isGenerated = false;
            $extra = strtolower((string)($row->extra ?? ''));
            if (str_contains($extra, 'generated')) $isGenerated = true;
            if (!empty($row->generation_expression)) $isGenerated = true;

            return $this->colMetaCache[$key] = [
                'data_type'     => strtolower((string)($row->data_type ?? '')),
                'max'           => $row->character_maximum_length ? (int)$row->character_maximum_length : null,
                'is_generated'  => $isGenerated,
                'column_type'   => (string)($row->column_type ?? ''),
            ];
        } catch (\Throwable $e) {
            return $this->colMetaCache[$key] = null;
        }
    }

    private function fkRef(string $table, string $column): ?array
    {
        $key = $table.'|'.$column;
        if (isset($this->fkCache[$key])) return $this->fkCache[$key];

        try {
            $row = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->select('REFERENCED_TABLE_NAME as ref_table', 'REFERENCED_COLUMN_NAME as ref_column')
                ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->first();

            if (!$row) return $this->fkCache[$key] = null;

            return $this->fkCache[$key] = [
                'ref_table'  => (string)$row->ref_table,
                'ref_column' => (string)$row->ref_column,
            ];
        } catch (\Throwable $e) {
            return $this->fkCache[$key] = null;
        }
    }

    private function isGeneratedColumn(string $table, string $column): bool
    {
        $meta = $this->columnMeta($table, $column);
        return (bool)($meta['is_generated'] ?? false);
    }

    private function isEnumOrSet(string $table, string $column): bool
    {
        $meta = $this->columnMeta($table, $column);
        if (!$meta) return false;
        $dt = $meta['data_type'] ?? '';
        return ($dt === 'enum' || $dt === 'set');
    }

    private function enumAllowedValues(string $table, string $column): array
    {
        $key = $table.'|'.$column;
        if (isset($this->enumCache[$key])) return $this->enumCache[$key];

        $meta = $this->columnMeta($table, $column);
        $colType = $meta['column_type'] ?? '';
        $allowed = [];

        if (preg_match("/^(enum|set)\((.*)\)$/i", $colType, $m)) {
            $list = $m[2];
            $parts = preg_split("/,(?=(?:[^']*'[^']*')*[^']*$)/", $list);
            foreach ($parts as $p) {
                $p = trim($p);
                if (strlen($p) >= 2 && $p[0] === "'" && substr($p, -1) === "'") {
                    $val = str_replace("\\'", "'", substr($p, 1, -1));
                    $allowed[] = $val;
                }
            }
        }

        return $this->enumCache[$key] = $allowed;
    }

    private function norm(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        return preg_replace('/\s+/', ' ', trim($s));
    }

    private function normalizeToAllowed(string $value, array $allowed): ?string
    {
        if (!$allowed) return $value;
        $vNorm = $this->norm($value);
        foreach ($allowed as $opt) {
            if ($this->norm($opt) === $vNorm) return $opt;
        }
        foreach ($allowed as $opt) {
            if (str_contains($vNorm, $this->norm($opt))) return $opt;
        }
        if (preg_match('/\bpcd\b|deficienc/i', $value)) {
            foreach ($allowed as $opt) {
                if (preg_match('/\bpcd\b|deficienc/i', $opt)) return $opt;
            }
        }
        if (preg_match('/ampla/i', $value)) {
            foreach ($allowed as $opt) {
                if (preg_match('/ampla/i', $opt)) return $opt;
            }
        }
        if (preg_match('/\bpp\b|pret|pard/i', $value)) {
            foreach ($allowed as $opt) {
                if (preg_match('/\bpp\b|pret|pard/i', $opt)) return $opt;
            }
        }
        return null;
    }

    public function index()
    {
        $user = Auth::guard('candidato')->user();

        $inscricoes = CandidatoInscricao::where(function ($q) use ($user) {
                $q->where('candidato_id', $user->id);
                if (!empty($user->cpf)) $q->orWhere('cpf', $user->cpf);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $colConcurso = $this->concursoKeyOnInscricoes();

        $concursoIds = $inscricoes->pluck($colConcurso)->unique()->filter()->values();
        $cargoIds    = $inscricoes->pluck('cargo_id')->unique()->filter()->values();

        $concursos = $concursoIds->isNotEmpty()
            ? DB::table('concursos')->whereIn('id', $concursoIds)->get()->keyBy('id')
            : collect();

        $cargos = collect();
        if ($cargoIds->isNotEmpty()) {
            if (Schema::hasTable('concursos_vagas_cargos')) {
                $cargos = DB::table('concursos_vagas_cargos')->whereIn('id', $cargoIds)->get()->keyBy('id');
            }
            if ($cargos->isEmpty() && Schema::hasTable('cargos')) {
                $cargos = DB::table('cargos')->whereIn('id', $cargoIds)->get()->keyBy('id');
            }
        }

        $inscricoesPorConcurso = $inscricoes->groupBy($colConcurso);

        return view('site.candidato.inscricoes.index', compact(
            'inscricoes',
            'inscricoesPorConcurso',
            'concursos',
            'cargos'
        ));
    }

    public function create(Request $request)
    {
        $concursos = DB::table('concursos')
            ->where('ativo', 1)
            ->where(function ($q) {
                if (Schema::hasColumn('concursos', 'oculto')) $q->where('oculto', 0);
                else $q->where('ocultar_site', 0);
            })
            ->where('inscricoes_online', 1)
            ->whereNotNull('inscricoes_inicio')
            ->whereNotNull('inscricoes_fim')
            ->whereRaw('NOW() BETWEEN inscricoes_inicio AND inscricoes_fim')
            ->orderBy('id', 'desc')
            ->get();

        $concursoSelecionado = null;
        $concursoParam = $request->get('concurso_id', $request->get('concurso'));
        if ($concursoParam) {
            $concursoSelecionado = DB::table('concursos')->where('id', (int)$concursoParam)->first();
        }

        $modalidadesPorCargo = [];

        if (
            $concursos->isNotEmpty() &&
            Schema::hasTable('concursos_vagas_itens') &&
            Schema::hasTable('concursos_vagas_cotas') &&
            Schema::hasTable('tipos_vagas_especiais')
        ) {
            $concursoIds = $concursos->pluck('id')->filter()->values();

            $hasVagasTotais = Schema::hasColumn('concursos_vagas_itens', 'vagas_totais');

            $totais = DB::table('concursos_vagas_itens as i')
                ->select(
                    'i.concurso_id',
                    'i.cargo_id',
                    DB::raw($hasVagasTotais ? 'SUM(COALESCE(i.vagas_totais, 0)) AS total_vagas' : '0 AS total_vagas')
                )
                ->whereIn('i.concurso_id', $concursoIds)
                ->groupBy('i.concurso_id', 'i.cargo_id')
                ->get();

            $cotas = DB::table('concursos_vagas_itens as i')
                ->join('concursos_vagas_cotas as c', 'c.item_id', '=', 'i.id')
                ->join('tipos_vagas_especiais as t', 't.id', '=', 'c.tipo_id')
                ->select(
                    'i.concurso_id',
                    'i.cargo_id',
                    'c.tipo_id',
                    't.nome as tipo_nome',
                    DB::raw('SUM(COALESCE(c.vagas, 0)) AS total_cota')
                )
                ->whereIn('i.concurso_id', $concursoIds)
                ->where('t.ativo', 1)
                ->groupBy('i.concurso_id', 'i.cargo_id', 'c.tipo_id', 't.nome')
                ->get();

            $cotasPorChave = [];
            foreach ($cotas as $row) {
                $key = $row->concurso_id.'|'.$row->cargo_id;
                $cotasPorChave[$key][] = $row;
            }

            foreach ($totais as $row) {
                $key           = $row->concurso_id.'|'.$row->cargo_id;
                $totalVagas    = (int)($row->total_vagas ?? 0);
                $listaModalids = [];

                if ($totalVagas > 0) $listaModalids['Ampla concorrência'] = 'Ampla concorrência';

                if (!empty($cotasPorChave[$key])) {
                    foreach ($cotasPorChave[$key] as $cotaRow) {
                        if ((int)$cotaRow->total_cota <= 0) continue;
                        $nomeTipo = trim((string)$cotaRow->tipo_nome);
                        if ($nomeTipo === '') continue;
                        $listaModalids[$nomeTipo] = $nomeTipo;
                    }
                }

                if (empty($listaModalids)) $listaModalids['Ampla concorrência'] = 'Ampla concorrência';
                $modalidadesPorCargo[$key] = $listaModalids;
            }
        }

        $modalidades = [];
        foreach ($modalidadesPorCargo as $mods) {
            foreach ($mods as $value => $label) $modalidades[$value] = $label;
        }
        if (empty($modalidades)) $modalidades = ['Ampla concorrência' => 'Ampla concorrência'];

        $condicoesEspeciaisMap = [];

        if (
            $concursos->isNotEmpty() &&
            Schema::hasTable('concurso_tipo_condicao_especial') &&
            Schema::hasTable('tipos_condicao_especial')
        ) {
            $concursoIds = $concursos->pluck('id')->filter()->values();

            $labelCandidateCols = ['nome','descricao','descricao_condicao','titulo','texto','label'];
            $labelCols = [];
            foreach ($labelCandidateCols as $col) {
                if (Schema::hasColumn('tipos_condicao_especial', $col)) $labelCols[] = $col;
            }

            $selects = ['ctce.concurso_id', 't.id'];
            if (Schema::hasColumn('tipos_condicao_especial', 'codigo')) $selects[] = 't.codigo';
            foreach (['exibir_observacoes','exibir_observacao','precisa_laudo','necessita_laudo','laudo_obrigatorio','envio_laudo_obrigatorio'] as $flagCol) {
                if (Schema::hasColumn('tipos_condicao_especial', $flagCol)) $selects[] = 't.'.$flagCol;
            }
            foreach ($labelCols as $col) $selects[] = 't.'.$col.' as '.$col;

            $q = DB::table('concurso_tipo_condicao_especial as ctce')
                ->join('tipos_condicao_especial as t', 't.id', '=', 'ctce.tipo_condicao_especial_id')
                ->whereIn('ctce.concurso_id', $concursoIds)
                ->select($selects);

            if (Schema::hasColumn('tipos_condicao_especial', 'ativo')) $q->where('t.ativo', 1);
            if (Schema::hasColumn('concurso_tipo_condicao_especial', 'ativo')) $q->where('ctce.ativo', 1);

            if (Schema::hasColumn('tipos_condicao_especial', 'ordem')) $q->orderBy('t.ordem');
            elseif (Schema::hasColumn('tipos_condicao_especial', 'nome')) $q->orderBy('t.nome');
            elseif (Schema::hasColumn('tipos_condicao_especial', 'descricao')) $q->orderBy('t.descricao');
            else $q->orderBy('t.id');

            $rows = $q->get();

            foreach ($rows as $row) {
                $label = null;
                foreach ($labelCols as $col) {
                    if (!empty($row->{$col})) { $label = $row->{$col}; break; }
                }
                if (!$label && isset($row->codigo) && $row->codigo !== '') $label = $row->codigo;
                if (!$label) $label = 'Condição especial #'.$row->id;

                $condicoesEspeciaisMap[$row->concurso_id][] = [
                    'id'                 => $row->id,
                    'value'              => $row->id,
                    'codigo'             => $row->codigo ?? null,
                    'label'              => $label,
                    'exibir_observacoes' => $row->exibir_observacoes ?? $row->exibir_observacao ?? 0,
                    'precisa_laudo'      => $row->precisa_laudo ?? $row->necessita_laudo ?? 0,
                    'laudo_obrigatorio'  => $row->laudo_obrigatorio ?? $row->envio_laudo_obrigatorio ?? 0,
                ];
            }
        }

        $condicoesEspeciais = $condicoesEspeciaisMap;

        $tiposIsencao    = [];
        $temIsencao      = true;
        $formasPagamento = [];

        return view('site.candidato.inscricoes.create', compact(
            'concursos',
            'concursoSelecionado',
            'modalidades',
            'modalidadesPorCargo',
            'condicoesEspeciaisMap',
            'condicoesEspeciais',
            'tiposIsencao',
            'temIsencao',
            'formasPagamento'
        ));
    }

    public function cargos($concursoId)
    {
        $cargos = DB::table('concursos_vagas_cargos')
            ->where('concurso_id', $concursoId)
            ->orderBy('nome')
            ->get();

        return response()->json($cargos);
    }

    public function localidades($concursoId, $cargoId)
    {
        $itens = DB::table('concursos_vagas_itens as i')
            ->leftJoin('concursos_vagas_localidades as l', 'l.id', '=', 'i.localidade_id')
            ->select('i.id as item_id','i.localidade_id','l.nome as localidade_nome')
            ->where('i.concurso_id', $concursoId)
            ->where('i.cargo_id', $cargoId)
            ->orderBy('l.nome')
            ->get();

        return response()->json($itens);
    }

    public function cidadesProva($concursoId, $cargoId = null)
    {
        $hasTable = fn(string $t) => Schema::hasTable($t);
        $hasCol   = fn(string $t, string $c) => Schema::hasColumn($t, $c);

        $tblCidades = null;
        foreach (['concursos_cidades', 'concursos_cidades_prova', 'cidades_prova'] as $t) {
            if ($hasTable($t) && $hasCol($t, 'concurso_id')) { $tblCidades = $t; break; }
        }
        if (!$tblCidades) return response()->json([]);

        $qb = DB::table($tblCidades.' as cp')->where('cp.concurso_id', (int)$concursoId);
        if ($hasCol($tblCidades, 'ativo')) $qb->where('cp.ativo', 1);

        if ($cargoId && $hasTable('concursos_cidades_cargos')) {
            $pivot = 'concursos_cidades_cargos';
            $qb->join($pivot.' as cc', 'cc.cidade_id', '=', 'cp.id')->where('cc.cargo_id', (int)$cargoId);
            if ($hasCol($pivot, 'ativo')) $qb->where('cc.ativo', 1);
        }

        $cidadeExpr = $hasCol($tblCidades, 'cidade') ? 'cp.cidade' : ($hasCol($tblCidades, 'nome') ? 'cp.nome' : "''");
        $ufExpr     = $hasCol($tblCidades, 'uf') ? 'cp.uf' : ($hasCol($tblCidades, 'estado') ? 'cp.estado' : "''");

        $qb->selectRaw('cp.id, '.$cidadeExpr.' as cidade, '.$ufExpr.' as uf');

        if ($hasCol($tblCidades, 'ordem')) $qb->orderBy('cp.ordem');
        elseif ($hasCol($tblCidades, 'cidade')) $qb->orderBy('cp.cidade');
        elseif ($hasCol($tblCidades, 'nome')) $qb->orderBy('cp.nome');
        else $qb->orderBy('cp.id');

        $rows = $qb->distinct()->get();

        $out = $rows->map(function ($r) {
            $cidade = trim((string)($r->cidade ?? ''));
            $uf     = trim((string)($r->uf ?? ''));
            $label  = $cidade && $uf ? ($cidade.' / '.$uf) : ($cidade ?: 'Cidade #'.$r->id);
            return ['id'=>(int)$r->id,'cidade'=>$cidade,'uf'=>$uf ?: null,'label'=>$label];
        });

        return response()->json($out);
    }

    public function cidades($concursoId, $cargoId = null)
    {
        return $this->cidadesProva($concursoId, $cargoId);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('candidato')->user();

        $data = $request->validate([
            'concurso_id'                  => ['required','integer'],
            'cargo_id'                     => ['required','integer'],
            'item_id'                      => ['nullable','integer'],
            'modalidade'                   => ['required','string','max:100'],
            'condicoes_especiais'          => ['nullable','string'],
            'condicoes_especiais_opcoes'   => ['nullable','array'],
            'condicoes_especiais_opcoes.*' => ['nullable','string','max:150'],
            'solicitou_isencao'            => ['nullable','boolean'],
            'forma_pagamento'              => ['nullable','string','max:50'],
            'cidade_prova'                 => ['nullable','string','max:100'],
            'laudo_medico'                 => ['nullable','file','mimes:pdf,jpg,jpeg,png','max:5120'],
        ]);

        $concurso = DB::table('concursos')->where('id', $data['concurso_id'])->first();
        abort_unless($concurso, 404);

        if (!($concurso->inscricoes_inicio && $concurso->inscricoes_fim &&
            now()->between($concurso->inscricoes_inicio, $concurso->inscricoes_fim))) {
            return back()->withInput()->withErrors(['concurso_id' => 'Período de inscrições encerrado para este concurso.']);
        }

        $cargoConcurso = DB::table('concursos_vagas_cargos')
            ->where('id', $data['cargo_id'])
            ->where('concurso_id', $concurso->id)
            ->first();
        if (!$cargoConcurso) {
            return back()->withInput()->withErrors(['cargo_id' => 'Cargo inválido para este concurso.']);
        }

        if (Schema::hasTable('concursos_vagas_itens')) {
            $temItens = DB::table('concursos_vagas_itens')
                ->where('concurso_id', $concurso->id)
                ->where('cargo_id', $cargoConcurso->id)
                ->exists();

            if ($temItens && empty($data['item_id'])) {
                return back()->withInput()->withErrors(['item_id' => 'Selecione a localidade para este cargo.']);
            }
        }

        $item = null;
        if (!empty($data['item_id'])) {
            $item = DB::table('concursos_vagas_itens')
                ->where('id', $data['item_id'])
                ->where('concurso_id', $concurso->id)
                ->where('cargo_id', $cargoConcurso->id)
                ->first();
            abort_unless($item, 404);
        }

        $configsConcurso = $this->decodeConfigs($concurso);
        $colConcurso     = $this->concursoKeyOnInscricoes();

        $limitePorCpf = array_key_exists('limite_inscricoes_por_cpf', $configsConcurso)
            ? (int)$configsConcurso['limite_inscricoes_por_cpf']
            : 1;

        $qtdeNoConcurso = (int) CandidatoInscricao::where(function ($q) use ($user) {
                $q->where('candidato_id', $user->id);
                if (!empty($user->cpf)) $q->orWhere('cpf', $user->cpf);
            })
            ->where($colConcurso, $concurso->id)
            ->count();

        if ($limitePorCpf > 0 && $qtdeNoConcurso >= $limitePorCpf) {
            $msg = $limitePorCpf === 1
                ? 'Você já possui inscrição neste concurso.'
                : 'Você já atingiu o limite de '.$limitePorCpf.' inscrições neste concurso para este CPF.';
            return redirect()->route('candidato.inscricoes.index')->withErrors(['general' => $msg]);
        }

        $bloquearMesmoCargo = array_key_exists('bloquear_multiplas_inscricoes_mesmo_cargo', $configsConcurso)
            ? (int)$configsConcurso['bloquear_multiplas_inscricoes_mesmo_cargo']
            : 1;

        if ($bloquearMesmoCargo === 1) {
            $jaMesmoCargo = CandidatoInscricao::where(function ($q) use ($user) {
                    $q->where('candidato_id', $user->id);
                    if (!empty($user->cpf)) $q->orWhere('cpf', $user->cpf);
                })
                ->where($colConcurso, $concurso->id)
                ->where('cargo_id', $cargoConcurso->id)
                ->exists();

            if ($jaMesmoCargo) {
                return redirect()->route('candidato.inscricoes.index')
                    ->withErrors(['general' => 'Você já possui inscrição neste cargo deste concurso.']);
            }
        }

        $selectedOptions = $data['condicoes_especiais_opcoes'] ?? [];
        $selectedOptions = is_array($selectedOptions) ? $selectedOptions : [];

        $labelsQueExigemLaudo = [];
        if (!empty($selectedOptions) && Schema::hasTable('tipos_condicao_especial') && Schema::hasTable('concurso_tipo_condicao_especial')) {
            $tipos = DB::table('concurso_tipo_condicao_especial as ctce')
                ->join('tipos_condicao_especial as t', 't.id', '=', 'ctce.tipo_condicao_especial_id')
                ->where('ctce.concurso_id', $concurso->id)
                ->select('t.*')
                ->get();

            foreach ($tipos as $t) {
                $label = null;
                foreach (['nome','descricao','descricao_condicao','titulo','texto','label','codigo'] as $c) {
                    if (Schema::hasColumn('tipos_condicao_especial', $c) && !empty($t->{$c})) {
                        $label = (string)$t->{$c};
                        break;
                    }
                }
                if (!$label) $label = 'Condição especial #'.$t->id;

                $precisa = 0;
                foreach (['precisa_laudo','necessita_laudo'] as $c) {
                    if (Schema::hasColumn('tipos_condicao_especial', $c) && !empty($t->{$c})) { $precisa = 1; break; }
                }
                $obrig = 0;
                foreach (['laudo_obrigatorio','envio_laudo_obrigatorio'] as $c) {
                    if (Schema::hasColumn('tipos_condicao_especial', $c) && !empty($t->{$c})) { $obrig = 1; break; }
                }

                $labelsQueExigemLaudo[$label] = max($precisa, $obrig);
            }
        }

        $algumaExigeLaudo = false;
        foreach ($selectedOptions as $lbl) {
            if (!empty($labelsQueExigemLaudo[$lbl])) { $algumaExigeLaudo = true; break; }
        }

        if ($algumaExigeLaudo && !$request->hasFile('laudo_medico')) {
            throw ValidationException::withMessages([
                'laudo_medico' => 'Para pelo menos uma das condições selecionadas, o envio de laudo médico é obrigatório.',
            ]);
        }

        $seqBase = null;
        if (Schema::hasColumn('concursos', 'sequence_inscricao') && isset($concurso->sequence_inscricao)) {
            $seqBase = (int)$concurso->sequence_inscricao;
        }
        if (!$seqBase) $seqBase = (int)($this->decodeConfigs($concurso)['sequence_inscricao'] ?? 1);
        if ($seqBase < 1) $seqBase = 1;

        $maxNumeroNoConcurso = CandidatoInscricao::where($colConcurso, $concurso->id)->max('numero');
        $nextNumero = $maxNumeroNoConcurso ? ((int)$maxNumeroNoConcurso + 1) : $seqBase;

        $cidadeProva = $data['cidade_prova'] ?? null;
        if (!$cidadeProva && $item && property_exists($item, 'localidade_id') && $item->localidade_id) {
            $local = DB::table('concursos_vagas_localidades')->where('id', $item->localidade_id)->first();
            if ($local && isset($local->nome)) $cidadeProva = $local->nome;
        }

        $textoCondicoes = $data['condicoes_especiais'] ?? null;
        if (!empty($selectedOptions)) {
            $opcoesStr = implode('; ', array_filter($selectedOptions, fn($v) => trim((string)$v) !== ''));
            if ($opcoesStr !== '') $textoCondicoes = $textoCondicoes ? ($opcoesStr.' | '.$textoCondicoes) : $opcoesStr;
        }

        $solicitouIsencao = !empty($data['solicitou_isencao']) ? 1 : 0;
        $formaPagamento   = $data['forma_pagamento'] ?? null;
        $pagamentoStatus  = 'pendente';

        $laudoPath = null;
        if ($request->hasFile('laudo_medico')) {
            $candId = (int)$user->id;
            $dir    = "candidatos/laudos/{$candId}";
            $laudoPath = $request->file('laudo_medico')->store($dir, ['disk' => 'public']);
        }

        $tblInscricoes = (new CandidatoInscricao)->getTable();
        $cargoFk = $this->fkRef($tblInscricoes, 'cargo_id');
        $cargoIdParaSalvar = null;

        if ($cargoFk && ($cargoFk['ref_table'] ?? null)) {
            $refTable = $cargoFk['ref_table'];

            if ($refTable === 'cargos') {
                if (Schema::hasColumn('concursos_vagas_cargos', 'cargo_id') && !empty($cargoConcurso->cargo_id)) {
                    $cargoGlobal = DB::table('cargos')->where('id', $cargoConcurso->cargo_id)->first();
                    if (!$cargoGlobal) {
                        return back()->withInput()->withErrors([
                            'cargo_id' => 'Cargo global não encontrado para este cargo do concurso (consistência de dados).'
                        ]);
                    }
                    $cargoIdParaSalvar = (int)$cargoGlobal->id;
                } else {
                    if (Schema::hasColumn('concursos_vagas_cargos', 'nome') && Schema::hasColumn('cargos', 'nome')) {
                        $cargoGlobal = DB::table('cargos')->where('nome', $cargoConcurso->nome ?? '')->first();
                        if ($cargoGlobal) {
                            $cargoIdParaSalvar = (int)$cargoGlobal->id;
                        } else {
                            return back()->withInput()->withErrors([
                                'cargo_id' => 'Não foi possível mapear o cargo do concurso para a tabela global de cargos.'
                            ]);
                        }
                    } else {
                        return back()->withInput()->withErrors([
                            'cargo_id' => 'Estrutura não mapeada: FK aponta para cargos, mas não há referência clara (cargo_id/nome).'
                        ]);
                    }
                }
            } elseif ($refTable === 'concursos_vagas_cargos') {
                $cargoIdParaSalvar = (int)$cargoConcurso->id;
            } else {
                return back()->withInput()->withErrors([
                    'cargo_id' => "FK de cargo_id aponta para uma tabela inesperada: {$refTable}."
                ]);
            }
        } else {
            $cargoIdParaSalvar = (int)$cargoConcurso->id;
        }

        $payload = [];
        $set = function(string $col, $val) use (&$payload, $tblInscricoes) {
            if (Schema::hasColumn($tblInscricoes, $col) && !$this->isGeneratedColumn($tblInscricoes, $col)) {
                $payload[$col] = $val;
            }
        };

        $set('cargo_id',            $cargoIdParaSalvar);
        $set('item_id',             $item->id ?? null);
        $set('user_id',             null);
        $set('candidato_id',        $user->id);
        $set('cpf',                 $user->cpf);
        $set('documento',           null);
        $set('cidade',              $cidadeProva);
        $set('nome_inscricao',      $user->nome);
        $set('nome_candidato',      $user->nome);
        $set('nascimento',          $user->data_nascimento);
        $set('status',              'confirmada');
        $set('numero',              $nextNumero);
        $set('pessoa_key',          'C#' . str_pad((string)$user->id, 20, '0', STR_PAD_LEFT));
        $set('local_key',           0);

        if (Schema::hasColumn($tblInscricoes, 'ativo') && !$this->isGeneratedColumn($tblInscricoes, 'ativo')) {
            $payload['ativo'] = 1;
        }

        $set('condicoes_especiais', $textoCondicoes);
        $set('solicitou_isencao',   $solicitouIsencao);
        $set('forma_pagamento',     $formaPagamento);
        $set('pagamento_status',    $pagamentoStatus);

        $set('edital_id',   $concurso->id);
        $set('concurso_id', $concurso->id);

        if (Schema::hasColumn($tblInscricoes, 'modalidade') && !$this->isGeneratedColumn($tblInscricoes, 'modalidade')) {
            $modalidadeVal = (string)($data['modalidade'] ?? '');
            if ($this->isEnumOrSet($tblInscricoes, 'modalidade')) {
                $allowed    = $this->enumAllowedValues($tblInscricoes, 'modalidade');
                $normalized = $this->normalizeToAllowed($modalidadeVal, $allowed);
                if ($normalized === null) {
                    $opts = $allowed ? 'Opções válidas: '.implode(', ', $allowed) : 'Verifique as opções disponíveis.';
                    throw ValidationException::withMessages([
                        'modalidade' => "Modalidade inválida para este concurso. {$opts}",
                    ]);
                }
                $payload['modalidade'] = $normalized;
            } else {
                $payload['modalidade'] = $this->safeForColumn($tblInscricoes, 'modalidade', $modalidadeVal);
            }
        }

        if ($laudoPath && Schema::hasColumn($tblInscricoes, 'laudo_path') && !$this->isGeneratedColumn($tblInscricoes, 'laudo_path')) {
            $payload['laudo_path'] = $laudoPath;
        } elseif ($laudoPath) {
            if (isset($payload['condicoes_especiais'])) {
                $payload['condicoes_especiais'] = trim(($payload['condicoes_especiais'] ? $payload['condicoes_especiais'].' | ' : '')."[Laudo anexado]");
            }
        }

        $insc = CandidatoInscricao::create($payload);

        return redirect()->route('candidato.inscricoes.show', $insc->id)
            ->with('success', 'Inscrição realizada com sucesso.');
    }

    public function show($id)
    {
        $user = Auth::guard('candidato')->user();

        $insc = CandidatoInscricao::where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('candidato_id', $user->id);
                if (!empty($user->cpf)) $q->orWhere('cpf', $user->cpf);
            })
            ->firstOrFail();

        $colConcurso = $this->concursoKeyOnInscricoes();
        $concursoId  = $insc->{$colConcurso};

        $concurso = DB::table('concursos')->where('id', $concursoId)->first();

        $cargo = null;
        if ($insc->cargo_id) {
            $cargo = DB::table('concursos_vagas_cargos')->where('id', $insc->cargo_id)->first();
            if (!$cargo && Schema::hasTable('cargos')) {
                $cargo = DB::table('cargos')->where('id', $insc->cargo_id)->first();
            }
        }

        $localidade = null;
        if ($insc->item_id && Schema::hasTable('concursos_vagas_itens') && Schema::hasTable('concursos_vagas_localidades')) {
            $item = DB::table('concursos_vagas_itens')->where('id', $insc->item_id)->first();
            if ($item && isset($item->localidade_id) && $item->localidade_id) {
                $localidade = DB::table('concursos_vagas_localidades')->where('id', $item->localidade_id)->first();
            }
        }

        // ===========================
        // Modalidade: label dinâmico para exibir
        // ===========================
        $modalidadeLabel = $insc->modalidade ?: 'Ampla concorrência';

        try {
            $listaModalidades = [];

            if (
                $concurso &&
                $cargo &&
                Schema::hasTable('concursos_vagas_itens') &&
                Schema::hasTable('concursos_vagas_cotas') &&
                Schema::hasTable('tipos_vagas_especiais')
            ) {
                $hasVagasTotais = Schema::hasColumn('concursos_vagas_itens', 'vagas_totais');

                $totais = DB::table('concursos_vagas_itens as i')
                    ->select(
                        'i.concurso_id',
                        'i.cargo_id',
                        DB::raw($hasVagasTotais ? 'SUM(COALESCE(i.vagas_totais,0)) AS total_vagas' : '0 AS total_vagas')
                    )
                    ->where('i.concurso_id', $concurso->id)
                    ->where('i.cargo_id', $cargo->id)
                    ->groupBy('i.concurso_id', 'i.cargo_id')
                    ->first();

                if ($totais && (int)($totais->total_vagas ?? 0) > 0) {
                    $listaModalidades[] = 'Ampla concorrência';
                }

                $cotas = DB::table('concursos_vagas_itens as i')
                    ->join('concursos_vagas_cotas as c', 'c.item_id', '=', 'i.id')
                    ->join('tipos_vagas_especiais as t', 't.id', '=', 'c.tipo_id')
                    ->select('t.nome as tipo_nome', DB::raw('SUM(COALESCE(c.vagas,0)) AS total_cota'))
                    ->where('i.concurso_id', $concurso->id)
                    ->where('i.cargo_id', $cargo->id)
                    ->where('t.ativo', 1)
                    ->groupBy('t.nome')
                    ->get();

                foreach ($cotas as $row) {
                    if ((int)$row->total_cota <= 0) continue;
                    $nomeTipo = trim((string)$row->tipo_nome);
                    if ($nomeTipo !== '') $listaModalidades[] = $nomeTipo;
                }

                if (empty($listaModalidades)) {
                    $listaModalidades[] = 'Ampla concorrência';
                }
            }

            // se a lista existe, tenta casar o salvo com o "bonito"
            if (!empty($listaModalidades)) {
                $want = (string)($insc->modalidade ?? '');
                $wantNorm = $this->norm($want);

                $pick = null;
                foreach ($listaModalidades as $m) {
                    if ($this->norm($m) === $wantNorm) { $pick = $m; break; }
                }
                if (!$pick) {
                    foreach ($listaModalidades as $m) {
                        if (str_contains($wantNorm, $this->norm($m))) { $pick = $m; break; }
                    }
                }
                // alias rápidos
                if (!$pick) {
                    if (preg_match('/\bpcd\b|deficienc/i', $want)) {
                        foreach ($listaModalidades as $m) if (preg_match('/\bpcd\b|deficienc/i', $m)) { $pick = $m; break; }
                    } elseif (preg_match('/\bpp\b|pret|pard/i', $want)) {
                        foreach ($listaModalidades as $m) if (preg_match('/\bpp\b|pret|pard/i', $m)) { $pick = $m; break; }
                    } elseif (preg_match('/ampla/i', $want)) {
                        foreach ($listaModalidades as $m) if (preg_match('/ampla/i', $m)) { $pick = $m; break; }
                    }
                }

                if ($pick) $modalidadeLabel = $pick;
            }
        } catch (\Throwable $e) {
            // em caso de qualquer problema, mantém o valor salvo
        }

        return view('site.candidato.inscricoes.show', compact(
            'insc','concurso','cargo','localidade','user','modalidadeLabel'
        ));
    }

    public function comprovante($id)
    {
        $user = Auth::guard('candidato')->user();

        $insc = CandidatoInscricao::where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('candidato_id', $user->id);
                if (!empty($user->cpf)) $q->orWhere('cpf', $user->cpf);
            })
            ->firstOrFail();

        $colConcurso = $this->concursoKeyOnInscricoes();
        $concursoId  = $insc->{$colConcurso};

        $concurso = DB::table('concursos')->where('id', $concursoId)->first();

        $cargo = null;
        if ($insc->cargo_id) {
            $cargo = DB::table('concursos_vagas_cargos')->where('id', $insc->cargo_id)->first();
            if (!$cargo && Schema::hasTable('cargos')) {
                $cargo = DB::table('cargos')->where('id', $insc->cargo_id)->first();
            }
        }

        $localidade = null;
        if ($insc->item_id && Schema::hasTable('concursos_vagas_itens') && Schema::hasTable('concursos_vagas_localidades')) {
            $item = DB::table('concursos_vagas_itens')->where('id', $insc->item_id)->first();
            if ($item && isset($item->localidade_id) && $item->localidade_id) {
                $localidade = DB::table('concursos_vagas_localidades')->where('id', $item->localidade_id)->first();
            }
        }

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
                'site.candidato.inscricoes.comprovante_pdf',
                compact('insc','concurso','cargo','localidade','user')
            );
            return $pdf->download('comprovante_' . ($insc->numero ?? $insc->id) . '.pdf');
        }

        return view('site.candidato.inscricoes.comprovante_html', compact(
            'insc','concurso','cargo','localidade','user'
        ));
    }
}
