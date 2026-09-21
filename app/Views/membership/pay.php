<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment — FKCCI Membership</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<style>
  .pay-wrap { max-width: 460px; margin: 40px auto; padding: 0 16px; }
  .amount { font-size: 34px; font-weight: 800; color: var(--navy-900); text-align: center; margin: 6px 0 18px; }
</style>
</head>
<body>
<div class="pay-wrap">
  <div class="text-center mb-16">
    <div class="brand-mark" style="margin:0 auto 10px;"><img src="<?= base_url('assets/img/fkcci-mark.png') ?>" alt="FKCCI"></div>
    <h2 style="font-size:18px;">Membership Payment</h2>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger mb-16"><div class="alert-icon">⚠️</div><div><p><?= esc(session()->getFlashdata('error')) ?></p></div></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><h3><?= esc($application['company']['name']) ?></h3></div>
    <div class="flex-between" style="font-size:13px;"><span class="text-muted">Application Ref</span><strong><?= esc($application['application_ref']) ?></strong></div>
    <div class="flex-between mt-8" style="font-size:13px;"><span class="text-muted">Category</span><strong><?= esc(ucfirst($application['company']['membership_category'] ?? '')) ?></strong></div>

    <div class="amount">₹<?= number_format((float) $application['total_fee'], 2) ?></div>

    <?php if ($simulated): ?>
      <div class="alert alert-warning mb-16">
        <div class="alert-icon">🧪</div>
        <div><p>Payment gateway is running in <strong>simulated</strong> mode — no real money moves. Clicking Pay Now records this as paid immediately, for demo/testing.</p></div>
      </div>
      <?= form_open('membership/pay/' . esc($application['application_ref'], 'attr') . '/confirm') ?>
        <input type="hidden" name="order_id" value="<?= esc($order['order_id']) ?>">
        <button class="btn btn-success btn-lg btn-block" type="submit">Pay Now (Simulated) →</button>
      <?= form_close() ?>
    <?php else: ?>
      <!-- Live mode: integrate Razorpay Checkout.js here, keyed to
           order.order_id, and POST razorpay_order_id/razorpay_payment_id/
           razorpay_signature to this same confirm route on success. Never
           tested against a real Razorpay account — see Config\PaymentGateway. -->
      <p class="text-muted text-center">Payment integration pending gateway configuration.</p>
    <?php endif; ?>
  </div>

  <p class="hint text-center mt-16">You can return to this page any time using your application reference at <a href="<?= base_url('membership/status') ?>">status lookup</a>.</p>
</div>
<?= view('partials/footer') ?>
</body>
</html>
