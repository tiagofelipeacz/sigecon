<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConcursoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['superadmin', 'org_admin']);
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'exists:clients,id'],
            'tipo' => ['required', 'in:1,2,3'],
            'situacao' => ['required', 'in:1,2,3,4,5,6'],
            'titulo' => ['required', 'string', 'max:255'],
            'edital_num' => ['nullable', 'string', 'max:255'],
            'edital_data' => ['nullable', 'date'],
            'inscricoes_online' => ['required', 'boolean'],
            'sequence_inscricao' => ['nullable', 'integer', 'min:1'],
            'inscricoes_inicio' => ['nullable', 'date'],
            'inscricoes_fim' => ['nullable', 'date', 'after_or_equal:inscricoes_inicio'],
            'ativo' => ['required', 'boolean'],
            'oculto' => ['required', 'boolean'],
            'arquivado' => ['required', 'boolean'],
            'testes' => ['required', 'boolean'],
            'destacar' => ['required', 'boolean'],
            'analise_documentos' => ['required', 'boolean'],
            'configs' => ['nullable', 'array'],
            'extras' => ['nullable', 'array'],
        ];
    }
}
