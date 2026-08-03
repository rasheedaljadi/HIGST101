<?php

namespace Webkul\Wallet\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Webkul\Wallet\Models\WalletWithdrawalRequest;

class WalletWithdrawalRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public WalletWithdrawalRequest $withdrawal
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(trans('wallet::app.notifications.withdrawal-rejected.subject') ?? 'تم رفض طلب السحب وإعادة المبلغ')
            ->greeting(trans('wallet::app.notifications.greeting', ['name' => $notifiable->first_name ?? $notifiable->name ?? 'العميل']))
            ->line(trans('wallet::app.notifications.withdrawal-rejected.line', [
                'amount' => core()->formatBasePrice($this->withdrawal->amount),
                'reason' => $this->withdrawal->rejection_reason ?? 'غير محدد',
            ]) ?? ('تم رفض طلب سحب '.core()->formatBasePrice($this->withdrawal->amount).' وإعادة المبلغ لرصيدك المتاح.'))
            ->action(trans('wallet::app.notifications.view-withdrawals') ?? 'عرض المحفظة', route('shop.wallet.index'));
    }
}
