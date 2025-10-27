<?php

namespace App\Notifications;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public string $token;

    /**
     * @param string $token token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * @param object $notifiable notifiable
     *
     * @return string[]
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param mixed $notifiable notifiable
     *
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $url = Common::buildResetPasswordUrl($this->token, $notifiable->email);

        return (new MailMessage())
            ->subject('【Sisuta】パスワード再設定のご案内')
            ->line("{$notifiable->full_name} 様")
            ->line('平素より Sisuta をご利用いただきありがとうございます。')
            ->line('パスワード再設定のリクエストを受け付けました。')
            ->line('以下のリンクから新しいパスワードを設定してください：')
            ->action('👉 パスワードを再設定する', $url)
            ->line('ご注意：')
            ->line('本リンクの有効期限は 24時間 です。')
            ->line('期限を過ぎた場合は、再度パスワード再設定をお申し込みください。')
            ->line('本メールにお心当たりがない場合は、破棄していただければアカウントに影響はありません。')
            ->line('よろしくお願いいたします。');
    }

    /**
     * @param object $notifiable notifiable
     *
     * @return array
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
