/* Real PNG tile animation from the supplied Cursed Land TMX.
 * Rebuild assets/cursed-land/map-data.js with node make-cursed-map.js after
 * editing Tiled. No procedural trees, fake glints, or collision obstacles.
 */
(function () {
  const data = window.CURSED_LAND_DATA;
  if (!data) return; // Preserve the PNG fallback if the export is absent.
  // TIMING: 1 = original artist timing; 2 = twice as fast.
  const tuning = { animationSpeed: 1 };
  const surface = document.createElement('canvas');
  surface.width = data.crop.w; surface.height = data.crop.h;
  // Let the existing game arena-image interface accept this live canvas.
  surface.complete = true; surface.naturalWidth = surface.width; surface.naturalHeight = surface.height;
  const g = surface.getContext('2d');
  const images = data.tilesets.map(s => { const im = new Image(); im.src = s.image; return im; });
  let ready = false, lastKey = '', renderCount = 0, nextFrameAt = -1;
  const animationList = [], operations = [];
  function frameAt(frames, milliseconds) {
    const total = frames.reduce((n, f) => n + f[1], 0);
    let t = ((milliseconds % total) + total) % total;
    for (const f of frames) { if (t < f[1]) return f[0]; t -= f[1]; }
    return frames[0][0];
  }
  function paintTile(target, tile, frame) {
    const [x, y, ts, local, flags] = tile, s = data.tilesets[ts], id = frame ?? local;
    const sx = id % s.columns * s.w, sy = Math.floor(id / s.columns) * s.h;
    target.save(); target.translate(x + 8, y + 8);
    // Tiled orthogonal transform: diagonal swap first, then H/V flip.
    target.scale(flags & 8 ? -1 : 1, flags & 4 ? -1 : 1);
    if (flags & 2) target.transform(0, 1, 1, 0, 0, 0);
    target.drawImage(images[ts], sx, sy, s.w, s.h, -8, -8, 16, 16);
    target.restore();
  }
  function initialize() {
    if (!images.every(im => im.complete && im.naturalWidth)) return false;
    const foreground = window.PRINCESS_TERRAIN?.prepare(data, images, paintTile) || new Set();
    for (const layer of data.layers) {
      if (!layer.visible) continue;
      const cached = document.createElement('canvas'); cached.width = surface.width; cached.height = surface.height;
      const cg = cached.getContext('2d'); cg.imageSmoothingEnabled = false;
      const animated = [];
      for (const tile of layer.tiles) {
        if (foreground.has(tile)) continue;
        const frames = data.tilesets[tile[2]].animations[tile[3]];
        if (frames) {
          const item = { tile, frames, index: animationList.length };
          animationList.push(item); animated.push(item);
        } else paintTile(cg, tile);
      }
      // Original layer order keeps moving water UNDER bridges, banks and plants.
      operations.push({ cached, animated, opacity: layer.opacity });
    }
    ready = true;
    window.ARENA_MAPS.adapted = surface;
    window.ARENA_MAP_CROPS.adapted = window.PRINCESS_TERRAIN?.crop() || { x: 0, y: 0, w: surface.width, h: surface.height };
    return true;
  }
  function renderAt(seconds) {
    if (!ready && !initialize()) return false;
    const milliseconds=seconds*1000*tuning.animationSpeed;
    // All supplied durations are multiples of 150ms. Avoid rebuilding frame
    // lists/strings several times per game frame (update and rendering share it).
    if(milliseconds<nextFrameAt&&milliseconds>=nextFrameAt-150)return true;
    nextFrameAt=(Math.floor(milliseconds/150)+1)*150;
    const frames = animationList.map(a => frameAt(a.frames, milliseconds));
    const key = frames.join(',');
    if (key === lastKey) return true;
    lastKey = key; renderCount++;
    g.clearRect(0, 0, surface.width, surface.height); g.imageSmoothingEnabled = false;
    for (const op of operations) {
      g.globalAlpha = op.opacity;
      g.drawImage(op.cached, 0, 0);
      for (const a of op.animated) paintTile(g, a.tile, frames[a.index]);
    }
    g.globalAlpha = 1;
    return true;
  }
  const previousDraw = draw;
  draw = function () {
    // Simulation time stops on pause; animation stays synchronized with combat.
    if (phase === 'boss' && boss && boss.art === 'adapted') renderAt(time);
    previousDraw();
  };
  window.PRINCESS_MAP = {
    tuning, renderAt, frameAt, surface,
    stats: () => ({ ready, layers: operations.length, animatedPlacements: animationList.length, renderCount,
      definitions: data.tilesets.reduce((n,s)=>n+Object.keys(s.animations).length,0),
      loadedAtlases: images.filter(im=>im.complete&&im.naturalWidth).length,
      failedAtlases: images.filter(im=>im.complete&&!im.naturalWidth).map(im=>im.src) })
  };
  // Prepare PNG layers/navigation while the start menu is open, rather than
  // spending the first combat frame constructing every canvas and mask.
  let warmScheduled=false;
  function warm(){if(warmScheduled||!images.every(im=>im.complete&&im.naturalWidth))return;
    warmScheduled=true;(window.requestIdleCallback||((fn)=>setTimeout(fn,0)))(()=>renderAt(0));
  }
  for(const image of images)image.addEventListener('load',warm);warm();

  // STARTUP GUARD — on a cold desktop launch (especially a direct file://
  // launch) hundreds of PNGs can still be decoding when Play is pressed.
  // Starting immediately used to show a dark field and fallback circle until
  // the map finished baking.  Hold Play briefly so combat begins only with
  // the real arena, hero and Adapted One art present.
  const startButton=document.querySelector('#startButton'),startGame=startButton?.onclick;
  if(startButton&&startGame){
    startButton.onclick=async function(event){
      if(startButton.dataset.loading==='1')return;
      startButton.dataset.loading='1';startButton.disabled=true;
      const required=['map','adapted-one','adapted-one-combat','hero-gun-right-idle-1'];
      const deadline=performance.now()+12000;
      let ready=false;
      while(performance.now()<deadline){
        renderAt(0);
        ready=!!(window.PRINCESS_MAP?.stats().ready&&required.every(name=>art[name]?.complete&&art[name].naturalWidth));
        if(ready)break;
        const stats=window.PRINCESS_MAP?.stats(),loaded=stats?`${stats.loadedAtlases}/${stats.loadedAtlases+(stats.failedAtlases?.length||0)||6}`:'…';
        startButton.textContent=`PREPARING ARENA ${loaded}`;
        await new Promise(resolve=>setTimeout(resolve,80));
      }
      startButton.disabled=false;delete startButton.dataset.loading;
      startButton.textContent=ready?'FACE THE ADAPTED ONE':'RETRY ARENA LOAD';
      if(!ready){
        // Do not launch a broken blank fight. This happens only when the
        // browser has denied the local PNG files; localhost/XAMPP works.
        document.querySelector('#message').textContent='ARENA ART IS STILL LOADING — WAIT, THEN PRESS RETRY.';
        document.querySelector('#message').style.opacity=1;
        return;
      }
      startGame.call(this,event);
    };
  }
})();
