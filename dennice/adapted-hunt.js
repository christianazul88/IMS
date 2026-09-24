/* Adapted One's powerful but readable pressure attacks.
 * TUNE the timings and radii below; visual art never changes the hit logic.
 */
(function(){
  const tuning={
    dashWindup:.55,dashSpeed:1180,
    // Atomic breath cue is charge -> primary detonation -> delayed aftershock.
    // Lower speeds keep both fronts on screen long enough to read and dodge.
    // 684px radius = ~3/4 of the 1824px explorable arena width.  The visual
    // diameter is therefore powerful but no longer blankets the entire map.
    shockWindup:1.35,waveSpeed:335,aftershockSpeed:285,aftershockDelay:.78,waveRadius:684,waveDamage:23,waveGap:.52,
    riftWarning:.9,riftRadius:62,blinkWindup:.48,blinkRecover:.66,
    blinkDamage:27,blinkOffset:128,rangedPressureDistance:290,pressureSeconds:2.4
  };
  ADAPTED_ONE_CONFIG.dashWindup=tuning.dashWindup;ADAPTED_ONE_CONFIG.dashSpeed=tuning.dashSpeed;
  Object.assign(ADAPTED_MOVES,{
    shockwave:{wind:tuning.shockWindup,active:.18,recover:.82,reach:125,step:0,damage:23,arc:Math.PI,label:'RUPTURE — DASH THROUGH THE RING'},
    rift:{wind:.55,active:.18,recover:.72,reach:0,step:0,damage:18,arc:0,label:'FAULT LINE — KEEP MOVING'},
    pursuit:{wind:.55,active:.3,recover:.8,reach:74,step:0,damage:22,arc:.7,label:'DOUBLE HUNT — TWO LUNGES'},
    blinkPunch:{wind:tuning.blinkWindup,active:.13,recover:tuning.blinkRecover,reach:96,step:0,damage:tuning.blinkDamage,arc:.78,label:'PHASE FIST — MOVE BEFORE THE FLASH'}
  });
  for(const pool of Object.values(ADAPTED_ATTACK_POOL))for(const name of ['shockwave','rift','pursuit','blinkPunch'])if(!pool.includes(name))pool.push(name);
  const choose=adaptedChooseAttack;
  adaptedChooseAttack=function(b){
    const target=adaptedTarget(b),far=dist(b,target)>tuning.rangedPressureDistance;
    if(far&&(b.kitePressure||0)>=tuning.pressureSeconds){
      const picks=['rift','shockwave','pursuit','blinkPunch'].filter(n=>!(b.cooldowns[n]>0)&&b.lastAttacks.at(-1)!==n);
      if(picks.length){b.kitePressure=0;return picks[b.attackIndex%picks.length];}
    }
    if(!(b.cooldowns.shockwave>0)&&b.attackIndex%4===1)return 'shockwave';
    if(far&&!(b.cooldowns.rift>0)&&b.attackIndex%3===2)return 'rift';
    if(!(b.cooldowns.blinkPunch>0)&&b.attackIndex%5===3)return 'blinkPunch';
    return choose(b);
  };
  const baseStart=adaptedStartAttack;
  adaptedStartAttack=function(b,name){
    if(name==='blinkPunch'){
      const move={...ADAPTED_MOVES.blinkPunch},target=adaptedPrediction(b,.12),a=Math.atan2(target.y-b.y,target.x-b.x),bounds=arenaWorldBounds();
      b.mode=name;b.attackName=name;b.move=move;b.sequence=[move];b.sequenceIndex=0;b.stage='windup';b.timer=move.wind;b.aimAngle=a;b.attackHits={};
      b.lastAttacks.push(name);if(b.lastAttacks.length>3)b.lastAttacks.shift();b.cooldowns[name]=5.2;
      b.teleportX=clamp(target.x-Math.cos(a)*tuning.blinkOffset,bounds.left,bounds.right);b.teleportY=clamp(target.y-Math.sin(a)*tuning.blinkOffset,bounds.top,bounds.bottom);
      b.hazards=b.hazards||[];b.hazards.push({kind:'blink',x:b.teleportX,y:b.teleportY,age:0,warning:move.wind,r:56,hit:false});
      say(move.label,move.wind);sfx('warn');return;
    }
    if(name==='pursuit'){
      baseStart(b,'dashPunch');b.attackName='pursuit';b.lastAttacks[b.lastAttacks.length-1]='pursuit';
      b.sequence[0].recover=.28;b.sequence.push({...ADAPTED_MOVES.dashPunch,wind:.5,recover:.95,damage:22});
      b.cooldowns.pursuit=7;say(ADAPTED_MOVES.pursuit.label,1.3);return;
    }
    baseStart(b,name);
    if(name==='shockwave'||name==='rift'){
      b.cooldowns[name]=name==='shockwave'?6:5;b.hazards=b.hazards||[];
      if(name==='rift'){
        const bounds=arenaWorldBounds();
        for(let i=0;i<3;i++){
          const warning=tuning.riftWarning+i*.24,q=adaptedPrediction(b,warning);
          const target=adaptedTarget(b);if(Math.hypot(q.x-target.x,q.y-target.y)<2){q.x+=Math.cos(b.aimAngle+Math.PI/2)*(i-1)*100;q.y+=Math.sin(b.aimAngle+Math.PI/2)*(i-1)*100;}
          b.hazards.push({kind:'rift',x:clamp(q.x,bounds.left,bounds.right),y:clamp(q.y,bounds.top,bounds.bottom),age:0,warning,r:tuning.riftRadius,hit:false});
        }
      }
    }
  };
  const baseAttackUpdate=adaptedAttackUpdate;
  adaptedAttackUpdate=function(b,dt){
    if(b.mode==='blinkPunch'){
      b.timer-=dt;if(b.timer>0)return;
      if(b.stage==='windup'){
        const oldX=b.x,oldY=b.y;b.x=b.teleportX;b.y=b.teleportY;const target=adaptedTarget(b);b.aimAngle=Math.atan2(target.y-b.y,target.x-b.x);
        b.trail.push({x:oldX,y:oldY,direction:b.direction,life:.22});b.trail.push({x:b.x,y:b.y,direction:b.direction,life:.18});
        b.stage='active';b.timer=b.move.active;b.strikeFlash=.18;adaptedMeleeHit(b);burst(b.x,b.y,'#ffe39a',16);shake=Math.max(shake,.2);sfx('roar');
      }else if(b.stage==='active'){b.stage='recover';b.timer=b.move.recover;}else adaptedRecover(b);
      return;
    }
    if(!['shockwave','rift'].includes(b.mode))return baseAttackUpdate(b,dt);
    b.timer-=dt;if(b.timer>0)return;
    if(b.stage==='windup'){
      b.stage='active';b.timer=b.move.active;b.strikeFlash=.18;sfx('roar');
      // The supplied atomic-breath clip is the release cue. It is deliberately
      // not played during wind-up, so the sound and white-hot detonation agree.
      if(b.mode==='shockwave')sfx('adaptedAtomicWave');
      if(b.mode==='shockwave'){
        // The MP3 has a primary discharge followed by a second pulse. Both
        // fronts use the same readable escape lane; the delayed one is slower.
        for(let i=0;i<2;i++)b.hazards.push({kind:'wave',x:b.x,y:b.y,age:-i*tuning.aftershockDelay,r:0,previousR:0,gap:b.aimAngle+Math.PI/2,hit:false,speed:i?tuning.aftershockSpeed:tuning.waveSpeed,aftershock:!!i});
      }
    }else if(b.stage==='active'){b.stage='recover';b.timer=b.move.recover;}else adaptedRecover(b);
  };
  const baseUpdate=adaptedBossUpdate;
  adaptedBossUpdate=function(dt){
    const b=boss;if(!b||b.art!=='adapted')return baseUpdate(dt);
    b.kitePressure=dist(b,adaptedTarget(b))>tuning.rangedPressureDistance?(b.kitePressure||0)+dt:Math.max(0,(b.kitePressure||0)-dt*2);
    baseUpdate(dt);if(boss!==b||b.mode==='death'){b.hazards=[];return;}
    for(const h of b.hazards||[]){
      h.age+=dt;if(h.age<0)continue;
      if(h.kind==='wave'){
        h.previousR=h.r;h.r=Math.max(0,h.age*(h.speed||tuning.waveSpeed));
        const target=adaptedTarget(b),d=Math.hypot(target.x-h.x,target.y-h.y),a=Math.atan2(target.y-h.y,target.x-h.x),safeGap=Math.abs(swordAngleDelta(a,h.gap))<tuning.waveGap;
        if(!h.hit&&!safeGap&&d>=h.previousR-24&&d<=h.r+24){h.hit=true;if(target===p){if(p.dashTime<=0)hurt(tuning.waveDamage)}else window.SHADOW_ROSTER?.damageShadow?.(target,tuning.waveDamage,b);}
      }else if(h.kind==='rift'&&!h.hit&&h.age>=h.warning){
        const target=adaptedTarget(b);h.hit=true;if(Math.hypot(target.x-h.x,target.y-h.y)<h.r+12){if(target===p){if(p.dashTime<=0)hurt(18)}else window.SHADOW_ROSTER?.damageShadow?.(target,18,b)}burst(h.x,h.y,'#ffbb77',8);
      }
    }
    b.hazards=(b.hazards||[]).filter(h=>h.kind==='wave'?h.r<tuning.waveRadius:h.age<h.warning+.28);
  };
  function draw(){
    if(!boss||boss.art!=='adapted')return;const b=boss;ctx.save();
    // ATOMIC CHARGE: frame 0 is held at the boss during the warning. This makes
    // the attack read as a build-up -> detonation -> travelling electric front,
    // instead of a static PNG simply becoming larger.
    if(b.mode==='shockwave'&&b.stage==='windup'){
      const charge=clamp(1-b.timer/tuning.shockWindup,0,1),texture=art['adapted-atomic-wave-frames'];
      const x=b.x-camera,y=b.y,size=74+charge*145,pulse=.7+.3*Math.sin(time*42);
      if(texture?.complete&&texture.naturalWidth){
        const sw=texture.naturalWidth/6;
        // Crossfade charge cells 0 -> 1: this adds in-between animation frames
        // without duplicating a 2MB source sheet in the browser cache.
        ctx.save();ctx.translate(x,y);ctx.rotate(time*1.6);ctx.globalCompositeOperation='lighter';
        ctx.globalAlpha=(.26+charge*.74)*(1-charge);ctx.drawImage(texture,0,0,sw,texture.naturalHeight,-size/2,-size/2,size,size);
        ctx.globalAlpha=(.26+charge*.74)*charge;ctx.drawImage(texture,sw,0,sw,texture.naturalHeight,-size/2,-size/2,size,size);ctx.restore();
      }
      ctx.globalAlpha=.18+charge*.46;ctx.strokeStyle='#a8f6ff';ctx.lineWidth=2+charge*4;ctx.beginPath();ctx.arc(x,y,size*.37+pulse*12,0,Math.PI*2);ctx.stroke();
    }
    for(const h of b.hazards||[]){if(h.age<0)continue;const x=h.x-camera,y=h.y;
      if(h.kind==='wave'){
        // Six authored cells plus blended transitions produce twelve visible
        // beats: ignition, blast, thin front, broad front, aftershock, fade.
        // Adjust this timing list if replacing the PNG sheet in the future.
        const texture=art['adapted-atomic-wave-frames'],beats=[{t:0,f:2},{t:.12,f:3},{t:.4,f:4},{t:1.16,f:5}];
        let beat=beats.findIndex((q,i)=>i===beats.length-1||h.age<beats[i+1].t);if(beat<0)beat=beats.length-1;
        const current=beats[beat],following=beats[Math.min(beats.length-1,beat+1)],span=Math.max(.001,following.t-current.t);
        const blend=following.f===current.f?0:clamp((h.age-current.t)/span,0,1);
        const frameScale=[.45,.68,.78,.96,1.06,1.17],scale=frameScale[current.f]*(1-blend)+frameScale[following.f]*blend,size=Math.max(165,h.r*2.08)*scale,rotation=h.gap-.72+Math.sin(h.age*7)*.025;
        ctx.save();ctx.translate(x,y);ctx.rotate(rotation);ctx.globalCompositeOperation='lighter';ctx.globalAlpha=Math.min(.98,.38+h.r/720);
        if(texture?.complete&&texture.naturalWidth){
          const sw=texture.naturalWidth/6,alpha=ctx.globalAlpha;
          ctx.globalAlpha=alpha*(1-blend);ctx.drawImage(texture,sw*current.f,0,sw,texture.naturalHeight,-size/2,-size/2,size,size);
          if(following.f!==current.f){ctx.globalAlpha=alpha*blend;ctx.drawImage(texture,sw*following.f,0,sw,texture.naturalHeight,-size/2,-size/2,size,size);}
        }
        ctx.restore();
        // Live electrical filaments are regenerated every frame. Keep them out
        // of the collision gap so players can trust what they see and dash
        // through the one safe opening.
        ctx.save();ctx.globalCompositeOperation='lighter';ctx.lineCap='round';
        for(let i=0;i<14;i++){
          let a=h.gap+tuning.waveGap+.12+(i/14)*(Math.PI*2-tuning.waveGap*2);a+=Math.sin(h.age*15+i*5)*.045;
          const inner=Math.max(18,h.r*.18),outer=h.r*(.84+Math.sin(h.age*12+i)*.09);
          ctx.globalAlpha=.2+.24*(.5+.5*Math.sin(h.age*24+i));ctx.strokeStyle=i%3?'#68eaff':'#f5fbff';ctx.lineWidth=1.5+(i%3===0?2:0);
          ctx.beginPath();ctx.moveTo(x+Math.cos(a)*inner,y+Math.sin(a)*inner);
          ctx.lineTo(x+Math.cos(a+.035)*outer*.58,y+Math.sin(a+.035)*outer*.58);
          ctx.lineTo(x+Math.cos(a)*outer,y+Math.sin(a)*outer);ctx.stroke();
        }
        ctx.restore();ctx.strokeStyle='#d6fbff';ctx.lineWidth=3;ctx.globalAlpha=.88;ctx.beginPath();ctx.arc(x,y,h.r,h.gap+tuning.waveGap,h.gap+Math.PI*2-tuning.waveGap);ctx.stroke();
      }else if(h.kind==='rift'){
        const warning=h.age<h.warning;ctx.globalAlpha=warning?.18:.65;ctx.fillStyle=warning?'#fa805c':'#fff1b0';ctx.beginPath();ctx.arc(x,y,h.r,0,Math.PI*2);ctx.fill();
        ctx.globalAlpha=1;ctx.strokeStyle=warning?'#ffa16d':'#fff2bf';ctx.lineWidth=3;ctx.stroke();if(warning){ctx.beginPath();ctx.arc(x,y,h.r*clamp(h.age/h.warning,0,1),0,Math.PI*2);ctx.stroke();}
      }else if(h.kind==='blink'){
        const warning=h.age<h.warning,pulse=.6+.4*Math.sin(h.age*22);ctx.globalAlpha=warning?.75:.25;ctx.strokeStyle='#b8fff7';ctx.lineWidth=3;ctx.setLineDash([7,6]);ctx.beginPath();ctx.arc(x,y,h.r+pulse*10,0,Math.PI*2);ctx.stroke();ctx.setLineDash([]);
        ctx.fillStyle='#ffdb9b';ctx.globalAlpha=warning?.2:0;ctx.beginPath();ctx.arc(x,y,h.r*.7,0,Math.PI*2);ctx.fill();
      }
    }
    ctx.restore();
  }
  window.PRINCESS_HUNT={tuning,draw};
})();
