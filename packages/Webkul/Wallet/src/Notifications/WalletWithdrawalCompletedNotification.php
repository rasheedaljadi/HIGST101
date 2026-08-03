<?php

namespace Webkul\Wallet\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Webkul\Wallet\Models\WalletWithdrawalRequest;

class WalletWithdrawalCompletedNotification extends Notification
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
            ->subject(trans('wallet::app.notifications.withdrawal-completed.subject') ?? 'تم تحويل مبلغ السحب بنجاح')
            ->greeting(trans('wallet::app.notifications.greeting', ['name' => $notifiable->first_name ?? $notifiable->name ?? 'العميل']))
            ->line(trans('wallet::app.notifications.withdrawal-completed.line', [
                'amount' => core()->formatBasePrice($this->withdrawal->amount),
                'reference' => $this->withdrawal->bank_transaction_reference ?? 'N/A',
            ]) ?? ('تم تحويل مبلغ '.core()->formatBasePrice($this->withdrawal->amount).' إلى حسابك بنجاح.'))
            ->action(trans('wallet::app.notifications.view-withdrawals') ?? 'عرض المحفظة', route('shop.wallet.index'));
    }
}
