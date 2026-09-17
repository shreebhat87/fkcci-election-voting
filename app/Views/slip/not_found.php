<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Slip Not Found — FKCCI Election Voting Slip System</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-shell">
  <main class="main">
    <div class="container" style="max-width:480px;">
      <div class="card text-center">
        <div style="font-size:40px; margin-bottom:10px;">🔍</div>
        <h2>Slip Not Found</h2>
        <p>No voting slip matches serial <strong><?= esc($serial) ?></strong>.</p>
        <a class="btn btn-primary mt-16" href="<?= base_url('/') ?>">← Back</a>
      </div>
    </div>
  </main>
  <?= view('partials/footer') ?>
</div>
</body>
</html>
