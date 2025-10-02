<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoVagaEspecial extends Model
{
    protected $table = 'tipos_vagas_especiais';

    protected $fillable = [
        'nome','ordem','ativo',
        'cliente_id','grupo',
        'sistac','necessita_laudo','laudo_obrigatorio',
        'informar_tipo_deficiencia','autodeclaracao',
        'envio_arquivo','info_candidato',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'sistac' => 'boolean',
        'necessita_laudo' => 'boolean',
        'laudo_obrigatorio' => 'boolean',
        'informar_tipo_deficiencia' => 'boolean',
        'autodeclaracao' => 'boolean',
    ];
}
