<header class="topbar">
  <div class="container topbar-inner">
    <a class="brand" href="<?= base_url('admin') ?>">
      <div class="brand-mark"><img src="<?= base_url('assets/img/fkcci-mark.png') ?>" alt="FKCCI"></div>
      <div class="brand-text">
        <div class="title">FKCCI Election 2026</div>
        <div class="subtitle">Admin Console</div>
      </div>
    </a>
    <nav class="nav-links">
      <a href="<?= base_url('admin') ?>" class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
      <a href="<?= base_url('admin/master-data') ?>" class="<?= ($active ?? '') === 'master-data' ? 'active' : '' ?>">Master Data</a>
      <a href="<?= base_url('admin/voter-log') ?>" class="<?= ($active ?? '') === 'voter-log' ? 'active' : '' ?>">Voter Log</a>
      <a href="<?= base_url('admin/membership') ?>" class="<?= ($active ?? '') === 'membership' ? 'active' : '' ?>">Membership</a>
      <a href="<?= base_url('exit') ?>" target="_blank" rel="noopener">Exit Scan ↗</a>
    </nav>
    <div class="session-chip">
      <div class="avatar avatar-sm" style="background:var(--gold-500); color:var(--navy-900);"><?= esc(initials(session()->get('name') ?? 'Admin')) ?></div>
      <span><?= esc(session()->get('name') ?? 'Election Admin') ?></span>
      <a href="<?= base_url('logout') ?>">Sign out</a>
    </div>
  </div>
</header>
