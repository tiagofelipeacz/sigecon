<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCondicaoEspecial extends Model
{
    protected $table = 'tipos_condicao_especial';

    protected $fillable = [
        'grupo',
        'titulo',
        'exibir_observacoes',
        'necessita_laudo_medico',
        'laudo_obrigatorio',
        'exige_arquivo_outros',
        'tamanho_fonte_especial',
        'ativo',
        'impressao_duplicada',
        'info_candidato',
    ];

    protected $casts = [
        'exibir_observacoes'     => 'boolean',
        'necessita_laudo_medico' => 'boolean',
        'laudo_obrigatorio'      => 'boolean',
        'exige_arquivo_outros'   => 'boolean',
        'ativo'                  => 'boolean',
        'impressao_duplicada'    => 'boolean',
    ];
}
