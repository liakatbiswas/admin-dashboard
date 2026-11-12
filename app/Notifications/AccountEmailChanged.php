<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountEmailChanged extends Notification
{
    use Queueable;

    public function __construct(
        public string $name,
        public string $oldEmail,
        public string $newEmail,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(config('app.name').': your email address changed')
            ->greeting('Hello '.$this->name.'!')
            ->line("The email address on your account changed from {$this->oldEmail} to {$this->newEmail}.")
            ->line('If you did not make this change, contact an administrator immediately.');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
