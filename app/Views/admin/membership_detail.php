<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($application['company']['name']) ?> — FKCCI Membership Admin</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<style>
  .dl-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; border-bottom: 1px solid var(--border); }
  .dl-row:last-child { border-bottom: none; }
  .dl-row .k { color: var(--text-400); }
  .dl-row .v { font-weight: 600; text-align: right; }
</style>
</head>
<body>
<div class="app-shell">
  <?= view('partials/admin_topbar', ['active' => $active]) ?>

  <main class="main">
    <div class="container">
      <div class="flex-between mb-16">
        <div>
          <a href="<?= base_url('admin/membership') ?>" style="font-size:12.5px; font-weight:600;">← All Applications</a>
          <h2 style="font-size:20px; margin-top:4px;"><?= esc($application['company']['name']) ?></h2>
        </div>
        <div class="text-right"><?= statusBadge($application['status']) ?><div class="text-muted mt-8" style="font-size:12px;"><?= esc($application['application_ref']) ?></div></div>
      </div>

      <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success mb-16"><div class="alert-icon">✅</div><div><p><?= esc(session()->getFlashdata('message')) ?></p></div></div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger mb-16"><div class="alert-icon">⚠️</div><div><p><?= esc(session()->getFlashdata('error')) ?></p></div></div>
      <?php endif; ?>

      <div class="grid-2">
        <div>
          <div class="card mb-24">
            <div class="card-header"><h3>Organisation</h3></div>
            <?php $c = $application['company']; ?>
            <div class="dl-row"><span class="k">Address</span><span class="v"><?= esc($c['address']) ?></span></div>
            <div class="dl-row"><span class="k">Phone / Mobile</span><span class="v"><?= esc($c['phone']) ?> / <?= esc($c['mobile']) ?></span></div>
            <div class="dl-row"><span class="k">E-mail / Website</span><span class="v"><?= esc($c['email'] ?: '—') ?> / <?= esc($c['website'] ?: '—') ?></span></div>
            <div class="dl-row"><span class="k">Established</span><span class="v"><?= esc($c['year_established'] ?: '—') ?></span></div>
            <div class="dl-row"><span class="k">Nature of Business</span><span class="v"><?= esc(ucfirst($c['nature_of_business'] ?? '—')) ?><?= $c['business_scale'] ? ' (' . ($c['business_scale'] === 'small' ? 'Small' : 'Large/Medium') . ')' : '' ?></span></div>
            <div class="dl-row"><span class="k">Product/Service</span><span class="v"><?= esc($c['product_description'] ?: '—') ?></span></div>
            <div class="dl-row"><span class="k">Annual Turnover</span><span class="v"><?= $c['annual_turnover'] ? '₹' . number_format((float) $c['annual_turnover']) : '—' ?></span></div>
            <div class="dl-row"><span class="k">GSTIN / PAN</span><span class="v"><?= esc($c['gstin'] ?: '—') ?> / <?= esc($c['pan'] ?: '—') ?></span></div>
            <div class="dl-row"><span class="k">MSME No.</span><span class="v"><?= esc($c['msme_registration_no'] ?: '—') ?></span></div>
            <div class="dl-row"><span class="k">Company Reg. No.</span><span class="v"><?= esc($c['company_registration_no'] ?: '—') ?></span></div>
            <div class="dl-row"><span class="k">IE Code</span><span class="v"><?= esc($c['ie_code'] ?: '—') ?></span></div>
            <div class="dl-row"><span class="k">Professional Inst. No.</span><span class="v"><?= esc($c['professional_institute_membership_no'] ?: '—') ?></span></div>
            <div class="dl-row"><span class="k">Bankers</span><span class="v"><?= esc($c['bankers_name'] ?: '—') ?></span></div>
            <div class="dl-row"><span class="k">Category</span><span class="v"><?= esc(ucfirst($c['membership_category'] ?? '—')) ?><?= $c['patron_tier'] ? ' (' . ucfirst($c['patron_tier']) . ')' : '' ?></span></div>
            <div class="dl-row"><span class="k">Membership No.</span><span class="v"><?= esc($c['membership_no'] ?: 'Not yet registered') ?></span></div>
          </div>

          <div class="card mb-24">
            <div class="card-header"><h3>Fee &amp; Payment</h3></div>
            <div class="dl-row"><span class="k">Admission Fee</span><span class="v">₹<?= number_format((float) $application['admission_fee'], 2) ?></span></div>
            <div class="dl-row"><span class="k">Subscription/Patron Fee</span><span class="v">₹<?= number_format((float) $application['subscription_fee'], 2) ?></span></div>
            <div class="dl-row"><span class="k">GST</span><span class="v">₹<?= number_format((float) $application['gst_amount'], 2) ?></span></div>
            <div class="dl-row"><span class="k">Total</span><span class="v">₹<?= number_format((float) $application['total_fee'], 2) ?></span></div>
            <?php if ($payment): ?>
              <div class="dl-row"><span class="k">Payment Status</span><span class="v"><?= $payment['status'] === 'paid' ? '<span class="badge badge-success">Paid</span>' : '<span class="badge badge-warning">' . esc(ucfirst($payment['status'])) . '</span>' ?></span></div>
              <div class="dl-row"><span class="k">Gateway / Order</span><span class="v"><?= esc($payment['gateway']) ?> / <?= esc($payment['gateway_order_id']) ?></span></div>
              <?php if ($payment['paid_at']): ?><div class="dl-row"><span class="k">Paid At</span><span class="v"><?= esc(formatIST($payment['paid_at'])) ?></span></div><?php endif; ?>
            <?php endif; ?>
          </div>

          <div class="card">
            <div class="card-header"><h3>Documents</h3></div>
            <?php if (! $documents): ?>
              <p class="text-muted" style="font-size:13px;">None uploaded.</p>
            <?php endif; ?>
            <?php foreach ($documents as $d): ?>
              <div class="dl-row">
                <span class="k"><?= esc(ucwords(str_replace('_', ' ', $d['doc_type']))) ?></span>
                <span class="v"><a href="<?= base_url('admin/membership/document/' . $d['id']) ?>" target="_blank" rel="noopener">View →</a></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div>
          <div class="card mb-24">
            <div class="card-header"><h3>Representatives</h3></div>
            <?php foreach ($reps as $r): ?>
              <div style="padding:10px 0; border-bottom:1px solid var(--border);">
                <div class="flex-between">
                  <strong style="font-size:13.5px;"><?= esc($r['name']) ?></strong>
                  <span class="text-muted" style="font-size:12px;"><?= esc($r['designation'] ?: '—') ?></span>
                </div>
                <div class="text-muted" style="font-size:12px; margin-top:2px;">Member ID: <?= esc($r['member_id'] ?: 'Not registered yet') ?></div>
                <?php if ($r['member_id']): ?>
                  <?php if ($r['rfid_tag']): ?>
                    <div class="flex-between mt-8">
                      <span class="badge badge-success" style="font-size:11px;">RFID: <?= esc($r['rfid_tag']) ?></span>
                      <a href="<?= base_url('admin/membership/card/' . $r['member_id']) ?>" target="_blank" rel="noopener" style="font-size:12px; font-weight:600;">🪪 Print Card →</a>
                    </div>
                  <?php else: ?>
                    <?= form_open('admin/membership/' . $application['id'] . '/rep/' . $r['id'] . '/rfid') ?>
                      <div class="flex gap-12 mt-8">
                        <input type="text" name="rfid_tag" placeholder="Tap or type RFID tag" style="flex:1; font-size:12px; padding:8px;">
                        <button class="btn btn-outline" style="padding:6px 12px; font-size:12px;" type="submit">Assign</button>
                      </div>
                    <?= form_close() ?>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="card">
            <div class="card-header"><h3>Workflow</h3></div>

            <?php if ($application['status'] === 'submitted'): ?>
              <p class="text-muted mb-16" style="font-size:13px;">Present this application to the Membership Committee.</p>
              <?= form_open('admin/membership/' . $application['id'] . '/committee/present') ?>
                <div class="field"><label>Presented On</label><input type="date" name="presented_on" value="<?= date('Y-m-d') ?>"></div>
                <div class="field-row">
                  <div class="field"><label>Proposed By</label><input type="text" name="proposed_by"></div>
                  <div class="field"><label>ID Card No.</label><input type="text" name="proposed_by_id_card_no"></div>
                </div>
                <div class="field-row">
                  <div class="field"><label>Seconded By</label><input type="text" name="seconded_by"></div>
                  <div class="field"><label>ID Card No.</label><input type="text" name="seconded_by_id_card_no"></div>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Present to Committee</button>
              <?= form_close() ?>

            <?php elseif ($application['status'] === 'committee_review'): ?>
              <p class="text-muted mb-16" style="font-size:13px;">Presented on <?= esc($application['committee_presented_on']) ?>. Record the committee's decision.</p>
              <?= form_open('admin/membership/' . $application['id'] . '/committee/recommend') ?>
                <button class="btn btn-success btn-block mb-8" type="submit">✓ Recommend</button>
              <?= form_close() ?>
              <?= form_open('admin/membership/' . $application['id'] . '/committee/reject') ?>
                <div class="field"><label>Rejection Reason</label><input type="text" name="reason" required></div>
                <button class="btn btn-danger btn-block" type="submit">✕ Reject</button>
              <?= form_close() ?>

            <?php elseif ($application['status'] === 'committee_recommended'): ?>
              <p class="text-muted mb-16" style="font-size:13px;">Recommended by the Membership Committee. Present to the Managing Committee.</p>
              <?= form_open('admin/membership/' . $application['id'] . '/managing/present') ?>
                <div class="field"><label>Presented On</label><input type="date" name="presented_on" value="<?= date('Y-m-d') ?>"></div>
                <button class="btn btn-primary btn-block" type="submit">Present to Managing Committee</button>
              <?= form_close() ?>

            <?php elseif ($application['status'] === 'managing_committee_review'): ?>
              <p class="text-muted mb-16" style="font-size:13px;">Presented on <?= esc($application['managing_committee_presented_on']) ?>. Record the final decision.</p>
              <?= form_open('admin/membership/' . $application['id'] . '/managing/approve') ?>
                <button class="btn btn-success btn-block mb-8" type="submit">✓ Approve</button>
              <?= form_close() ?>
              <?= form_open('admin/membership/' . $application['id'] . '/managing/reject') ?>
                <div class="field"><label>Rejection Reason</label><input type="text" name="reason" required></div>
                <button class="btn btn-danger btn-block" type="submit">✕ Reject</button>
              <?= form_close() ?>

            <?php elseif ($application['status'] === 'approved' && ! $application['company']['membership_no']): ?>
              <p class="text-muted mb-16" style="font-size:13px;">Approved by the Managing Committee. Enter into the Membership Register to assign the Membership No. and Member IDs.</p>
              <?= form_open('admin/membership/' . $application['id'] . '/register') ?>
                <button class="btn btn-primary btn-block" type="submit">📋 Register &amp; Issue Numbers</button>
              <?= form_close() ?>

            <?php elseif ($application['status'] === 'approved'): ?>
              <div class="alert alert-success">
                <div class="alert-icon">✅</div>
                <div>
                  <h4>Registered — <?= esc($application['company']['membership_no']) ?></h4>
                  <p>Registered <?= esc($application['registered_on']) ?>, certificate issued <?= esc($application['certificate_issued_on']) ?>. Assign RFID tags in the Representatives panel as members collect their cards.</p>
                </div>
              </div>

            <?php elseif (in_array($application['status'], ['committee_rejected', 'rejected'], true)): ?>
              <div class="alert alert-danger">
                <div class="alert-icon">🚫</div>
                <div>
                  <h4>Not Approved</h4>
                  <p><?= esc($application['committee_rejection_reason'] ?: $application['managing_committee_rejection_reason']) ?></p>
                </div>
              </div>

            <?php else: ?>
              <p class="text-muted" style="font-size:13px;">Waiting on payment confirmation.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?= view('partials/footer') ?>
</div>
</body>
</html>
