<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConcursoController extends Controller
{
    /**
     * Página pública: lista de concursos com cards e imagem do CLIENTE.
     */
    public function index(Request $request)
    {
        $q       = trim((string) $request->get('q', ''));
        $status  = trim((string) $request->get('status', '')); // '' | 'ativos' | 'inativos' | 'andamento' | 'suspenso' | 'finalizado'
        $perPage = 12;

        $tblConcursos = 'concursos';
        $tblClients   = 'clients';

        // SELECT dinâmico somente com colunas que existem
        $select = ['co.id'];
        $hasCol = fn($tbl,$col) => Schema::hasColumn($tbl,$col);

        if ($hasCol($tblConcursos,'titulo'))     $select[] = 'co.titulo';
        if ($hasCol($tblConcursos,'descricao'))  $select[] = 'co.descricao';
        if ($hasCol($tblConcursos,'ativo'))      $select[] = 'co.ativo';
        if ($hasCol($tblConcursos,'created_at')) $select[] = 'co.created_at';

        // slug seguro
        if ($hasCol($tblConcursos,'slug')) {
            $select[] = DB::raw('COALESCE(co.slug, co.id) as slug');
        } else {
            $select[] = DB::raw('co.id as slug');
        }

        // ====== Período de inscrições ======
        $inscStartCandidates = ['inscricoes_inicio','inicio_inscricao','dt_inicio_inscricao','inscricao_inicio','inscricoes_de','inicio_inscricoes','inscricoes_ini','insc_inicio'];
        $inscEndCandidates   = ['inscricoes_fim','fim_inscricao','dt_fim_inscricao','inscricao_fim','inscricoes_ate','fim_inscricoes','insc_fim'];
        $inscIniCol = null; $inscFimCol = null;
        foreach ($inscStartCandidates as $c) if ($hasCol($tblConcursos,$c)) { $inscIniCol = $c; break; }
        foreach ($inscEndCandidates   as $c) if ($hasCol($tblConcursos,$c)) { $inscFimCol = $c; break; }
        if ($inscIniCol) $select[] = DB::raw("co.`{$inscIniCol}` as inscricoes_ini");
        if ($inscFimCol) $select[] = DB::raw("co.`{$inscFimCol}` as inscricoes_fim");

        // ====== Pedidos de isenção ======
        $isenIniCol = null; $isenFimCol = null;
        foreach (['isencao_inicio','inicio_isencao','isen_inicio','pedidos_isencao_inicio','isencoes_inicio','isencao_ini'] as $c) {
            if ($hasCol($tblConcursos,$c)) { $isenIniCol = $c; break; }
        }
        foreach (['isencao_fim','fim_isencao','isen_fim','pedidos_isencao_fim','isencoes_fim'] as $c) {
            if ($hasCol($tblConcursos,$c)) { $isenFimCol = $c; break; }
        }
        if ($isenIniCol) $select[] = DB::raw("co.`{$isenIniCol}` as isencao_ini");
        if ($isenFimCol) $select[] = DB::raw("co.`{$isenFimCol}` as isencao_fim");

        // ====== Data da prova objetiva ======
        $provaCol = null;
        foreach (['data_prova_objetiva','prova_objetiva_data','dt_prova_objetiva','aplicacao_prova','data_prova','prova_data'] as $c) {
            if ($hasCol($tblConcursos,$c)) { $provaCol = $c; break; }
        }
        if ($provaCol) $select[] = DB::raw("co.`{$provaCol}` as prova_data");

        // ====== Número do edital ======
        $editalCol = null;
        foreach (['edital','edital_numero','num_edital','n_edital','edital_n','numero_edital','edital_num'] as $c) {
            if ($hasCol($tblConcursos,$c)) { $editalCol = $c; break; }
        }
        if ($editalCol) $select[] = DB::raw("co.`{$editalCol}` as edital_num");

        // JOIN opcional com clients p/ pegar nome e imagem
        $joinClients = Schema::hasTable($tblClients);
        if ($joinClients) {
            // Nome do cliente (primeiro campo existente)
            $clienteNomeAdded = false;
            foreach (['cliente','name','razao_social','nome_fantasia','nome'] as $cn) {
                if ($hasCol($tblClients, $cn)) {
                    $select[] = DB::raw("cl.`{$cn}` as cliente_nome");
                    $clienteNomeAdded = true;
                    break;
                }
            }
            if (!$clienteNomeAdded) $select[] = DB::raw("'' as cliente_nome");

            // Campos possíveis de imagem do cliente (mandamos como alias cl_*)
            foreach (['logo_path','logo','imagem','image','foto','photo','banner_path','banner'] as $imgCol) {
                if ($hasCol($tblClients, $imgCol)) {
                    $select[] = DB::raw("cl.`{$imgCol}` as cl_{$imgCol}");
                }
            }
        } else {
            $select[] = DB::raw("'' as cliente_nome");
        }

        $qb = DB::table("{$tblConcursos} as co")->select($select);

        if ($joinClients) {
            $qb->leftJoin("{$tblClients} as cl", 'cl.id', '=', 'co.client_id');
            if ($hasCol($tblClients,'deleted_at')) {
                $qb->whereNull('cl.deleted_at');
            }
        }

        // Filtros de busca
        if ($q !== '') {
            $like = '%'.str_replace(' ','%',$q).'%';
            $qb->where(function($w) use ($like, $tblConcursos, $hasCol) {
                if ($hasCol($tblConcursos,'titulo'))    $w->orWhere('co.titulo','like',$like);
                if ($hasCol($tblConcursos,'descricao')) $w->orWhere('co.descricao','like',$like);
                if ($hasCol($tblConcursos,'subtitulo')) $w->orWhere('co.subtitulo','like',$like);
            });
        }

        // Filtros de status
        if ($status !== '') {
            if ($status === 'ativos'   && $hasCol($tblConcursos,'ativo')) {
                $qb->where('co.ativo', 1);
            } elseif ($status === 'inativos' && $hasCol($tblConcursos,'ativo')) {
                $qb->where('co.ativo','<>',1);
            } elseif (in_array($status, ['andamento','suspenso','finalizado'], true)) {
                $colStatus = null;
                foreach (['status','situacao','situacao_concurso'] as $c) {
                    if ($hasCol($tblConcursos, $c)) { $colStatus = $c; break; }
                }
                if ($colStatus) {
                    if     ($status === 'andamento')  $qb->whereIn("co.$colStatus", ['andamento','em_andamento','em andamento','ativo','aberto']);
                    elseif ($status === 'suspenso')   $qb->whereIn("co.$colStatus", ['suspenso','suspensa','susp']);
                    elseif ($status === 'finalizado') $qb->whereIn("co.$colStatus", ['finalizado','encerrado','finalizada','encerrada','concluido','concluída']);
                } else {
                    if ($status === 'andamento'  && $hasCol($tblConcursos,'ativo'))   $qb->where('co.ativo', 1);
                    if ($status === 'finalizado' && $hasCol($tblConcursos,'ativo'))   $qb->where('co.ativo','<>',1);
                    if ($status === 'suspenso'   && $hasCol($tblConcursos,'suspenso')) $qb->where('co.suspenso', 1);
                }
            }
        }

        $qb->orderByDesc('co.id');
        $concursos = $qb->paginate($perPage)->withQueryString();

        // Pós-processamento: imagem + fallback do total de vagas (usando o mesmo subselect)
        $subForOne = $this->totalVagasSubquery(); // reutiliza para fallback quando necessário

        $concursos->getCollection()->transform(function ($row) use ($subForOne) {
            // imagem
            $clientRow = [];
            foreach (['logo_path','logo','imagem','image','foto','photo','banner_path','banner'] as $imgCol) {
                $key = 'cl_'.$imgCol;
                if (property_exists($row, $key)) {
                    $clientRow[$imgCol] = $row->{$key};
                    unset($row->{$key});
                }
            }
            $row->card_image = $this->pickClientImage($clientRow);

            // total de vagas: se vier null, tenta via subselect pontual
            if (!property_exists($row, 'total_vagas') || $row->total_vagas === null) {
                $row->total_vagas = $this->computeTotalVagas((int)$row->id, $subForOne);
            }

            return $row;
        });

        // ====== AGORA BUSCA AS CONFIGS DO SITE (DINÂMICAS) ======
        $site = $this->loadSiteConfig();

        // Faixa de logos (belt) — até 10 imagens válidas
        $belt = $concursos->getCollection()
            ->pluck('card_image')->filter()->unique()->take(10)->values();

        return view('site.concursos.index', compact('concursos','q','status','site','belt'));
    }

    /**
     * Detalhe do concurso público.
     */
    public function show($slugOrId)
    {
        $tblConcursos = 'concursos';
        $tblClients   = 'clients';
        $hasCol = fn($tbl,$col) => Schema::hasColumn($tbl,$col);

        $select = ['co.id'];
        if ($hasCol($tblConcursos,'titulo'))     $select[] = 'co.titulo';
        if ($hasCol($tblConcursos,'descricao'))  $select[] = 'co.descricao'; // << corrigido: NADA de "desicao"
        if ($hasCol($tblConcursos,'ativo'))      $select[] = 'co.ativo';
        if ($hasCol($tblConcursos,'created_at')) $select[] = 'co.created_at';

        if ($hasCol($tblConcursos,'slug')) {
            $select[] = DB::raw('COALESCE(co.slug, co.id) as slug');
        } else {
            $select[] = DB::raw('co.id as slug');
        }

        // ====== Datas de inscrição / isenção / prova / edital (aliases padronizados) ======
        $inscStartCandidates = ['inscricoes_inicio','inicio_inscricao','dt_inicio_inscricao','inscricao_inicio','inscricoes_de','inicio_inscricoes','inscricoes_ini','insc_inicio'];
        $inscEndCandidates   = ['inscricoes_fim','fim_inscricao','dt_fim_inscricao','inscricao_fim','inscricoes_ate','fim_inscricoes','insc_fim'];
        $inscIniCol = null; $inscFimCol = null;
        foreach ($inscStartCandidates as $c) if ($hasCol($tblConcursos,$c)) { $inscIniCol = $c; break; }
        foreach ($inscEndCandidates   as $c) if ($hasCol($tblConcursos,$c)) { $inscFimCol = $c; break; }
        if ($inscIniCol) $select[] = DB::raw("co.`{$inscIniCol}` as inscricoes_ini");
        if ($inscFimCol) $select[] = DB::raw("co.`{$inscFimCol}` as inscricoes_fim");

        $isenIniCol = null; $isenFimCol = null;
        foreach (['isencao_inicio','inicio_isencao','isen_inicio','pedidos_isencao_inicio','isencoes_inicio','isencao_ini'] as $c) {
            if ($hasCol($tblConcursos,$c)) { $isenIniCol = $c; break; }
        }
        foreach (['isencao_fim','fim_isencao','isen_fim','pedidos_isencao_fim','isencoes_fim'] as $c) {
            if ($hasCol($tblConcursos,$c)) { $isenFimCol = $c; break; }
        }
        if ($isenIniCol) $select[] = DB::raw("co.`{$isenIniCol}` as isencao_ini");
        if ($isenFimCol) $select[] = DB::raw("co.`{$isenFimCol}` as isencao_fim");

        $provaCol = null;
        foreach (['data_prova_objetiva','prova_objetiva_data','dt_prova_objetiva','aplicacao_prova','data_prova','prova_data'] as $c) {
            if ($hasCol($tblConcursos,$c)) { $provaCol = $c; break; }
        }
        if ($provaCol) $select[] = DB::raw("co.`{$provaCol}` as prova_data");

        $editalCol = null;
        foreach (['edital','edital_numero','num_edital','n_edital','edital_n','numero_edital','edital_num'] as $c) {
            if ($hasCol($tblConcursos,$c)) { $editalCol = $c; break; }
        }
        if ($editalCol) $select[] = DB::raw("co.`{$editalCol}` as edital_num");

        // JOIN opcional com clients (nome + imagens)
        $joinClients = Schema::hasTable($tblClients);
        if ($joinClients) {
            $clienteNomeAdded = false;
            foreach (['cliente','name','razao_social','nome_fantasia','nome'] as $cn) {
                if ($hasCol($tblClients, $cn)) {
                    $select[] = DB::raw("cl.`{$cn}` as cliente_nome");
                    $clienteNomeAdded = true;
                    break;
                }
            }
            if (!$clienteNomeAdded) $select[] = DB::raw("'' as cliente_nome");

            foreach (['logo_path','logo','imagem','image','foto','photo','banner_path','banner'] as $imgCol) {
                if ($hasCol($tblClients, $imgCol)) {
                    $select[] = DB::raw("cl.`{$imgCol}` as cl_{$imgCol}");
                }
            }
        } else {
            $select[] = DB::raw("'' as cliente_nome");
        }

        $qb = DB::table("{$tblConcursos} as co")->select($select);

        if ($joinClients) {
            $qb->leftJoin("{$tblClients} as cl", 'cl.id', '=', 'co.client_id');
            if ($hasCol($tblClients,'deleted_at')) {
                $qb->whereNull('cl.deleted_at');
            }
        }

        if ($hasCol($tblConcursos,'slug') && !ctype_digit((string)$slugOrId)) {
            $qb->where('co.slug', $slugOrId);
        } else {
            $qb->where('co.id', (int)$slugOrId);
        }

        $row = $qb->first();
        abort_unless($row, 404);

        // Imagem do topo/detalhe
        $clientRow = [];
        foreach (['logo_path','logo','imagem','image','foto','photo','banner_path','banner'] as $imgCol) {
            $key = 'cl_'.$imgCol;
            if (property_exists($row, $key)) {
                $clientRow[$imgCol] = $row->{$key};
                unset($row->{$key});
            }
        }
        $row->hero_image = $this->pickClientImage($clientRow);

        // ===== Calcula se a inscrição está aberta (booleano consumido pela view) =====
        $row->inscricao_aberta = $this->calcInscricaoAberta(
            $row->inscricoes_ini ?? null,
            $row->inscricoes_fim ?? null
        );

        // ===== Blocos dinâmicos =====
        $anexos     = $this->fetchFirstByConcursoId($row->id, ['concursos_anexos','concurso_anexos','concursos_publicacoes','publicacoes','anexos']);
        $cronograma = $this->fetchFirstByConcursoId($row->id, ['concursos_cronograma','cronogramas','cronograma','concursos_eventos','eventos']);

        // VAGAS: tenta resumo por cargo (dinâmico); se não der, usa a primeira tabela "genérica"
        $vagasResumo = $this->buildVagasResumo($row->id);
        $vagas       = $vagasResumo->isNotEmpty()
            ? $vagasResumo
            : $this->fetchFirstByConcursoId($row->id, ['concursos_vagas_itens','concursos_vagas','vagas','cargos_itens','vagas_itens','vaga_itens']);

        // Configs do site (dinâmicas)
        $site = $this->loadSiteConfig();
        $site['banner_title'] = $site['banner_title'] ?: 'Detalhes do Concurso';
        $site['banner_sub']   = $site['banner_sub']   ?: '';

        return view('site.concursos.show', [
            'concurso'   => $row,
            'site'       => $site,
            'anexos'     => $anexos,
            'cronograma' => $cronograma,
            'vagas'      => $vagas,            // resumo por cargo com alias "vagas"
            // 'vagas_locais' => $this->buildVagasLocais($row->id), // use se quiser listar por localidade na view
        ]);
    }

    /**
     * Resolve a melhor URL pública para a imagem do cliente.
     */
    private function pickClientImage(array $cl): ?string
    {
        $candidates = [
            $cl['logo_path'] ?? null,
            $cl['logo'] ?? null,
            $cl['banner_path'] ?? null,
            $cl['banner'] ?? null,
            $cl['imagem'] ?? null,
            $cl['image'] ?? null,
            $cl['foto'] ?? null,
            $cl['photo'] ?? null,
        ];

        foreach ($candidates as $p) {
            $p = trim((string)$p);
            if ($p === '') continue;

            $p = str_replace('\\', '/', $p);

            if (Str::startsWith($p, ['http://','https://','data:image'])) {
                return $p;
            }
            if (Str::startsWith($p, ['/storage/','storage/'])) {
                return asset(ltrim($p,'/'));
            }

            $norm = ltrim($p, '/');
            if (Str::startsWith($norm, 'public/')) {
                $norm = substr($norm, 7);
            }

            if (Storage::disk('public')->exists($norm)) {
                return asset('storage/'.$norm);
            }

            if (file_exists(public_path($p)))                return asset($p);
            if (file_exists(public_path($norm)))             return asset($norm);
            if (file_exists(public_path('storage/'.$norm)))  return asset('storage/'.$norm);
        }

        return null;
    }

    /**
     * Subselect dinâmico para total de vagas.
     */
    private function totalVagasSubquery(): ?\Illuminate\Database\Query\Builder
    {
        $hasTable = fn($t) => Schema::hasTable($t);
        $hasCol   = fn($t,$c) => Schema::hasColumn($t,$c);

        $itemTables  = ['concursos_vagas_itens','concurso_vaga_itens','vaga_itens','vagas_itens','cargos_itens','cargo_localidades','itens'];
        $cargoTables = ['cargos','concursos_cargos','vaga_cargos','vagas_cargos','concurso_cargos'];

        $qtyCandidates = ['vagas_totais','qtd_total','quantidade','qtd','vagas','qtde','qtd_vagas'];

        $tblItens = null;
        foreach ($itemTables as $t) {
            if ($hasTable($t)) { $tblItens = $t; break; }
        }
        if (!$tblItens) return null;

        $qtyCols = [];
        foreach ($qtyCandidates as $qc) {
            if ($hasCol($tblItens,$qc)) $qtyCols[] = $qc;
        }
        if (empty($qtyCols)) return null;

        if (count($qtyCols) === 1) {
            $qtyExpr = "COALESCE(it.`{$qtyCols[0]}`,0)";
        } else {
            $parts = array_map(fn($c) => "COALESCE(it.`$c`,0)", $qtyCols);
            $qtyExpr = 'GREATEST('.implode(',', $parts).')';
        }

        $crField = null;
        foreach (['cr','cadastro_reserva'] as $c)
            if ($hasCol($tblItens,$c)) { $crField = $c; break; }

        if ($hasCol($tblItens,'concurso_id')) {
            $q = DB::table("$tblItens as it")
                ->select('it.concurso_id', DB::raw("SUM($qtyExpr) as total_vagas"));

            if ($hasCol($tblItens,'deleted_at')) $q->whereNull('it.deleted_at');
            if ($crField) {
                $q->where(function($w) use ($crField){
                    $w->whereNull("it.$crField")->orWhere("it.$crField", 0);
                });
            }

            return $q->groupBy('it.concurso_id');
        }

        if ($hasCol($tblItens,'cargo_id')) {
            $tblCargos = null;
            foreach ($cargoTables as $t) {
                if ($hasTable($t) && $hasCol($t,'id') && $hasCol($t,'concurso_id')) { $tblCargos = $t; break; }
            }
            if (!$tblCargos) return null;

            $q = DB::table("$tblItens as it")
                ->join("$tblCargos as cg", 'cg.id', '=', 'it.cargo_id')
                ->select('cg.concurso_id', DB::raw("SUM($qtyExpr) as total_vagas"));

            if ($hasCol($tblItens,'deleted_at')) $q->whereNull('it.deleted_at');
            if (Schema::hasColumn($tblCargos,'deleted_at')) $q->whereNull('cg.deleted_at');
            if ($crField) {
                $q->where(function($w) use ($crField){
                    $w->whereNull("it.$crField")->orWhere("it.$crField", 0);
                });
            }

            return $q->groupBy('cg.concurso_id');
        }

        return null;
    }

    /**
     * Fallback pontual para total de vagas de um concurso específico.
     */
    private function computeTotalVagas(int $concursoId, ?\Illuminate\Database\Query\Builder $sub = null): int
    {
        if (!$sub) $sub = $this->totalVagasSubquery();
        if ($sub) {
            $val = DB::query()->fromSub($sub, 'vg')
                ->where('vg.concurso_id', $concursoId)
                ->value('total_vagas');
            if ($val !== null) return (int) $val;
        }

        $col = null;
        foreach (['vagas_total','total_vagas'] as $c)
            if (Schema::hasColumn('concursos', $c)) { $col = $c; break; }

        if ($col) {
            return (int) DB::table('concursos')->where('id',$concursoId)->value($col);
        }

        return 0;
    }

    /** Calcula booleano de inscrição aberta a partir de (ini, fim). */
    private function calcInscricaoAberta($iniRaw, $fimRaw): bool
    {
        if (!$iniRaw && !$fimRaw) return false;
        try {
            $now = Carbon::now();
            $ini = $iniRaw ? Carbon::parse($iniRaw)->startOfDay() : null;
            $fim = $fimRaw ? Carbon::parse($fimRaw)->endOfDay()   : null;

            if     ($ini && $fim)  return $now->between($ini, $fim);
            elseif ($ini && !$fim) return $now->greaterThanOrEqualTo($ini);
            elseif (!$ini && $fim) return $now->lessThanOrEqualTo($fim);
        } catch (\Throwable $e) {}
        return false;
    }

    /**
     * Busca a primeira tabela existente (entre candidatos) que possua concurso_id
     * e retorna todos os itens do concurso. Útil para anexos/cronograma.
     */
    private function fetchFirstByConcursoId(int $concursoId, array $candidateTables): Collection
    {
        foreach ($candidateTables as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t,'concurso_id')) {
                $orderCol = Schema::hasColumn($t,'ordem')       ? 'ordem' :
                            (Schema::hasColumn($t,'created_at') ? 'created_at' :
                            (Schema::hasColumn($t,'data')       ? 'data' : 'id'));

                $dir = $orderCol === 'ordem' ? 'asc' : 'desc';

                return DB::table($t)
                    ->where('concurso_id', $concursoId)
                    ->orderBy($orderCol, $dir)
                    ->get();
            }
        }
        return collect();
    }

    /**
     * Resumo de vagas por CARGO para uso na view pública.
     * Retorna colunas: cargo, vagas (int), codigo (opcional), nivel (opcional).
     */
    private function buildVagasResumo(int $concursoId): Collection
    {
        if (
            !Schema::hasTable('concursos_vagas_itens') ||
            !Schema::hasTable('concursos_vagas_cargos')
        ) {
            return collect();
        }

        $hasVagasTotais = Schema::hasColumn('concursos_vagas_itens', 'vagas_totais');
        $usaLocalString = Schema::hasColumn('concursos_vagas_itens', 'local');

        // soma de cotas por item
        $subCotas = DB::table('concursos_vagas_cotas')
            ->selectRaw('item_id, SUM(vagas) as total_cotas')
            ->groupBy('item_id');

        $exprTotalItem = $hasVagasTotais
            ? 'COALESCE(i.vagas_totais, x.total_cotas, 0)'
            : 'COALESCE(x.total_cotas, 0)';

        $colNivelSelect = Schema::hasColumn('concursos_vagas_cargos','nivel') ? 'c.nivel' :
                          (Schema::hasColumn('concursos_vagas_cargos','nivel_id') ? 'n.nome' : 'NULL');

        $qb = DB::table('concursos_vagas_itens as i')
            ->join('concursos_vagas_cargos as c', 'c.id', '=', 'i.cargo_id')
            ->leftJoinSub($subCotas, 'x', 'x.item_id', '=', 'i.id');

        if (Schema::hasColumn('concursos_vagas_cargos','nivel_id')) {
            $qb->leftJoin('niveis_escolaridade as n', 'n.id', '=', 'c.nivel_id');
        }

        $linhas = $qb->where('i.concurso_id', $concursoId)
            ->selectRaw("
                c.nome as cargo,
                ".(Schema::hasColumn('concursos_vagas_cargos','codigo') ? 'c.codigo' : 'NULL')." as codigo,
                {$colNivelSelect} as nivel,
                SUM({$exprTotalItem}) as vagas
            ")
            ->groupBy('c.nome');

        if (Schema::hasColumn('concursos_vagas_cargos','codigo')) {
            $linhas->groupBy('c.codigo');
        }
        if (Schema::hasColumn('concursos_vagas_cargos','nivel_id')) {
            $linhas->groupBy('n.nome');
        } elseif (Schema::hasColumn('concursos_vagas_cargos','nivel')) {
            $linhas->groupBy('c.nivel');
        }

        return $linhas->orderBy('c.nome')->get();
    }

    /**
     * Linhas por localidade (opcional para exibir na view).
     * Retorna: cargo_nome, local_nome, quantidade (int), cr (0/1).
     */
    private function buildVagasLocais(int $concursoId): Collection
    {
        if (
            !Schema::hasTable('concursos_vagas_itens') ||
            !Schema::hasTable('concursos_vagas_cargos')
        ) {
            return collect();
        }

        $usaLocalString = Schema::hasColumn('concursos_vagas_itens', 'local');
        $hasVagasTotais = Schema::hasColumn('concursos_vagas_itens', 'vagas_totais');
        $hasCR          = Schema::hasColumn('concursos_vagas_itens', 'cr');

        $subCotas = DB::table('concursos_vagas_cotas')
            ->selectRaw('item_id, SUM(vagas) as total_cotas')
            ->groupBy('item_id');

        $exprQuantidade = $hasVagasTotais
            ? 'COALESCE(i.vagas_totais, x.total_cotas, 0)'
            : 'COALESCE(x.total_cotas, 0)';

        $qb = DB::table('concursos_vagas_itens as i')
            ->join('concursos_vagas_cargos as c', 'c.id', '=', 'i.cargo_id')
            ->leftJoin('concursos_vagas_localidades as l', 'l.id', '=', 'i.localidade_id')
            ->leftJoinSub($subCotas, 'x', 'x.item_id', '=', 'i.id')
            ->where('i.concurso_id', $concursoId);

        $selects = [
            'c.nome as cargo_nome',
            DB::raw($usaLocalString ? 'i.local as local_nome' : 'l.nome as local_nome'),
            DB::raw("{$exprQuantidade} as quantidade"),
        ];
        if ($hasCR) $selects[] = 'i.cr';

        return $qb->select($selects)
            ->orderBy('c.nome')
            ->orderBy($usaLocalString ? 'i.local' : 'l.nome')
            ->get();
    }

    /**
     * Detecta uma tabela chave/valor e retorna metadados (tabela, colunas).
     */
    private function detectKV(): ?array
    {
        foreach (['settings','configs','configurations'] as $t) {
            if (!Schema::hasTable($t)) continue;
            foreach ([['key','value'], ['chave','valor'], ['name','value'], ['k','v']] as [$k,$v]) {
                if (Schema::hasColumn($t,$k) && Schema::hasColumn($t,$v)) {
                    $updated = Schema::hasColumn($t,'updated_at') ? 'updated_at' : null;
                    return ['table'=>$t,'key'=>$k,'value'=>$v,'updated_at'=>$updated];
                }
            }
        }
        return null;
    }

    /**
     * Resolve caminho/URL de imagem para uma URL pública acessível.
     */
    private function resolvePublicUrl(string $pathOrUrl): ?string
    {
        $p = trim($pathOrUrl);
        if ($p === '') return null;
        $p = str_replace('\\','/',$p);

        if (Str::startsWith($p, ['http://','https://','data:image'])) return $p;
        if (Str::startsWith($p, ['/storage/','storage/'])) return asset(ltrim($p,'/'));

        $norm = ltrim($p, '/');
        if (Str::startsWith($norm, 'public/')) $norm = substr($norm, 7);

        if (Storage::disk('public')->exists($norm)) return asset('storage/'.$norm);
        if (file_exists(public_path($p)))               return asset($p);
        if (file_exists(public_path($norm)))            return asset($norm);
        if (file_exists(public_path('storage/'.$norm))) return asset('storage/'.$norm);

        return asset('storage/'.$norm);
    }

    /**
     * Carrega as configurações públicas do site (tabela larga + chave-valor).
     */
    private function loadSiteConfig(): array
    {
        $site = [
            'brand'        => 'GestaoConcursos',
            'primary'      => '#0f172a',
            'accent'       => '#111827',
            'banner_url'   => null,
            'banner_title' => 'Concursos e Processos Seletivos',
            'banner_sub'   => 'Inscreva-se, acompanhe publicações e consulte resultados.',
        ];

        $wideTbl = Schema::hasTable('site_settings') ? 'site_settings' : null;
        if ($wideTbl) {
            try {
                $row = Schema::hasColumn($wideTbl,'id')
                    ? (DB::table($wideTbl)->where('id',1)->first() ?? DB::table($wideTbl)->first())
                    : DB::table($wideTbl)->first();

                if ($row) {
                    foreach (['brand','primary','accent','banner_title','banner_sub'] as $k) {
                        if (isset($row->{$k}) && $row->{$k} !== '') $site[$k] = (string)$row->{$k};
                    }
                    foreach (['banner_url','banner_path','banner','image','hero_image','hero_url'] as $k) {
                        if (!empty($row->{$k})) { $site['banner_url'] = $this->resolvePublicUrl((string)$row->{$k}); break; }
                    }
                }
            } catch (\Throwable $e) {}
        }

        if ($kv = $this->detectKV()) {
            $rows = DB::table($kv['table'])
                ->whereIn($kv['key'], [
                    'site.brand','site.primary','site.accent',
                    'site.banner_title','site.banner_sub','site.banner_url'
                ])->get();

            foreach ($rows as $r) {
                $k = (string) $r->{$kv['key']};
                $v = (string) $r->{$kv['value']};
                switch ($k) {
                    case 'site.brand':         $site['brand']        = $v; break;
                    case 'site.primary':       $site['primary']      = $v; break;
                    case 'site.accent':        $site['accent']       = $v; break;
                    case 'site.banner_title':  $site['banner_title'] = $v; break;
                    case 'site.banner_sub':    $site['banner_sub']   = $v; break;
                    case 'site.banner_url':    $site['banner_url']   = $this->resolvePublicUrl($v); break;
                }
            }
        }

        return $site;
    }
}
