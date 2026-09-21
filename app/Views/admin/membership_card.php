<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ID Card — <?= esc($rep['member_id']) ?> — FKCCI Membership</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<style>
  /* CR-80 card, 86mm x 54mm — the standard PVC ID card size, landscape. */
  @page { size: 86mm 54mm; margin: 0; }

  body { background: #eef2f8; }
  .card-stage { display: flex; justify-content: center; padding: 30px 16px; gap: 24px; flex-wrap: wrap; }

  .id-card {
    width: 86mm; height: 54mm; background: #fff; position: relative; overflow: hidden;
    border-radius: 3mm; box-shadow: var(--shadow-lg); font-family: Arial, Helvetica, sans-serif;
    padding: 3mm 3mm 2mm; box-sizing: border-box;
  }
  .id-card .corner { position: absolute; width: 8mm; height: 8mm; background: #c0392b; z-index: 1; }
  .id-card .corner.tr { top: -4mm; right: -4mm; transform: rotate(45deg); }
  .id-card .corner.bl { bottom: -4mm; left: -4mm; transform: rotate(45deg); }

  .id-card .head { display: flex; gap: 2mm; align-items: flex-start; }
  .id-card .logo { width: 9mm; height: 9mm; border-radius: 50%; overflow: hidden; flex-shrink: 0; border: 0.3mm solid #c0392b; }
  .id-card .logo img { width: 100%; height: 100%; object-fit: cover; }
  .id-card .org-kn { color: #c0392b; font-size: 2.1mm; font-weight: 700; line-height: 1.15; }
  .id-card .org-en { color: #1d4e89; font-size: 2.2mm; font-weight: 800; line-height: 1.15; margin-top: 0.3mm; }

  .id-card .badge-row { position: absolute; top: 2.5mm; right: 5mm; text-align: right; z-index: 3; }
  .id-card .cat-badge { font-size: 2.3mm; font-weight: 800; color: #c0392b; white-space: nowrap; }
  .id-card .mem-no { font-size: 2.1mm; font-weight: 800; color: #c0392b; }

  .id-card .body-row { display: flex; margin-top: 2mm; gap: 2mm; }
  .id-card .info { flex: 1; min-width: 0; }
  .id-card .company-name { font-size: 3mm; font-weight: 800; color: #1a1a1a; line-height: 1.2; margin-bottom: 1.8mm; }
  .id-card .info-line { font-size: 2.6mm; font-weight: 700; color: #1a1a1a; border-bottom: 0.3mm solid #c0392b; padding-bottom: 0.6mm; margin-bottom: 1.6mm; display: flex; gap: 1mm; align-items: center; }
  .id-card .info-line .ic { font-size: 2.6mm; }

  .id-card .photo { width: 16mm; height: 19mm; border: 0.3mm solid #ccc; flex-shrink: 0; background: #eee; }
  .id-card .photo img { width: 100%; height: 100%; object-fit: cover; }

  .id-card .sign-row { position: absolute; bottom: 1.6mm; left: 3mm; right: 3mm; display: flex; justify-content: space-between; gap: 2mm; }
  .id-card .sign-col { text-align: center; flex: 1; }
  .id-card .sign-line { border-top: 0.25mm solid #999; margin-bottom: 0.4mm; height: 3mm; }
  .id-card .sign-name { font-size: 1.9mm; font-weight: 700; color: #1a1a1a; }
  .id-card .sign-title { font-size: 1.6mm; color: #555; }
</style>
</head>
<body>

<div class="card-stage no-print-controls">
  <div class="id-card" id="printCard">
    <div class="corner tr"></div>
    <div class="corner bl"></div>

    <div class="badge-row">
      <div class="cat-badge"><?= esc(strtoupper($company['membership_category'] ?? '')) ?></div>
      <div class="mem-no"><?= esc($company['membership_no']) ?></div>
    </div>

    <div class="head">
      <div class="logo"><img src="<?= base_url('assets/img/fkcci-mark.png') ?>" alt="FKCCI"></div>
      <div>
        <div class="org-kn">ಕರ್ನಾಟಕ ವಾಣಿಜ್ಯ ಮತ್ತು ಕೈಗಾರಿಕಾ ಮಹಾಸಂಸ್ಥೆ</div>
        <div class="org-en">FEDERATION OF KARNATAKA CHAMBERS OF<br>COMMERCE &amp; INDUSTRY</div>
      </div>
    </div>

    <div class="body-row">
      <div class="info">
        <div class="company-name"><?= esc($company['name']) ?></div>
        <div class="info-line"><span class="ic">🗂</span> <?= esc($rep['name']) ?></div>
        <div class="info-line"><span class="ic">📅</span> <?= esc(ucfirst($company['nature_of_business'] ?? '')) ?><?= $company['business_scale'] ? ' - ' . esc($company['business_scale'] === 'small' ? 'Small' : 'Large/Medium') : '' ?></div>
      </div>
      <div class="photo">
        <?php if ($rep['photo_path']): ?>
          <img src="<?= site_url('photos/' . $rep['photo_path']) ?>" alt="">
        <?php endif; ?>
      </div>
    </div>

    <div class="sign-row">
      <div class="sign-col">
        <div class="sign-line"></div>
        <div class="sign-name"><?= esc($membershipConfig->secretaryGeneralName) ?></div>
        <div class="sign-title"><?= esc($membershipConfig->secretaryGeneralTitle) ?></div>
      </div>
      <div class="sign-col">
        <div class="sign-line"></div>
        <div class="sign-name"><?= esc($membershipConfig->presidentName) ?></div>
        <div class="sign-title"><?= esc($membershipConfig->presidentTitle) ?></div>
      </div>
      <div class="sign-col">
        <div class="sign-line"></div>
        <div class="sign-title">Member Signature</div>
      </div>
    </div>
  </div>
</div>

<div class="text-center no-print" style="padding-bottom:30px;">
  <button class="btn btn-primary btn-lg" onclick="window.print()">🖨️ Print Card</button>
  <p class="hint mt-8">Card size: 86mm × 54mm (CR-80) — load PVC card stock in the printer before printing.</p>
</div>

<style>
  @media print {
    body * { visibility: hidden; }
    #printCard, #printCard * { visibility: visible; }
    #printCard { position: absolute; top: 0; left: 0; box-shadow: none; }
    .card-stage { padding: 0; }
  }
</style>

</body>
</html>
