<?php

namespace Webkul\Wallet\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Webkul\Wallet\Models\WalletWithdrawalRequest;

class WalletWithdrawalSubmittedNotification extends Notification
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
            ->subject(trans('wallet::app.notifications.withdrawal-submitted.subject'))
            ->greeting(trans('wallet::app.notifications.greeting', ['name' => $notifiable->name]))
            ->line(trans('wallet::app.notifications.withdrawal-submitted.line', [
                'amount' => core()->formatBasePrice($this->withdrawal->amount),
            ]))
            ->action(trans('wallet::app.notifications.view-withdrawals'), route('shop.customer.wallet.withdrawal.index'));
    }
}
