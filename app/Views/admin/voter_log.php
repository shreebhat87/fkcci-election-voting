<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Voter Log — FKCCI Election Voting Slip System</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-shell">
  <?= view('partials/admin_topbar', ['active' => $active]) ?>

  <main class="main">
    <div class="container">
      <div class="flex-between mb-16">
        <h2 style="font-size:20px;">Voter Log</h2>
        <a class="btn btn-outline" href="<?= base_url('admin/voter-log/export') ?>">⬇ Export CSV</a>
      </div>

      <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success mb-16"><div class="alert-icon">✅</div><div><p><?= esc(session()->getFlashdata('message')) ?></p></div></div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger mb-16"><div class="alert-icon">⚠️</div><div><p><?= esc(session()->getFlashdata('error')) ?></p></div></div>
      <?php endif; ?>

      <div class="card mb-24">
        <?= form_open('admin/voter-log', ['method' => 'get']) ?>
          <div class="field-row">
            <div class="field">
              <label for="q">Search</label>
              <input type="search" id="q" name="q" value="<?= esc($q) ?>" placeholder="Search name, company, member ID, slip no…">
            </div>
            <div class="field">
              <label for="counter">Counter</label>
              <select id="counter" name="counter" onchange="this.form.submit()">
                <option value="">All counters</option>
                <?php for ($c = 1; $c <= 10; $c++): ?>
                  <option value="<?= $c ?>" <?= (string) $counter === (string) $c ? 'selected' : '' ?>>Counter <?= $c ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="field">
              <label for="status">Status</label>
              <select id="status" name="status" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="issued" <?= $status === 'issued' ? 'selected' : '' ?>>Issued</option>
                <option value="void" <?= $status === 'void' ? 'selected' : '' ?>>Void</option>
              </select>
            </div>
          </div>
          <button class="btn btn-primary" type="submit">Search</button>
        <?= form_close() ?>
      </div>

      <div class="card">
        <div class="card-header"><h3><?= count($votes) ?> slips</h3></div>
        <table>
          <thead>
            <tr><th>Slip No.</th><th>Member</th><th>Company</th><th>Counter</th><th>Time</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
          <?php if (! $votes): ?>
            <tr><td colspan="7" class="text-muted text-center">No matching slips.</td></tr>
          <?php endif; ?>
          <?php foreach ($votes as $v): ?>
            <tr>
              <td style="font-family:monospace; font-size:12px;"><?= esc($v['serial_no']) ?></td>
              <td><?= esc($v['member_name']) ?><div class="text-muted" style="font-size:11.5px;"><?= esc($v['member_code']) ?></div></td>
              <td><?= esc($v['company_name']) ?></td>
              <td>Counter <?= esc($v['counter_no']) ?></td>
              <td><?= esc(formatIST($v['issued_at'])) ?></td>
              <td><?= $v['status'] === 'issued' ? '<span class="badge badge-success">Issued</span>' : '<span class="badge badge-danger">Void</span>' ?></td>
              <td>
                <?php if ($v['status'] === 'issued'): ?>
                  <a href="<?= site_url('slip/' . $v['serial_no']) ?>" target="_blank" rel="noopener" style="font-size:12px; font-weight:600; margin-right:12px;">Print</a>
                  <button class="btn btn-ghost" style="padding:6px 10px; font-size:12px;" onclick="openVoidModal('<?= esc($v['serial_no'], 'js') ?>', '<?= esc($v['member_name'], 'js') ?>', '<?= esc($v['company_name'], 'js') ?>')">Void</button>
                <?php else: ?>
                  <a href="<?= site_url('verify/' . $v['serial_no']) ?>" target="_blank" rel="noopener" style="font-size:12px;">Verify</a>
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

<!-- Void modal -->
<div class="modal-backdrop hidden" id="voidModal">
  <div class="modal">
    <div class="icon-circle warning">🗑️</div>
    <h3>Void Voting Slip</h3>
    <p id="voidSlipInfo"></p>
    <?= form_open('', ['id' => 'voidForm']) ?>
      <div class="field mt-16" style="text-align:left;">
        <label for="voidReason">Reason for voiding</label>
        <select id="voidReason" name="reason">
          <option>Issued in error</option>
          <option>Duplicate scan</option>
          <option>Member disputed identity</option>
          <option>Printer/system fault — reissued</option>
          <option>Other (specify in log)</option>
        </select>
      </div>
      <div class="modal-actions">
        <button class="btn btn-outline" type="button" onclick="closeVoidModal()">Cancel</button>
        <button class="btn btn-danger" type="submit">Void Slip</button>
      </div>
    <?= form_close() ?>
  </div>
</div>

<script>
function openVoidModal(serial, name, company) {
  document.getElementById("voidSlipInfo").textContent = `${name} — ${company} — Slip ${serial}`;
  document.getElementById("voidForm").action = "<?= base_url('admin/voter-log/void') ?>/" + encodeURIComponent(serial);
  document.getElementById("voidModal").classList.remove("hidden");
}
function closeVoidModal() {
  document.getElementById("voidModal").classList.add("hidden");
}
</script>
</body>
</html>
