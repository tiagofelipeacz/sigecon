<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $inscStartCandidates = ['inscricoes_inicio','inicio_inscricao','dt_inicio_inscricao','inscricao_inicio','inscricoes_de','inicio_inscricoes'];
        $inscEndCandidates   = ['inscricoes_fim','fim_inscricao','dt_fim_inscricao','inscricao_fim','inscricoes_ate','fim_inscricoes'];
        $inscIniCol = null; $inscFimCol = null;
        foreach ($inscStartCandidates as $c) if ($hasCol($tblConcursos,$c)) { $inscIniCol = $c; break; }
        foreach ($inscEndCandidates   as $c) if ($hasCol($tblConcursos,$c)) { $inscFimCol = $c; break; }
        if ($inscIniCol) $select[] = DB::raw("co.`{$inscIniCol}` as inscricoes_ini");
        if ($inscFimCol) $select[] = DB::raw("co.`{$inscFimCol}` as inscricoes_fim");

        // ====== Pedidos de isenção ======
        $isenIniCol = null; $isenFimCol = null;
        foreach (['isencao_inicio','inicio_isencao','isen_inicio','pedidos_isencao_inicio','isencoes_inicio'] as $c) {
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
        foreach (['edital','edital_numero','num_edital','n_edital','edital_n','numero_edital'] as $c) {
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

        // >>> total de vagas por subselect (evita N+1)
        if ($sub = $this->totalVagasSubquery()) {
            $qb->leftJoinSub($sub, 'vg', function($j){
                $j->on('vg.concurso_id', '=', 'co.id');
            });

            // coluna de total no concursos: prioriza 'vagas_total' (seu banco), depois 'total_vagas'
            $concursosTotalCol = null;
            foreach (['vagas_total','total_vagas'] as $c) {
                if ($hasCol($tblConcursos, $c)) { $concursosTotalCol = $c; break; }
            }

            if ($concursosTotalCol) {
                $qb->addSelect(DB::raw("COALESCE(vg.total_vagas, co.`{$concursosTotalCol}`, 0) as total_vagas"));
            } else {
                $qb->addSelect(DB::raw('COALESCE(vg.total_vagas, 0) as total_vagas'));
            }
        } else {
            // Sem subselect, tenta coluna no concursos
            $concursosTotalCol = null;
            foreach (['vagas_total','total_vagas'] as $c) if ($hasCol($tblConcursos,$c)) { $concursosTotalCol = $c; break; }
            if ($concursosTotalCol) {
                $qb->addSelect(DB::raw("COALESCE(co.`{$concursosTotalCol}`, 0) as total_vagas"));
            } else {
                $qb->addSelect(DB::raw('0 as total_vagas'));
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
        if ($hasCol($tblConcursos,'descricao'))  $select[] = 'co.descricao';
        if ($hasCol($tblConcursos,'ativo'))      $select[] = 'co.ativo';
        if ($hasCol($tblConcursos,'created_at')) $select[] = 'co.created_at';

        if ($hasCol($tblConcursos,'slug')) {
            $select[] = DB::raw('COALESCE(co.slug, co.id) as slug');
        } else {
            $select[] = DB::raw('co.id as slug');
        }

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

        // Configs do site (dinâmicas)
        $site = $this->loadSiteConfig();
        $site['banner_title'] = $site['banner_title'] ?: 'Detalhes do Concurso';
        $site['banner_sub']   = $site['banner_sub']   ?: '';

        return view('site.concursos.show', ['concurso' => $row, 'site' => $site]);
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

            // Normaliza barras (Windows)
            $p = str_replace('\\', '/', $p);

            // URL absoluta ou base64
            if (Str::startsWith($p, ['http://','https://','data:image'])) {
                return $p;
            }

            // Se já vier como /storage/... (ou storage/...), devolve como asset direto
            if (Str::startsWith($p, ['/storage/','storage/'])) {
                return asset(ltrim($p,'/'));
            }

            // Normaliza: remove barra inicial e prefixo 'public/'
            $norm = ltrim($p, '/');
            if (Str::startsWith($norm, 'public/')) {
                $norm = substr($norm, 7);
            }

            // Existe no disco 'public'? então expõe como /storage/{path}
            if (Storage::disk('public')->exists($norm)) {
                return asset('storage/'.$norm);
            }

            // Tentativas diretas no /public
            if (file_exists(public_path($p)))                return asset($p);
            if (file_exists(public_path($norm)))             return asset($norm);
            if (file_exists(public_path('storage/'.$norm)))  return asset('storage/'.$norm);
        }

        return null; // usa placeholder no blade
    }

    /**
     * Subselect dinâmico para total de vagas.
     */
    private function totalVagasSubquery(): ?\Illuminate\Database\Query\Builder
    {
        $hasTable = fn($t) => Schema::hasTable($t);
        $hasCol   = fn($t,$c) => Schema::hasColumn($t,$c);

        // Prioriza o nome que você tem: concursos_vagas_itens
        $itemTables  = ['concursos_vagas_itens','concurso_vaga_itens','vaga_itens','vagas_itens','cargos_itens','cargo_localidades','itens'];
        $cargoTables = ['cargos','concursos_cargos','vaga_cargos','vagas_cargos','concurso_cargos'];

        // ordem de preferência para quantidade (inclui 'vagas_totais'!)
        $qtyCandidates = ['vagas_totais','qtd_total','quantidade','qtd','vagas','qtde','qtd_vagas'];

        // encontra a tabela de itens
        $tblItens = null;
        foreach ($itemTables as $t) {
            if ($hasTable($t)) { $tblItens = $t; break; }
        }
        if (!$tblItens) return null;

        // quais colunas de quantidade existem
        $qtyCols = [];
        foreach ($qtyCandidates as $qc) {
            if ($hasCol($tblItens,$qc)) $qtyCols[] = $qc;
        }
        if (empty($qtyCols)) return null;

        // Se só houver 1 coluna de quantidade, não precisa de GREATEST
        if (count($qtyCols) === 1) {
            $qtyExpr = "COALESCE(it.`{$qtyCols[0]}`,0)";
        } else {
            $parts = array_map(fn($c) => "COALESCE(it.`$c`,0)", $qtyCols);
            $qtyExpr = 'GREATEST('.implode(',', $parts).')';
        }

        // filtros auxiliares
        $crField = null;
        foreach (['cr','cadastro_reserva'] as $c)
            if ($hasCol($tblItens,$c)) { $crField = $c; break; }

        // caso A: itens possuem concurso_id
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

        // caso B: itens possuem cargo_id e precisamos do join em cargos.* com concurso_id
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

        // fallback: coluna no concursos (prioriza 'vagas_total', depois 'total_vagas')
        $col = null;
        foreach (['vagas_total','total_vagas'] as $c)
            if (Schema::hasColumn('concursos', $c)) { $col = $c; break; }

        if ($col) {
            return (int) DB::table('concursos')->where('id',$concursoId)->value($col);
        }

        return 0;
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
        // defaults
        $site = [
            'brand'        => 'GestaoConcursos',
            'primary'      => '#0f172a',
            'accent'       => '#111827',
            'banner_url'   => null,
            'banner_title' => 'Concursos e Processos Seletivos',
            'banner_sub'   => 'Inscreva-se, acompanhe publicações e consulte resultados.',
        ];

        // (A) TABELA LARGA: site_settings
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
            } catch (\Throwable $e) {
                // ignora e segue para KV
            }
        }

        // (B) CHAVE-VALOR: settings/configs/configurations (sobrepõe)
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
