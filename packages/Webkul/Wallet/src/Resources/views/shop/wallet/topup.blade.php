@extends('shop::layouts.account')

@section('page_title')
    {{ __('wallet::app.shop.topup.title') }}
@stop

@section('account-content')
<div class="flex flex-col gap-6 max-w-lg">
    <p class="text-2xl font-bold text-gray-800 dark:text-white">
        {{ __('wallet::app.shop.topup.title') }}
    </p>

    <form action="{{ route('shop.customer.wallet.topup.initiate') }}" method="POST">
        @csrf

        <div class="flex flex-col gap-4">
            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required">
                    {{ __('wallet::app.admin.wallet.deposits.amount') }}
                </x-shop::form.control-group.label>
                <x-shop::form.control-group.control
                    type="number"
                    name="amount"
                    step="0.01"
                    min="{{ $minAmount }}"
                    placeholder="{{ $minAmount }}"
                    :value="old('amount')"
                />
                <x-shop::form.control-group.error control-name="amount" />
            </x-shop::form.control-group>

            <input type="hidden" name="payment_method" value="bank_transfer" />

            <button type="submit" class="primary-button w-full mt-4">
                {{ __('wallet::app.shop.topup.title') }}
            </button>
        </div>
    </form>
</div>
@stop
