<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = url(route('admin.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('รีเซ็ตรหัสผ่านผู้ดูแลระบบ SRP Admin')
            ->greeting('สวัสดี')
            ->line('เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีผู้ดูแลระบบของคุณ')
            ->action('ตั้งรหัสผ่านใหม่', $resetUrl)
            ->line('ลิงก์นี้จะหมดอายุภายใน 60 นาที')
            ->line('หากคุณไม่ได้เป็นผู้ร้องขอ สามารถละเว้นอีเมลฉบับนี้ได้');
    }
}
