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
            ->subject(config('app.name').': '.__('your email address changed'))
            ->greeting(__('Hello :name!', ['name' => $this->name]))
            ->line(__('The email address on your account changed from :old to :new.', ['old' => $this->oldEmail, 'new' => $this->newEmail]))
            ->line(__('If you did not make this change, contact an administrator immediately.'));
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
