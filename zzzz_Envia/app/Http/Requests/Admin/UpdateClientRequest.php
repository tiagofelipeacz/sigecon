<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente'       => ['required','string','max:255'],
            'email'         => ['nullable','email','max:255'],
            'cnpj'          => ['nullable','string','max:20'],
            'razao_social'  => ['nullable','string','max:255'],
            'website'       => ['nullable','url','max:255'],
            'observacoes'   => ['nullable','string'],

            'cep'           => ['nullable','string','max:9'],
            'endereco'      => ['nullable','string','max:255'],
            'numero'        => ['nullable','string','max:20'],
            'complemento'   => ['nullable','string','max:255'],
            'bairro'        => ['nullable','string','max:120'],
            'cidade'        => ['nullable','string','max:120'],
            'estado'        => ['nullable','string','size:2'],

            // 👇 Aceita a nova imagem (opcional)
            'logo'          => ['sometimes','nullable','image','mimes:jpg,jpeg,png,webp,svg','max:4096'],
        ];
    }
}
