<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceAlert extends Notification
{
    use Queueable;

    public function __construct(public string $title, public string $body) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->title)->line($this->body);
    }

    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body];
    }
}
