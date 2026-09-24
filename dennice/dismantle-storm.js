// Dismantle: invisible travel, visible wounds. Tune these independently of damage.
(() => {
  const tuning = { impactLife: .28, pushSpeed: 26, pushDuration: .24, windCount: 48, vignetteOpacity: .76 };
  let impacts = [];
  const hit = ultimateDismantleHit;
  ultimateDismantleHit = function(target, slash, isBoss = false, onDown = null) {
    if (!target || target.hp <= 0 || slash.hits.has(target)) return;
    hit(target, slash, isBoss, onDown);
    // The HERO is the source, never the random invisible collision lane.
    const angle = Math.atan2(target.y - p.y, target.x - p.x);
    target.impactVX = target.impactVY = 0; // Dedicated gentle push avoids double recoil.
    target.dismantlePush = { x: Math.cos(angle), y: Math.sin(angle), remaining: tuning.pushDuration };
    impacts.push({ target, angle: slash.a, age: 0, seed: Math.random() * 6.28 });
    if (impacts.length > 48) impacts.shift();
  };
  const previousUpdate = update;
  update = function(dt) {
    if (!running || paused) return previousUpdate(dt);
    previousUpdate(dt);
    impacts = impacts.filter(fx => (fx.age += dt) < tuning.impactLife);
    for (const actor of [boss, ...enemies]) {
      const push = actor?.dismantlePush;
      if (!push || actor.hp <= 0) continue;
      const step = Math.min(dt, push.remaining), from = { x: actor.x, y: actor.y };
      actor.x += push.x * tuning.pushSpeed * step;
      actor.y += push.y * tuning.pushSpeed * step;
      push.remaining -= step;
      if (window.PRINCESS_TERRAIN?.active()) window.PRINCESS_TERRAIN.constrainActor(actor, from);
      if (push.remaining <= 0) actor.dismantlePush = null;
    }
  };
  const previousReset = reset;
  reset = function(...args) { impacts = []; return previousReset(...args); };
  const previousDraw = drawUltimateFx;
  drawUltimateFx = function() {
    if (ultimate?.kind !== 'dismantle') return previousDraw();
    const u = ultimate, top = 70 + (window.PRINCESS_TERRAIN?.viewY || 0), height = H - 144;
    const strength = Math.min(1, u.age / .45, Math.max(0, (u.duration + .3 - u.age) / .7));
    ctx.save(); ctx.beginPath(); ctx.rect(0, top, W, height); ctx.clip();
    // Screen-space atmosphere: dark edges preserve the boss's warning colours.
    const fog = ctx.createRadialGradient(W / 2, top + height / 2, height * .18, W / 2, top + height / 2, W * .64);
    fog.addColorStop(0, 'rgba(18,8,32,0)'); fog.addColorStop(1, `rgba(30,4,43,${tuning.vignetteOpacity * strength})`);
    ctx.fillStyle = fog; ctx.fillRect(0, top, W, height);
    ctx.strokeStyle = '#e4d2ff'; ctx.lineWidth = 2;
    // Curved wind wisps are atmosphere, NOT visible travelling sword cuts.
    for (let i = 0; i < tuning.windCount; i++) {
      const x = ((i * 173 + u.age * (80 + i % 5 * 12)) % (W + 220)) - 110;
      const y = top + (i * 83 % height) + Math.sin(u.age * 2 + i) * 18;
      ctx.globalAlpha = strength * (.18 + .16 * Math.sin(i + u.age) ** 2);
      ctx.beginPath(); ctx.moveTo(x, y); ctx.quadraticCurveTo(x + 55, y - 25, x + 140, y - 4); ctx.stroke();
    }
    // A rotating ground halo identifies the caster without drawing attack rays.
    ctx.save(); ctx.translate(p.x - camera, p.y + 16); ctx.scale(1, .38);
    ctx.globalAlpha = strength * .55; ctx.strokeStyle = '#d8a2ee'; ctx.lineWidth = 2;
    for (let i = 0; i < 3; i++) { ctx.beginPath(); ctx.arc(0, 0, 38 + i * 11, u.age * (i % 2 ? -2 : 2) + i * 2, u.age * (i % 2 ? -2 : 2) + i * 2 + 1.8); ctx.stroke(); }
    ctx.restore();
    // Only confirmed damage spawns these short cuts, attached to the victim.
    for (const fx of impacts) {
      const progress = fx.age / tuning.impactLife, fade = (1 - progress) ** 2;
      ctx.save(); ctx.translate(fx.target.x - camera, fx.target.y - 15); ctx.rotate(fx.angle);
      const length = (fx.target.r || 24) * 1.5 + 26;
      for (let i = -1; i <= 1; i++) {
        const y = i * 12 + Math.sin(fx.seed) * 5, reach = length * Math.min(1, progress * 7 + .15);
        ctx.globalAlpha = fade * .75; ctx.strokeStyle = '#a45485'; ctx.lineWidth = 5;
        ctx.beginPath(); ctx.moveTo(-length * .55, y + 8); ctx.quadraticCurveTo(0, y - 7, reach * .6, y - 9); ctx.stroke();
        ctx.globalAlpha = fade; ctx.strokeStyle = '#fff1f5'; ctx.lineWidth = 1.5; ctx.stroke();
      }
      ctx.restore();
    }
    ctx.restore();
  };
  window.DISMANTLE_STORM = { tuning, get impactCount() { return impacts.length; } };
})();
