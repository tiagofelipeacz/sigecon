<?php

namespace App\Http\Controllers\Admin\Concursos;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use App\Models\Cronograma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CronogramaController extends Controller
{
    /**
     * Converte vários formatos comuns (BR e ISO) para 'Y-m-d H:i:s'.
     * Retorna null quando não conseguir interpretar.
     */
    private function parseDateTime(?string $v): ?string
    {
        $v = is_string($v) ? trim($v) : '';
        if ($v === '') return null;

        // Formatos comuns primeiro
        foreach (['d/m/Y H:i', 'd/m/Y H:i:s', 'd/m/Y'] as $fmt) {
            try {
                // Para 'd/m/Y' preenchermos 00:00:00 para manter datetime
                $dt = Carbon::createFromFormat($fmt, $v);
                if ($fmt === 'd/m/Y') $dt = $dt->startOfDay();
                return $dt->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {}
        }

        // Tenta parse livre (suporta 'Y-m-d', 'Y-m-d H:i:s', etc.)
        try { return Carbon::parse($v)->format('Y-m-d H:i:s'); } catch (\Throwable $e) {}

        return null;
    }

    /**
     * Lista os itens do cronograma.
     * Passa $menu_active para o partial do menu marcar "Cronograma".
     */
    public function index(Concurso $concurso)
    {
        $itens = Cronograma::where('concurso_id', $concurso->id)
            ->orderBy('ordem')
            ->orderBy('inicio')
            ->get();

        // Mantém padrão do restante do admin (menu ativo)
        $menu_active = 'cronograma';

        return view('admin.concursos.cronograma', compact('concurso', 'itens', 'menu_active'));
    }

    /**
     * Cria um item do cronograma.
     */
    public function store(Request $request, Concurso $concurso)
    {
        $data = $request->validate([
            'titulo'     => 'required|string|max:255',
            'descricao'  => 'nullable|string',
            'local'      => 'nullable|string|max:255',
            'inicio'     => 'nullable|string',
            'fim'        => 'nullable|string',
            'publicar'   => 'nullable|boolean',
        ]);

        $data['inicio']     = $this->parseDateTime($data['inicio'] ?? null);
        $data['fim']        = $this->parseDateTime($data['fim'] ?? null);
        $data['publicar']   = $request->boolean('publicar');
        $data['concurso_id']= $concurso->id;

        // Se vier fim antes de início, ajusta para não quebrar filtros
        if ($data['inicio'] && $data['fim'] && $data['fim'] < $data['inicio']) {
            $data['fim'] = $data['inicio'];
        }

        // próxima ordem
        $max = Cronograma::where('concurso_id', $concurso->id)->max('ordem');
        $data['ordem'] = (int) $max + 1;

        Cronograma::create($data);

        return back()->with('success', 'Item do cronograma adicionado.');
    }

    /**
     * Atualiza um item do cronograma.
     */
    public function update(Request $request, Concurso $concurso, Cronograma $item)
    {
        abort_unless($item->concurso_id === $concurso->id, 404);

        $data = $request->validate([
            'titulo'     => 'required|string|max:255',
            'descricao'  => 'nullable|string',
            'local'      => 'nullable|string|max:255',
            'inicio'     => 'nullable|string',
            'fim'        => 'nullable|string',
            'publicar'   => 'nullable|boolean',
            'ordem'      => 'nullable|integer|min:1',
        ]);

        $data['inicio']   = $this->parseDateTime($data['inicio'] ?? null);
        $data['fim']      = $this->parseDateTime($data['fim'] ?? null);
        $data['publicar'] = $request->boolean('publicar');

        if ($data['inicio'] && $data['fim'] && $data['fim'] < $data['inicio']) {
            $data['fim'] = $data['inicio'];
        }

        // Reordenar, se necessário
        if (isset($data['ordem']) && $data['ordem'] !== $item->ordem) {
            $nova = max(1, (int)$data['ordem']);
            DB::transaction(function () use ($concurso, $item, $nova) {
                if ($nova > $item->ordem) {
                    Cronograma::where('concurso_id', $concurso->id)
                        ->whereBetween('ordem', [$item->ordem + 1, $nova])
                        ->decrement('ordem');
                } else {
                    Cronograma::where('concurso_id', $concurso->id)
                        ->whereBetween('ordem', [$nova, $item->ordem - 1])
                        ->increment('ordem');
                }
                $item->ordem = $nova;
                $item->save();
            });
            unset($data['ordem']);
        }

        $item->update($data);

        return back()->with('success', 'Item atualizado.');
    }

    /**
     * Remove um item e corrige a ordem dos demais.
     */
    public function destroy(Concurso $concurso, Cronograma $item)
    {
        abort_unless($item->concurso_id === $concurso->id, 404);

        DB::transaction(function () use ($concurso, $item) {
            $ordem = $item->ordem;
            $item->delete();
            Cronograma::where('concurso_id', $concurso->id)
                ->where('ordem', '>', $ordem)
                ->decrement('ordem');
        });

        return back()->with('success', 'Item removido.');
    }

    /**
     * Atualiza ordens em massa: espera um array [id => ordem].
     */
    public function reorder(Request $request, Concurso $concurso)
    {
        $ordens = (array) $request->input('ordem', []); // [id => ordem]

        DB::transaction(function () use ($concurso, $ordens) {
            foreach ($ordens as $id => $ordem) {
                $it = Cronograma::where('concurso_id', $concurso->id)
                    ->where('id', (int)$id)
                    ->first();
                if ($it) $it->update(['ordem' => max(1, (int)$ordem)]);
            }
        });

        return back()->with('success', 'Ordem atualizada.');
    }

    /**
     * Alterna a publicação (visibilidade) do item.
     */
    public function togglePublicar(Concurso $concurso, Cronograma $item)
    {
        abort_unless($item->concurso_id === $concurso->id, 404);

        $item->publicar = !$item->publicar;
        $item->save();

        return back()->with('success', 'Visibilidade atualizada.');
    }
}
