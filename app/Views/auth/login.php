<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in — FKCCI Election Voting Slip System</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>

<div class="login-wrap">
  <div style="width:100%;max-width:460px;">
    <div class="login-header">
      <div class="brand-mark"><img src="<?= base_url('assets/img/fkcci-mark.png') ?>" alt="FKCCI"></div>
      <h1>FKCCI Election Voting Slip System</h1>
      <p>Federation of Karnataka Chambers of Commerce &amp; Industry</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger mb-16">
        <div class="alert-icon">⚠️</div>
        <div><p><?= esc(session()->getFlashdata('error')) ?></p></div>
      </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('message')): ?>
      <div class="alert alert-success mb-16">
        <div class="alert-icon">✅</div>
        <div><p><?= esc(session()->getFlashdata('message')) ?></p></div>
      </div>
    <?php endif; ?>

    <div class="login-card">
      <?= form_open('/login', ['autocomplete' => 'off']) ?>
        <div class="field">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" value="<?= esc(old('username')) ?>" autofocus required>
          <p class="hint">Admin: your assigned username. Counter operator: <code>counter1</code>–<code>counter10</code>.</p>
        </div>
        <div class="field">
          <label for="password">Password / PIN</label>
          <input type="password" id="password" name="password" required>
        </div>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Sign in →</button>
      <?= form_close() ?>
    </div>
  </div>
</div>

<?= view('partials/footer') ?>

</body>
</html>
