// Reuse the existing DOM/audio/canvas harness, but exercise the shipped script
// list and direct-duel contract rather than the retired campaign scenarios.
const fs=require('fs'),path=require('path'),vm=require('vm');
let harness=fs.readFileSync(path.join(__dirname,'smoke.js'),'utf8').split('  // --- Scenario 1:')[0];
harness=harness.replace("'arena-terrain.js'].map", "'arena-terrain.js','shockwave-live.js','duel-rework.js','dismantle-storm.js','shadow-summoner.js','character-select.js','shadow-playable.js','shadow-roster.js','adapted-combo-forms.js'].map");
harness=harness.replace('getCamera: () => camera,',`getCamera: () => camera,
      duel: () => window.DUEL_REWORK,
      dash: () => dash(),
      aim: a => {p.angle=a;mouse.down=false;},
      finish: () => adaptedFinishOpening(),
      hunt: () => window.PRINCESS_HUNT,
      clearShots: () => {shots=[];},`);
harness+=`
  const assert=require('assert');
  t.callReset();t.setRunning(true);
  assert.equal(t.getPhase(),'boss');assert.equal(t.getBoss().art,'adapted');
  assert.equal(t.getBoss().openingPreview,false);assert.equal(t.getP().weapon,'sword');
  assert.equal(t.getEnemies().length,0);console.log('PASS direct duel and starting loadout');
  let b=t.getBoss(),p=t.getP();b.mode='intro';b.timer=999;b.x=p.x+65;b.y=p.y;t.aim(0);
  const hp=b.hp;t.callSwordAttack();assert.equal(b.hp,hp,'No damage before windup');
  pump(8,1/60);assert(b.hp<hp,'Sword strike lands during visible swing');
  pump(35,1/60);assert.equal(p.swordCombo,1,'One click produces one cut');
  console.log('PASS delayed sword damage and one-click combo');
  p.fire=0;p.swordAnim=0;t.callSwordAttack();t.dash();
  assert.equal(t.duel().state.strike,null);assert(p.dashTime>0);const heroHp=p.hp;t.callHurt(30);assert.equal(p.hp,heroHp);
  console.log('PASS evade cancels attack and blocks damage');
  pump(35,1/60);p.fire=0;p.swordAnim=0;p.swordPierce=null;b.x=p.x+100;b.y=p.y;b.mode='intro';b.timer=999;t.aim(0);
  const slamHP=b.hp;t.duel().earthsplitter();pump(30,1/60);assert(b.hp<slamHP);assert(t.duel().state.slamCooldown>0);assert.equal(t.duel().state.slamField.duration,10);
  pump(540,1/60);assert(t.duel().state.slamField,'Earthsplitter fault remains for ten seconds');pump(70,1/60);assert.equal(t.duel().state.slamField,null);
  console.log('PASS ten-second Earthsplitter field, repeated hits and cooldown');
  t.callReset();t.setRunning(true);b=t.getBoss();b.mode='intro';b.timer=999;
  window.document.querySelector('#game').dispatchEvent(new window.MouseEvent('pointerdown',{button:2,bubbles:true,clientX:700,clientY:360}));
  assert(t.duel().state.slam,'Right click starts Earthsplitter');assert.equal(t.duel().state.strike,null,'Right click must not also queue a sword cut');
  console.log('PASS real pointer event: right-click skill without sword double-trigger');
  t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();b.mode='intro';b.timer=999;t.callUltimate(true,'sword');assert.equal(t.getUltimate().duration,10);
  console.log('PASS original ten-second Dismantle');
  b.x=p.x+100;b.y=p.y;
  t.callDismantleHit({x:b.x+300,y:b.y,a:.8,damage:.65,hits:new Set()});
  assert(b.dismantlePush.x>0,'Push away from hero, not random slash origin');
  assert.equal(window.DISMANTLE_STORM.impactCount,1);t.callDraw();
  pump(24,1/60);assert(Number.isFinite(b.x)&&Number.isFinite(b.y),'Recoil stays valid with terrain');
  t.callReset();assert.equal(window.DISMANTLE_STORM.impactCount,0);
  console.log('PASS victim-local slash impacts, hero-sourced recoil and reset cleanup');
  // Isolate pressure-front collision at 30/60/120 Hz: the rendered gap must
  // be safe and an evade must also prevent damage from a swept wave edge.
  for(const dt of [1/30,1/60,1/120])for(const mode of ['hit','gap','evade']){
    t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();b.mode='intro';b.timer=999;b.x=p.x-100;b.y=p.y;
    b.hazards=[{kind:'wave',x:b.x,y:b.y,age:0,r:0,previousR:0,gap:mode==='gap'?0:Math.PI,hit:false,speed:335}];
    const before=p.hp;for(let i=0;i<Math.ceil(.4/dt);i++){if(mode==='evade')p.dashTime=.1;t.stepAdapted(dt);}
    assert(mode==='hit'?p.hp<before:p.hp===before,'Wave collision '+mode+' @ '+dt);
  }
  console.log('PASS shockwave collision, escape corridor and invulnerability at 30/60/120 Hz');
  for(const name of ['shockwave','rift','pursuit','blinkPunch','quickPunch','heavyPunch','combo','relentless','flurry3','flurry5','flurry10','flurry15','dashPunch','backhand','grab','slam','blast','judgment','guard']){
    t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();p.hp=100000;p.maxHp=100000;t.startAdaptedMove(name);
    for(let i=0;i<240;i++){t.callUpdate(1/60);t.callDraw();t.hunt().draw();assert(Number.isFinite(p.hp));assert(Number.isFinite(b.x));}
    console.log('PASS attack/render '+name);
  }
  // The Conductor is a pure summoner: click creates/commands the wing, then
  // the familiar continues attacking without additional player input.
  for(const count of [3,5,10,15]){t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();p.hp=p.maxHp=100000;t.startAdaptedMove('flurry'+count);assert.equal(b.sequence.length,count);const seen=new Set();for(let i=0;i<1500&&b.sequence;i++){seen.add(b.sequenceIndex);p.dashTime=.1;t.stepAdapted(1/60);}assert.equal(seen.size,count,'Every hit in the string executes');assert.equal(b.sequence,null,'Full string reaches recovery');}console.log('PASS complete 3/5/10/15-hit timelines and final recovery');
  window.CHARACTER_SELECT.select('shadow-summoner');t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();p.hp=p.maxHp=100000;b.hp=b.max=100000;b.mode='intro';b.timer=999;
  const roster=window.SHADOW_ROSTER,shadowHP=b.hp;t.callFire();assert.equal(roster.state.beasts[0].id,1,'Click summons wing');assert.equal(p.weapon,'shadow');
  pump(150,1/60);assert(b.hp<shadowHP,'Winged shadow automatically attacks');
  assert(roster.startSpecial());assert.equal(roster.state.special.duration,4.5);pump(300,1/60);assert.equal(roster.state.special,null,'Wing special ends, releasing its stun');assert(!(b.electricStun>0));
  for(const id of [2,3,4]){t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();b.hp=b.max=10000;b.mode='intro';b.timer=999;p.hp=p.maxHp=10000;b.x=p.x+90;b.y=p.y;roster.select(id);const before=b.hp;pump(180,1/60);if(id===3){assert.equal(b.hp,before,'Guardian never attacks unprovoked');const hp=p.hp;b.heroGrace=0;t.callHurt(20);assert(p.hp>hp-20,'Guardian absorbs incoming damage');assert(b.hp<before,'Guardian reflects damage')}else assert(b.hp<before,'Melee familiar closes and hits');assert(roster.startSpecial());pump(30,1/60);t.callDraw();t.hunt().draw();pump(360,1/60);assert.equal(roster.state.special,null);console.log('PASS contract '+id+' autonomous behavior and bounded special')}
  // Melee attacks use an invisible forward range line/capsule.  The helper is
  // exported so future balance changes can be verified without drawing debug
  // geometry over the playfield.
  t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();b.mode='intro';b.timer=999;roster.select(2);let melee=roster.state.beasts[0];melee.x=b.x-260;melee.y=b.y;melee.face=1;assert(!roster.meleeInRange(melee,b),'Gorilla cannot begin a windup outside its range line');melee.x=b.x-90;assert(roster.meleeInRange(melee,b),'Gorilla enters its range line before attacking');b.x=p.x+600;b.y=p.y;melee.x=p.x+60;pump(1,1/60);assert(!melee.attack&&melee.frame<2,'Gorilla locomotion never displays an attack pose while out of range');roster.select(4);melee=roster.state.beasts[0];melee.x=b.x-300;melee.y=b.y;melee.face=1;assert(!roster.meleeInRange(melee,b),'Titan cannot begin a windup outside its range line');melee.x=b.x-150;assert(roster.meleeInRange(melee,b),'Titan enters its range line before attacking');console.log('PASS invisible melee range lines gate Gorilla and Titan windups');
  // Summons also provide a real aggro target. Enemies fall back to the player
  // when the shadow is defeated or when the Vanguard is selected.
  t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();b.mode='intro';b.timer=999;window.CHARACTER_SELECT.select('shadow-summoner');roster.select(2);const lure=roster.state.beasts[0];lure.x=p.x+90;lure.y=p.y;const pursuer={x:lure.x+80,y:lure.y,hp:100,max:100,r:20,kind:'minion',speed:100,attack:1,shot:1,hit:0,wave:0};t.getEnemies().push(pursuer);assert.equal(roster.enemyTarget(pursuer),lure,'Enemy switches aggro to the nearest living shadow');assert.equal(roster.enemyProjectileTarget(pursuer),lure,'Ranged enemy shots inherit shadow aggro');const beforePursuerX=pursuer.x;t.callUpdate(1/60);assert(pursuer.x<beforePursuerX,'Enemy movement follows the shadow target');lure.defeated=true;assert.equal(roster.enemyTarget(pursuer),p,'Defeated shadow releases aggro');window.CHARACTER_SELECT.select('vanguard');assert.equal(roster.enemyTarget(pursuer),p,'Vanguard keeps normal player aggro');console.log('PASS enemy aggro handoff and player fallback');
  // Shadow contracts are a real life resource: summoning costs 20% max HP,
  // defeat starts a manual 15s cooldown, and quiet time restores health.
  t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();b.mode='intro';b.timer=999;window.CHARACTER_SELECT.select('shadow-summoner');p.hp=p.maxHp=100;assert(roster.select(1));assert.equal(p.hp,80,'Summoning sacrifices 20% of max health');const sacrificed=p.hp;const ward=roster.state.beasts[0];b.x=ward.x+120;b.y=ward.y;assert.equal(roster.bossTarget(b),ward,'Boss prioritizes a living shadow');roster.damageShadow(ward,999);assert(ward.defeated&&ward.cooldown>=15,'Defeat starts the 15s re-summon cooldown');assert.equal(roster.bossTarget(b),p,'Boss returns to the player after the shadow falls');assert(!roster.select(1),'Cooldown blocks an immediate re-summon');assert.equal(p.hp,sacrificed,'Blocked summon does not sacrifice more health');p.hp=40;t.callUpdate(1);assert(p.hp>40,'Idle Conductor regenerates health');pump(839,1/60);assert(ward.cooldown>0,'Cooldown remains active before 15 seconds');pump(3,1/60);assert(ward.cooldown<=0,'Cooldown expires after 15 seconds');p.hp=p.maxHp;assert(roster.select(1));assert.equal(p.hp,80,'Re-summon pays the 20% sacrifice again');console.log('PASS shadow sacrifice, idle regeneration, cooldown and boss priority');
  window.CHARACTER_SELECT.select('shadow-summoner');t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();b.hp=b.max=10000;b.mode='intro';b.timer=999;p.hp=p.maxHp=10000;assert(roster.startUltimate());assert.equal(roster.state.beasts.length,4);pump(370,1/60);assert.equal(roster.state.cinema,null);assert.equal(roster.state.beasts.length,1,'Court dismisses extra summons');assert(!roster.startUltimate(),'Ultimate cooldown prevents chaining');
  console.log('PASS four-contract summoner, limited stun, reflection and cinematic cleanup');
  // The titan distributes one damage budget across targets. Adding targets
  // must not multiply its total beam damage, and echoes must hit secondaries.
  t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();b.hp=b.max=10000;b.mode='intro';b.timer=999;p.hp=p.maxHp=10000;b.x=p.x+90;b.y=p.y;roster.select(4);roster.startSpecial();pump(50,1/60);assert.equal(roster.state.beams.length,1);assert(roster.state.beams[0].source&&Number.isFinite(roster.state.beams[0].angle),'Titan breath remains attached to its beam origin');
  t.getEnemies().push({x:b.x+60,y:b.y,hp:10000,max:10000,r:20,speed:0,kind:'minion',attack:100,shot:100,hit:0,wave:0});pump(20,1/60);assert.equal(roster.state.beams.length,2,'Titan acquires multiple beam targets');const summed=b.hp+t.getEnemies()[0].hp;pump(60,1/60);const lost=summed-b.hp-t.getEnemies()[0].hp;assert(lost>0&&lost<10,'One fixed beam DPS budget for multiple targets');console.log('PASS split laser damage budget: '+lost.toFixed(2)+' damage/sec');
  // The mouth core is distance-safe: point-blank targets, normal range, and
  // far-away targets all render without invalid beam geometry or a detached VFX.
  for(const range of [45,260,940]){t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();b.hp=b.max=10000;b.mode='intro';b.timer=999;p.hp=p.maxHp=10000;b.x=p.x+range;b.y=p.y;roster.select(4);assert(roster.startSpecial());pump(50,1/60);t.callDraw();t.hunt().draw();const beam=roster.state.beams[0];assert(beam&&Number.isFinite(beam.angle)&&Number.isFinite(beam.x)&&Number.isFinite(beam.y),'Breath beam stays valid at '+range+'px');}console.log('PASS Titan breath remains stable from point-blank to long range');
  t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();b.hp=b.max=10000;b.mode='intro';b.timer=999;p.hp=p.maxHp=10000;b.x=p.x+60;b.y=p.y;roster.select(2);const secondary={x:b.x+75,y:b.y,hp:10000,max:10000,r:20,speed:0,kind:'minion',attack:100,shot:100,hit:0,wave:0};t.getEnemies().push(secondary);pump(120,1/60);assert(secondary.hp<10000,'Gorilla echo reaches nearby secondary target');
  t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();b.mode='intro';b.timer=999;roster.select(3);b.heroGrace=0;t.callHurt(20);const capacity=roster.state.beasts[0].guard;roster.select(1);roster.select(3);assert.equal(roster.state.beasts[0].guard,capacity,'Switching cannot refill guard');
  window.CHARACTER_SELECT.select('vanguard');
  t.callReset();t.setRunning(true);b=t.getBoss();p=t.getP();b.hp=b.max=10000;b.mode='intro';b.timer=999;p.hp=p.maxHp=10000;assert(roster.startUltimate());pump(370,1/60);assert.equal(roster.state.cinema,null);assert(b.hp<10000&&b.hp>9900,'Heavenbreaker damage remains bounded');console.log('PASS male cinematic ultimate damage '+(10000-b.hp).toFixed(2));
  t.callReset();t.setRunning(true);b=t.getBoss();b.hp=0;t.callBossDown();pump(180,1/60);assert.equal(t.getBoss(),null);assert(['rescue','credits'].includes(t.getPhase()));
  assert.equal(errors.length,0,errors.join('\\n'));console.log('PASS victory reaches ending without another level');
  dom.window.close();
}
run().catch(e=>{console.error(e);process.exitCode=1;});`;
vm.runInNewContext(harness,{require,__dirname,console,process,Buffer,Float32Array,Uint8Array,setTimeout,clearTimeout},{filename:'duel-test-harness.js'});
