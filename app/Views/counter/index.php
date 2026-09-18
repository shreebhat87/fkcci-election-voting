<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Counter <?= esc($counterNo) ?> — FKCCI Election Voting Slip System</title>
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-shell">

  <header class="topbar no-print">
    <div class="container topbar-inner">
      <a class="brand" href="<?= base_url('counter') ?>">
        <div class="brand-mark"><img src="<?= base_url('assets/img/fkcci-mark.png') ?>" alt="FKCCI"></div>
        <div class="brand-text">
          <div class="title">FKCCI Election 2026</div>
          <div class="subtitle">Counter Terminal</div>
        </div>
      </a>
      <div class="session-chip">
        <span class="dot"></span>
        <span>Counter <?= esc($counterNo) ?></span>
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
          <div class="stat-label">Votes Issued (This Counter)</div>
          <div class="stat-value" id="counterVotes">0</div>
        </div>
        <div class="stat-tile accent-gold">
          <div class="stat-label">Total Votes Cast (All Counters)</div>
          <div class="stat-value" id="totalVotes">0</div>
        </div>
        <div class="stat-tile accent-success">
          <div class="stat-label">Companies Represented</div>
          <div class="stat-value"><span id="votedCompanies">0</span><span class="text-muted" style="font-size:16px;"> / <span id="totalCompanies">0</span></span></div>
        </div>
      </div>

      <div class="grid-2">
        <div>
          <div class="card mb-24" id="scanCard">
            <div class="card-header">
              <h2>Scan Voter's RFID Card</h2>
              <span class="badge badge-neutral">Zebra RFID Reader</span>
            </div>

            <div class="scan-zone" id="scanZone">
              <div class="scan-icon">📡</div>
              <h3 id="scanZoneTitle">Ready to scan</h3>
              <p>Tap the member's RFID card on the reader. The reader types the tag directly into the field below (keyboard-wedge mode) — no need to click into it first.</p>
              <input type="text" id="rfidInput" placeholder="Waiting for card scan…" autocomplete="off" autofocus>
            </div>
          </div>

          <div class="card hidden" id="resultCard"></div>
        </div>

        <div>
          <div class="card">
            <div class="card-header">
              <h3>Recent Activity — This Counter</h3>
            </div>
            <div id="recentList"></div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <?= view('partials/footer') ?>
</div>

<!-- Already-voted modal -->
<div class="modal-backdrop hidden no-print" id="votedModal">
  <div class="modal">
    <div class="icon-circle danger">🚫</div>
    <h3>Vote Already Cast</h3>
    <p>Only one voting slip is allowed per company. A representative of this company has already voted.</p>
    <div class="modal-details" id="votedDetails"></div>
    <div class="modal-actions">
      <button class="btn btn-danger btn-block" onclick="closeVotedModal()">Acknowledge — No Slip Issued</button>
    </div>
  </div>
</div>

<!-- Unknown card modal -->
<div class="modal-backdrop hidden no-print" id="unknownModal">
  <div class="modal">
    <div class="icon-circle warning">⚠️</div>
    <h3>Card Not Recognized</h3>
    <p>This RFID tag was not found in the master voter list. Please verify the card, or send the member to the election admin desk.</p>
    <div class="modal-details"><div><span class="k">Scanned tag</span><span class="v" id="unknownTag">—</span></div></div>
    <div class="modal-actions">
      <button class="btn btn-outline btn-block" onclick="closeUnknownModal()">Close</button>
    </div>
  </div>
</div>

<!-- Print area -->
<div id="printArea" class="hidden">
  <div class="slip" id="slipTemplate"></div>
</div>

<div class="toast-wrap" id="toastWrap"></div>

<script src="<?= base_url('assets/js/app.js') ?>"></script>
<script>
const counterNo = <?= (int) $counterNo ?>;

function tickClock() {
  document.getElementById("clock").textContent = new Date().toLocaleTimeString("en-IN", { hour: "2-digit", minute: "2-digit" });
}
tickClock();
setInterval(tickClock, 15000);

/* ---------- Zebra RFID reader (keyboard-wedge) input handling ----------
   The reader behaves exactly like a keyboard: scanning a card "types" the
   tag ID into whatever input currently has focus, then sends Enter. The
   only integration work is making sure #rfidInput has focus essentially
   all the time this screen is open, so a scan never lands nowhere. */
const rfidInput = document.getElementById("rfidInput");

function refocusInput() {
  if (!scanningLocked() && document.activeElement !== rfidInput) {
    rfidInput.focus();
  }
}
function scanningLocked() {
  return rfidInput.disabled
    || !document.getElementById("votedModal").classList.contains("hidden")
    || !document.getElementById("unknownModal").classList.contains("hidden");
}
// Refocus after any click anywhere on the scan card (operator tapping the
// screen shouldn't be able to knock focus off the reader target), on
// window refocus (e.g. after the print dialog closes), and as a periodic
// safety net in case anything else steals it.
document.getElementById("scanCard").addEventListener("mousedown", () => setTimeout(refocusInput, 0));
window.addEventListener("focus", refocusInput);
setInterval(refocusInput, 1500);

rfidInput.addEventListener("keydown", (e) => {
  if (e.key === "Enter" && e.target.value.trim()) {
    handleScan(e.target.value.trim());
  }
});

function refreshStats() {
  getJSON("<?= site_url('counter/stats') ?>").then(stats => {
    document.getElementById("totalVotes").textContent = stats.total_votes;
    document.getElementById("votedCompanies").textContent = stats.voted_companies;
    document.getElementById("totalCompanies").textContent = stats.total_companies;
    document.getElementById("counterVotes").textContent = stats.counter_votes;
    renderRecent(stats.recent);
  });
}

function renderRecent(votes) {
  const wrap = document.getElementById("recentList");
  if (!votes.length) {
    wrap.innerHTML = '<p class="text-muted" style="font-size:13px;">No slips issued yet at this counter.</p>';
    return;
  }
  wrap.innerHTML = votes.map(v => `
    <div class="flex-between" style="padding:10px 0; border-bottom:1px solid var(--border);">
      <div>
        <div style="font-weight:700; font-size:13px;">${escapeHtml(v.name)}</div>
        <div class="text-muted" style="font-size:11.5px;">${escapeHtml(v.company_name)}</div>
      </div>
      <div style="text-align:right;">
        <div class="text-muted" style="font-size:11.5px;">${escapeHtml(v.time_ago)}</div>
        <a href="${v.slip_url}" target="_blank" rel="noopener" style="font-size:11.5px; font-weight:600;">View / Print ↗</a>
      </div>
    </div>
  `).join("");
}

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

function resetScanZone() {
  document.getElementById("resultCard").classList.add("hidden");
  document.getElementById("resultCard").innerHTML = "";
  document.getElementById("scanZone").classList.remove("is-active");
  document.getElementById("scanZoneTitle").textContent = "Ready to scan";
  rfidInput.value = "";
  rfidInput.disabled = false;
  rfidInput.focus();
}

function handleScan(rfid) {
  document.getElementById("scanZone").classList.add("is-active");
  document.getElementById("scanZoneTitle").textContent = "Card read — looking up member…";
  rfidInput.disabled = true;

  postJSON("<?= site_url('counter/lookup') ?>", { rfid }).then(res => {
    if (res.status === "unknown") {
      document.getElementById("unknownTag").textContent = res.rfid || rfid;
      document.getElementById("unknownModal").classList.remove("hidden");
      resetScanZone();
      return;
    }
    if (res.status === "already_voted") {
      showAlreadyVoted(res.vote);
      resetScanZone();
      return;
    }
    renderEligible(res.member);
  }).catch(() => {
    showToast("Network error — please try scanning again.");
    resetScanZone();
  });
}

function showAlreadyVoted(vote) {
  document.getElementById("votedDetails").innerHTML = `
    <div><span class="k">Voted by</span><span class="v">${escapeHtml(vote.member_name || "—")}</span></div>
    <div><span class="k">Slip No.</span><span class="v">${escapeHtml(vote.serial_no)}</span></div>
    <div><span class="k">Counter</span><span class="v">Counter ${escapeHtml(String(vote.counter_no))}</span></div>
    <div><span class="k">Time</span><span class="v">${escapeHtml(vote.issued_at)}</span></div>
  `;
  document.getElementById("votedModal").classList.remove("hidden");
}

function renderEligible(member) {
  document.getElementById("scanZone").classList.remove("is-active");
  const card = document.getElementById("resultCard");
  card.classList.remove("hidden");
  const photoHtml = member.photo_url
    ? `<div class="photo-avatar avatar-lg"><img src="${member.photo_url}" alt="" style="width:100%;height:100%;object-fit:cover;"></div>`
    : `<div class="avatar avatar-lg" style="background:#1d4e89;">${escapeHtml(initials(member.name))}</div>`;
  card.innerHTML = `
    <div class="card-header">
      <h3>Member Verified</h3>
      <span class="badge badge-success">✓ Eligible to Vote</span>
    </div>
    <div class="member-result">
      ${photoHtml}
      <div class="info">
        <h3>${escapeHtml(member.name)}</h3>
        <p>${escapeHtml(member.designation || "")} · ${escapeHtml(member.company_name)}</p>
        ${!member.photo_url ? '<p class="no-photo-note">📷 No photo on file — verify identity against the physical card.</p>' : ""}
        <div class="meta-row">
          <div class="meta-item"><span class="label">Member ID</span><strong>${escapeHtml(member.member_id)}</strong></div>
          <div class="meta-item"><span class="label">RFID Tag</span><strong>${escapeHtml(member.rfid_tag)}</strong></div>
          <div class="meta-item"><span class="label">Mobile</span><strong>${escapeHtml(member.mobile || "—")}</strong></div>
        </div>
      </div>
    </div>
    <div class="flex gap-12 mt-16 no-print">
      <button class="btn btn-success btn-lg" style="flex:1;" onclick="issueSlip('${member.rfid_tag}')">🖨️ Print Voting Slip</button>
      <button class="btn btn-outline btn-lg" onclick="resetScanZone()">Cancel</button>
    </div>
  `;
  document.getElementById("scanZoneTitle").textContent = "Ready to scan";
  rfidInput.disabled = false;
  rfidInput.value = "";
}

function initials(name) {
  return (name || "").split(" ").filter(Boolean).slice(0, 2).map(w => w[0]).join("").toUpperCase();
}

function issueSlip(rfid) {
  postJSON("<?= site_url('counter/issue') ?>", { rfid }).then(res => {
    if (res.status === "already_voted") {
      showAlreadyVoted(res.vote);
      resetScanZone();
      refreshStats();
      return;
    }
    if (res.status !== "issued") {
      showToast("Could not issue slip — please try again.");
      resetScanZone();
      return;
    }
    buildSlip(res.vote);
    window.print();
    showToast(`Slip ${res.vote.serial_no} issued for ${res.vote.name} (${res.vote.company_name})`);
    refreshStats();
    showIssuedConfirmation(res.vote, res.slip_url);
  }).catch(() => {
    showToast("Network error — the vote may not have been recorded. Check the voter log before rescanning.");
    resetScanZone();
  });
}

function showIssuedConfirmation(vote, slipUrl) {
  document.getElementById("scanZone").classList.remove("is-active");
  document.getElementById("scanZoneTitle").textContent = "Slip issued — ready for next voter";
  rfidInput.value = "";
  rfidInput.disabled = true;

  const card = document.getElementById("resultCard");
  card.classList.remove("hidden");
  card.innerHTML = `
    <div class="card-header">
      <h3>Slip Issued</h3>
      <span class="badge badge-success">✓ Sent to Printer</span>
    </div>
    <div class="alert alert-success">
      <div class="alert-icon">✅</div>
      <div>
        <h4>${escapeHtml(vote.name)} — ${escapeHtml(vote.company_name)}</h4>
        <p>Slip <strong>${escapeHtml(vote.serial_no)}</strong> issued at Counter ${escapeHtml(String(vote.counter_no))}.</p>
      </div>
    </div>
    <p class="hint mt-8">Printer offline, wrong tray, or need a different printer? Reopen the slip below — it can be viewed on screen or reprinted to any connected printer without rescanning the card (the vote is already recorded).</p>
    <div class="flex gap-12 mt-16 no-print">
      <a class="btn btn-outline btn-lg" style="flex:1;" href="${slipUrl}" target="_blank" rel="noopener">🖨️ View / Print Again</a>
      <button class="btn btn-primary btn-lg" style="flex:1;" onclick="resetScanZone()">➡ Next Voter</button>
    </div>
  `;
}

function buildSlip(vote) {
  document.getElementById("slipTemplate").innerHTML = `
    <div class="slip-header">
      <div class="org">Federation of Karnataka Chambers of Commerce &amp; Industry</div>
      <div class="title">OFFICIAL VOTING SLIP</div>
      <div class="election">FKCCI Election 2026</div>
    </div>
    <div class="slip-row"><span class="k">Member ID</span><span class="v">${escapeHtml(vote.member_id)}</span></div>
    <div class="slip-row"><span class="k">Name</span><span class="v">${escapeHtml(vote.name)}</span></div>
    <div class="slip-row"><span class="k">Company</span><span class="v">${escapeHtml(vote.company_name)}</span></div>
    <div class="slip-qr"><img src="<?= site_url('slip') ?>/${vote.serial_no}/qr" alt="QR code" width="150" height="150"></div>
    <div class="slip-serial">${escapeHtml(vote.serial_no)}</div>
    <div class="slip-row mt-8"><span class="k">Counter</span><span class="v">Counter ${escapeHtml(String(vote.counter_no))}</span></div>
    <div class="slip-row"><span class="k">Issued</span><span class="v">${escapeHtml(vote.issued_at)}</span></div>
    <div class="slip-footer">Present this slip at the polling booth. Non-transferable. One slip per company.</div>
  `;
}

function closeVotedModal() {
  document.getElementById("votedModal").classList.add("hidden");
  refocusInput();
}
function closeUnknownModal() {
  document.getElementById("unknownModal").classList.add("hidden");
  refocusInput();
}

refreshStats();
</script>
</body>
</html>
