<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConcursoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tipo' => ['required','string','in:concurso,processo_seletivo,seletivo_simplificado,chamada_publica'],
            'client_id' => ['required','exists:clients,id'],
            'numero_edital' => ['nullable','string','max:100'],
            'situacao' => ['required','string','in:rascunho,inscricoes_abertas,inscricoes_encerradas,em_andamento,homologado,finalizado,cancelado'],
            'titulo' => ['required','string','max:255'],
            'legenda_interna' => ['nullable','string','max:255'],
            'ativo' => ['required','boolean'],
            'ocultar_site' => ['required','boolean'],
            'action' => ['nullable','in:save,save_close,save_new'],
        ];
    }
}
