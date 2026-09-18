<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Slip Verification — FKCCI Election</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<style>
  body { background: var(--navy-900); }
  .verify-wrap { max-width: 440px; margin: 0 auto; padding: 24px 16px 48px; }
  .verify-banner { border-radius: var(--radius-lg); padding: 28px 22px; text-align: center; color: #fff; margin-bottom: 18px; }
  .verify-banner .icon { font-size: 46px; margin-bottom: 8px; }
  .verify-banner.ok { background: linear-gradient(135deg, #1f9d55, #177a42); }
  .verify-banner.bad { background: linear-gradient(135deg, #c0392b, #8f2a20); }
  .verify-banner.void { background: linear-gradient(135deg, #b7791f, #8f5d13); }
  .verify-card { background: #fff; border-radius: var(--radius-lg); padding: 22px; box-shadow: var(--shadow-lg); }
</style>
</head>
<body>

<div class="verify-wrap">
  <div class="text-center" style="margin: 18px 0 20px;">
    <div class="brand-mark" style="margin:0 auto 10px;"><img src="<?= base_url('assets/img/fkcci-mark.png') ?>" alt="FKCCI"></div>
    <h2 style="color:#fff;">Voting Slip Verification</h2>
    <p style="color:#b8c4da;">FKCCI Election 2026</p>
  </div>

  <?php if (! $vote): ?>
    <div class="verify-banner bad">
      <div class="icon">✕</div>
      <h3 style="color:#fff;">Slip Not Found</h3>
      <p style="color:#fdeceb;">This QR code does not match any issued voting slip.</p>
    </div>

  <?php elseif ($vote['status'] === 'void'): ?>
    <div class="verify-banner void">
      <div class="icon">⚠</div>
      <h3 style="color:#fff;">Slip Voided</h3>
      <p style="color:#fbf0d9;">This slip was cancelled by an administrator and is no longer valid.</p>
    </div>
    <div class="verify-card">
      <div class="flex gap-12" style="align-items:center;">
        <?php if ($photoUrl): ?>
          <div class="photo-avatar avatar-lg" style="opacity:.6;"><img src="<?= esc($photoUrl) ?>" alt="" style="width:100%;height:100%;object-fit:cover;"></div>
        <?php else: ?>
          <div class="avatar avatar-lg" style="background:#8592a3;"><?= esc(initials($vote['member_name'])) ?></div>
        <?php endif; ?>
        <div>
          <h3><?= esc($vote['member_name']) ?></h3>
          <p><?= esc($vote['company_name']) ?></p>
        </div>
      </div>
      <div class="mt-16" style="font-size:13px;">
        <div class="flex-between mt-8"><span class="text-muted">Slip No.</span><strong><?= esc($vote['serial_no']) ?></strong></div>
        <div class="flex-between mt-8"><span class="text-muted">Void reason</span><strong><?= esc($vote['void_reason'] ?: '—') ?></strong></div>
      </div>
    </div>

  <?php else: ?>
    <div class="verify-banner ok">
      <div class="icon">✓</div>
      <h3 style="color:#fff;">Valid Voting Slip</h3>
      <p style="color:#e3f8ec;">Issued by FKCCI Election System</p>
    </div>
    <div class="verify-card">
      <div class="flex gap-12" style="align-items:center;">
        <?php if ($photoUrl): ?>
          <div class="photo-avatar avatar-lg"><img src="<?= esc($photoUrl) ?>" alt="" style="width:100%;height:100%;object-fit:cover;"></div>
        <?php else: ?>
          <div class="avatar avatar-lg" style="background:#1d4e89;"><?= esc(initials($vote['member_name'])) ?></div>
        <?php endif; ?>
        <div>
          <h3><?= esc($vote['member_name']) ?></h3>
          <p><?= esc($vote['designation'] ?: '') ?> · <?= esc($vote['company_name']) ?></p>
          <?php if (! $photoUrl): ?><p class="no-photo-note">📷 No photo on file</p><?php endif; ?>
        </div>
      </div>
      <div class="mt-16" style="font-size:13px;">
        <div class="flex-between mt-8"><span class="text-muted">Member ID</span><strong><?= esc($vote['member_code']) ?></strong></div>
        <div class="flex-between mt-8"><span class="text-muted">Company</span><strong style="text-align:right; max-width:220px;"><?= esc($vote['company_name']) ?></strong></div>
        <div class="flex-between mt-8"><span class="text-muted">Slip No.</span><strong><?= esc($vote['serial_no']) ?></strong></div>
        <div class="flex-between mt-8"><span class="text-muted">Issued at</span><strong>Counter <?= esc($vote['counter_no']) ?>, <?= esc($vote['issued_at']) ?></strong></div>
      </div>
    </div>
  <?php endif; ?>

  <p class="text-center mt-24" style="color:#8592a3; font-size:12px;">Powered by: <strong style="color:#b8c4da;">World Vision Softek</strong></p>

</div>

</body>
</html>
