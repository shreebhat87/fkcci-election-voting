<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Apply for Membership — FKCCI</title>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<style>
  .apply-wrap { max-width: 780px; margin: 0 auto; padding: 24px 16px 56px; }
  .apply-header { text-align: center; margin: 18px 0 26px; }
  .section-title { font-size: 15px; font-weight: 800; color: var(--navy-900); margin: 0 0 14px; padding-bottom: 8px; border-bottom: 2px solid var(--border); }
  .rep-block { border: 1px solid var(--border); border-radius: var(--radius); padding: 14px; margin-bottom: 14px; }
  .rep-block h4 { font-size: 13px; margin-bottom: 10px; color: var(--text-400); text-transform: uppercase; letter-spacing: .4px; }
  .fee-box { background: var(--navy-050); border-radius: var(--radius); padding: 16px; }
  .fee-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13.5px; }
  .fee-row.total { font-weight: 800; font-size: 16px; border-top: 1px solid var(--border); margin-top: 6px; padding-top: 10px; }
  .doc-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 13px; }
  .doc-row:last-child { border-bottom: none; }
</style>
</head>
<body>

<div class="apply-wrap">
  <div class="apply-header">
    <div class="brand-mark" style="margin:0 auto 10px;"><img src="<?= base_url('assets/img/fkcci-mark.png') ?>" alt="FKCCI"></div>
    <h1 style="font-size:20px;">Application for Membership</h1>
    <p class="text-muted">Federation of Karnataka Chambers of Commerce &amp; Industry</p>
  </div>

  <?php $errs = $errors ?? []; ?>
  <?php if ($errs): ?>
    <div class="alert alert-danger mb-16">
      <div class="alert-icon">⚠️</div>
      <div>
        <h4>Please fix the following</h4>
        <ul style="margin:6px 0 0 18px;">
          <?php foreach ($errs as $e): ?><li><?= esc(is_array($e) ? implode(', ', $e) : $e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <?= form_open_multipart('membership/apply', ['id' => 'applyForm']) ?>

    <div class="card mb-24">
      <div class="section-title">1 · Organisation Details</div>
      <div class="field">
        <label for="name">Name of the Organisation *</label>
        <input type="text" id="name" name="name" value="<?= esc(old('name')) ?>" required>
      </div>
      <div class="field">
        <label for="address">Address *</label>
        <textarea id="address" name="address" rows="3" required><?= esc(old('address')) ?></textarea>
      </div>
      <div class="field-row">
        <div class="field"><label for="phone">Phone</label><input type="text" id="phone" name="phone" value="<?= esc(old('phone')) ?>"></div>
        <div class="field"><label for="mobile">Mobile *</label><input type="text" id="mobile" name="mobile" value="<?= esc(old('mobile')) ?>" required></div>
      </div>
      <div class="field-row">
        <div class="field"><label for="email">E-mail</label><input type="email" id="email" name="email" value="<?= esc(old('email')) ?>"></div>
        <div class="field"><label for="website">Website</label><input type="text" id="website" name="website" value="<?= esc(old('website')) ?>"></div>
      </div>
      <div class="field" style="max-width:200px;">
        <label for="year_established">Year of Establishment</label>
        <input type="number" id="year_established" name="year_established" min="1900" max="<?= date('Y') ?>" value="<?= esc(old('year_established')) ?>">
      </div>
    </div>

    <div class="card mb-24">
      <div class="section-title">2 · Nature of Business</div>
      <div class="field">
        <label for="nature_of_business">Nature of Business *</label>
        <select id="nature_of_business" name="nature_of_business" required onchange="onNatureChange()">
          <option value="">Select…</option>
          <option value="manufacture">Manufacture</option>
          <option value="trade">Trade</option>
          <option value="service">Service</option>
          <option value="profession">Profession</option>
          <option value="association">Association</option>
          <option value="district_chamber">District Chamber of Commerce</option>
          <option value="other_association">Other Association</option>
        </select>
      </div>
      <div class="field hidden" id="scaleField">
        <label for="business_scale">Scale (turnover Rs.10 crore threshold) *</label>
        <select id="business_scale" name="business_scale" onchange="recalcFee()">
          <option value="">Select…</option>
          <option value="small">Small</option>
          <option value="large_medium">Large / Medium</option>
        </select>
      </div>
      <div class="field">
        <label for="product_description">Product / Service Description</label>
        <textarea id="product_description" name="product_description" rows="2"><?= esc(old('product_description')) ?></textarea>
      </div>
    </div>

    <div class="card mb-24">
      <div class="section-title">3 · Membership Category</div>
      <div class="field-row">
        <div class="field">
          <label for="membership_category">Category *</label>
          <select id="membership_category" name="membership_category" required onchange="onCategoryChange()">
            <option value="ordinary">Ordinary (annual)</option>
            <option value="patron">Patron (one-time)</option>
          </select>
        </div>
        <div class="field hidden" id="patronTierField">
          <label for="patron_tier">Patron Tier</label>
          <select id="patron_tier" name="patron_tier" onchange="recalcFee()">
            <option value="">Standard (category-based fee)</option>
            <option value="gold">Gold — Rs. 3,00,000</option>
            <option value="platinum">Platinum — Rs. 5,00,000</option>
          </select>
        </div>
      </div>
      <div class="fee-box" id="feeBox">
        <p class="text-muted" style="font-size:12.5px;">Select nature of business and category above to see the fee.</p>
      </div>
    </div>

    <div class="card mb-24">
      <div class="section-title">4 · Representatives</div>
      <p class="hint mb-16">Small category: 2 representatives. Large/Medium and Association/Chamber members: up to 3. Each representative's RFID access card is issued after your application is approved.</p>

      <div class="rep-block">
        <h4>Representative 1 *</h4>
        <div class="field-row">
          <div class="field"><label>Name *</label><input type="text" name="rep1_name" value="<?= esc(old('rep1_name')) ?>" required></div>
          <div class="field"><label>Designation</label><input type="text" name="rep1_designation" value="<?= esc(old('rep1_designation')) ?>"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Mobile</label><input type="text" name="rep1_mobile" value="<?= esc(old('rep1_mobile')) ?>"></div>
          <div class="field"><label>E-mail</label><input type="email" name="rep1_email" value="<?= esc(old('rep1_email')) ?>"></div>
        </div>
        <div class="field"><label>Photo (passport size)</label><input type="file" name="rep1_photo" accept=".jpg,.jpeg,.png"></div>
      </div>

      <div class="rep-block">
        <h4>Representative 2 *</h4>
        <div class="field-row">
          <div class="field"><label>Name *</label><input type="text" name="rep2_name" value="<?= esc(old('rep2_name')) ?>" required></div>
          <div class="field"><label>Designation</label><input type="text" name="rep2_designation" value="<?= esc(old('rep2_designation')) ?>"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Mobile</label><input type="text" name="rep2_mobile" value="<?= esc(old('rep2_mobile')) ?>"></div>
          <div class="field"><label>E-mail</label><input type="email" name="rep2_email" value="<?= esc(old('rep2_email')) ?>"></div>
        </div>
        <div class="field"><label>Photo (passport size)</label><input type="file" name="rep2_photo" accept=".jpg,.jpeg,.png"></div>
      </div>

      <div class="rep-block hidden" id="rep3Block">
        <h4>Representative 3 (optional)</h4>
        <div class="field-row">
          <div class="field"><label>Name</label><input type="text" name="rep3_name" value="<?= esc(old('rep3_name')) ?>"></div>
          <div class="field"><label>Designation</label><input type="text" name="rep3_designation" value="<?= esc(old('rep3_designation')) ?>"></div>
        </div>
        <div class="field-row">
          <div class="field"><label>Mobile</label><input type="text" name="rep3_mobile" value="<?= esc(old('rep3_mobile')) ?>"></div>
          <div class="field"><label>E-mail</label><input type="email" name="rep3_email" value="<?= esc(old('rep3_email')) ?>"></div>
        </div>
        <div class="field"><label>Photo (passport size)</label><input type="file" name="rep3_photo" accept=".jpg,.jpeg,.png"></div>
      </div>
    </div>

    <div class="card mb-24">
      <div class="section-title">5 · Financial &amp; Registration Information</div>
      <div class="field" style="max-width:280px;">
        <label for="annual_turnover">Annual Turnover, Previous FY (Rs.)</label>
        <input type="number" id="annual_turnover" name="annual_turnover" step="0.01" value="<?= esc(old('annual_turnover')) ?>">
      </div>
      <div class="field-row">
        <div class="field"><label>GSTIN</label><input type="text" name="gstin" value="<?= esc(old('gstin')) ?>"></div>
        <div class="field"><label>MSME Registration No.</label><input type="text" name="msme_registration_no" value="<?= esc(old('msme_registration_no')) ?>"></div>
      </div>
      <div class="field-row">
        <div class="field"><label>PAN</label><input type="text" name="pan" value="<?= esc(old('pan')) ?>"></div>
        <div class="field"><label>Company Registration No.</label><input type="text" name="company_registration_no" value="<?= esc(old('company_registration_no')) ?>"></div>
      </div>
      <div class="field-row">
        <div class="field"><label>IE Code</label><input type="text" name="ie_code" value="<?= esc(old('ie_code')) ?>"></div>
        <div class="field"><label>Professional (Institute Membership No.)</label><input type="text" name="professional_institute_membership_no" value="<?= esc(old('professional_institute_membership_no')) ?>"></div>
      </div>
      <div class="field"><label>Name of Bankers</label><input type="text" name="bankers_name" value="<?= esc(old('bankers_name')) ?>"></div>
    </div>

    <div class="card mb-24">
      <div class="section-title">6 · Supporting Documents (as applicable)</div>
      <div class="doc-row"><span>Company Registration Certificate</span><input type="file" name="doc_company_reg" accept=".pdf,.jpg,.jpeg,.png"></div>
      <div class="doc-row"><span>GST Registration Certificate &amp; PAN Copy</span><input type="file" name="doc_gst_pan" accept=".pdf,.jpg,.jpeg,.png"></div>
      <div class="doc-row"><span>Latest Audited Profit &amp; Loss Account</span><input type="file" name="doc_audited_pl" accept=".pdf,.jpg,.jpeg,.png"></div>
      <div class="doc-row"><span>MOA/AOA · Partnership Deed · Trust Deed · Bye-Laws</span><input type="file" name="doc_moa_aoa" accept=".pdf,.jpg,.jpeg,.png"></div>
      <div class="doc-row"><span>MSME Certificate</span><input type="file" name="doc_msme" accept=".pdf,.jpg,.jpeg,.png"></div>
    </div>

    <button class="btn btn-primary btn-lg btn-block" type="submit">Continue to Payment →</button>
    <p class="hint text-center mt-16">Already applied? <a href="<?= base_url('membership/status') ?>">Check your application status</a>.</p>
  <?= form_close() ?>
</div>

<?= view('partials/footer') ?>

<script>
const SCALED = ["manufacture", "trade", "service"];
const MULTI_REP = ["association", "district_chamber", "other_association"];

const FEES = {
  ordinary_admission: 1180,
  ordinary: {
    "manufacture:small": [3540, 4720], "manufacture:large_medium": [8850, 10030],
    "trade:small": [3540, 4720], "trade:large_medium": [8850, 10030],
    "service:small": [3540, 4720], "service:large_medium": [8850, 10030],
    "profession": [3540, 4720], "district_chamber": [7080, 8260],
    "association": [7080, 8260], "other_association": [7080, 8260],
  },
  patron: {
    "manufacture:small": [45000, 53100], "manufacture:large_medium": [75000, 88500],
    "trade:small": [45000, 53100], "trade:large_medium": [75000, 88500],
    "service:small": [45000, 53100], "service:large_medium": [75000, 88500],
    "profession": [45000, 53100], "district_chamber": [115000, 135700],
    "association": [115000, 135700], "other_association": [115000, 135700],
  },
  patron_tier_flat: { gold: 300000, platinum: 500000 },
};

function onNatureChange() {
  const nature = document.getElementById("nature_of_business").value;
  document.getElementById("scaleField").classList.toggle("hidden", !SCALED.includes(nature));
  const maxReps = MULTI_REP.includes(nature) ? 3 : (SCALED.includes(nature) && document.getElementById("business_scale").value === "large_medium" ? 3 : 2);
  document.getElementById("rep3Block").classList.toggle("hidden", maxReps < 3);
  recalcFee();
}

function onCategoryChange() {
  document.getElementById("patronTierField").classList.toggle("hidden", document.getElementById("membership_category").value !== "patron");
  recalcFee();
}

function fmt(n) { return "₹" + n.toLocaleString("en-IN"); }

function recalcFee() {
  const nature = document.getElementById("nature_of_business").value;
  const scale = document.getElementById("business_scale").value;
  const category = document.getElementById("membership_category").value;
  const tier = document.getElementById("patron_tier").value;
  const box = document.getElementById("feeBox");

  if (!nature || (SCALED.includes(nature) && !scale)) {
    box.innerHTML = '<p class="text-muted" style="font-size:12.5px;">Select nature of business and category above to see the fee.</p>';
    return;
  }

  if (category === "patron" && tier) {
    const flat = FEES.patron_tier_flat[tier];
    box.innerHTML = `<div class="fee-row total"><span>Total (one-time, ${tier})</span><span>${fmt(flat)}</span></div>`;
    return;
  }

  const key = SCALED.includes(nature) ? `${nature}:${scale}` : nature;
  const table = category === "patron" ? FEES.patron : FEES.ordinary;
  const row = table[key];
  if (!row) { box.innerHTML = ""; return; }

  const [sub, total] = row;
  if (category === "patron") {
    box.innerHTML = `
      <div class="fee-row"><span>Patron fee (base)</span><span>${fmt(sub)}</span></div>
      <div class="fee-row"><span>GST @ 18%</span><span>${fmt(total - sub)}</span></div>
      <div class="fee-row total"><span>Total (one-time)</span><span>${fmt(total)}</span></div>`;
  } else {
    box.innerHTML = `
      <div class="fee-row"><span>Admission fee (incl. GST)</span><span>${fmt(FEES.ordinary_admission)}</span></div>
      <div class="fee-row"><span>Annual subscription (incl. GST)</span><span>${fmt(sub)}</span></div>
      <div class="fee-row total"><span>Total payable now</span><span>${fmt(total)}</span></div>`;
  }
}

onNatureChange();
onCategoryChange();
</script>

</body>
</html>
