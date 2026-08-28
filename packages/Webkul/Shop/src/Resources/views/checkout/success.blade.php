<x-shop::layouts
	:has-header="true"
	:has-feature="false"
	:has-footer="true"
>
    <!-- Page Title -->
    <x-slot:title>
		@lang('shop::app.checkout.success.thanks')
    </x-slot>

	<!-- Page content -->
	<div class="container mt-8 px-[60px] max-lg:px-8">
		<div class="grid place-items-center gap-y-5 max-md:gap-y-2.5">
			{{ view_render_event('bagisto.shop.checkout.success.image.before', ['order' => $order]) }}

			<img
				class="max-md:h-[100px] max-md:w-[100px]"
				src="{{ bagisto_asset('images/thank-you.png') }}"
				alt="@lang('shop::app.checkout.success.thanks')"
				title="@lang('shop::app.checkout.success.thanks')"
                loading="lazy"
                decoding="async"
			>

			{{ view_render_event('bagisto.shop.checkout.success.image.after', ['order' => $order]) }}

			<p class="text-xl max-md:text-sm">
				@if (auth()->guard('customer')->user())
					@lang('shop::app.checkout.success.order-id-info', [
						'order_id' => '<a class="text-blue-700" href="'.route('shop.customers.account.orders.view', $order->id).'">'.$order->increment_id.'</a>'
					])
				@else
					@lang('shop::app.checkout.success.order-id-info', ['order_id' => $order->increment_id])
				@endif
			</p>

			<p class="font-medium md:text-2xl">
				@lang('shop::app.checkout.success.thanks')
			</p>

			<p class="text-xl text-zinc-500 max-md:text-center max-md:text-xs">
				@if (! empty($order->checkout_message))
					{!! nl2br($order->checkout_message) !!}
				@else
					@lang('shop::app.checkout.success.info')
				@endif
			</p>

			<!-- Order Summary Box -->
			<div class="w-full max-w-xl rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 text-right my-3">
				<h3 class="text-base font-bold text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-gray-800 pb-3 mb-4 flex items-center justify-between">
					<span>ملخص تفاصيل الطلب</span>
					<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400">
						#{{ $order->increment_id }}
					</span>
				</h3>

				<div class="space-y-3 text-sm">
					<div class="flex justify-between items-center text-zinc-600 dark:text-gray-300">
						<span>إجمالي المنتجات:</span>
						<span class="font-semibold text-zinc-900 dark:text-white">
							{{ core()->formatPrice($order->sub_total, $order->order_currency_code) }}
						</span>
					</div>

					@if ($order->haveStockableItems())
						<div class="flex justify-between items-center text-zinc-600 dark:text-gray-300">
							<div class="flex items-center gap-1.5">
								<span>رسوم التوصيل والشحن:</span>
								@if ($order->shipping_title)
									<span class="text-xs text-zinc-400">({{ $order->shipping_title }})</span>
								@endif
							</div>
							<span class="font-semibold {{ (float) $order->shipping_amount > 0 ? 'text-navyBlue dark:text-blue-400' : 'text-emerald-600' }}">
								{{ (float) $order->shipping_amount > 0 ? core()->formatPrice($order->shipping_amount, $order->order_currency_code) : 'مجاناً' }}
							</span>
						</div>
					@endif

					@if ((float) $order->discount_amount > 0)
						<div class="flex justify-between items-center text-emerald-600 dark:text-emerald-400">
							<span>الخصم:</span>
							<span class="font-semibold">
								- {{ core()->formatPrice($order->discount_amount, $order->order_currency_code) }}
							</span>
						</div>
					@endif

					<!-- Payment Method Info -->
					@php
						$payment = $order->payment;
						$additional = $payment?->additional ?? [];
						$snapshot = $additional['offline_payment_snapshot'] ?? null;
						$paymentTitle = $payment?->method_title ?: core()->getConfigData('sales.payment_methods.' . ($payment?->method ?? '') . '.title');
					@endphp

					<div class="border-t border-zinc-100 dark:border-gray-800 pt-3">
						<div class="flex justify-between items-center text-zinc-600 dark:text-gray-300">
							<span>طريقة الدفع المختارة:</span>
							<span class="font-bold text-zinc-900 dark:text-white">
								{{ $paymentTitle }}
							</span>
						</div>

						@if (! empty($snapshot))
							<div class="mt-2.5 p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/60 dark:border-zinc-700/60 text-xs text-zinc-600 dark:text-zinc-300 space-y-1">
								<p><strong>حساب التحويل:</strong> {{ $snapshot['account']['display_name'] ?? '' }} ({{ $snapshot['account']['provider_name'] ?? '' }})</p>
								<p><strong>اسم المستلم:</strong> {{ $snapshot['account']['recipient_name'] ?? '' }}</p>
								<p><strong>رقم الحساب / المحفظة:</strong> <span class="font-bold text-zinc-900 dark:text-white">{{ $snapshot['destination']['account_identifier'] ?? '' }}</span></p>
							</div>
						@endif
					</div>

					<div class="flex justify-between items-center border-t border-zinc-100 dark:border-gray-800 pt-3 text-base font-bold text-zinc-900 dark:text-white">
						<span>المبلغ الإجمالي:</span>
						<span class="text-lg text-emerald-600 dark:text-emerald-400">
							{{ core()->formatPrice($order->grand_total, $order->order_currency_code) }}
						</span>
					</div>
				</div>
			</div>

			{{ view_render_event('bagisto.shop.checkout.success.continue-shopping.before', ['order' => $order]) }}

			<a href="{{ route('shop.home.index') }}">
				<div class="w-max cursor-pointer rounded-2xl bg-navyBlue px-11 py-3 text-center text-base font-medium text-white max-md:rounded-lg max-md:px-6 max-md:py-1.5">
             		@lang('shop::app.checkout.cart.index.continue-shopping')
				</div>
			</a>

			{{ view_render_event('bagisto.shop.checkout.success.continue-shopping.after', ['order' => $order]) }}
		</div>
	</div>
</x-shop::layouts>
