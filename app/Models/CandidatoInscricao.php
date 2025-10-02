<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidatoInscricao extends Model
{
    protected $table = 'candidato_inscricoes';

    protected $fillable = [
        'candidato_id','concurso_id','cargo_id','localidade_id','item_id','protocolo',
        'status','taxa_inscricao','comprovante_pdf_path','pagamento_status','extras'
    ];

    protected $casts = [
        'taxa_inscricao' => 'decimal:2',
        'extras' => 'array',
    ];

    public function candidato()
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }
}
