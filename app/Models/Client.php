<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cliente',
        'email',
        'cnpj',
        'razao_social',
        'website',
        'logo_path',
        'observacoes',
        'cep',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
    ];

    // (opcional nas versões atuais, mas não atrapalha)
    protected $casts = [
        'deleted_at' => 'datetime',
    ];
}
