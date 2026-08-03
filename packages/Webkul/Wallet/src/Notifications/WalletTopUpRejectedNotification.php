<?php

namespace Webkul\Wallet\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Webkul\Wallet\Models\WalletTopUp;

class WalletTopUpRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public WalletTopUp $topup
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(trans('wallet::app.notifications.topup-rejected.subject') ?? 'تم رفض طلب إيداع الرصيد')
            ->greeting(trans('wallet::app.notifications.greeting', ['name' => $notifiable->first_name ?? $notifiable->name ?? 'العميل']))
            ->line(trans('wallet::app.notifications.topup-rejected.line', [
                'amount' => core()->formatBasePrice($this->topup->amount),
                'reason' => $this->topup->admin_notes ?? 'غير محدد',
            ]) ?? ('عذراً، تم رفض طلب إيداع '.core()->formatBasePrice($this->topup->amount)))
            ->action(trans('wallet::app.notifications.view-wallet') ?? 'عرض المحفظة', route('shop.wallet.index'));
    }
}
