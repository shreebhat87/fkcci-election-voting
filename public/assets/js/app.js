/* Shared AJAX helpers — CSRF-safe using CodeIgniter's session-based CSRF
   protection (Config/Security.php: csrfProtection='session', regenerate=
   false). The token is session-stored server-side, not cookie-based (CI4's
   CSRF cookie defaults to HttpOnly, which JS can never read), so each page
   renders it once into a <meta name="csrf-token"> tag and we read that.
   regenerate=false means the same token stays valid for the whole login
   session — required here since the counter screen fires many sequential
   AJAX calls over hours, not one form submit per page load. */

function csrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}

async function postJSON(url, data) {
  const body = new URLSearchParams();
  Object.keys(data || {}).forEach(k => body.append(k, data[k] ?? ''));
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
    },
    body,
  });
  return res.json();
}

async function getJSON(url) {
  const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
  return res.json();
}
