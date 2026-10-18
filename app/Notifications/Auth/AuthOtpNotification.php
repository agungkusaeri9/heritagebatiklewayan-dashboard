<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AuthOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $otp,
        public string $purpose = 'reset_password',
        public int $expiresInMinutes = 5
    ) {}

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
        $isReset = $this->purpose === 'reset_password';
        $subject = $isReset
            ? 'Kode OTP Pemulihan Kata Sandi'
            : 'Kode OTP Verifikasi Email';

        $actionText = $isReset
            ? 'pemulihan kata sandi akun Batik Anda'
            : 'verifikasi email akun Batik Anda';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Halo!')
            ->line("Berikut adalah kode OTP untuk {$actionText}:")
            ->line("# {$this->otp}")
            ->line("Kode OTP ini hanya berlaku selama {$this->expiresInMinutes} menit.")
            ->line('Jangan berikan kode ini kepada siapa pun demi keamanan akun Anda.')
            ->line('Jika Anda tidak merasa meminta kode ini, silakan abaikan email ini.');
    }
}
