/* =========================================================
   FKCCI Election Voting Slip System — Prototype mock data layer
   Simulates the master data import + server-side vote ledger.
   In the real CodeIgniter 4 app this is replaced by MySQL tables
   (members, companies, votes) and REST endpoints.
   ========================================================= */

const FKCCI = (function () {
  const STORAGE_KEY = "fkcci_proto_state_v1";
  const ELECTION_NAME = "FKCCI General Council Election 2026";
  const ELECTION_DATE = "2026-09-24";
  const COUNTERS = Array.from({ length: 10 }, (_, i) => i + 1);

  // ---- Master data (mock — normally imported from the Excel sheet) ----
  const COMPANIES = [
    "Bangalore Precision Tools Pvt Ltd",
    "Karnataka Silk Exports Ltd",
    "South India Steel Traders",
    "Deccan Electronics Pvt Ltd",
    "Mysuru Agro Industries",
    "Cauvery Textiles Pvt Ltd",
    "Vidyanagar Engineering Works",
    "Nandi Hills Foods Pvt Ltd",
    "Garuda Logistics Pvt Ltd",
    "Sandalwood Chemicals Pvt Ltd",
  ];

  const NAME_PAIRS = [
    ["Ramesh Gowda", "Suma Ramesh"],
    ["Anitha Rao", "Vikram Rao"],
    ["Manjunath K", "Deepa Manjunath"],
    ["Suresh Kumar", "Lakshmi Suresh"],
    ["Prakash Shetty", "Nandini Shetty"],
    ["Harish Babu", "Shwetha Harish"],
    ["Ravindra Patil", "Meera Patil"],
    ["Girish Nayak", "Pooja Girish"],
    ["Arvind Shenoy", "Kavya Arvind"],
    ["Naveen Reddy", "Divya Naveen"],
  ];

  const DESIGNATIONS = ["Managing Director", "Director", "Partner", "Proprietor"];

  function pad(n, len) { return String(n).padStart(len, "0"); }
  function initials(name) {
    return name.split(" ").filter(Boolean).slice(0, 2).map(w => w[0]).join("").toUpperCase();
  }
  const AVATAR_COLORS = ["#1d4e89", "#8a1c2b", "#1f9d55", "#b7791f", "#5b3a8e", "#0b2545", "#c9a227"];
  function avatarColor(seed) {
    let h = 0;
    for (let i = 0; i < seed.length; i++) h = (h * 31 + seed.charCodeAt(i)) | 0;
    return AVATAR_COLORS[Math.abs(h) % AVATAR_COLORS.length];
  }

  // Photo is an OPTIONAL field — uploaded separately from the Excel import
  // (see admin-upload.html) and matched to a member by filename == Member ID.
  // These few are seeded as "not yet uploaded" to demo the fallback state;
  // nothing in the voting flow requires a photo to be present.
  const NO_PHOTO_MEMBER_IDS = new Set(["FKCCI-003A", "FKCCI-003B", "FKCCI-010B"]);

  function buildMembers() {
    const members = [];
    COMPANIES.forEach((company, ci) => {
      NAME_PAIRS[ci].forEach((name, ni) => {
        const memberId = `FKCCI-${pad(ci + 1, 3)}${ni === 0 ? "A" : "B"}`;
        const rfid = `E200${pad(ci + 1, 4)}${pad(ni + 1, 2)}9A7B${pad(ci * 7 + ni, 4)}`;
        const hasPhoto = !NO_PHOTO_MEMBER_IDS.has(memberId);
        members.push({
          rfid,
          memberId,
          name,
          company,
          designation: DESIGNATIONS[(ci + ni) % DESIGNATIONS.length],
          mobile: `98${pad(10000000 + ci * 111 + ni * 37, 8)}`,
          email: `${name.toLowerCase().replace(/\s+/g, ".")}@example.com`,
          initials: initials(name),
          color: avatarColor(name + company),
          hasPhoto,
          photoPath: hasPhoto ? `/uploads/photos/${memberId}.jpg` : null,
        });
      });
    });
    return members;
  }

  const MEMBERS = buildMembers();

  function findMemberByRFID(rfid) {
    const clean = (rfid || "").trim().toUpperCase();
    return MEMBERS.find(m => m.rfid.toUpperCase() === clean) || null;
  }

  function findMemberById(memberId) {
    return MEMBERS.find(m => m.memberId === memberId) || null;
  }

  // ---- State (localStorage-backed vote ledger, simulates server DB) ----
  function loadState() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (raw) return JSON.parse(raw);
    } catch (e) { /* ignore */ }
    return seedState();
  }

  function seedState() {
    // Pre-seed a few votes so the dashboard / log look realistic on first load.
    const now = Date.now();
    const seedVotes = [
      { memberIdx: 0, counter: 3, minsAgo: 62 },   // Bangalore Precision Tools -> A voted
      { memberIdx: 3, counter: 7, minsAgo: 48 },   // South India Steel Traders -> A voted
      { memberIdx: 9, counter: 1, minsAgo: 30 },   // Deccan Electronics -> B voted
      { memberIdx: 12, counter: 5, minsAgo: 15 },  // Cauvery Textiles -> A voted
      { memberIdx: 17, counter: 9, minsAgo: 4 },   // Nandi Hills Foods -> B voted
    ];
    const votes = seedVotes.map((s, i) => {
      const m = MEMBERS[s.memberIdx];
      return {
        serial: `FKCCI/2026/${pad(i + 1, 6)}`,
        rfid: m.rfid,
        memberId: m.memberId,
        name: m.name,
        company: m.company,
        designation: m.designation,
        counter: s.counter,
        timestamp: now - s.minsAgo * 60000,
        status: "issued",
      };
    });
    const state = { votes, nextSerial: seedVotes.length + 1 };
    saveState(state);
    return state;
  }

  function saveState(state) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(state)); } catch (e) { /* ignore */ }
  }

  function resetDemoData() {
    localStorage.removeItem(STORAGE_KEY);
    return seedState();
  }

  function getAllVotes() {
    return loadState().votes.slice().sort((a, b) => b.timestamp - a.timestamp);
  }

  function getCompanyVoteRecord(company) {
    const votes = loadState().votes;
    return votes.find(v => v.company === company && v.status === "issued") || null;
  }

  function issueVote(member, counter) {
    const state = loadState();
    const serial = `FKCCI/2026/${pad(state.nextSerial, 6)}`;
    const record = {
      serial,
      rfid: member.rfid,
      memberId: member.memberId,
      name: member.name,
      company: member.company,
      designation: member.designation,
      counter,
      timestamp: Date.now(),
      status: "issued",
    };
    state.votes.push(record);
    state.nextSerial += 1;
    saveState(state);
    return record;
  }

  function voidVote(serial, reason) {
    const state = loadState();
    const v = state.votes.find(v => v.serial === serial);
    if (v) {
      v.status = "void";
      v.voidReason = reason || "Not specified";
      v.voidedAt = Date.now();
      saveState(state);
    }
    return v;
  }

  function findVoteBySerial(serial) {
    return loadState().votes.find(v => v.serial === serial) || null;
  }

  function getStats() {
    const votes = loadState().votes.filter(v => v.status === "issued");
    const totalMembers = MEMBERS.length;
    const totalCompanies = COMPANIES.length;
    const votedCompanies = new Set(votes.map(v => v.company)).size;
    const perCounter = {};
    COUNTERS.forEach(c => (perCounter[c] = 0));
    votes.forEach(v => (perCounter[v.counter] = (perCounter[v.counter] || 0) + 1));
    return {
      totalMembers,
      totalCompanies,
      votesCast: votes.length,
      votedCompanies,
      pendingCompanies: totalCompanies - votedCompanies,
      turnoutPct: totalCompanies ? Math.round((votedCompanies / totalCompanies) * 100) : 0,
      perCounter,
    };
  }

  function formatTime(ts) {
    const d = new Date(ts);
    return d.toLocaleString("en-IN", {
      day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit", hour12: true,
    });
  }

  function timeAgo(ts) {
    const mins = Math.round((Date.now() - ts) / 60000);
    if (mins < 1) return "just now";
    if (mins < 60) return `${mins} min ago`;
    const hrs = Math.round(mins / 60);
    return `${hrs} hr${hrs > 1 ? "s" : ""} ago`;
  }

  return {
    ELECTION_NAME, ELECTION_DATE, COUNTERS, MEMBERS, COMPANIES,
    findMemberByRFID, findMemberById,
    getCompanyVoteRecord, issueVote, voidVote, findVoteBySerial, getAllVotes,
    getStats, resetDemoData, formatTime, timeAgo,
  };
})();
