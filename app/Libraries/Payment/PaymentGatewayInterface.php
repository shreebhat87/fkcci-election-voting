<?php

namespace App\Libraries\Payment;

interface PaymentGatewayInterface
{
    /**
     * Start a payment for the given amount (in rupees). `receiptRef` is our
     * own application_ref, passed through so the gateway's dashboard/webhook
     * can be tied back to the right application.
     *
     * @return array{order_id: string, checkout_url: ?string}
     *   checkout_url is null for the simulated gateway (we render our own
     *   "Pay Now" page instead of redirecting to a real checkout).
     */
    public function createOrder(float $amount, string $receiptRef): array;

    /**
     * Verify a completed payment from whatever the gateway handed back
     * (POSTed form fields for a redirect flow, or a webhook payload).
     *
     * @return array{success: bool, order_id: ?string, payment_id: ?string}
     */
    public function verifyPayment(array $callbackData): array;
}
