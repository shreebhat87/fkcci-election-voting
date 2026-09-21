<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Card Not Ready — FKCCI Membership</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="login-wrap">
  <div style="width:100%;max-width:460px;">
    <div class="login-card text-center">
      <div class="icon-circle warning" style="margin:0 auto 14px;">🪪</div>
      <h3>Card Not Ready</h3>
      <?php if (! $rep): ?>
        <p class="text-muted mt-8">No representative found with Member ID <strong><?= esc($memberId) ?></strong>.</p>
      <?php else: ?>
        <p class="text-muted mt-8"><strong><?= esc($rep['name']) ?></strong> (<?= esc($memberId) ?>) doesn't have an RFID tag assigned yet. Assign one from the application's admin page before printing the card.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
