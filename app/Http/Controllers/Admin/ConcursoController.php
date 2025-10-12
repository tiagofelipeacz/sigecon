<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Concurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConcursoController extends Controller
{
    // --------------------------
    // Helpers de datas (BR -> ISO)
    // --------------------------
    private function parseDate(?string $v): ?string
    {
        $v = is_string($v) ? trim($v) : '';
        if ($v === '') return null;

        try { return Carbon::createFromFormat('d/m/Y', $v)->format('Y-m-d'); } catch (\Throwable $e) {}
        try { return Carbon::parse($v)->format('Y-m-d'); } catch (\Throwable $e) {}

        return null;
    }

    private function parseDateTime(?string $v): ?string
    {
        $v = is_string($v) ? trim($v) : '';
        if ($v === '') return null;

        foreach (['d/m/Y H:i', 'd/m/Y H:i:s'] as $fmt) {
            try { return Carbon::createFromFormat($fmt, $v)->format('Y-m-d H:i:s'); } catch (\Throwable $e) {}
        }
        try { return Carbon::parse($v)->format('Y-m-d H:i:s'); } catch (\Throwable $e) {}

        return null;
    }

    // --------------------------
    // LISTAGEM
    // --------------------------
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $status = (string) $request->input('status', 'todos');

        // explicitamente sem itens soft-deletados (mesmo sendo default)
        $query = Concurso::query()
            ->withoutTrashed()
            ->with(['client','clientLegacy','clientAlt','clientPlural']);

        if ($q !== '') {
            $query->where(function ($x) use ($q) {
                $x->where('titulo', 'like', "%{$q}%")
                  ->orWhere('nome', 'like', "%{$q}%")
                  ->orWhere('descricao', 'like', "%{$q}%");
            });
        }

        if ($status !== '' && $status !== 'todos') {
            $query->where(function ($x) use ($status) {
                $x->where('status', $status)
                  ->orWhere('situacao', $status);
            });
        }

        $concursos = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.concursos.index', [
            'concursos' => $concursos,
            'q'         => $q,
        ]);
    }

    // --------------------------
    // CRIAÇÃO
    // --------------------------
    public function create()
    {
        $table  = (new Client)->getTable();
        $cols   = Schema::getColumnListing($table);
        $prefer = [
            'cliente','razao_social','nome_fantasia','fantasia',
            'nome','name','titulo','empresa','descricao',
        ];

        $colToUse = collect($prefer)->first(fn ($c) => in_array($c, $cols, true));

        if ($colToUse) {
            $clients = Client::orderBy($colToUse)->pluck($colToUse, 'id');
        } else {
            $clients = Client::orderBy('id')->get()
                ->mapWithKeys(function ($c) {
                    $label = $c->cliente
                        ?? $c->razao_social
                        ?? $c->nome_fantasia
                        ?? $c->fantasia
                        ?? $c->nome
                        ?? $c->name
                        ?? $c->titulo
                        ?? $c->empresa
                        ?? $c->descricao
                        ?? null;
                    $label = is_string($label) ? trim($label) : null;
                    return [$c->id => ($label ?: "Cliente #{$c->id}")];
                });
        }

        return view('admin.concursos.create', ['clients' => $clients]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo'            => 'required|string|max:100',
            'cliente_id'      => 'required|integer|exists:clients,id',
            'edital_num'      => 'nullable|string|max:100',
            'situacao'        => 'required|string|max:100',
            'titulo'          => 'required|string|max:255',
            'legenda_interna' => 'nullable|string|max:255',
            'ativo'           => 'required|boolean',
            'oculto'          => 'required|boolean',
        ]);

        $data['ativo']  = $request->boolean('ativo');
        $data['oculto'] = $request->boolean('oculto');

        $configs = (array) ($data['configs'] ?? []);
        if (!empty($data['edital_num'])) {
            $configs['numero_edital'] = $data['edital_num'];
        }
        $configs['sequence_inscricao'] = $configs['sequence_inscricao'] ?? 1;
        $data['configs'] = $configs;
        unset($data['edital_num']);

        $concursosTable = (new Concurso)->getTable();
        $concursosCols  = Schema::getColumnListing($concursosTable);

        if (in_array('cliente_id', $concursosCols, true)) {
            // ok
        } elseif (in_array('client_id', $concursosCols, true)) {
            $data['client_id'] = $data['cliente_id']; unset($data['cliente_id']);
        } elseif (in_array('id_cliente', $concursosCols, true)) {
            $data['id_cliente'] = $data['cliente_id']; unset($data['cliente_id']);
        } elseif (in_array('clients_id', $concursosCols, true)) {
            $data['clients_id'] = $data['cliente_id']; unset($data['cliente_id']);
        }

        if (!in_array('oculto', $concursosCols, true) && array_key_exists('oculto', $data) && in_array('ocultar_site', $concursosCols, true)) {
            $data['ocultar_site'] = $data['oculto']; unset($data['oculto']);
        }
        if (!in_array('situacao', $concursosCols, true) && array_key_exists('situacao', $data) && in_array('status', $concursosCols, true)) {
            $data['status'] = $data['situacao']; unset($data['situacao']);
        }

        $payload = array_intersect_key($data, array_flip($concursosCols));

        if (in_array('sequence_inscricao', $concursosCols, true) && !array_key_exists('sequence_inscricao', $payload)) {
            $payload['sequence_inscricao'] = 1;
        }
        if (in_array('configs', $concursosCols, true)) {
            $payload['configs'] = $data['configs'] ?? null;
        }
        if (in_array('extras', $concursosCols, true) && array_key_exists('extras', $data)) {
            $payload['extras'] = $data['extras'];
        }

        $concurso = Concurso::create($payload);

        return redirect()
            ->route('admin.concursos.config', $concurso)
            ->with('success', 'Concurso criado. Agora conclua as configurações.');
    }

    // --------------------------
    // EDIÇÃO BÁSICA
    // --------------------------
    public function edit(Concurso $concurso)
    {
        $concurso->loadMissing(['client','clientLegacy','clientAlt','clientPlural']);
        return view('admin.concursos.edit', compact('concurso'));
    }

    public function update(Request $request, Concurso $concurso)
    {
        $data = $request->validate([
            'titulo'          => 'required|string|max:255',
            'situacao'        => 'nullable|string|max:100',
            'ativo'           => 'nullable|boolean',
            'oculto'          => 'nullable|boolean',
            'legenda_interna' => 'nullable|string|max:255',
        ]);

        $data['ativo']  = $request->boolean('ativo');
        $data['oculto'] = $request->boolean('oculto');

        $concursosCols = Schema::getColumnListing((new Concurso)->getTable());

        if (!in_array('oculto', $concursosCols, true) && array_key_exists('oculto', $data) && in_array('ocultar_site', $concursosCols, true)) {
            $data['ocultar_site'] = $data['oculto']; unset($data['oculto']);
        }
        if (!in_array('situacao', $concursosCols, true) && array_key_exists('situacao', $data) && in_array('status', $concursosCols, true)) {
            $data['status'] = $data['situacao']; unset($data['situacao']);
        }

        $payload = array_intersect_key($data, array_flip($concursosCols));
        $concurso->update($payload);

        return redirect()
            ->route('admin.concursos.edit', $concurso)
            ->with('success', 'Concurso atualizado.');
    }

    // --------------------------
    // SHOW -> redireciona para a VISÃO GERAL
    // --------------------------
    public function show(Concurso $concurso)
    {
        return redirect()->route('admin.concursos.visao-geral', $concurso);
    }

    // --------------------------
    // CONFIGURAÇÕES (tela do seu layout)
    // --------------------------
    public function config(Concurso $concurso)
    {
        $concurso->refresh();
        $concurso->loadMissing(['client','clientLegacy','clientAlt','clientPlural']);

        // Renderiza a view que você já tem (layouts.sigecon + right-menu)
        return view('admin.concursos.config', [
            'concurso'      => $concurso,
            'sidebarActive' => 'config',
        ]);
    }

    public function updateConfig(Request $request, Concurso $concurso)
    {
        $base = $request->validate([
            'titulo'          => 'nullable|string|max:255',
            'situacao'        => 'nullable|string|max:100',
            'ativo'           => 'nullable|boolean',
            'oculto'          => 'nullable|boolean',
            'legenda_interna' => 'nullable|string|max:255',
        ]);

        $base['ativo']  = $request->boolean('ativo');
        $base['oculto'] = $request->boolean('oculto');

        $concursosTable = (new Concurso)->getTable();
        $concursosCols  = Schema::getColumnListing($concursosTable);

        if (!in_array('oculto', $concursosCols, true) && array_key_exists('oculto', $base) && in_array('ocultar_site', $concursosCols, true)) {
            $base['ocultar_site'] = $base['oculto']; unset($base['oculto']);
        }
        if (!in_array('situacao', $concursosCols, true) && array_key_exists('situacao', $base) && in_array('status', $concursosCols, true)) {
            $base['status'] = $base['situacao']; unset($base['situacao']);
        }

        $configsOld = (array) ($concurso->configs ?? []);
        $configsIn  = (array) $request->input('configs', []);

        // --- Novos campos de isenção: normalização ---
        if (array_key_exists('flag_isencao', $configsIn)) {
            $configsIn['flag_isencao'] = ((string)$configsIn['flag_isencao'] === '1') ? 1 : 0;
        }
        if (array_key_exists('permitir_cancelamento_isencao', $configsIn)) {
            $configsIn['permitir_cancelamento_isencao'] = ((string)$configsIn['permitir_cancelamento_isencao'] === '1') ? 1 : 0;
        }

        // Datas em BR -> ISO (mantém original se parse falhar)
        $rawIni = trim((string) data_get($configsIn, 'data_isencao_inicio', ''));
        $rawFim = trim((string) data_get($configsIn, 'data_isencao_fim', ''));
        $isoIni = $rawIni !== '' ? $this->parseDateTime($rawIni) : null;
        $isoFim = $rawFim !== '' ? $this->parseDateTime($rawFim) : null;

        if (array_key_exists('data_isencao_inicio', $configsIn)) {
            $configsIn['data_isencao_inicio'] = $isoIni ?? $rawIni;
        }
        if (array_key_exists('data_isencao_fim', $configsIn)) {
            $configsIn['data_isencao_fim'] = $isoFim ?? $rawFim;
        }

        if (in_array('data_isencao_inicio', $concursosCols, true)) {
            $base['data_isencao_inicio'] = $isoIni;
        }
        if (in_array('data_isencao_fim', $concursosCols, true)) {
            $base['data_isencao_fim'] = $isoFim;
        }

        if (array_key_exists('tipo', $configsIn)) {
            $configsIn['tipo'] = (string) $configsIn['tipo'];
            if (in_array('tipo', $concursosCols, true)) {
                $base['tipo'] = $configsIn['tipo'];
            }
        }

        if (array_key_exists('numero_edital', $configsIn)) {
            $configsIn['numero_edital'] = trim((string) $configsIn['numero_edital']);
        }

        if (array_key_exists('data_edital', $configsIn)) {
            $parsed = $this->parseDate($configsIn['data_edital']);
            $configsIn['data_edital'] = $parsed ?: null;
            if (in_array('edital_data', $concursosCols, true)) {
                $base['edital_data'] = $parsed;
            }
        }

        if (array_key_exists('inscricoes_online', $configsIn)) {
            $io = ((string)$configsIn['inscricoes_online'] === '1' || $configsIn['inscricoes_online'] === 1) ? 1 : 0;
            $configsIn['inscricoes_online'] = $io;
            if (in_array('inscricoes_online', $concursosCols, true)) {
                $base['inscricoes_online'] = $io;
            }
        }

        foreach (['inscricoes_inicio','inscricoes_fim'] as $k) {
            if (array_key_exists($k, $configsIn)) {
                $dt = $this->parseDateTime($configsIn[$k]);
                $configsIn[$k] = $dt;
                if (in_array($k, $concursosCols, true)) {
                    $base[$k] = $dt;
                }
            }
        }

        if (array_key_exists('flag_ocultar_datahora_no_site', $configsIn)) {
            $configsIn['flag_ocultar_datahora_no_site'] =
                ((string)$configsIn['flag_ocultar_datahora_no_site'] === '1') ? 1 : 0;
        }

        if (array_key_exists('sequence_inscricao', $configsIn)) {
            $seq = (int) preg_replace('/\D+/', '', (string) $configsIn['sequence_inscricao']);
            if ($seq < 1) $seq = 1;
            $configsIn['sequence_inscricao'] = $seq;

            if (in_array('sequence_inscricao', $concursosCols, true)) {
                $base['sequence_inscricao'] = $seq;
            }
        }

        $configsNew = array_replace_recursive($configsOld, $configsIn);

        $extrasOld = (array) ($concurso->extras ?? []);
        $extrasIn  = (array) $request->input('extra', []);
        foreach ($extrasIn as $k => $v) {
            if (is_string($v)) $extrasIn[$k] = trim($v);
        }
        $extrasNew = array_replace_recursive($extrasOld, $extrasIn);

        $payload = array_intersect_key($base, array_flip($concursosCols));
        if (in_array('configs', $concursosCols, true)) {
            $payload['configs'] = $configsNew;
        }
        if (in_array('extras', $concursosCols, true)) {
            $payload['extras'] = $extrasNew;
        }

        $concurso->update($payload);

        // ====== SALVAR TIPOS DE ISENÇÃO (pivot) ======
        $tipos = (array) $request->input('tipos_isencao', []);
        $tipos = array_values(array_unique(array_map('intval', $tipos)));

        $flag = (int) data_get($configsNew, 'flag_isencao', 0);
        if ($flag !== 1) {
            $tipos = [];
        }

        DB::transaction(function () use ($concurso, $tipos) {
            DB::table('concurso_tipo_isencao')
                ->where('concurso_id', $concurso->id)
                ->delete();

            if (!empty($tipos)) {
                $now  = now();
                $rows = array_map(fn ($id) => [
                    'concurso_id'     => $concurso->id,
                    'tipo_isencao_id' => $id,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ], $tipos);

                DB::table('concurso_tipo_isencao')->insert($rows);
            }
        });

        // ====== SALVAR TIPOS DE CONDIÇÕES ESPECIAIS (pivot) ======
        $conds = array_values(array_unique(array_map('intval', (array) $request->input('condicoes_especiais', []))));
        $flagCE = (int) data_get($configsNew, 'flag_condicoesespeciais', 0);
        if ($flagCE !== 1) {
            $conds = [];
        }

        DB::transaction(function () use ($concurso, $conds) {
            DB::table('concurso_tipo_condicao_especial')
                ->where('concurso_id', $concurso->id)
                ->delete();

            if (!empty($conds)) {
                $now = now();
                $rows = array_map(fn($id) => [
                    'concurso_id'               => $concurso->id,
                    'tipo_condicao_especial_id' => $id,
                    'created_at'                => $now,
                    'updated_at'                => $now,
                ], $conds);

                DB::table('concurso_tipo_condicao_especial')->insert($rows);
            }
        });

        return back()->with('success', 'Configurações atualizadas.');
    }

    // --------------------------
    // Toggle "ativo" (usado na listagem)
    // --------------------------
    public function toggleAtivo(Concurso $concurso)
    {
        $concurso->ativo = !$concurso->ativo;
        $concurso->save();

        return back()->with('success', 'Status de ativo atualizado.');
    }

    // --------------------------
    // Alias legado
    // --------------------------
    public function legacyIndexDados(Concurso $concurso)
    {
        return redirect()->route('admin.concursos.config', $concurso);
    }

    public function destroy(Concurso $concurso)
    {
        // agora faz soft delete (coluna deleted_at)
        $concurso->delete();
        return redirect()->route('admin.concursos.index')->with('success', 'Concurso excluído.');
    }
}
