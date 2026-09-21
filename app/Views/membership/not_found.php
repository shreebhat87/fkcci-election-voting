<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Application Not Found — FKCCI</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="login-wrap">
  <div style="width:100%;max-width:460px;">
    <div class="login-card text-center">
      <div class="icon-circle warning" style="margin:0 auto 14px;">⚠️</div>
      <h3>Application Not Found</h3>
      <p class="text-muted mt-8">No application matches reference <strong><?= esc($ref) ?></strong>. Check the reference and try again.</p>
      <a class="btn btn-primary btn-lg btn-block mt-16" href="<?= base_url('membership/status') ?>">Try Again</a>
      <a class="btn btn-outline btn-lg btn-block mt-8" href="<?= base_url('membership/apply') ?>">New Application</a>
    </div>
  </div>
</div>
<?= view('partials/footer') ?>
</body>
</html>
