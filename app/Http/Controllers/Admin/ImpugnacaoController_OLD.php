<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Concurso;
use App\Models\ImpugnacaoEdital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ImpugnacaoController extends Controller
{
    /**
     * Lista de impugnações.
     */
    public function index(Concurso $concurso, Request $req)
    {
        $q     = trim((string) $req->get('q', ''));
        $sit   = (string) $req->get('sit', '');   // '' | 'pendente' | 'respondida'
        $pub   = (string) $req->get('pub', '');   // '' | '1' | '0'
        $ativo = (string) $req->get('ativo', ''); // '' | '1' | '0'

        $table = ImpugnacaoEdital::resolveTableName();

        $rows = ImpugnacaoEdital::query()
            ->when(Schema::hasColumn($table, 'concurso_id'), function ($w) use ($concurso) {
                $w->where('concurso_id', $concurso->id);
            })
            ->when($q !== '', function ($w) use ($q) {
                $like = "%{$q}%";
                $w->where(function ($x) use ($like) {
                    $x->orWhere('protocolo', 'like', $like)
                      ->orWhere('numero', 'like', $like)
                      ->orWhere('codigo', 'like', $like)
                      ->orWhere('nome', 'like', $like)
                      ->orWhere('remetente_nome', 'like', $like)
                      ->orWhere('autor_nome', 'like', $like)
                      ->orWhere('email', 'like', $like)
                      ->orWhere('remetente_email', 'like', $like)
                      ->orWhere('autor_email', 'like', $like)
                      ->orWhere('mensagem', 'like', $like)
                      ->orWhere('texto', 'like', $like)
                      ->orWhere('descricao', 'like', $like);
                });
            })
            ->when($sit !== '', function ($w) use ($sit) {
                if ($sit === 'pendente') {
                    $w->where(function ($x) {
                        $x->where('respondida', 0)->orWhereNull('respondida')
                          ->orWhere('respondido', 0)->orWhereNull('respondido')
                          ->orWhere('status_respondido', 0)->orWhereNull('status_respondido');
                    });
                } elseif ($sit === 'respondida') {
                    $w->where(function ($x) {
                        $x->where('respondida', 1)
                          ->orWhere('respondido', 1)
                          ->orWhere('status_respondido', 1);
                    });
                }
            })
            ->when($pub !== '', function ($w) use ($pub) {
                $val = (int) $pub;
                $w->where(function ($x) use ($val) {
                    $x->where('publicada', $val)
                      ->orWhere('publicar', $val)
                      ->orWhere('status_publicado', $val);
                });
            })
            ->when($ativo !== '' && Schema::hasColumn($table, 'ativo'), function ($w) use ($ativo) {
                $w->where('ativo', (int) $ativo);
            })
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.concursos.impugnacoes.index', compact('concurso', 'rows', 'q', 'sit', 'pub', 'ativo'));
    }

    /**
     * Form de edição.
     */
    public function edit(Concurso $concurso, ImpugnacaoEdital $impugnacao)
    {
        $this->assertOwner($concurso, $impugnacao);
        return view('admin.concursos.impugnacoes.edit', [
            'concurso'    => $concurso,
            'impugnacao'  => $impugnacao,
        ]);
    }

    /**
     * Atualiza campos básicos (respondida/publicada/ativo/resposta).
     */
    public function update(Concurso $concurso, ImpugnacaoEdital $impugnacao, Request $req)
    {
        $this->assertOwner($concurso, $impugnacao);

        $data = $req->validate([
            'respondida' => ['nullable', 'in:0,1'],
            'publicada'  => ['nullable', 'in:0,1'],
            'ativo'      => ['nullable', 'in:0,1'],
            'resposta'   => ['nullable', 'string'],
        ]);

        $respondida = filter_var($req->input('respondida', 0), FILTER_VALIDATE_BOOLEAN);
        $publicada  = filter_var($req->input('publicada', 0), FILTER_VALIDATE_BOOLEAN);
        $ativo      = filter_var($req->input('ativo', 1), FILTER_VALIDATE_BOOLEAN);
        $resposta   = (string) $req->input('resposta', '');

        $tbl = $impugnacao->getTable();
        $update = [];

        // respondida -> respondido -> status_respondido
        if (Schema::hasColumn($tbl, 'respondida'))          $update['respondida']        = $respondida;
        elseif (Schema::hasColumn($tbl, 'respondido'))      $update['respondido']        = $respondida;
        elseif (Schema::hasColumn($tbl, 'status_respondido')) $update['status_respondido'] = $respondida;

        // publicada -> publicar -> status_publicado
        if (Schema::hasColumn($tbl, 'publicada'))           $update['publicada']         = $publicada;
        elseif (Schema::hasColumn($tbl, 'publicar'))        $update['publicar']          = $publicada;
        elseif (Schema::hasColumn($tbl, 'status_publicado'))$update['status_publicado']  = $publicada;

        // ativo
        if (Schema::hasColumn($tbl, 'ativo'))               $update['ativo']             = $ativo;

        // resposta -> resposta_texto
        if (Schema::hasColumn($tbl, 'resposta'))            $update['resposta']          = $resposta;
        elseif (Schema::hasColumn($tbl, 'resposta_texto'))  $update['resposta_texto']    = $resposta;

        $impugnacao->fill($update)->save();

        return redirect()
            ->route('admin.concursos.impugnacoes.edit', [$concurso, $impugnacao->id])
            ->with('status', 'Impugnação atualizada com sucesso!');
    }

    /**
     * Garante que pertence ao concurso (quando existir a coluna).
     */
    private function assertOwner(Concurso $concurso, ImpugnacaoEdital $impugnacao): void
    {
        $tbl = $impugnacao->getTable();
        if (Schema::hasColumn($tbl, 'concurso_id')) {
            if ((int) $impugnacao->concurso_id !== (int) $concurso->id) {
                abort(404);
            }
        }
    }
}
