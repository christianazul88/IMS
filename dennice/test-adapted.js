const assert=require('node:assert/strict');

module.exports=function testAdapted(t){
  const reset=(distance=300)=>{
    t.callReset(true);t.setRunning(true);t.setDifficulty('easy');
    const b=t.getBoss(),p=t.getP();b.mode='neutral';b.timer=99;
    b.x=t.getCamera()+620;b.y=370;p.x=b.x+distance;p.y=b.y;p.hp=p.maxHp=100;
    return {b,p};
  };
  // Frame-rate independence: same input outcome at 30, 60 and 120 Hz.
  for(const hz of [30,60,120]){
    let {b,p}=reset(300),startX=b.x,startY=b.y;
    t.startAdaptedMove('dashPunch');
    for(let i=0;i<Math.floor(hz*.5);i++)t.stepAdapted(1/hz);
    assert.equal(p.hp,100,'Dash damaged hero during its wind-up');
    assert.equal(b.x,startX,'Dash moved before the warning ended');
    assert.equal(b.y,startY);
    let lockedY=b.endY;p.y+=160;
    for(let i=0;i<hz;i++)t.stepAdapted(1/hz);
    assert.equal(p.hp,100,'Sidestep failed against a supposedly locked dash');
    assert.equal(b.endY,lockedY,'Dash illegally retargeted after committing');

    ({b,p}=reset(300));t.startAdaptedMove('dashPunch');
    for(let i=0;i<hz;i++)t.stepAdapted(1/hz);
    assert.equal(p.hp,76,'Stationary hero should take one dash punch, not zero or repeated hits');
    assert.equal(b.stage,'recover','Dash must end in an opening');

    ({b,p}=reset(300));p.dashTime=10;t.startAdaptedMove('dashPunch');let px=p.x,py=p.y;
    for(let i=0;i<hz;i++)t.stepAdapted(1/hz);
    assert.equal(p.hp,100,'Evade must suppress punch damage');
    assert.equal(p.x,px,'Evade must suppress punch knockback');assert.equal(p.y,py);
  }
  // Close attacks use a directed strike, not an invisible radial hit/prediction.
  let {b,p}=reset(65);t.startAdaptedMove('heavyPunch');p.x=b.x-65;
  for(let i=0;i<65;i++)t.stepAdapted(1/60);
  assert.equal(p.hp,100,'Hero behind a committed punch was hit');
  ({b,p}=reset(230));p.y=75;t.startAdaptedMove('judgment');
  assert.equal(b.target.y,75,'Beam prediction incorrectly uses the boss body bounds');
  t.callHurt(18);t.callHurt(18);
  assert.equal(p.hp,82,'Overlapping halo pellets bypassed hit grace');

  // Every phase/attack exits its sequence, stays in view and has a safe warning.
  for(let phase=1;phase<=4;phase++)for(const name of t.getMoves()){
    ({b,p}=reset(['dashPunch','blast','judgment'].includes(name)?300:100));
    b.phaseIndex=phase;t.startAdaptedMove(name);
    assert.ok(b.timer>=.22,`${name} has no warning`);
    for(let i=0;i<400&&b.sequence;i++){
      p.hp=100;t.stepAdapted(1/60);
      assert.ok(Number.isFinite(b.x+b.y),`${name} produced invalid coordinates`);
      assert.ok(b.y>=190&&b.y<=596,`${name} left the playable vertical bounds`);
    }
    assert.equal(b.sequence,null,`${name}/phase ${phase} never recovers`);
  }
  // Distant target cannot be met with stationary short-range punches.
  ({b,p}=reset(500));for(let i=0;i<100;i++)assert.ok(['dashPunch','blast'].includes(t.chooseAdaptedMove()));
  // New pressure attacks retain warnings, evade windows and locked targets.
  ({b,p}=reset(160));t.startAdaptedMove('shockwave');
  for(let i=0;i<45;i++)t.stepAdapted(1/60);
  assert.equal(p.hp,100,'Shockwave hit before the wind-up finished');
  // The atomic version intentionally has a longer charge, then two separate
  // damaging fronts matched to the primary blast and delayed aftershock.
  for(let i=0;i<135;i++)t.stepAdapted(1/60);
  assert.equal(p.hp,54,'Primary wave and delayed aftershock should each hit once');
  ({b,p}=reset(160));p.dashTime=10;t.startAdaptedMove('shockwave');
  for(let i=0;i<270;i++)t.stepAdapted(1/60);
  assert.equal(p.hp,100,'Shockwave ignored dash invulnerability');
  ({b,p}=reset(260));t.startAdaptedMove('blinkPunch');let marker={x:b.teleportX,y:b.teleportY};
  for(let i=0;i<20;i++)t.stepAdapted(1/60);
  assert.equal(b.x,t.getCamera()+620,'Teleport punch moved before its marker finished warning');
  for(let i=0;i<20;i++)t.stepAdapted(1/60);
  assert.ok(Math.hypot(b.x-marker.x,b.y-marker.y)<2,'Teleport punch missed its locked marker');
  ({b,p}=reset(260));p.dashTime=10;t.startAdaptedMove('blinkPunch');
  for(let i=0;i<60;i++)t.stepAdapted(1/60);
  assert.equal(p.hp,100,'Teleport punch ignored dash invulnerability');
  ({b,p}=reset(350));t.startAdaptedMove('rift');const locked=b.hazards.map(h=>[h.x,h.y]);
  p.y+=180;for(let i=0;i<100;i++)t.stepAdapted(1/60);
  assert.equal(p.hp,100,'Rift followed the hero after its circles locked');
  assert.ok(locked.length===3,'Rift should mark three escape-lane hazards');
  ({b,p}=reset(350));t.setKey('d',true);t.startAdaptedMove('rift');
  for(let i=0;i<85;i++){p.x+=p.speed/60;t.stepAdapted(1/60);}t.setKey('d',false);
  assert.ok(p.hp<100,'Running unchanged through a predicted escape lane evaded every rift');
  ({b,p}=reset(500));b.kitePressure=3;
  assert.ok(['rift','shockwave','pursuit'].includes(t.chooseAdaptedMove()),'Kiting did not trigger pressure attacks');
  // Full arena coordinates survive normal updates instead of screen clamping.
  ({b,p}=reset(200));const origin=t.getL()-100;p.x=origin+1600;p.y=1000;
  t.callUpdate(1/60);assert.ok(p.x>origin+1280&&p.y>720,'World exploration was clamped to the old screen');
  t.setMouse(p.x-t.getCamera()+100,p.y);p.angle=0;p.fire=0;t.callFire();t.callShots(1/60);
  assert.ok(t.getShots().some(s=>s.team==='player'&&s.y>720),'Projectiles were culled below the old screen');
  // Flying bullets do not teach the boss every frame, and changing weapon
  // does not change a projectile's original adaptation category.
  ({b,p}=reset(500));p.weapon='pistol';p.angle=0;t.callFire();let memory=b.adapt.meters.ballistic;
  for(let i=0;i<15;i++)t.callShots(1/120);
  assert.equal(b.adapt.meters.ballistic,memory,'Airborne bullets inflated adaptation');
  b.adapt.meters.ballistic=100;
  assert.ok(t.adaptedScale('ballistic',p)<t.adaptedScale('shotgun',p),'Switching tactics gives no resistance advantage');
  for(let i=0;i<240;i++)t.stepAdapted(1/60);
  assert.ok(b.adapt.meters.ballistic<100,'Unused adaptation never decays');
  // Frontal guard has a real flank, and recovery rewards an offensive response.
  ({b,p}=reset(100));t.startAdaptedMove('guard');let front=t.adaptedScale('ballistic',p);
  p.x=b.x-100;assert.ok(t.adaptedScale('ballistic',p)>front*2,'Guard also protects its back');
  b.stage='recover';assert.ok(t.adaptedScale('heavyMelee',p)>1,'Recovery is not punishable');
  // The boss's new halo bolts remain valid sword-parry targets.
  ({b,p}=reset(250));p.weapon='sword';p.swordAngle=0;p.swordAnim=.2;
  t.getShots().push({x:p.x+58,y:p.y,vx:-420,vy:0,team:'enemy',d:18,s:8,life:2,a:Math.PI,kind:'adapted-blast'});
  t.callShots(1/60);
  assert.ok(t.getShots().some(s=>s.team==='player'&&s.vx>0),'Halo bolt could not be deflected');
  // Million-hit Dismantle is still lethal elsewhere; this adaptive boss must
  // survive a single cut, visibly react, and retain a finite slow/recovery.
  ({b,p}=reset(200));t.callDismantleHit({x:p.x,y:p.y,damage:.65,hits:new Set()});
  assert.ok(b.hp>0&&b.hp<b.max,'Dismantle deleted or failed to damage the boss');
  t.stepAdapted(1/60);assert.ok(b.hitReact>0&&b.dismantleSlow>0,'Dismantle reaction was bypassed');
  for(let i=0;i<60;i++)t.stepAdapted(1/60);
  assert.equal(b.dismantleSlow,0,'Dismantle slow became permanent');
  // A corpse cannot adapt back to life when another slash crosses it.
  t.callBossDown();for(let i=0;i<170;i++)if(t.getBoss())t.stepAdapted(1/60);
  assert.equal(t.getPhase(),'travel','Death failed to hand off to Act I');
  // Known anatomical landmarks from the PNG, not equal-row assumptions.
  const headY=[38,238,416,616,810,989,1146,1402],footY=[210,389,575,782,959,1132,1330,1519];
  for(let row=0;row<8;row++)for(let dir=0;dir<4;dir++){
    let r=t.getFrameRect(row,dir);assert.ok(r.y<=headY[row],`Head clipped in row ${row}`);
    assert.ok(r.y+r.h>footY[row],`Feet clipped in row ${row}`);
    assert.ok(r.x>=0&&r.x+r.w<=1024&&r.y+r.h<=1536,'Source rectangle outside atlas');
  }
  const cfg=t.getAdaptedConfig();assert.equal(cfg.sizeRatio,2);assert.equal(cfg.heroBodyHeight,47);
};
