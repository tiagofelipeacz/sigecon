<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidatoDocumento extends Model
{
    protected $table = 'candidato_documentos';

    protected $fillable = [
        'candidato_id','tipo','numero','arquivo_path','validade','status','obs_admin'
    ];

    protected $casts = [
        'validade' => 'date',
    ];

    public function candidato()
    {
        return $this->belongsTo(Candidato::class, 'candidato_id');
    }
}
