<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Application Status — FKCCI Membership</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<style>
  .status-wrap { max-width: 560px; margin: 0 auto; padding: 24px 16px 56px; }
  .steps { list-style: none; margin: 0; padding: 0; }
  .steps li { display: flex; gap: 12px; padding: 10px 0; align-items: flex-start; }
  .steps .dot { width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; margin-top: 1px; }
  .steps li.done .dot { background: var(--success-500, #1f9d55); color: #fff; }
  .steps li.current .dot { background: var(--gold-500); color: var(--navy-900); }
  .steps li.pending .dot { background: var(--border); color: var(--text-400); }
  .steps li.rejected .dot { background: var(--danger-600); color: #fff; }
</style>
</head>
<body>
<div class="status-wrap">
  <div class="text-center mb-16" style="margin-top:18px;">
    <div class="brand-mark" style="margin:0 auto 10px;"><img src="<?= base_url('assets/img/fkcci-mark.png') ?>" alt="FKCCI"></div>
    <h2 style="font-size:19px;">Application Status</h2>
  </div>

  <?php
    $company = $application['company'];
    $status = $application['status'];
    $isRejected = str_contains($status, 'rejected');
    $stepOrder = ['awaiting_payment', 'submitted', 'committee_recommended', 'approved'];
    $currentIdx = array_search($status === 'committee_review' ? 'submitted' : ($status === 'managing_committee_review' ? 'committee_recommended' : $status), $stepOrder, true);
    if ($currentIdx === false) { $currentIdx = $isRejected ? count($stepOrder) : 0; }
  ?>

  <div class="card mb-24">
    <div class="card-header">
      <h3><?= esc($company['name']) ?></h3>
      <span class="badge badge-neutral"><?= esc($application['application_ref']) ?></span>
    </div>

    <?php if ($isRejected): ?>
      <div class="alert alert-danger mb-16">
        <div class="alert-icon">🚫</div>
        <div>
          <h4>Application Not Approved</h4>
          <p><?= esc($application['committee_rejection_reason'] ?: $application['managing_committee_rejection_reason'] ?: 'No reason recorded.') ?></p>
        </div>
      </div>
    <?php else: ?>
      <ul class="steps">
        <li class="<?= $currentIdx > 0 ? 'done' : 'current' ?>"><div class="dot"><?= $currentIdx > 0 ? '✓' : '1' ?></div><div><strong>Submitted &amp; Paid</strong><div class="text-muted" style="font-size:12px;">Application received.</div></div></li>
        <li class="<?= $currentIdx > 1 ? 'done' : ($currentIdx === 1 ? 'current' : 'pending') ?>"><div class="dot"><?= $currentIdx > 1 ? '✓' : '2' ?></div><div><strong>Membership Committee</strong><div class="text-muted" style="font-size:12px;">Application reviewed &amp; recommended.</div></div></li>
        <li class="<?= $currentIdx > 2 ? 'done' : ($currentIdx === 2 ? 'current' : 'pending') ?>"><div class="dot"><?= $currentIdx > 2 ? '✓' : '3' ?></div><div><strong>Managing Committee</strong><div class="text-muted" style="font-size:12px;">Final approval decision.</div></div></li>
        <li class="<?= $currentIdx >= 3 ? 'done' : 'pending' ?>"><div class="dot"><?= $currentIdx >= 3 ? '✓' : '4' ?></div><div><strong>Registered &amp; Active</strong><div class="text-muted" style="font-size:12px;"><?= $company['membership_no'] ? 'Membership No. ' . esc($company['membership_no']) : 'ID cards issued at the chamber office.' ?></div></div></li>
      </ul>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header"><h3>Representatives</h3></div>
    <?php foreach ($reps as $r): ?>
      <div class="flex-between" style="padding:8px 0; border-bottom:1px solid var(--border); font-size:13px;">
        <span><?= esc($r['name']) ?><?= $r['designation'] ? ' · ' . esc($r['designation']) : '' ?></span>
        <span class="text-muted"><?= esc($r['member_id'] ?: 'Pending') ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="hint text-center mt-16">Questions about your application? Contact FKCCI at <strong>membership@fkcci.in</strong>.</p>
</div>
<?= view('partials/footer') ?>
</body>
</html>
