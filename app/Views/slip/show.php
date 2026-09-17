<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Voting Slip <?= esc($vote['serial_no']) ?> — FKCCI Election Voting Slip System</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<style>
  .slip-page-wrap { max-width: 520px; margin: 0 auto; padding: 24px 16px 56px; }
  .slip-stage { background: #eef2f8; border-radius: var(--radius-lg); padding: 28px 16px; display: flex; justify-content: center; }
</style>
</head>
<body>

<?php $isVoid = $vote['status'] === 'void'; ?>

<header class="topbar no-print">
  <div class="container topbar-inner">
    <a class="brand" href="javascript:history.back()">
      <div class="brand-mark">FK</div>
      <div class="brand-text">
        <div class="title">FKCCI Election 2026</div>
        <div class="subtitle">Voting Slip</div>
      </div>
    </a>
    <div class="nav-links">
      <a href="javascript:history.back()">← Back</a>
    </div>
  </div>
</header>

<main class="slip-page-wrap">

  <div class="card-header no-print" style="margin-bottom:16px;">
    <div>
      <h2 style="font-size:19px;"><?= esc($vote['serial_no']) ?></h2>
      <p style="font-size:13px;"><?= esc($vote['member_name']) ?> · <?= esc($vote['company_name']) ?></p>
    </div>
    <?= $isVoid ? '<span class="badge badge-danger">Void</span>' : '<span class="badge badge-success">Issued</span>' ?>
  </div>

  <?php if ($isVoid): ?>
    <div class="alert alert-danger mb-16 no-print">
      <div class="alert-icon">⚠️</div>
      <div>
        <h4>This slip has been voided</h4>
        <p>Reason: <?= esc($vote['void_reason'] ?: 'Not specified') ?>. It must not be printed or handed to the voter.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-success mb-16 no-print">
      <div class="alert-icon">🖨️</div>
      <div>
        <h4>Ready to print</h4>
        <p>If the original print failed or you need a different printer, use the button below — the print dialog lets you pick any connected or network printer, including a thermal slip printer, or save as PDF.</p>
      </div>
    </div>
  <?php endif; ?>

  <div class="slip-stage" id="printArea">
    <div class="slip<?= $isVoid ? ' is-void' : '' ?>" style="position:relative;">
      <?php if ($isVoid): ?>
        <div class="badge badge-danger" style="position:absolute; top:14px; right:14px;">VOID</div>
      <?php endif; ?>
      <div class="slip-header">
        <div class="org">Federation of Karnataka Chambers of Commerce &amp; Industry</div>
        <div class="title">OFFICIAL VOTING SLIP</div>
        <div class="election">FKCCI Election 2026</div>
      </div>
      <div class="slip-row"><span class="k">Member ID</span><span class="v"><?= esc($vote['member_code']) ?></span></div>
      <div class="slip-row"><span class="k">Name</span><span class="v"><?= esc($vote['member_name']) ?></span></div>
      <div class="slip-row"><span class="k">Company</span><span class="v"><?= esc($vote['company_name']) ?></span></div>
      <div class="slip-row"><span class="k">Designation</span><span class="v"><?= esc($vote['designation'] ?: '—') ?></span></div>
      <div class="slip-qr"><img src="<?= site_url('slip/' . $vote['serial_no'] . '/qr') ?>" width="150" height="150" alt="QR code"></div>
      <div class="slip-serial"><?= esc($vote['serial_no']) ?></div>
      <div class="slip-row mt-8"><span class="k">Counter</span><span class="v">Counter <?= esc($vote['counter_no']) ?></span></div>
      <div class="slip-row"><span class="k">Issued</span><span class="v"><?= esc($vote['issued_at']) ?></span></div>
      <div class="slip-footer">Present this slip at the polling booth. Non-transferable. One slip per company.</div>
    </div>
  </div>

  <div class="flex gap-12 mt-24 no-print">
    <?php if ($isVoid): ?>
      <button class="btn btn-outline btn-lg btn-block" disabled>🚫 Printing disabled — slip is void</button>
    <?php else: ?>
      <button class="btn btn-success btn-lg" style="flex:1;" onclick="window.print()">🖨️ Print / Choose Printer</button>
    <?php endif; ?>
    <a class="btn btn-outline btn-lg" href="<?= site_url('verify/' . $vote['serial_no']) ?>" target="_blank" rel="noopener">View QR Verification</a>
  </div>

</main>

<?= view('partials/footer') ?>

</body>
</html>
