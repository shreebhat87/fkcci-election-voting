/* =========================================================
   Placeholder "member photo" renderer for the prototype only.
   Simulates a bulk-uploaded headshot (deterministic per member so the
   same member always gets the same placeholder). Photo is OPTIONAL —
   members without one simply fall back to the initials avatar used
   elsewhere in the app; nothing in the flow requires a photo.
   The production app stores a real uploaded JPEG/PNG at
   members.photo_path and serves it directly instead of drawing this.
   ========================================================= */

function renderPersonPhoto(canvas, seedText, size) {
  size = size || 96;
  const ctx = canvas.getContext("2d");
  canvas.width = size;
  canvas.height = size;

  let seed = 0;
  for (let i = 0; i < seedText.length; i++) seed = (seed * 31 + seedText.charCodeAt(i)) | 0;
  seed = Math.abs(seed) || 7;
  function rand() {
    seed = (seed * 1103515245 + 12345) & 0x7fffffff;
    return seed / 0x7fffffff;
  }

  const hue = Math.floor(rand() * 360);
  const grad = ctx.createLinearGradient(0, 0, size, size);
  grad.addColorStop(0, `hsl(${hue}, 45%, 90%)`);
  grad.addColorStop(1, `hsl(${(hue + 25) % 360}, 42%, 74%)`);
  ctx.fillStyle = grad;
  ctx.fillRect(0, 0, size, size);

  ctx.fillStyle = `hsl(${hue}, 22%, 42%)`;
  const headR = size * 0.19;
  ctx.beginPath();
  ctx.arc(size / 2, size * 0.4, headR, 0, Math.PI * 2);
  ctx.fill();

  ctx.beginPath();
  ctx.ellipse(size / 2, size * 0.98, size * 0.33, size * 0.28, 0, Math.PI, Math.PI * 2);
  ctx.fill();
}
