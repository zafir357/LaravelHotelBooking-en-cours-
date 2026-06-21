<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// MAIL_MAILER=log en local : cette notification n'envoie pas un vrai email,
// elle écrit son contenu (donc le lien) dans storage/logs/laravel.log — c'est
// volontaire pour développer/tester sans configurer de vrai service d'email.
// Pas de ShouldQueue : on veut que le lien apparaisse immédiatement dans le
// log, sans dépendre d'un worker de queue (`php artisan queue:work`) qui ne
// tourne pas forcément en développement.
class SetPasswordInvite extends Notification
{
    public function __construct(
        private readonly string $url,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have been invited as a receptionist')
            ->greeting('Hello!')
            ->line('An account has been created for you at Maison Bellevue.')
            ->action('Set your password', $this->url)
            ->line('This link expires in 48 hours.');
    }
}
