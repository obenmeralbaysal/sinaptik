<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InviteMember extends Notification
{
    use Queueable;

    public function __construct(public Invitation $invitation, public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('sinsocial.frontend_url'), '/').'/davet?token='.$this->token;

        return (new MailMessage)
            ->subject('Sinaptik Social çalışma alanı daveti')
            ->line('Bir çalışma alanına davet edildiniz. Bağlantı 7 gün geçerlidir ve tek kullanımlıktır.')
            ->action('Daveti kabul et', $url);
    }
}
