<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Check Application Status — FKCCI</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="login-wrap">
  <div style="width:100%;max-width:460px;">
    <div class="login-header">
      <div class="brand-mark" style="margin:0 auto 14px;"><img src="<?= base_url('assets/img/fkcci-mark.png') ?>" alt="FKCCI"></div>
      <h1 style="font-size:19px;">Check Application Status</h1>
      <p>Enter the reference shown when you applied.</p>
    </div>
    <div class="login-card">
      <?= form_open('membership/status', ['method' => 'get']) ?>
        <div class="field">
          <label for="ref">Application Reference</label>
          <input type="text" id="ref" name="ref" placeholder="FKCCI-XXXXXXXX" autofocus required style="text-transform:uppercase;">
        </div>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Check Status →</button>
      <?= form_close() ?>
      <p class="hint text-center mt-16"><a href="<?= base_url('membership/apply') ?>">← New application</a></p>
    </div>
  </div>
</div>
<?= view('partials/footer') ?>
</body>
</html>
