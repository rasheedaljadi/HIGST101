<?php

namespace Webkul\Payment;

use Illuminate\Support\Facades\Config;
use Webkul\Checkout\Contracts\Cart;

class Payment
{
    /**
     * Returns all supported payment methods
     *
     * @return array
     */
    public function getSupportedPaymentMethods()
    {
        return [
            'payment_methods' => $this->getPaymentMethods(),
        ];
    }

    /**
     * Returns all supported payment methods
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        $paymentMethods = [];

        foreach (Config::get('payment_methods') as $paymentMethodConfig) {
            $paymentMethod = app($paymentMethodConfig['class']);

            if ($paymentMethod->isAvailable()) {
                $paymentMethods[] = [
                    'method' => $paymentMethod->getCode(),
                    'method_title' => $paymentMethod->getTitle(),
                    'description' => $paymentMethod->getDescription(),
                    'sort' => $paymentMethod->getSortOrder(),
                    'image' => $paymentMethod->getImage(),
                ];
            }
        }

        usort($paymentMethods, function ($a, $b) {
            if ($a['sort'] == $b['sort']) {
                return 0;
            }

            return ($a['sort'] < $b['sort']) ? -1 : 1;
        });

        return $paymentMethods;
    }

    /**
     * Returns payment redirect url if have any
     *
     * @param  Cart  $cart
     * @return string|null
     */
    public function getRedirectUrl($cart)
    {
        $method = $cart?->payment?->method;

        if (empty($method)) {
            return null;
        }

        $class = Config::get('payment_methods.'.$method.'.class');

        if (empty($class) || ! class_exists($class)) {
            return null;
        }

        $payment = app($class);

        return method_exists($payment, 'getRedirectUrl') ? $payment->getRedirectUrl() : null;
    }

    /**
     * Returns payment method additional information
     *
     * @param  string  $code
     * @return array
     */
    public static function getAdditionalDetails($code)
    {
        if (empty($code)) {
            return [];
        }

        $class = Config::get('payment_methods.'.$code.'.class');

        if (empty($class) || ! class_exists($class)) {
            return [];
        }

        $paymentMethodClass = app($class);

        if (method_exists($paymentMethodClass, 'getAdditionalDetails')) {
            return $paymentMethodClass->getAdditionalDetails();
        }

        return [];
    }
}
