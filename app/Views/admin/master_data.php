<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Master Data — FKCCI Election Voting Slip System</title>
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<div class="app-shell">
  <?= view('partials/admin_topbar', ['active' => $active]) ?>

  <main class="main">
    <div class="container">
      <h2 style="font-size:20px;" class="mb-16">Master Voter Data</h2>

      <div class="grid-2">
        <div>
          <div class="card mb-24">
            <div class="card-header"><h3><span class="card-muted-label">Step 1</span><br>Import Master Data (Excel)</h3></div>
            <p style="font-size:13px;">Upload the master list exported from the previous RFID system. Two rows are expected per company (designated members). Photos are <strong>not</strong> part of this file — see Step 2 below.</p>

            <form id="excelForm" enctype="multipart/form-data">
              <div class="dropzone mt-16" id="excelDropzone" onclick="document.getElementById('excelFile').click()">
                <div class="icon">📄</div>
                <h4>Drop your .xlsx file here</h4>
                <p style="font-size:12.5px;">or click to browse</p>
                <input type="file" id="excelFile" name="excel_file" accept=".xlsx,.xls,.csv" class="hidden">
              </div>
            </form>

            <div class="mt-16 hidden" id="excelProgress">
              <p style="font-size:13px;">Uploading and validating…</p>
              <div class="bar-track" style="height:8px;"><div class="bar-fill" style="width:100%;"></div></div>
            </div>

            <div class="mt-16 hidden" id="excelResult"></div>
          </div>

          <div class="card mb-24">
            <div class="card-header"><h3>Expected Column Mapping</h3></div>
            <table>
              <thead><tr><th>Excel Column</th><th>Maps To</th><th>Required</th></tr></thead>
              <tbody>
                <tr><td>RFID Tag ID</td><td><code>members.rfid_tag</code></td><td><span class="badge badge-danger">Required</span></td></tr>
                <tr><td>Member ID</td><td><code>members.member_id</code></td><td><span class="badge badge-danger">Required</span></td></tr>
                <tr><td>Member Name</td><td><code>members.name</code></td><td><span class="badge badge-danger">Required</span></td></tr>
                <tr><td>Company Name</td><td><code>companies.name</code></td><td><span class="badge badge-danger">Required</span></td></tr>
                <tr><td>Designation</td><td><code>members.designation</code></td><td><span class="badge badge-neutral">Optional</span></td></tr>
                <tr><td>Mobile</td><td><code>members.mobile</code></td><td><span class="badge badge-neutral">Optional</span></td></tr>
                <tr><td>Email</td><td><code>members.email</code></td><td><span class="badge badge-neutral">Optional</span></td></tr>
              </tbody>
            </table>
          </div>

          <div class="card">
            <div class="card-header">
              <h3><span class="card-muted-label">Step 2</span><br>Member Photos</h3>
              <span class="badge badge-neutral">Optional</span>
            </div>
            <p style="font-size:13px;">Upload a <strong>.zip of photo files</strong>, named exactly as the Member ID — e.g. <code>FKCCI-001A.jpg</code>. Matched automatically by filename; no manual mapping. Shown to the counter operator and on the QR verification screen only — <strong>never printed on the voting slip</strong>. A member without a photo just shows initials instead; nothing is blocked.</p>

            <form id="photoForm" enctype="multipart/form-data">
              <div class="dropzone mt-16" id="photoDropzone" onclick="document.getElementById('photoZip').click()">
                <div class="icon">🖼️</div>
                <h4>Drop your photos.zip here</h4>
                <p style="font-size:12.5px;">or click to browse</p>
                <input type="file" id="photoZip" name="photo_zip" accept=".zip" class="hidden">
              </div>
            </form>

            <div class="mt-16 hidden" id="photoProgress">
              <p style="font-size:13px;">Uploading and matching…</p>
              <div class="bar-track" style="height:8px;"><div class="bar-fill" style="width:100%;"></div></div>
            </div>

            <div class="mt-16 hidden" id="photoResult"></div>
          </div>
        </div>

        <div>
          <div class="card mb-24">
            <div class="card-header"><h3>Current Master Data</h3></div>
            <div class="stat-tile accent-navy mb-16">
              <div class="stat-label">Members Loaded</div>
              <div class="stat-value"><?= esc($loadedCount) ?></div>
              <div class="stat-sub"><?= esc($companyCount) ?> companies</div>
            </div>
            <div class="stat-tile accent-gold mb-16">
              <div class="stat-label">Photo Coverage</div>
              <div class="stat-value"><?= esc($photoCount) ?> / <?= esc($loadedCount) ?></div>
              <div class="stat-sub">Optional — missing photos fall back to initials on screen</div>
            </div>
          </div>

          <div class="card mb-24">
            <div class="card-header"><h3>Import History</h3></div>
            <?php $allHistory = array_merge(
                array_map(static fn ($b) => $b + ['label' => 'Excel'], $excelHistory),
                array_map(static fn ($b) => $b + ['label' => 'Photos'], $photoHistory)
            );
            usort($allHistory, static fn ($a, $b) => strcmp($b['imported_at'], $a['imported_at'])); ?>
            <table>
              <thead><tr><th>Type</th><th>File</th><th>Rows</th><th>Status</th></tr></thead>
              <tbody>
              <?php if (! $allHistory): ?>
                <tr><td colspan="4" class="text-muted">No imports yet.</td></tr>
              <?php endif; ?>
              <?php foreach ($allHistory as $b): ?>
                <tr>
                  <td><span class="badge badge-neutral"><?= esc($b['label']) ?></span></td>
                  <td><?= esc($b['filename']) ?></td>
                  <td><?= esc($b['valid_count']) ?> / <?= esc($b['row_count']) ?></td>
                  <td><?= $b['error_count'] > 0 ? '<span class="badge badge-warning">' . (int) $b['error_count'] . ' skipped</span>' : '<span class="badge badge-success">Success</span>' ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="card">
            <div class="card-header"><h3>Members Without a Photo</h3></div>
            <p style="font-size:12.5px;" class="text-muted mb-16">Not a problem — these members can still be scanned and issued a slip normally.</p>
            <?php if (! $noPhotoMembers): ?>
              <p class="text-muted" style="font-size:13px;">Every member has a photo on file.</p>
            <?php endif; ?>
            <?php foreach ($noPhotoMembers as $m): ?>
              <div class="flex-between" style="padding:7px 0; border-bottom:1px solid var(--border); font-size:13px;">
                <div>
                  <div style="font-weight:600;"><?= esc($m['name']) ?></div>
                  <div class="text-muted" style="font-size:11.5px;"><?= esc($m['member_id']) ?> · <?= esc($m['company_name']) ?></div>
                </div>
                <span class="badge badge-neutral">No photo</span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?= view('partials/footer') ?>
</div>

<script src="<?= base_url('assets/js/app.js') ?>"></script>
<script>
function escapeHtml(s) {
  const d = document.createElement("div");
  d.textContent = s ?? "";
  return d.innerHTML;
}

async function uploadWithProgress(url, formData) {
  const res = await fetch(url, {
    method: "POST",
    headers: { "X-CSRF-TOKEN": csrfToken(), "X-Requested-With": "XMLHttpRequest" },
    body: formData,
  });
  return res.json();
}

/* ---------- Excel import ---------- */
const excelDz = document.getElementById("excelDropzone");
const excelFile = document.getElementById("excelFile");
["dragover"].forEach(evt => excelDz.addEventListener(evt, e => { e.preventDefault(); excelDz.classList.add("is-drag"); }));
["dragleave"].forEach(evt => excelDz.addEventListener(evt, e => { e.preventDefault(); excelDz.classList.remove("is-drag"); }));
excelDz.addEventListener("drop", e => {
  e.preventDefault();
  excelDz.classList.remove("is-drag");
  if (e.dataTransfer.files.length) { excelFile.files = e.dataTransfer.files; previewExcel(); }
});
excelFile.addEventListener("change", previewExcel);

function previewExcel() {
  if (!excelFile.files.length) return;
  document.getElementById("excelProgress").classList.remove("hidden");
  document.getElementById("excelResult").classList.add("hidden");
  const fd = new FormData();
  fd.append("excel_file", excelFile.files[0]);
  uploadWithProgress("<?= site_url('admin/master-data/preview-excel') ?>", fd).then(res => {
    document.getElementById("excelProgress").classList.add("hidden");
    showExcelPreview(res);
  }).catch(() => {
    document.getElementById("excelProgress").classList.add("hidden");
    alert("Upload failed — please try again.");
  });
}

function showExcelPreview(res) {
  const el = document.getElementById("excelResult");
  el.classList.remove("hidden");
  if (res.error) {
    el.innerHTML = `<div class="alert alert-danger"><div class="alert-icon">⚠️</div><div><h4>Could not import</h4><p>${escapeHtml(res.error)}</p></div></div>`;
    return;
  }
  const errorList = res.errors.length
    ? `<div class="modal-details mt-8" style="text-align:left; max-height:160px; overflow:auto;">${res.errors.map(e => `<div>${escapeHtml(e)}</div>`).join("")}</div>`
    : "";
  el.innerHTML = `
    <div class="alert ${res.error_rows > 0 ? "alert-warning" : "alert-success"}">
      <div class="alert-icon">${res.error_rows > 0 ? "⚠️" : "✅"}</div>
      <div>
        <h4>Validation complete</h4>
        <p>${res.total_rows} rows parsed · ${res.valid_rows} valid · ${res.error_rows} with errors.</p>
        ${errorList}
      </div>
    </div>
    <div class="flex gap-12 mt-16">
      <button class="btn btn-primary" style="flex:1;" onclick='commitExcel(${JSON.stringify(res.token)}, ${JSON.stringify(res.extension)}, ${JSON.stringify(res.original_name)})'>Confirm &amp; Import ${res.valid_rows} Members</button>
      <button class="btn btn-outline" onclick="document.getElementById('excelResult').classList.add('hidden')">Cancel</button>
    </div>
  `;
}

function commitExcel(token, extension, originalName) {
  postJSON("<?= site_url('admin/master-data/commit-excel') ?>", { token, extension, original_name: originalName }).then(res => {
    if (res.error) { alert(res.error); return; }
    alert(`Imported ${res.imported} members` + (res.errors ? ` (${res.errors} rows had errors)` : "") + ". Reloading…");
    window.location.reload();
  });
}

/* ---------- Photo bulk upload ---------- */
const photoDz = document.getElementById("photoDropzone");
const photoZip = document.getElementById("photoZip");
["dragover"].forEach(evt => photoDz.addEventListener(evt, e => { e.preventDefault(); photoDz.classList.add("is-drag"); }));
["dragleave"].forEach(evt => photoDz.addEventListener(evt, e => { e.preventDefault(); photoDz.classList.remove("is-drag"); }));
photoDz.addEventListener("drop", e => {
  e.preventDefault();
  photoDz.classList.remove("is-drag");
  if (e.dataTransfer.files.length) { photoZip.files = e.dataTransfer.files; previewPhotos(); }
});
photoZip.addEventListener("change", previewPhotos);

function previewPhotos() {
  if (!photoZip.files.length) return;
  document.getElementById("photoProgress").classList.remove("hidden");
  document.getElementById("photoResult").classList.add("hidden");
  const fd = new FormData();
  fd.append("photo_zip", photoZip.files[0]);
  uploadWithProgress("<?= site_url('admin/master-data/preview-photos') ?>", fd).then(res => {
    document.getElementById("photoProgress").classList.add("hidden");
    showPhotoPreview(res);
  }).catch(() => {
    document.getElementById("photoProgress").classList.add("hidden");
    alert("Upload failed — please try again.");
  });
}

function showPhotoPreview(res) {
  const el = document.getElementById("photoResult");
  el.classList.remove("hidden");
  if (res.error) {
    el.innerHTML = `<div class="alert alert-danger"><div class="alert-icon">⚠️</div><div><h4>Could not process zip</h4><p>${escapeHtml(res.error)}</p></div></div>`;
    return;
  }
  const unmatchedList = res.unmatched.length
    ? `<div class="alert alert-warning mt-16"><div class="alert-icon">⚠️</div><div><h4>${res.unmatched_count} file(s) could not be matched</h4><div class="modal-details mt-8" style="text-align:left; max-height:140px; overflow:auto;">${res.unmatched.map(u => `<div>${escapeHtml(u)}</div>`).join("")}</div></div></div>`
    : "";
  el.innerHTML = `
    <div class="alert alert-success">
      <div class="alert-icon">✅</div>
      <div><h4>${res.matched_count} photo(s) matched automatically</h4><p>By filename = Member ID.</p></div>
    </div>
    ${unmatchedList}
    <div class="flex gap-12 mt-16">
      <button class="btn btn-primary" style="flex:1;" onclick='commitPhotos(${JSON.stringify(res.token)})'>Confirm &amp; Save ${res.matched_count} Photos</button>
      <button class="btn btn-outline" onclick="document.getElementById('photoResult').classList.add('hidden')">Cancel</button>
    </div>
  `;
}

function commitPhotos(token) {
  postJSON("<?= site_url('admin/master-data/commit-photos') ?>", { token }).then(res => {
    if (res.error) { alert(res.error); return; }
    alert(`Saved ${res.saved} photos` + (res.skipped ? ` (${res.skipped} skipped)` : "") + ". Reloading…");
    window.location.reload();
  });
}
</script>
</body>
</html>
