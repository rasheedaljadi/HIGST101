@extends('shop::layouts.account')

@section('page_title')
    {{ __('wallet::app.shop.withdrawal.title') }}
@stop

@section('account-content')
<div class="flex flex-col gap-6 max-w-lg">
    <p class="text-2xl font-bold text-gray-800 dark:text-white">
        {{ __('wallet::app.shop.withdrawal.title') }}
    </p>

    <div class="rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 p-4 text-white">
        <p class="text-sm opacity-80">{{ __('wallet::app.shop.wallet.available-balance') }}</p>
        <p class="text-2xl font-bold">{{ core()->formatBasePrice($wallet->available_balance) }}</p>
    </div>

    <form action="{{ route('shop.customer.wallet.withdrawal.store') }}" method="POST">
        @csrf
        <div class="flex flex-col gap-4">
            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required">Amount</x-shop::form.control-group.label>
                <x-shop::form.control-group.control type="number" name="amount" step="0.01" min="{{ $minAmount }}" :value="old('amount')" />
                <x-shop::form.control-group.error control-name="amount" />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required">Beneficiary Name</x-shop::form.control-group.label>
                <x-shop::form.control-group.control type="text" name="beneficiary_name" :value="old('beneficiary_name')" />
                <x-shop::form.control-group.error control-name="beneficiary_name" />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required">Bank Name</x-shop::form.control-group.label>
                <x-shop::form.control-group.control type="text" name="bank_name" :value="old('bank_name')" />
                <x-shop::form.control-group.error control-name="bank_name" />
            </x-shop::form.control-group>

            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required">IBAN</x-shop::form.control-group.label>
                <x-shop::form.control-group.control type="text" name="iban" :value="old('iban')" />
                <x-shop::form.control-group.error control-name="iban" />
            </x-shop::form.control-group>

            <button type="submit" class="primary-button w-full mt-4">
                {{ __('wallet::app.shop.withdrawal.title') }}
            </button>
        </div>
    </form>
</div>
@stop
