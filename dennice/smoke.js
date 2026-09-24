// Headless smoke test: loads the real index.html + game.js in jsdom with a
// no-op canvas 2D context stub, then drives simulated frames through every
// phase (travel, boss, demon boss, No Mercy shield, rescue, credits) while
// watching for thrown exceptions. This cannot verify pixels/visuals, but it
// exercises the real update()/draw() code paths end to end.
const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const errors = [];

function makeCtxStub() {
  const handler = {
    get(target, prop) {
      if (prop === 'canvas') return target.__canvas;
      if (prop in target) return target[prop];
      // Any ctx.method(...) call becomes a no-op; any ctx.property read
      // returns a chainable stub value good enough for arithmetic/strings.
      return (...args) => new Proxy(function(){}, handler);
    },
    set(target, prop, value) { target[prop] = value; return true; }
  };
  return new Proxy({ __canvas: null }, handler);
}

async function run() {
  const html = fs.readFileSync(path.join(__dirname, 'index.html'), 'utf8');
  const dom = new JSDOM(html, {
    url: 'http://localhost/',
    runScripts: 'outside-only',
    pretendToBeVisual: true,
    resources: 'usable'
  });
  const { window } = dom;

  // Stub canvas 2D context (jsdom has no real renderer).
  window.HTMLCanvasElement.prototype.getContext = function () {
    const stub = makeCtxStub();
    stub.__canvas = this;
    return stub;
  };
  // fullscreen / audio / raf / localStorage stubs jsdom doesn't provide.
  window.HTMLElement.prototype.requestFullscreen = function () { return Promise.resolve(); };
  window.AudioContext = window.webkitAudioContext = function () {
    return {
      createGain: () => ({ gain: { value: 0, setValueAtTime(){}, exponentialRampToValueAtTime(){} }, connect(){} }),
      createOscillator: () => ({ type:'', frequency:{setValueAtTime(){},exponentialRampToValueAtTime(){}}, connect(){}, start(){}, stop(){} }),
      createBuffer: () => ({ getChannelData: () => new Float32Array(4096) }),
      createBufferSource: () => ({ buffer:null, connect(){}, start(){} }),
      destination: {},
      currentTime: 0,
      resume: () => Promise.resolve()
    };
  };
  window.Audio = function () { return { play: () => Promise.resolve(), pause(){} }; };
  let rafId = 1;
  window.requestAnimationFrame = (cb) => { window.__rafCb = cb; return rafId++; };
  const store = {};
  window.localStorage = {
    getItem: (k) => (k in store ? store[k] : null),
    setItem: (k, v) => { store[k] = String(v); },
  };

  window.onerror = (msg, src, line, col, err) => { errors.push((err && err.stack) || msg); };
  window.addEventListener('error', (e) => { errors.push((e.error && e.error.stack) || e.message); });

  // game.js declares its internals with top-level let/const, so (like any
  // normal <script>) they are NOT properties of window, and (unlike real
  // browsers) jsdom/Node's vm does not reliably share that lexical scope
  // across *separate* eval() calls. So the accessor bridge is appended to
  // the SAME script string and evaluated in one shot, guaranteeing the
  // closures below can see p/shield/enemies/etc.
  const gameJs = ['game.js','hero-remodel.js','adapted-one.js','adapted-hunt.js','adapted-lab.js','arena-cover.js','assets/cursed-land/map-data.js','arena-map.js','arena-terrain.js'].map(file => fs.readFileSync(path.join(__dirname, file), 'utf8')).join('\n');
  const exposer = `
    window.__t = {
      getP: () => p,
      getShield: () => shield,
      getShieldMax: () => SHIELD_MAX,
      getEnemies: () => enemies,
      getShots: () => shots,
      getPickups: () => pickups,
      getBoss: () => boss,
      getAct: () => act,
      getPhase: () => phase,
      getAct1Spawned: () => act1Spawned,
      setAct: (v) => { act = v; },
      getL: () => L,
      setL: (v) => { L = v; },
      getRunning: () => running,
      setRunning: (v) => { running = v; },
      getDifficulty: () => difficultyKey,
      setDifficulty: (v) => { difficultyKey = v; },
      callReset: (opening) => reset(!!opening),
      callUpdate: (dt) => update(dt),
      callDraw: () => draw(),
      callHurt: (n) => hurt(n),
      callBossDown: () => bossDown(),
      callEnterDemonBoss: () => enterDemonBoss(),
      callEnterBoss: () => enterBoss(true),
      callRoadLength: (a) => roadLength(a),
      callSetMusic: (n) => setMusic(n),
      killDemonPart: (part) => demonPartDown(boss, part),
      setNextSpawn: (v) => { nextSpawn = v; },
      setMouse: (x, y) => { mouse.x = x; mouse.y = y; },
      callSwordAttack: () => swordAttack(true),
      callUltimate: (force, weapon) => ultimateAttack(!!force, weapon || null),
      getUltimate: () => ultimate,
      setDeveloperMode: (v) => { developerMode = !!v; },
      getCleaveEchoes: () => cleaveEchoes,
      getBloodStains: () => bloodStains,
      startAdaptedMove: (name) => adaptedStartAttack(boss,name),
      stepAdapted: (dt) => { time+=dt; bossUpdate(dt); },
      chooseAdaptedMove: () => adaptedChooseAttack(boss),
      adaptedScale: (category,source) => adaptedDamageScale(category,source),
      recordAdapted: (category) => adaptedRecord(category),
      getFrameRect: (row,dir) => adaptedFrameRect(row,dir),
      getAdaptedConfig: () => ADAPTED_ONE_CONFIG,
      getAtlas: () => ADAPTED_ATLAS,
      getMoves: () => Object.keys(ADAPTED_MOVES),
      callFire: () => fire(),
      callShots: (dt) => shotsUpdate(dt),
      callDismantleHit: (slash) => ultimateDismantleHit(boss,slash,true,()=>bossDown()),
      setKey: (key,value) => { keys[key]=value; },
      getCamera: () => camera,
    };
  `;
  try {
    window.eval(gameJs + '\n' + exposer);
  } catch (e) {
    errors.push('TOP-LEVEL EVAL ERROR: ' + e.stack);
  }

  if (errors.length) {
    console.log('Errors after initial script load:');
    errors.forEach(e => console.log(' -', e));
    process.exitCode = 1;
    return;
  }
  console.log('Initial load: OK (assets array, art map, DOM wiring all executed without throwing)');
  const t = window.__t;

  // Helper: pump N simulated frames of `dt` seconds each.
  function pump(n, dt) {
    for (let i = 0; i < n; i++) {
      try {
        t.callUpdate(dt);
        t.callDraw();
      } catch (e) {
        errors.push('FRAME ERROR @' + i + ': ' + e.stack);
        break;
      }
    }
  }

  function report(label) {
    if (errors.length) {
      console.log(`FAIL during ${label}:`);
      errors.forEach(e => console.log(' -', e));
      process.exitCode = 1;
      errors.length = 0;
      return false;
    }
    console.log(`OK: ${label}`);
    return true;
  }

  // --- Scenario 1: normal Easy playthrough sanity (travel -> forced boss) ---
  t.setDifficulty('easy');
  t.callReset();
  t.setRunning(true);
  t.callSetMusic('journey');
  pump(120, 1 / 60); // ~2s of travel
  report('easy travel (2s)');

  // --- Scenario 1b: opening Adapted One intro, memory, phase shift, victory route ---
  t.setDifficulty('easy');
  t.callReset(true); t.setRunning(true);
  if (t.getPhase() !== 'boss' || !t.getBoss() || t.getBoss().art !== 'adapted') errors.push('Opening run should enter The Adapted One boss state');
  const openingBoss = t.getBoss();
  openingBoss.mode = 'intro'; openingBoss.timer = 999; // isolate memory/phase checks from attack damage
  t.getP().weapon = 'sword'; t.callSwordAttack();
  if (!(openingBoss.adapt && openingBoss.adapt.meters.heavyMelee > 0)) errors.push('Adapted One did not record heavy-melee usage');
  openingBoss.hp = openingBoss.max * .74; pump(1, 1 / 60);
  if (openingBoss.phaseIndex !== 2 || openingBoss.mode !== 'adapt') errors.push('Adapted One did not enter adaptation phase at 75% HP');
  pump(140, 1 / 60);
  t.callBossDown(); pump(180, 1 / 60);
  if (t.getPhase() !== 'travel' || t.getBoss()) errors.push('Defeated opening boss did not hand off to Act I travel');
  report('opening Adapted One encounter + Act I handoff');

  // Behavioural regressions for the opening boss, separate from visual QA.
  try { require('./test-adapted.js')(t); }
  catch(e) { errors.push(e.stack); }
  report('Adapted One: every move, dodge geometry, adaptation and full-body frame bounds');

  // Act I now schedules a 300-minion total budget with a 36-enemy active cap.
  // Remove each wave immediately here so the smoke run verifies the complete
  // budget without spending minutes fighting a literal horde.
  t.callReset(); t.setRunning(true);
  for (let i = 0; i < 90 && t.getAct() === 0; i++) {
    t.getP().x = Math.min(t.getL() - 100, t.getP().x + 30);
    t.getEnemies().forEach(e => { e.hp = 0; });
    pump(1, 1/60);
  }
  if (t.getAct1Spawned() !== 300) errors.push(`Act I horde budget spawned ${t.getAct1Spawned()} instead of 300`);
  report('Act I 300-minion horde budget');

  // Force straight to the Act I boss to exercise bossUpdate/demon-less boss path.
  t.getP().x = t.getL() - 90;
  t.getEnemies().length = 0;
  pump(1, 1/60);
  pump(300, 1/60); // ~5s of boss fight incl. several attack patterns
  report('Act I boss fight (5s)');

  // --- Scenario 2: No Mercy shield absorbs damage and goes on cooldown ---
  t.setDifficulty('noMercy');
  t.callReset();
  t.setRunning(true);
  t.callSetMusic('journey');
  t.setNextSpawn(Infinity); // isolate the shield math from the normal enemy spawner/combat
  const shieldMax = t.getShieldMax();
  const startHp = t.getP().hp;
  t.callHurt(20);
  if (t.getP().hp !== startHp) errors.push(`No Mercy shield should fully absorb a 20dmg hit while charged, but player HP changed ${startHp}->${t.getP().hp}`);
  if (t.getShield().hp !== shieldMax - 20) errors.push(`Expected shield.hp ${shieldMax - 20} after absorbing 20, got ${t.getShield().hp}`);
  // Drain the shield completely and confirm overflow spills onto HP + cooldown starts.
  // (55 dmg: 40 left in the shield gets absorbed, 15 genuinely reaches the player —
  // enough to prove overflow works without dropping the player to 0 HP mid-test.)
  t.callHurt(55);
  if (t.getShield().cooldown <= 0) errors.push('Shield should start a cooldown once depleted');
  if (t.getP().hp >= startHp) errors.push('Overflow damage past shield capacity should still hurt the player');
  const hpAfterBreak = t.getP().hp;
  t.callHurt(5);
  if (t.getP().hp !== hpAfterBreak - 5) errors.push('While shield is on cooldown, damage should apply normally to the player');
  pump(600, 1/60); // 10s, enough for SHIELD_COOLDOWN (8s) to fully elapse
  if (t.getShield().cooldown > 0) errors.push('Shield cooldown should have elapsed after 10 simulated seconds');
  if (t.getShield().hp !== shieldMax) errors.push(`Shield should recharge to ${shieldMax} after cooldown, got ${t.getShield().hp}`);
  report('No Mercy shield absorb/break/cooldown/recharge cycle');

  // --- Scenario 3: auto rocket pod homes in on an enemy ---
  t.setDifficulty('easy');
  t.callReset();
  t.setRunning(true);
  t.callSetMusic('journey');
  t.setNextSpawn(Infinity); // isolate this scenario from the normal travel-phase spawner
  t.getP().hasRocketPack = true;
  t.getP().rocketCd = 0;
  t.getP().x = 400; t.getP().y = 360;
  t.getEnemies().length = 0;
  t.getEnemies().push({ x: 700, y: 360, kind: 'minion', hp: 50, max: 50, r: 20, speed: 0, attack: 0, shot: 0, hit: 0, wave: 0 });
  t.getShots().length = 0;
  pump(1, 1/60); // should fire one homing rocket this frame
  const rocket = t.getShots().find(s => s.kind === 'rocket' && s.team === 'player' && s.homing);
  if (!rocket) errors.push('Auto rocket pod did not fire a homing rocket at a nearby enemy');
  else {
    // Move the target and confirm the rocket steers toward its new position
    // instead of flying dead straight.
    const initialAngle = rocket.a;
    t.getEnemies()[0].y = 200; // target darts upward
    pump(30, 1/60); // half a second of homing flight
    const steered = Math.atan2(rocket.vy, rocket.vx);
    if (Math.abs(steered - initialAngle) < 0.01) errors.push('Homing rocket did not steer after its target moved');
  }
  report('Auto rocket pod fire + homing steer');

  // --- Scenario 4: rocket pickup + rocketrapid pickup wire up correctly ---
  t.setDifficulty('easy');
  t.callReset();
  t.setRunning(true);
  t.callSetMusic('journey');
  t.getPickups().length = 0;
  t.getPickups().push({ x: t.getP().x, y: t.getP().y, type: 'rocketpack' });
  pump(1, 1/60);
  if (!t.getP().hasRocketPack) errors.push('rocketpack pickup did not grant hasRocketPack');
  t.getPickups().push({ x: t.getP().x, y: t.getP().y, type: 'rocketrapid' });
  pump(1, 1/60);
  if (!(t.getP().rocketRapid > 0)) errors.push('rocketrapid pickup did not set a rocketRapid buff timer');
  report('rocketpack / rocketrapid pickups');

  // --- Scenario 5: heavy-sword chain, straight finisher, and echo cleave ---
  t.setDifficulty('easy');
  t.callReset();
  t.setRunning(true);
  t.callSetMusic('journey');
  t.setNextSpawn(Infinity);
  const swordP = t.getP();
  swordP.weapon = 'sword'; swordP.hasCleave = true; swordP.x = 400; swordP.y = 360;
  t.getEnemies().length = 0;
  const primary = { x: 475, y: 360, kind:'minion', hp:30, max:30, r:18, speed:100, attack:10, shot:10, hit:0, wave:0 };
  const secondary = { x: 620, y:360, kind:'minion', hp:30, max:30, r:18, speed:100, attack:10, shot:10, hit:0, wave:0 };
  t.getEnemies().push(primary, secondary);
  pump(1, 1/60); // establishes a right-facing aim angle from the default mouse position
  t.callSwordAttack();
  if (primary.hp >= primary.max) errors.push('Sword first hit did not damage its primary target');
  pump(20, 1/60); // a lone click must finish without silently queueing hit two
  if (swordP.swordCombo !== 1 || swordP.swordQueued) errors.push('One sword click should finish as exactly one swing');
  pump(28, 1/60); // allows the slow cleave echo to cross the secondary target
  if (secondary.hp >= secondary.max) errors.push('Cleave echo did not damage an enemy behind the first target');
  if (!(secondary.cleaveSlow > 0)) errors.push('Cleave echo did not apply its movement slow to a secondary target');
  if (t.getBloodStains().length === 0) errors.push('Sword damage did not leave persistent floor blood');
  // Reset the field, then repeat the chain quickly enough to retain combo
  // state and inspect the third hit's straight, committed travel.
  t.callReset(); t.setRunning(true); t.setNextSpawn(Infinity);
  const pierceP = t.getP(); pierceP.weapon = 'sword'; pierceP.x = 400; pierceP.y = 360;
  pump(1, 1/60);
  t.callSwordAttack(); pump(18, 1/60);
  t.callSwordAttack(); pump(18, 1/60);
  const beforePierceX = pierceP.x;
  t.callSwordAttack(); pump(18, 1/60);
  if (pierceP.swordCombo !== 3) errors.push(`Expected third sword combo stage, got ${pierceP.swordCombo}`);
  if (pierceP.x <= beforePierceX + 40) errors.push('Third sword hit did not travel forward as a piercing dash');
  report('Heavy sword: reverse chain + piercing finisher + echo cleave + gore');

  // A sword swing is a directional parry, not a permanent shield. A bullet
  // approaching from the guarded front should reverse and become a player
  // projectile; the same shot from behind is intentionally left dangerous.
  t.callReset(); t.setRunning(true); t.setNextSpawn(Infinity);
  const parryP = t.getP(); parryP.weapon = 'sword'; parryP.x = 400; parryP.y = 360;
  t.setMouse(640, 360); pump(1, 1/60);
  t.getShots().push({x: parryP.x + 58, y: parryP.y, vx: -300, vy: 0, team:'enemy', d:7, s:5, life:2.2, a:Math.PI, kind:'bullet'});
  t.callSwordAttack(); pump(1, 1/60);
  const deflected = t.getShots().find(s => s.team === 'player' && s.vx > 0);
  if (!deflected) errors.push('Sword parry did not deflect an incoming front bullet');
  report('Sword parry: front bullet reflected during active swing');

  // --- Scenario 6: weapon ultimates + developer preview path ---
  t.setDeveloperMode(true);
  t.callReset(); t.setRunning(true); t.setNextSpawn(Infinity);
  const ultSwordP = t.getP(); ultSwordP.weapon = 'sword'; ultSwordP.x = 400; ultSwordP.y = 360;
  t.setMouse(640, 360); pump(1, 1/60);
  const ultTarget = { x: 560, y: 360, kind:'minion', hp:40, max:40, r:18, speed:100, attack:10, shot:10, hit:0, wave:0 };
  t.getEnemies().push(ultTarget);
  if (!t.callUltimate(true, 'sword')) errors.push('Developer sword ultimate could not start');
  pump(1, 1/60);
  if (!(ultTarget.impactPause > 0 && ultTarget.impactTime > 0 && ultTarget.dismantleSlow > 0)) errors.push('Dismantle did not apply a minion hit-stop/recoil/slow reaction');
  pump(659, 1/60); // ten-second field plus the final slash fade-out
  if (ultTarget.hp >= ultTarget.max) errors.push('Dismantle ultimate did not damage a radial target');
  if (t.getUltimate()) errors.push('Dismantle ultimate did not finish its slash storm');
  report('Ultimate developer test: sword Dismantle slash storm');

  // The same impact language must read on a boss: a cut briefly freezes the
  // pattern and drives the existing boss stagger window, not just a flash.
  t.callReset(); t.setRunning(true); t.setNextSpawn(Infinity); t.setAct(0); t.setL(t.callRoadLength(0)); t.callEnterBoss();
  const reactionBoss = t.getBoss(), reactionP = t.getP(); reactionBoss.mode = 'intro'; reactionBoss.timer = 999; reactionP.weapon = 'sword';
  pump(1, 1/60); reactionBoss.x = reactionP.x + 160; reactionBoss.y = reactionP.y; t.setMouse(960, 360);
  if (!t.callUltimate(true, 'sword')) errors.push('Boss reaction Dismantle could not start');
  pump(1, 1/60);
  if (!(reactionBoss.swordStagger > 0 && reactionBoss.impactPause > 0 && reactionBoss.dismantleSlow > 0)) errors.push('Dismantle did not stagger/pause/slow a boss target');
  report('Dismantle impact reaction: minion + boss');

  const ultimateGunCases = [
    ['pistol', 'bullet', 'pistol ultimate volley'],
    ['shotgun', 'bullet', 'shotgun ultimate scatter'],
    ['machinegun', 'rocket', 'machine-gun ultimate rocket gatling']
  ];
  for (const [weapon, kind, label] of ultimateGunCases) {
    t.callReset(); t.setRunning(true); t.setNextSpawn(Infinity);
    const gunP = t.getP(); gunP.weapon = weapon; gunP.hasShotgun = weapon === 'shotgun'; gunP.hasMachineGun = weapon === 'machinegun'; gunP.x = 400; gunP.y = 360;
    t.setMouse(640, 360); pump(1, 1/60);
    if (!t.callUltimate(true, weapon)) errors.push(`Developer ${weapon} ultimate could not start`);
    pump(2, 1/60);
    if (!t.getShots().some(s => s.team === 'player' && s.kind === kind)) errors.push(`${weapon} ultimate did not emit ${kind} projectiles`);
    pump(100, 1/60);
    if (t.getUltimate()) errors.push(`${weapon} ultimate did not finish`);
    report(`Ultimate developer test: ${label}`);
  }
  t.setDeveloperMode(false);

  // --- Scenario 7: Siegebreaker sword payoff against a boss core ---
  t.setDifficulty('easy');
  t.callReset();
  t.setRunning(true);
  t.setAct(0); t.setL(t.callRoadLength(0));
  t.callEnterBoss();
  const siegeBoss = t.getBoss(), siegeP = t.getP();
  siegeBoss.mode = 'intro'; siegeBoss.timer = 999; // isolate sword impact from patterns
  siegeP.weapon = 'sword'; siegeP.x = siegeBoss.x - 115; siegeP.y = siegeBoss.y;
  t.setMouse(siegeBoss.x - (t.getL() - 100), siegeBoss.y);
  pump(1, 1/60);
  const siegeStartHp = siegeBoss.hp;
  t.callSwordAttack(); pump(18, 1/60);
  t.callSwordAttack(); pump(18, 1/60);
  t.callSwordAttack(); pump(14, 1/60);
  const siegeDamage = siegeStartHp - siegeBoss.hp;
  if (siegeDamage < 30) errors.push(`Completed sword combo should deal meaningful boss burst damage, got ${siegeDamage}`);
  if (!(siegeBoss.swordStagger > 0)) errors.push('Sword dash finisher did not stagger the boss core');
  report('Siegebreaker boss burst + finisher stagger');

  // --- Scenario 8: demon (final) boss path with parts + core seal logic ---
  t.setDifficulty('hard');
  t.callReset();
  t.setRunning(true);
  t.callSetMusic('journey');
  t.setAct(3);
  t.setL(t.callRoadLength(3));
  t.callEnterDemonBoss();
  pump(300, 1/60); // 5s across intro + a few attack patterns
  report('Demon Lord fight (5s, hard difficulty)');
  // Kill both flank parts directly and confirm the core unseals without throwing.
  if (t.getBoss() && t.getBoss().parts) {
    t.getBoss().parts.forEach(part => { if (part.alive) t.killDemonPart(part); });
    pump(60, 1/60);
    report('Demon Lord flank destruction -> core exposed');
    if (t.getBoss() && t.getBoss().coreLocked) {
      console.log('FAIL: core should be unsealed after both flanks are destroyed');
      process.exitCode = 1;
    } else {
      console.log('OK: core unseals once both flank heads are destroyed');
    }
  }

  // --- Scenario 8: No Mercy + Demon Lord + rocket pod all together (stress) ---
  t.setDifficulty('noMercy');
  t.callReset();
  t.setRunning(true);
  t.callSetMusic('journey');
  t.getP().hasRocketPack = true;
  t.setAct(3);
  t.setL(t.callRoadLength(3));
  t.callEnterDemonBoss();
  for (let i = 0; i < 8; i++) t.getEnemies().push({ x: t.getP().x + i*20, y: t.getP().y, kind:'imp', hp:5, max:5, r:16, speed:100, attack:0, shot:0, hit:0, wave:0 });
  pump(600, 1/60); // 10s combined stress test
  report('Combined stress: No Mercy shield + rocket pod + Demon Lord (10s)');

  if (process.exitCode === 1) {
    console.log('\nSMOKE TEST: FAILED');
  } else {
    console.log('\nSMOKE TEST: ALL SCENARIOS PASSED');
  }
}

run();
