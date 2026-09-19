<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $isAdmin ? 'Exit Scan (Admin)' : 'Exit Desk ' . esc($deskNo) ?> — FKCCI Election Voting Slip System</title>
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<style>
  .qr-stage { position: relative; width: 100%; max-width: 360px; margin: 0 auto; border-radius: var(--radius-lg); overflow: hidden; background: #000; aspect-ratio: 1 / 1; }
  .qr-stage video { width: 100%; height: 100%; object-fit: cover; display: block; }
  .qr-target { position: absolute; inset: 14%; border: 3px solid rgba(255,255,255,.75); border-radius: 16px; box-shadow: 0 0 0 999px rgba(0,0,0,.28); pointer-events: none; }
  .qr-target.hit { border-color: var(--success-500, #1f9d55); }
  .qr-stage .qr-hint { position: absolute; left: 0; right: 0; bottom: 10px; text-align: center; color: #fff; font-size: 12.5px; text-shadow: 0 1px 3px rgba(0,0,0,.6); }
  .qr-fallback { max-width: 360px; margin: 14px auto 0; }
  .qr-camera-error { max-width: 360px; margin: 0 auto; text-align: center; padding: 18px; }
</style>
</head>
<body>
<div class="app-shell">

  <header class="topbar no-print">
    <div class="container topbar-inner">
      <a class="brand" href="<?= base_url('exit') ?>">
        <div class="brand-mark"><img src="<?= base_url('assets/img/fkcci-mark.png') ?>" alt="FKCCI"></div>
        <div class="brand-text">
          <div class="title">FKCCI Election 2026</div>
          <div class="subtitle"><?= $isAdmin ? 'Exit Scan — Admin Backup Station' : 'Exit Desk Terminal' ?></div>
        </div>
      </a>
      <div class="session-chip">
        <span class="dot"></span>
        <span><?= $isAdmin ? 'Admin' : ('Desk ' . esc($deskNo)) ?></span>
        <span style="color:#8592a3;">·</span>
        <span id="clock">--:--</span>
        <a href="<?= base_url('logout') ?>">Sign out</a>
      </div>
    </div>
  </header>

  <main class="main">
    <div class="container">

      <div class="stat-grid mb-24" style="grid-template-columns: repeat(3,1fr);">
        <div class="stat-tile accent-navy">
          <div class="stat-label"><?= $isAdmin ? 'Confirmed (All Desks)' : 'Confirmed (This Desk)' ?></div>
          <div class="stat-value" id="deskConfirmed">0</div>
        </div>
        <div class="stat-tile accent-gold">
          <div class="stat-label">Total Confirmed — All Desks</div>
          <div class="stat-value" id="totalConfirmed">0</div>
        </div>
        <div class="stat-tile accent-success">
          <div class="stat-label">EVM Turnout</div>
          <div class="stat-value"><span id="turnoutPct">0</span>%<span class="text-muted" style="font-size:16px;"> of <span id="totalIssued">0</span> issued</span></div>
        </div>
      </div>

      <div class="grid-2">
        <div>
          <div class="card mb-24" id="scanCard">
            <div class="card-header">
              <h2>Scan Returned Slip QR</h2>
              <span class="badge badge-neutral">Camera Scanner</span>
            </div>
            <p class="text-muted mb-16" style="font-size:13px;">Ask the member for their voting slip and hold its QR code up to the camera. It's confirmed automatically as soon as it's read.</p>

            <div id="cameraWrap">
              <div class="qr-stage" id="qrStage">
                <video id="qrVideo" autoplay playsinline muted></video>
                <div class="qr-target" id="qrTarget"></div>
                <div class="qr-hint">Center the slip's QR code in the frame</div>
              </div>
            </div>

            <div class="qr-camera-error hidden" id="cameraError">
              <div class="icon-circle warning" style="margin:0 auto 10px;">📷</div>
              <h4>Camera unavailable</h4>
              <p class="text-muted" style="font-size:13px;" id="cameraErrorMsg">Couldn't access the camera. Check permissions, or enter the slip number manually below.</p>
              <button class="btn btn-outline mt-8" onclick="startCamera()">Retry Camera</button>
            </div>

            <div class="qr-fallback">
              <label for="manualSerial" style="font-size:12.5px; font-weight:700; color:var(--text-400);">Or enter slip number manually</label>
              <div class="flex gap-12 mt-8">
                <input type="text" id="manualSerial" placeholder="FKCCI-2026-000123" style="flex:1;">
                <button class="btn btn-primary" onclick="submitManual()">Confirm</button>
              </div>
            </div>
          </div>

          <div class="card hidden" id="resultCard"></div>
        </div>

        <div>
          <div class="card">
            <div class="card-header">
              <h3><?= $isAdmin ? 'Recent Activity — All Desks' : 'Recent Activity — This Desk' ?></h3>
            </div>
            <div id="recentList"></div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <?= view('partials/footer') ?>
</div>

<div class="toast-wrap" id="toastWrap"></div>

<script src="<?= base_url('assets/js/vendor/jsQR.js') ?>"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<script>
function tickClock() {
  document.getElementById("clock").textContent = new Date().toLocaleTimeString("en-IN", { hour: "2-digit", minute: "2-digit" });
}
tickClock();
setInterval(tickClock, 15000);

function escapeHtml(s) {
  const d = document.createElement("div");
  d.textContent = s ?? "";
  return d.innerHTML;
}

function showToast(msg) {
  const wrap = document.getElementById("toastWrap");
  const el = document.createElement("div");
  el.className = "toast";
  el.innerHTML = `<span class="dot-ok">●</span> ${escapeHtml(msg)}`;
  wrap.appendChild(el);
  setTimeout(() => el.remove(), 3800);
}

/* ---------- Camera QR scanning (jsQR) ----------
   Runs a requestAnimationFrame decode loop over the live <video> feed via
   an offscreen canvas. While a scan is being submitted (or its result is
   on screen), decoding is paused — otherwise the same QR sitting in frame
   would be decoded and POSTed dozens of times a second. */
const video = document.getElementById("qrVideo");
const qrTarget = document.getElementById("qrTarget");
const canvas = document.createElement("canvas");
const ctx = canvas.getContext("2d", { willReadFrequently: true });
let scanning = false;
let busy = false;
let rafHandle = null;

async function startCamera() {
  document.getElementById("cameraError").classList.add("hidden");
  document.getElementById("cameraWrap").classList.remove("hidden");
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: "environment" } },
      audio: false,
    });
    video.srcObject = stream;
    await video.play();
    scanning = true;
    rafHandle = requestAnimationFrame(decodeLoop);
  } catch (err) {
    document.getElementById("cameraWrap").classList.add("hidden");
    document.getElementById("cameraError").classList.remove("hidden");
    document.getElementById("cameraErrorMsg").textContent =
      "Couldn't access the camera (" + (err.message || err.name || "unknown error") + "). "
      + "Check browser permissions, or enter the slip number manually below.";
  }
}

function decodeLoop() {
  if (!scanning) return;
  if (!busy && video.readyState === video.HAVE_ENOUGH_DATA && video.videoWidth > 0) {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const frame = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const code = jsQR(frame.data, frame.width, frame.height, { inversionAttempts: "dontInvert" });
    if (code && code.data) {
      qrTarget.classList.add("hit");
      submitPayload(code.data);
      setTimeout(() => qrTarget.classList.remove("hit"), 400);
    }
  }
  rafHandle = requestAnimationFrame(decodeLoop);
}

function submitManual() {
  const input = document.getElementById("manualSerial");
  const value = input.value.trim();
  if (!value) return;
  submitPayload(value);
  input.value = "";
}

function submitPayload(payload) {
  if (busy) return;
  busy = true;
  postJSON("<?= site_url('exit/scan') ?>", { payload }).then(res => {
    renderResult(res);
    refreshStats();
  }).catch(() => {
    showToast("Network error — please rescan.");
    busy = false;
  });
}

function renderResult(res) {
  const card = document.getElementById("resultCard");
  card.classList.remove("hidden");

  if (res.status === "confirmed") {
    const v = res.vote;
    card.innerHTML = `
      <div class="card-header">
        <h3>Vote Confirmed</h3>
        <span class="badge badge-success">✓ EVM Ballot Cast</span>
      </div>
      <div class="alert alert-success">
        <div class="alert-icon">✅</div>
        <div>
          <h4>${escapeHtml(v.member_name)} — ${escapeHtml(v.company_name)}</h4>
          <p>Slip <strong>${escapeHtml(v.serial_no)}</strong> confirmed at ${escapeHtml(v.voted_at)}.</p>
        </div>
      </div>`;
    showToast(`Confirmed: ${v.member_name} (${v.company_name})`);
    setTimeout(resumeScanning, 1800);
    return;
  }

  if (res.status === "already_marked") {
    const v = res.vote;
    card.innerHTML = `
      <div class="card-header">
        <h3>Already Confirmed</h3>
        <span class="badge badge-warning">⚠ Duplicate Scan</span>
      </div>
      <div class="alert alert-warning">
        <div class="alert-icon">⚠️</div>
        <div>
          <h4>${escapeHtml(v.member_name || "—")} — ${escapeHtml(v.company_name || "—")}</h4>
          <p>Slip <strong>${escapeHtml(v.serial_no)}</strong> was already confirmed at ${escapeHtml(v.voted_at)}. No duplicate recorded.</p>
        </div>
      </div>
      <button class="btn btn-outline btn-lg btn-block mt-16 no-print" onclick="resumeScanning()">OK — Resume Scanning</button>`;
    return;
  }

  if (res.status === "void") {
    const v = res.vote;
    card.innerHTML = `
      <div class="card-header">
        <h3>Slip Voided</h3>
        <span class="badge badge-danger">🚫 Not Valid</span>
      </div>
      <div class="alert alert-danger">
        <div class="alert-icon">🚫</div>
        <div>
          <h4>${escapeHtml(v.member_name || "—")} — ${escapeHtml(v.company_name || "—")}</h4>
          <p>Slip <strong>${escapeHtml(v.serial_no)}</strong> was voided (${escapeHtml(v.void_reason || "reason not specified")}). It cannot be confirmed as an EVM vote — send the member to the admin desk.</p>
        </div>
      </div>
      <button class="btn btn-outline btn-lg btn-block mt-16 no-print" onclick="resumeScanning()">OK — Resume Scanning</button>`;
    return;
  }

  // not_found
  card.innerHTML = `
    <div class="card-header">
      <h3>QR Not Recognized</h3>
      <span class="badge badge-danger">⚠ Unknown</span>
    </div>
    <div class="alert alert-danger">
      <div class="alert-icon">⚠️</div>
      <div>
        <h4>No matching slip found</h4>
        <p>This QR code doesn't match any issued voting slip. Try rescanning, or enter the slip number manually.</p>
      </div>
    </div>
    <button class="btn btn-outline btn-lg btn-block mt-16 no-print" onclick="resumeScanning()">OK — Resume Scanning</button>`;
}

function resumeScanning() {
  document.getElementById("resultCard").classList.add("hidden");
  busy = false;
}

function refreshStats() {
  getJSON("<?= site_url('exit/stats') ?>").then(stats => {
    document.getElementById("deskConfirmed").textContent = stats.desk_confirmed;
    document.getElementById("totalConfirmed").textContent = stats.total_confirmed;
    document.getElementById("totalIssued").textContent = stats.total_issued;
    document.getElementById("turnoutPct").textContent = stats.turnout_pct;
    renderRecent(stats.recent);
  });
}

function renderRecent(items) {
  const wrap = document.getElementById("recentList");
  if (!items.length) {
    wrap.innerHTML = '<p class="text-muted" style="font-size:13px;">No confirmations yet.</p>';
    return;
  }
  wrap.innerHTML = items.map(v => `
    <div class="flex-between" style="padding:10px 0; border-bottom:1px solid var(--border);">
      <div>
        <div style="font-weight:700; font-size:13px;">${escapeHtml(v.name)}</div>
        <div class="text-muted" style="font-size:11.5px;">${escapeHtml(v.company_name)}</div>
      </div>
      <div style="text-align:right;">
        <div class="text-muted" style="font-size:11.5px;">${escapeHtml(v.time_ago)}</div>
        <div style="font-size:11px; font-family:monospace;">${escapeHtml(v.serial_no)}</div>
      </div>
    </div>
  `).join("");
}

document.getElementById("manualSerial").addEventListener("keydown", (e) => {
  if (e.key === "Enter") submitManual();
});

startCamera();
refreshStats();
setInterval(refreshStats, 5000);
</script>
</body>
</html>
