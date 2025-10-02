<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Auth\MustVerifyEmail;

class Candidato extends Authenticatable implements MustVerifyEmailContract
{
    use HasFactory, Notifiable, MustVerifyEmail;

    protected $table = 'candidatos';

    protected $fillable = [
        'nome','cpf','email','telefone','celular','data_nascimento','sexo','estado_civil','foto_path',
        'endereco_cep','endereco_rua','endereco_numero','endereco_complemento','endereco_bairro','cidade','estado',
        'nome_mae','nome_pai','nacionalidade','naturalidade_uf','naturalidade_cidade','nacionalidade_ano_chegada',
        'sistac_nis','qt_filhos','cnh_categoria','id_deficiencia','observacoes_internas',
        // Credenciais (login NÃO é usado; CPF é o login)
        'password','remember_token','email_verified_at','status','last_login_at'
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'data_nascimento'    => 'date',
        'qt_filhos'          => 'integer',
        'email_verified_at'  => 'datetime',
        'last_login_at'      => 'datetime',
        'status'             => 'boolean',
    ];

    /**
     * Auth uses CPF as username field.
     */
    public function getAuthIdentifierName()
    {
        return 'cpf';
    }

    /**
     * Sanitize CPF to digits only on set.
     */
    public function setCpfAttribute($value)
    {
        $digits = preg_replace('/\D+/', '', (string)$value ?? '');
        $this->attributes['cpf'] = $digits !== '' ? $digits : null;
    }

    /**
     * Notification for password reset.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\CandidatoResetPassword($token, $this->email));
    }

    /**
     * Helper to show CPF masked in views if desired.
     */
    public function getCpfMaskedAttribute(): ?string
    {
        $cpf = $this->attributes['cpf'] ?? null;
        if (!$cpf || strlen($cpf) !== 11) return $cpf;
        return substr($cpf,0,3).'.'.substr($cpf,3,3).'.'.substr($cpf,6,3).'-'.substr($cpf,9,2);
    }
}