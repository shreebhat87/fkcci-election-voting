<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — FKCCI Election Voting Slip System</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-shell">
  <?= view('partials/admin_topbar', ['active' => $active]) ?>

  <main class="main">
    <div class="container">
      <div class="flex-between mb-24">
        <div>
          <h2 style="font-size:20px;">Election Day Overview</h2>
          <p style="font-size:13px;">FKCCI Election 2026 · 10 counters active</p>
        </div>
      </div>

      <div class="stat-grid mb-24">
        <div class="stat-tile accent-navy">
          <div class="stat-label">Total Eligible Members</div>
          <div class="stat-value"><?= esc($totalMembers) ?></div>
          <div class="stat-sub">Across <?= esc($totalCompanies) ?> companies (2 reps each)</div>
        </div>
        <div class="stat-tile accent-gold">
          <div class="stat-label">Votes Cast</div>
          <div class="stat-value"><?= esc($votesCast) ?></div>
          <div class="stat-sub">Voting slips issued so far</div>
        </div>
        <div class="stat-tile accent-success">
          <div class="stat-label">Companies Represented</div>
          <div class="stat-value"><?= esc($votedCompanies) ?> / <?= esc($totalCompanies) ?></div>
          <div class="stat-sub"><?= esc($turnoutPct) ?>% turnout</div>
        </div>
        <div class="stat-tile accent-danger">
          <div class="stat-label">Companies Pending</div>
          <div class="stat-value"><?= esc($pendingCompaniesCount) ?></div>
          <div class="stat-sub">Have not voted yet</div>
        </div>
      </div>

      <div class="grid-2">
        <div class="card">
          <div class="card-header">
            <h3>Votes Issued per Counter</h3>
          </div>
          <?php $maxVal = max(1, ...array_values($perCounter)); ?>
          <?php foreach ($perCounter as $counterNo => $count): ?>
            <div class="bar-row">
              <div class="bar-label">Counter <?= esc($counterNo) ?></div>
              <div class="bar-track"><div class="bar-fill" style="width:<?= round(($count / $maxVal) * 100) ?>%;"></div></div>
              <div class="bar-value"><?= esc($count) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="card">
          <div class="card-header"><h3>Companies Pending</h3></div>
          <p style="font-size:13px;">Companies where neither designated representative has voted yet.</p>
          <?php if ($pendingCompanies): ?>
            <?php foreach ($pendingCompanies as $c): ?>
              <div class="flex-between" style="padding:7px 0; border-bottom:1px solid var(--border); font-size:13px;">
                <span><?= esc($c['name']) ?></span><span class="badge badge-warning">Pending</span>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted" style="font-size:13px;">All companies represented.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mt-24">
        <div class="card-header">
          <h3>Recent Activity — All Counters</h3>
          <a href="<?= base_url('admin/voter-log') ?>" style="font-size:13px; font-weight:600;">View full voter log →</a>
        </div>
        <table>
          <thead>
            <tr><th>Slip No.</th><th>Member</th><th>Company</th><th>Counter</th><th>Time</th><th></th></tr>
          </thead>
          <tbody>
          <?php if (! $recent): ?>
            <tr><td colspan="6" class="text-muted">No votes recorded yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recent as $v): ?>
            <tr>
              <td style="font-family:monospace; font-size:12px;"><?= esc($v['serial_no']) ?></td>
              <td><?= esc($v['name']) ?></td>
              <td><?= esc($v['company_name']) ?></td>
              <td>Counter <?= esc($v['counter_no']) ?></td>
              <td><?= esc(timeAgo($v['issued_at'])) ?></td>
              <td>
                <?php if ($v['status'] === 'issued'): ?>
                  <a href="<?= site_url('slip/' . $v['serial_no']) ?>" target="_blank" rel="noopener" style="font-size:12px; font-weight:600;">Print</a>
                <?php else: ?>
                  <span class="badge badge-danger">Void</span>
                <?php endif; ?>
              </td>
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
