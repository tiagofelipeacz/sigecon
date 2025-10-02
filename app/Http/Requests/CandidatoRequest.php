<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CandidatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('candidato')?->id ?? null;

        return [
            'nome'  => ['required','string','max:255'],
            'cpf'   => [
                'nullable','string','max:14',
                // aceita 000.000.000-00 ou 00000000000; banco já tem unique
                Rule::unique('candidatos','cpf')->ignore($id),
            ],
            'email' => ['nullable','email','max:150', Rule::unique('candidatos','email')->ignore($id)],
            'telefone' => ['nullable','string','max:50'],
            'celular'  => ['nullable','string','max:50'],
            'data_nascimento' => ['nullable','date'],
            'sexo' => ['nullable', Rule::in(['M','F','O'])],
            'estado_civil' => ['nullable', Rule::in(['solteiro','casado','separado','divorciado','viuvo','outro'])],
            'endereco_cep' => ['nullable','string','max:12'],
            'endereco_rua' => ['nullable','string','max:255'],
            'endereco_numero' => ['nullable','string','max:20'],
            'endereco_complemento' => ['nullable','string','max:255'],
            'endereco_bairro' => ['nullable','string','max:255'],
            'cidade' => ['nullable','string','max:255'],
            'estado' => ['nullable','string','size:2'],
            'nome_mae' => ['nullable','string','max:255'],
            'nome_pai' => ['nullable','string','max:255'],
            'nacionalidade' => ['nullable','string','max:100'],
            'naturalidade_uf' => ['nullable','string','size:2'],
            'naturalidade_cidade' => ['nullable','string','max:255'],
            'nacionalidade_ano_chegada' => ['nullable','integer'],
            'sistac_nis' => ['nullable','string','max:20'],
            'qt_filhos' => ['nullable','integer'],
            'cnh_categoria' => ['nullable','string','max:10'],
            'id_deficiencia' => ['nullable','integer'],
            'observacoes_internas' => ['nullable','string'],
            'status' => ['nullable','boolean'],
            'foto'   => ['nullable','image','mimes:jpg,jpeg,png','max:3072'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome do candidato.',
            'email.email'   => 'Informe um e-mail válido.',
            'estado.size'   => 'UF deve ter 2 letras (ex.: CE).',
        ];
    }
}
