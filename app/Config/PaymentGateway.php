<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Online payment for membership admission/subscription fees. Same
 * simulated-until-configured pattern as Config\Zoho — see that file's
 * docblock for the general approach.
 *
 * IMPORTANT — this build only *simulates* payment: `driver` defaults to
 * 'simulated' and SimulatedPaymentGateway records a payment as successful
 * immediately, no money moves, no network call. LiveRazorpayGateway is
 * written against Razorpay's documented Orders API but has never been run
 * against a real Razorpay account — there were no credentials to test
 * with (same caveat as the Zoho live driver). Razorpay was picked as the
 * default "live" implementation as the most common gateway for Indian
 * membership/nonprofit collections; a different gateway is one more class
 * against PaymentGatewayInterface, not a rewrite.
 *
 * To go live:
 *
 *   1. Set paymentGateway.driver = live in .env
 *   2. Fill in paymentGateway.razorpayKeyId / razorpayKeySecret in .env
 *   3. Add Razorpay's Checkout.js to the pay view and confirm the webhook/
 *      redirect handling in PaymentController against Razorpay's current
 *      docs — API shapes drift, and this was never tested live.
 *
 * Nothing outside PaymentGatewayFactory needs to change — every caller
 * only ever depends on PaymentGatewayInterface.
 */
class PaymentGateway extends BaseConfig
{
    /** 'simulated' | 'live' */
    public string $driver = 'simulated';

    // ---- Razorpay (live driver only) ----
    public string $razorpayKeyId = '';
    public string $razorpayKeySecret = '';
}
