<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;

class CandidatoVerifyEmail extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $verificationUrl = URL::temporarySignedRoute(
            'candidato.verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );

        return (new MailMessage)
            ->subject('Confirme seu e-mail - Área do Candidato')
            ->greeting('Olá!')
            ->line('Clique no botão abaixo para confirmar seu endereço de e-mail.')
            ->action('Confirmar e-mail', $verificationUrl)
            ->line('Se você não criou uma conta, nenhuma ação adicional é necessária.');
    }
}