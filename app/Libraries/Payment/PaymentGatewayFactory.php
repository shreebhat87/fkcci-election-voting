<?php

namespace App\Libraries\Payment;

/**
 * The one place that decides simulated vs live, same pattern as
 * ZohoClientFactory. Every caller depends only on PaymentGatewayInterface,
 * so going live later is exactly: set paymentGateway.driver=live and the
 * credentials in .env — no code changes.
 */
class PaymentGatewayFactory
{
    public static function make(): PaymentGatewayInterface
    {
        $config = config('PaymentGateway');

        return $config->driver === 'live'
            ? new LiveRazorpayGateway($config)
            : new SimulatedPaymentGateway();
    }
}
