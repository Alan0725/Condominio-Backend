<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordCode extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
    ) {
    }

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Código para recuperar tu contraseña - Condominio App')
            ->greeting('¡Hola!')
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta en la aplicación del condominio.')
            ->line("Tu código de verificación es: **{$this->code}**")
            ->line('Este código expira en 15 minutos.')
            ->line('Si tú no solicitaste este cambio, puedes ignorar este correo; tu contraseña seguirá siendo la misma.')
            ->salutation('Saludos, Administración del Condominio');
    }
}
