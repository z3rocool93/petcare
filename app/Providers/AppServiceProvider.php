<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- Añade esta línea
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void { /* ... */ }

    public function boot(): void
    {
        // Forzar HTTPS si estamos en el entorno de Azure
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }

        // Personalizamos el correo de recuperación de contraseña
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Recuperar Contraseña - PetCare') // Asunto en español
                ->greeting('¡Hola, ' . $notifiable->name . '!')
                ->line('Recibiste este correo porque solicitaste restablecer la contraseña de tu cuenta en PetCare.')
                ->action('Restablecer Contraseña', $url) // Botón en español
                ->line('Este enlace de recuperación expirará en 60 minutos.')
                ->line('Si no solicitaste este cambio, puedes ignorar este correo de forma segura.')
                ->salutation('Saludos, el equipo de PetCare 🐾');
        });
    }
}
