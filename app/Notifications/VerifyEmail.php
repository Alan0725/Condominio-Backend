<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends BaseVerifyEmail
{
    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirma tu correo electrónico - Condominio App')
            ->greeting('¡Hola!')
            ->line('Gracias por registrarte en la aplicación del condominio.')
            ->line('Por favor confirma tu dirección de correo electrónico haciendo clic en el siguiente botón.')
            ->action('Confirmar correo electrónico', $url)
            ->line('Si tú no creaste esta cuenta, no necesitas realizar ninguna acción.')
            ->salutation('Saludos, Administración del Condominio');
    }
}
