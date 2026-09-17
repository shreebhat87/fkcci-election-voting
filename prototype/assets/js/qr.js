/* =========================================================
   Decorative "QR-style" pattern renderer for the prototype only.
   NOT a real QR encoder — modules are a deterministic pseudo-random
   pattern derived from the input string, with authentic-looking
   finder squares, so UI review reads correctly at a glance.
   The production app will generate real scannable QR codes
   server-side in PHP (e.g. endroid/qr-code).
   ========================================================= */

function renderPseudoQR(canvas, text, size) {
  size = size || 176;
  const modules = 25;
  const cell = size / modules;
  const ctx = canvas.getContext("2d");
  canvas.width = size;
  canvas.height = size;
  ctx.fillStyle = "#ffffff";
  ctx.fillRect(0, 0, size, size);
  ctx.fillStyle = "#0b2545";

  let seed = 0;
  for (let i = 0; i < text.length; i++) seed = (seed * 31 + text.charCodeAt(i)) | 0;
  seed = Math.abs(seed) || 42;
  function rand() {
    seed = (seed * 1103515245 + 12345) & 0x7fffffff;
    return seed / 0x7fffffff;
  }

  function inFinderZone(r, c) {
    const zones = [
      [0, 0], [0, modules - 7], [modules - 7, 0],
    ];
    return zones.some(([zr, zc]) => r >= zr && r < zr + 7 && c >= zc && c < zc + 7);
  }

  for (let r = 0; r < modules; r++) {
    for (let c = 0; c < modules; c++) {
      if (inFinderZone(r, c)) continue;
      if (rand() < 0.46) {
        ctx.fillRect(Math.round(c * cell), Math.round(r * cell), Math.ceil(cell), Math.ceil(cell));
      }
    }
  }

  function drawFinder(r0, c0) {
    const x = c0 * cell, y = r0 * cell, w = 7 * cell;
    ctx.fillStyle = "#0b2545";
    ctx.fillRect(x, y, w, w);
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(x + cell, y + cell, w - 2 * cell, w - 2 * cell);
    ctx.fillStyle = "#0b2545";
    ctx.fillRect(x + 2 * cell, y + 2 * cell, w - 4 * cell, w - 4 * cell);
  }
  drawFinder(0, 0);
  drawFinder(0, modules - 7);
  drawFinder(modules - 7, 0);
}
