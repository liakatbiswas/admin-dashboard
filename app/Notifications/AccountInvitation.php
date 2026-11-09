<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $minutes = config('auth.passwords.'.config('fortify.passwords').'.expire');

        return (new MailMessage)
            ->subject(config('app.name').': আপনার অ্যাকাউন্ট তৈরি হয়েছে')
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('আপনার জন্য একটি অ্যাকাউন্ট খোলা হয়েছে। পাসওয়ার্ড সেট করতে নিচের বাটনে ক্লিক করুন।')
            ->action('Set Password', $url)
            ->line("এই লিংক {$minutes} মিনিট পর্যন্ত কার্যকর থাকবে।");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
