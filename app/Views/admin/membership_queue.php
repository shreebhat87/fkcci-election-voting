<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Membership Applications — FKCCI Admin</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-shell">
  <?= view('partials/admin_topbar', ['active' => $active]) ?>

  <main class="main">
    <div class="container">
      <div class="flex-between mb-16">
        <h2 style="font-size:20px;">Membership Applications</h2>
        <a class="btn btn-outline" href="<?= base_url('membership/apply') ?>" target="_blank" rel="noopener">↗ Public Application Form</a>
      </div>

      <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success mb-16"><div class="alert-icon">✅</div><div><p><?= esc(session()->getFlashdata('message')) ?></p></div></div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger mb-16"><div class="alert-icon">⚠️</div><div><p><?= esc(session()->getFlashdata('error')) ?></p></div></div>
      <?php endif; ?>

      <div class="card mb-24">
        <?= form_open('admin/membership', ['method' => 'get']) ?>
          <div class="field" style="max-width:320px;">
            <label for="status">Status</label>
            <select id="status" name="status" onchange="this.form.submit()">
              <option value="">All</option>
              <option value="awaiting_payment" <?= $status === 'awaiting_payment' ? 'selected' : '' ?>>Awaiting Payment</option>
              <option value="submitted" <?= $status === 'submitted' ? 'selected' : '' ?>>Submitted (needs committee)</option>
              <option value="committee_review" <?= $status === 'committee_review' ? 'selected' : '' ?>>At Committee</option>
              <option value="committee_recommended" <?= $status === 'committee_recommended' ? 'selected' : '' ?>>Recommended (needs managing cmte.)</option>
              <option value="committee_rejected" <?= $status === 'committee_rejected' ? 'selected' : '' ?>>Rejected by Committee</option>
              <option value="managing_committee_review" <?= $status === 'managing_committee_review' ? 'selected' : '' ?>>At Managing Committee</option>
              <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
              <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
          </div>
        <?= form_close() ?>
      </div>

      <div class="card">
        <div class="card-header"><h3><?= count($applications) ?> applications</h3></div>
        <table>
          <thead>
            <tr><th>Ref</th><th>Company</th><th>Category</th><th>Status</th><th>Mem. No.</th><th>Submitted</th><th></th></tr>
          </thead>
          <tbody>
          <?php if (! $applications): ?>
            <tr><td colspan="7" class="text-muted text-center">No applications.</td></tr>
          <?php endif; ?>
          <?php foreach ($applications as $a): ?>
            <tr>
              <td style="font-family:monospace; font-size:12px;"><?= esc($a['application_ref']) ?></td>
              <td><?= esc($a['company_name']) ?></td>
              <td><?= esc(ucfirst($a['membership_category'] ?? '—')) ?></td>
              <td><?= statusBadge($a['status']) ?></td>
              <td><?= esc($a['membership_no'] ?: '—') ?></td>
              <td><?= esc(formatIST($a['submitted_at'])) ?></td>
              <td><a href="<?= base_url('admin/membership/' . $a['id']) ?>" style="font-size:12px; font-weight:600;">Open →</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <?= view('partials/footer') ?>
</div>
</body>
</html>
