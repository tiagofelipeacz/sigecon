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
        $status  = trim((string) $request->get('status', '')); // '' | 'ativos' | 'inativos'
        $perPage = 12;

        $tblConcursos = 'concursos';
        $tblClients   = 'clients';

        // SELECT dinâmico somente com colunas que existem
        $select = [
            'co.id',
        ];

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
            if (!$clienteNomeAdded) {
                $select[] = DB::raw("'' as cliente_nome");
            }

            // Campos possíveis de imagem do cliente (vamos mandar todos como alias cl_*)
            foreach (['logo_path','logo','imagem','image','foto','photo','banner_path','banner'] as $imgCol) {
                if ($hasCol($tblClients, $imgCol)) {
                    $select[] = DB::raw("cl.`{$imgCol}` as cl_{$imgCol}");
                }
            }
        } else {
            $select[] = DB::raw("'' as cliente_nome");
        }

        $qb = DB::table("{$tblConcursos} as co")
            ->select($select);

        if ($joinClients) {
            $qb->leftJoin("{$tblClients} as cl", 'cl.id', '=', 'co.client_id');

            // Respeita soft delete do clients (se existir)
            if ($hasCol($tblClients,'deleted_at')) {
                $qb->whereNull('cl.deleted_at');
            }
        }

        // Filtros
        if ($q !== '') {
            $like = '%'.str_replace(' ','%',$q).'%';
            $qb->where(function($w) use ($like, $tblConcursos, $hasCol) {
                if ($hasCol($tblConcursos,'titulo'))    $w->orWhere('co.titulo','like',$like);
                if ($hasCol($tblConcursos,'descricao')) $w->orWhere('co.descricao','like',$like);
                if ($hasCol($tblConcursos,'subtitulo')) $w->orWhere('co.subtitulo','like',$like); // só se existir
            });
        }

        if ($status === 'ativos'   && $hasCol($tblConcursos,'ativo')) $qb->where('co.ativo', 1);
        if ($status === 'inativos' && $hasCol($tblConcursos,'ativo')) $qb->where('co.ativo','<>',1);

        $qb->orderByDesc('co.id');

        $concursos = $qb->paginate($perPage)->withQueryString();

        // Monta a URL da imagem do card a partir do CLIENTE:
        $concursos->getCollection()->transform(function ($row) {
            $clientRow = [];
            foreach (['logo_path','logo','imagem','image','foto','photo','banner_path','banner'] as $imgCol) {
                $key = 'cl_'.$imgCol;
                if (property_exists($row, $key)) {
                    $clientRow[$imgCol] = $row->{$key};
                    unset($row->{$key});
                }
            }
            $row->card_image = $this->pickClientImage($clientRow);
            return $row;
        });

        // Config simples do site (pode trocar depois por tabela settings)
        $site = [
            'brand'        => 'GestaoConcursos',
            'primary'      => '#0f172a',
            'accent'       => '#111827',
            'banner_url'   => null,
            'banner_title' => 'Concursos e Processos Seletivos',
            'banner_sub'   => 'Inscreva-se, acompanhe publicações e consulte resultados.',
        ];

        // Opcional: faixa de logos (belt) — pega até 10 imagens válidas
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
            if (!$clienteNomeAdded) {
                $select[] = DB::raw("'' as cliente_nome");
            }
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

        $site = [
            'brand'        => 'GestaoConcursos',
            'primary'      => '#0f172a',
            'accent'       => '#111827',
            'banner_url'   => null,
            'banner_title' => 'Detalhes do Concurso',
            'banner_sub'   => '',
        ];

        return view('site.concursos.show', ['concurso' => $row, 'site' => $site]);
    }

    /**
     * Resolve a melhor URL pública para a imagem do cliente.
     * Normaliza caminhos legados: backslashes, 'public/...', 'storage/app/public/...', etc.
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

            // 1) URL absoluta ou base64 -> usa direto
            if (Str::startsWith($p, ['http://','https://','data:image'])) {
                return $p;
            }

            // 2) Normaliza separadores e prefixos redundantes
            //    - troca "\" por "/"
            //    - remove "public/" do início
            //    - encurta "storage/app/public/" para caminho relativo do disco
            //    - remove barra inicial sobrando
            $p = str_replace('\\', '/', $p);
            $p = preg_replace('#^/?public/#', '', $p);
            $p = preg_replace('#^/?storage/app/public/#', '', $p);
            // Se veio "public/storage/..." normaliza para "storage/..."
            $p = preg_replace('#^/?public/storage/#', 'storage/', $p);
            $p = ltrim($p, '/');

            // 3) Se já vier "storage/..." (ex.: storage/logos/arquivo.png), devolve como asset()
            if (Str::startsWith($p, 'storage/')) {
                return asset($p);
            }

            // 4) Caminho relativo no disco 'public' (ex.: "logos/arquivo.jpg")
            if (Storage::disk('public')->exists($p)) {
                return Storage::disk('public')->url($p); // => /storage/...
            }

            // 5) Tentativas finais em /public
            if (file_exists(public_path($p))) {
                return asset($p);
            }
            if (file_exists(public_path('storage/'.$p))) {
                return asset('storage/'.$p);
            }
        }

        return null; // deixe o blade aplicar placeholder
    }
}
