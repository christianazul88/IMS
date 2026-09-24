/* FOUR SHADOW CONTRACTS. Sizes are visible body heights relative to the
 * Conductor's 66px body: gorilla 1.5x, titan 4x. Only one contract is active
 * normally; the cinematic ultimate temporarily summons the whole court.
 * Balance: short stun pulses, shared special cooldown, a fixed total beam
 * DPS split across targets, and a limited guard pool prevent permanent locks.
 */
(() => {
  const cfg={heroHeight:66,wingHeight:76,gorillaHeight:99,guardHeight:92,titanHeight:264,
    specialDurations:[0,4.5,4,4,5],specialCooldown:14,ultimateDuration:6,ultimateCooldown:28,
    wingDamage:3,wingRate:.62,gorillaDamage:7,gorillaRate:1.15,titanDamage:10,titanRate:1.8,
    // Invisible melee range lines.  The line starts at the shadow's body,
    // points along `face`, and has a small vertical tolerance so contact is
    // readable without turning the attack into a full-circle hitbox.
    meleeReach:{2:112,4:178},meleeLineWidth:{2:54,4:86},
    shadowAggroRadius:560,shadowAggroBias:80,shadowHealth:{1:32,2:48,3:64,4:120},
    // Every contract costs a fifth of the Conductor's maximum life.  A broken
    // contract never auto-reforms: it stays unavailable for this cooldown and
    // must be deliberately summoned again (paying the same life sacrifice).
    summonCostRatio:.2,summonCooldown:15,idleRegen:4,
    beamDPS:7,guardCapacity:36,guardRecharge:6};
  const female=()=>window.selectedHero==='shadow-summoner';
  const sheet=new Image(),body=new Image(),commands=new Image(),breath=new Image();let requested=false;
  // The roster atlas is authored as four rows x eight poses.  The fallback
  // check keeps older cached six-pose sheets from breaking while a browser
  // refreshes the new asset.
  function assets(){if(requested)return;requested=true;sheet.src='assets/shadow-roster-atlas-8f.png';body.src='assets/shadow-summoner-movement-atlas.png';commands.src='assets/shadow-summoner-attack-atlas.png';breath.src='assets/abyss-titan-laser-breath-6f-v2.png'}
  document.querySelector('[data-hero="shadow-summoner"]')?.addEventListener('click',assets);
  const names=['','WINGED SHADE','ECHO GORILLA','MIRROR WARDEN','ABYSS TITAN'];
  let selected=1,beasts=[],contracts=new Map(),special=null,cinema=null,cd=0,ultCd=0,clock=0,pose=0,bolts=[],echoes=[],beams=[],guardFX=[];
  const live=()=>homingCandidates().filter(q=>targetAlive(q)&&q.mode!=='death');
  const nearest=a=>live().sort((x,y)=>dist(a,x)-dist(a,y))[0];
  function make(id){if(contracts.has(id))return contracts.get(id);const hp=cfg.shadowHealth[id]||32,a={id,x:p.x+(id===3?-35:60),y:p.y,age:0,cd:.2,attack:null,frame:0,frameFloat:0,face:1,r:id===4?62:id===2?34:28,hp,maxHp:hp,defeated:false,cooldown:0,impactPause:0,guard:cfg.guardCapacity,recharge:0,summoned:false};contracts.set(id,a);return a;}
  function summonCost(){return Math.max(1,Math.ceil((p.maxHp||100)*cfg.summonCostRatio))}
  function living(id){return beasts.find(a=>a&&a.id===id&&!a.defeated)}
  function summonShadow(id,{replace=true,silent=false}={}){
    if(!female()||!running||paused||special||cinema)return null;
    const a=make(clamp(id,1,4));
    if(!a.defeated&&beasts.includes(a))return a; // Switching to an active contract is free.
    if(a.cooldown>0){if(!silent)say(names[a.id]+' REFORMS IN '+a.cooldown.toFixed(1)+'s',.9);return null}
    const cost=summonCost();
    if(p.hp<=cost){if(!silent)say('TOO LITTLE LIFE TO SUMMON '+names[a.id],1);return null}
    p.hp=Math.max(1,p.hp-cost);a.defeated=false;a.hp=a.maxHp;a.cooldown=0;a.impactPause=0;a.attack=null;if(!a.summoned)a.guard=cfg.guardCapacity;a.summoned=true;a.recharge=0;a.x=p.x+(a.id===3?-35:60);a.y=p.y;a.face=1;a.age=0;
    if(replace)beasts=[a];else if(!beasts.includes(a))beasts.push(a);
    if(!silent)say(names[a.id]+' SUMMONED — '+cost+' LIFE SACRIFICED',1);
    return a;
  }
  function select(id){if(!female()||!running||paused||special)return false;const next=clamp(id,1,4);if(!cinema&&!summonShadow(next))return false;selected=next;p.weapon='shadow';pose=.4;assets();return true}
  function resetRoster(){selected=1;beasts=[];contracts=new Map();special=cinema=null;cd=ultCd=clock=pose=0;bolts=[];echoes=[];beams=[];guardFX=[];if(female()){assets();p.weapon='shadow';p.hasSword=p.hasShotgun=p.hasMachineGun=p.hasRocketPack=false;p.fire=0;}}
  const resetBase=reset;reset=function(...a){const out=resetBase(...a);resetRoster();return out};
  const respawnBase=respawnAtCheckpoint;respawnAtCheckpoint=function(...a){const out=respawnBase(...a);resetRoster();return out};
  function damage(q,n,source,stun=0){if(!targetAlive(q)||q.mode==='death')return 0;let d=n;if(q===boss&&q.art==='adapted'){d*=adaptedDamageScale('heavyMelee',source);q.hit=.12;q.electricStun=Math.max(q.electricStun||0,stun);q.swordStagger=Math.max(q.swordStagger||0,.08)}q.hp-=d;bloodBurst(q.x,q.y,4);if(q.hp<=0){if(q===boss)bossDown();else if(boss?.parts?.includes(q))demonPartDown(boss,q)}return d}
  function command(){if(!female()||!running||paused||p.fire>0)return false;let a=living(selected);if(!a)a=summonShadow(selected);if(!a)return false;a.cd=Math.min(a.cd,.06);pose=.24;p.fire=.3;return true}
  const baseFire=fire;fire=function(){return female()?command():baseFire()};
  const baseSwitch=switchWeapon;switchWeapon=function(){return female()?select(selected%4+1):baseSwitch()};
  function startSpecial(){if(!female()||!running||paused||cd>0||special||cinema)return false;let a=living(selected);if(!a)a=summonShadow(selected);if(!a)return false;special={id:selected,age:0,duration:cfg.specialDurations[selected],pulse:selected===1?.65:0};cd=cfg.specialCooldown;pose=.6;sfx('shadowUltimate');say(['','TEMPEST DESCENT','RESONANT RAMPAGE','MIRROR BASTION','ABYSSAL PRISM'][selected],1.5);return true}
  function startUltimate(){if(!running||paused||cinema||ultCd>0||special)return false;const isFemale=female();if(isFemale){assets();const court=[];for(let id=1;id<=4;id++){const a=living(id)||summonShadow(id,{replace:false,silent:true});if(a)court.push(a)}beasts=court;if(!beasts.length)return false}cinema={female:isFemale,age:0,duration:cfg.ultimateDuration,pulse:0,final:false};ultCd=cfg.ultimateCooldown;sfx('shadowUltimate');say(isFemale?'SOVEREIGN ECLIPSE — THE FOURFOLD COURT':'HEAVENBREAKER — THE LAST JUDGMENT',2);return true}
  function setFrame(a,phase){const n=8;a.frameFloat=((phase%n)+n)%n;a.frame=Math.floor(a.frameFloat);}
  function activeShadows(){return beasts.filter(a=>a&&!a.defeated&&a.hp>0)}
  function enemyTarget(e){if(!female()||!running)return p;const choices=activeShadows();if(!choices.length)return p;let best=choices[0],bestD=dist(e,best);for(let i=1;i<choices.length;i++){const d=dist(e,choices[i]);if(d<bestD){best=choices[i];bestD=d}}const playerD=dist(e,p);return bestD<=cfg.shadowAggroRadius&&bestD<=playerD+cfg.shadowAggroBias?best:p}
  function isShadowTarget(a){return !!a&&activeShadows().includes(a)}
  function damageShadow(a,n){if(!isShadowTarget(a))return 0;a.hit=.18;a.impactPause=Math.max(a.impactPause||0,.14);a.hp=Math.max(0,a.hp-n);burst(a.x,a.y,'#b88cff',6);if(a.hp<=0){a.defeated=true;a.cooldown=cfg.summonCooldown;a.attack=null;a.impactPause=0;say(names[a.id]+' DISPERSED — SUMMON AGAIN IN '+cfg.summonCooldown+'s',1.1)}return n}
  // The Adapted One and future bosses use the same priority rule as normal
  // enemies: a living shadow is the nearest valid target; only when the court
  // is empty does the boss regain permission to target the Conductor.
  function bossTarget(b){if(!female()||!running)return p;const choices=activeShadows();if(!choices.length)return p;let best=choices[0],bestD=dist(b,best);for(let i=1;i<choices.length;i++){const d=dist(b,choices[i]);if(d<bestD){best=choices[i];bestD=d}}return best}
  function enemyProjectileTarget(e){return enemyTarget(e)}
  function projectile(a,q){bolts.push({x:a.x,y:a.y-35,previousX:a.x,previousY:a.y-35,target:q,life:3});setFrame(a,3.4);adaptedRecord('projectile',.4)}
  function meleeRangeLine(a){const reach=cfg.meleeReach[a.id]||0,dir=a.face||1;return {x1:a.x,y1:a.y,x2:a.x+dir*reach,y2:a.y,reach,width:cfg.meleeLineWidth[a.id]||0}}
  function meleeInRange(a,q){if(!q||!cfg.meleeReach[a.id])return false;const line=meleeRangeLine(a),radius=q.r||18,dx=(q.x-a.x)*(a.face||1),dy=Math.abs(q.y-a.y);return dx>=-radius&&dx<=line.reach+radius&&dy<=line.width+radius}
  function strike(a,q,heavy=false){if(!meleeInRange(a,q))return false;const list=live(),amount=a.id===2?cfg.gorillaDamage:cfg.titanDamage;damage(q,amount,a);sfx('shadowImpact');if(a.id===2){const victims=list.filter(t=>t!==q&&dist(t,q)<210);for(let i=0;i<victims.length;i++)echoes.push({age:0,delay:.12+i*.09,from:{x:q.x,y:q.y},target:victims[i],damage:amount*.42,hit:false});echoes.push({age:0,delay:0,from:{x:a.x,y:a.y},target:q,damage:0,hit:true});}else for(const t of list)if(t!==q&&dist(t,q)<125)damage(t,amount*.4,a);if(heavy)burst(q.x,q.y,'#b49cff',14);return true}
  function updateBeast(a,dt){a.age+=dt;a.cd=Math.max(0,a.cd-dt);a.recharge=Math.max(0,a.recharge-dt);if(a.defeated){setFrame(a,0);return}if(a.impactPause>0){a.impactPause=Math.max(0,a.impactPause-dt);setFrame(a,6);return}if(a.id===3&&a.recharge===0)a.guard=Math.min(cfg.guardCapacity,a.guard+8*dt);const q=nearest(a),boost=special?.id===a.id;
    if(a.id===3){a.x+=(p.x-Math.cos(p.angle)*30-a.x)*Math.min(1,dt*10);a.y+=(p.y+8-a.y)*Math.min(1,dt*10);setFrame(a,guardFX.length?4.6:boost?2.2:(clock*1.35)%2);return}
    if(a.id===1){if(boost&&q){const cycle=(special.age%1.05)/1.05,start={x:p.x+45,y:p.y-65},t=cycle<.62?cycle/.62:(1-cycle)/.38;a.x=start.x+(q.x-start.x)*t;a.y=start.y+(q.y-start.y)*t-Math.sin(t*Math.PI)*85;setFrame(a,Math.min(7.92,cycle*8));if(special.age>=special.pulse){special.pulse=special.age+1.05;damage(q,3.3,a,.18);guardFX.push({x:q.x,y:q.y,age:0,electric:true});}}else{a.x+=(p.x+65*Math.cos(clock*1.3)-a.x)*Math.min(1,dt*7);a.y+=(p.y-60+Math.sin(clock*4)*9-a.y)*Math.min(1,dt*7);setFrame(a,(clock*3.2)%4);if(q&&a.cd<=0){projectile(a,q);a.cd=cfg.wingRate}}return}
    if(a.id===4&&boost){if(q)a.face=q.x<a.x?-1:1;
      // The atlas contains a pre-painted laser in cells 5–6.  We reserve those
      // cells for regular attacks: the special owns the laser entirely so its
      // mouth core and animated beam can never fight a second baked-in beam.
      setFrame(a,special.age<.65?2+special.age/.65*1.55:(clock*3.2)%2);
      if(special.age>=.65){beams=live().map(t=>{const renderScale=window.PRINCESS_TERRAIN?.active()?window.PRINCESS_TERRAIN.tuning.characterScale:1,
        // TITAN_MOUTH_SOCKET: adjust these two ratios if a future Titan atlas
        // changes its head position. Values are relative to its rendered height.
        x=a.x+a.face*cfg.titanHeight*.20*renderScale,y=a.y+24-cfg.titanHeight*.73*renderScale;
        return{x,y,target:t,source:a,angle:Math.atan2(t.y-y,t.x-x)}});
        if(special.age>=special.pulse){special.pulse=special.age+.16;for(const b of beams)damage(b.target,cfg.beamDPS*.16/Math.max(1,beams.length),a)}}return}
    if(a.attack){a.attack.age+=dt;const t=a.attack.age/a.attack.duration;const phase=Math.min(7.92,t*8);setFrame(a,a.id===2&&a.attack.variant?Math.min(7.92,phase+.35):phase);if(!a.attack.hit&&t>=.48){a.attack.hit=true;if(targetAlive(a.attack.target)&&meleeInRange(a,a.attack.target))strike(a,a.attack.target,boost)}if(t>=1)a.attack=null;return}
    const goal=q?{x:q.x-(a.id===4?105:65)*(a.x<q.x?1:-1),y:q.y}:{x:p.x+75,y:p.y+20};const d=dist(a,goal),angle=Math.atan2(goal.y-a.y,goal.x-a.x),speed=a.id===4?170:290;a.face=q?(q.x<a.x?-1:1):Math.cos(angle)<0?-1:1;
    // Cells 0–1 are the only idle/locomotion poses. Attack and special
    // cells 2–7 are selected exclusively by a committed action phase below.
    if(d>10){const previous={x:a.x,y:a.y};a.x+=Math.cos(angle)*Math.min(d,speed*dt);a.y+=Math.sin(angle)*Math.min(d,speed*dt);window.PRINCESS_TERRAIN?.constrainActor(a,previous);setFrame(a,(a.age*4.4)%2)}else setFrame(a,(clock*1.5)%2);
    // The attack action is gated by the invisible range line, not just the
    // movement goal. A target outside this capsule cannot start a windup.
    if(q&&meleeInRange(a,q)&&a.cd<=0){a.attack={age:0,duration:boost?.52:a.id===2?.68:.95,target:q,hit:false,variant:Math.floor(a.age)%2};a.cd=(a.id===2?cfg.gorillaRate:cfg.titanRate)*(boost?.65:1)}
  }
  // Guard only reacts to an incoming hit. It never performs an autonomous
  // attack; reflection is capped and its pool must recharge after depletion.
  const oldHurt=hurt;hurt=function(n){if(!female()||p.dashTime>0||boss?.heroGrace>0)return oldHurt(n);const g=beasts.find(a=>a.id===3);if(g&&g.guard>0&&g.recharge<=0){const blocked=Math.min(n,g.guard,special?.id===3?n:n*.75);g.guard-=blocked;g.recharge=g.guard<=0?cfg.guardRecharge:0;guardFX.push({x:p.x,y:p.y,age:0});const q=nearest(g);if(q)damage(q,Math.min(8,blocked*.55),g);sfx('shadowElectric');if(n-blocked>.01)return oldHurt(n-blocked);return}return oldHurt(n)};
  function updateRoster(dt){if(!running||paused)return;clock+=dt;pose=Math.max(0,pose-dt);cd=Math.max(0,cd-dt);ultCd=Math.max(0,ultCd-dt);beams=[];
    for(const a of contracts.values())a.cooldown=Math.max(0,(a.cooldown||0)-dt);
    if(female()){
      // Regeneration is intentionally idle-only: movement, firing, dashing,
      // a special, or the cinematic ultimate all suspend the recovery pulse.
      const idle=!HERO_ANIM.moving&&!mouse.down&&p.fire<=0&&p.dashTime<=0&&!special&&!cinema;
      if(idle&&p.hp<p.maxHp)p.hp=Math.min(p.maxHp,p.hp+cfg.idleRegen*dt);
      for(const a of beasts)updateBeast(a,dt);if(special){special.age+=dt;if(special.age>=special.duration)special=null}for(const s of bolts){s.life-=dt;if(!targetAlive(s.target)){s.life=0;continue}const d=dist(s,s.target),a=Math.atan2(s.target.y-s.y,s.target.x-s.x);s.previousX=s.x;s.previousY=s.y;if(d<650*dt+30){damage(s.target,cfg.wingDamage,s);s.life=0}else{s.x+=Math.cos(a)*650*dt;s.y+=Math.sin(a)*650*dt}}bolts=bolts.filter(s=>s.life>0);for(const e of echoes){e.age+=dt;if(!e.hit&&e.age>=e.delay){e.hit=true;damage(e.target,e.damage,e.from);e.target.dismantleSlow=Math.max(e.target.dismantleSlow||0,.35)}}echoes=echoes.filter(e=>e.age<e.delay+.45)}
    for(const e of guardFX)e.age+=dt;guardFX=guardFX.filter(e=>e.age<.4);
    if(cinema){cinema.age+=dt;const u=cinema;if(!u.female&&u.age>1&&u.age>=u.pulse){u.pulse=u.age+.7;for(const q of live()){damage(q,3.5,p);guardFX.push({x:q.x,y:q.y,age:0,gold:true})}}if(u.age>4.8&&!u.final){u.final=true;for(const q of live()){damage(q,u.female?12:18,p,.15);guardFX.push({x:q.x,y:q.y,age:0,gold:!u.female})}shake=Math.max(shake,.3);sfx('shadowUltimate')}if(u.age>=u.duration){cinema=null;if(female()){const keep=beasts.find(a=>a&&!a.defeated&&a.id===selected)||beasts.find(a=>a&&!a.defeated);beasts=keep?[keep]:[]}}}
  }
  const oldUpdate=update;update=function(dt){oldUpdate(dt);updateRoster(dt);refreshButtons()};
  // Eight authored poses per creature, four rows; use the PNG's real alpha.
  // A small adjacent-pose blend supplies in-betweens without leaving a dark
  // ghost trail. Never scale a fade by summon lifetime: living beasts draw 1.
  function contactShadow(x,y,w,alpha=.34){ctx.save();ctx.globalAlpha=alpha;ctx.fillStyle='#05060b';ctx.filter='blur(2px)';ctx.beginPath();ctx.ellipse(x-camera,y+24,w,w*.24,0,0,7);ctx.fill();ctx.filter='none';ctx.globalCompositeOperation='lighter';ctx.globalAlpha=alpha*.58;ctx.strokeStyle='#ad7cff';ctx.shadowColor='#9b60ff';ctx.shadowBlur=7;ctx.lineWidth=1.5;ctx.beginPath();ctx.ellipse(x-camera,y+23,w*.78,w*.17,0,0,7);ctx.stroke();ctx.restore()}
  function drawBeast(a){if(!sheet.naturalWidth||a.defeated)return;const row=a.id-1,cols=sheet.naturalWidth>=1600?8:6,raw=a.frameFloat??a.frame,col=(((raw%8)+8)%8)*cols/8,base=Math.floor(col),next=(base+1)%cols,mix=Math.min(.28,col-base),h=[0,cfg.wingHeight,cfg.gorillaHeight,cfg.guardHeight,cfg.titanHeight][a.id],readableScale=a.id===1?1.16:a.id===2?1.2:a.id===3?1.13:1.05,w=h*1.08*readableScale,cw=sheet.naturalWidth/cols,ch=sheet.naturalHeight/4;
    // Summons use very dark source art against a very dark map. This subtle rim
    // treatment keeps their animated silhouette readable without making them
    // translucent or turning them into UI overlays.
    contactShadow(a.x,a.y,Math.max(19,w*.34),a.id===4?.45:.38);ctx.save();ctx.translate(a.x-camera,a.y+24);ctx.scale(a.face||1,1);ctx.globalCompositeOperation='source-over';ctx.filter='brightness(1.28) contrast(1.22) saturate(1.5) drop-shadow(0 3px 2px rgba(3,2,9,.9)) drop-shadow(0 0 3px rgba(176,119,255,.95))';ctx.globalAlpha=1-mix;ctx.drawImage(sheet,base*cw,row*ch,cw,ch,-w/2,-h,w,h);if(mix>.01){ctx.globalAlpha=mix;ctx.drawImage(sheet,next*cw,row*ch,cw,ch,-w/2,-h,w,h)}ctx.restore()}
  // Arena terrain has a depth-sorted world queue. Registering summons there
  // lets grass, trees, and foreground props naturally pass in front of them.
  function queueWorldActors(queue,scaleActor){if(!female()||!running)return;for(const a of beasts)if(a&&!a.defeated)queue.push({y:a.y+24,paint:()=>scaleActor?scaleActor(a,()=>drawBeast(a)):drawBeast(a)})}
  const oldHero=drawHero;drawHero=function(){if(!female())return oldHero();assets();contactShadow(p.x,p.y,30,.44);const sector=(Math.round(p.angle/(Math.PI/4))+8)%8,col=[1,0,0,0,1,3,2,3][sector],mirror=[false,false,false,true,true,true,false,false][sector],moving=HERO_ANIM.moving,im=pose>0?commands:body,row=pose>0?0:p.dashTime>0?3:moving?1+Math.floor(clock*8)%2:0,frame=pose>0?Math.floor(clock*12)%4:col;ctx.save();ctx.translate(p.x-camera,p.y+24);ctx.scale(mirror?-1:1,1);ctx.filter='brightness(1.25) contrast(1.18) saturate(1.35) drop-shadow(0 3px 2px rgba(3,2,9,.9)) drop-shadow(0 0 2px rgba(159,109,255,.8))';if(im.naturalWidth)ctx.drawImage(im,frame*im.naturalWidth/4,row*im.naturalHeight/4,im.naturalWidth/4,im.naturalHeight/4,-50,-110,100,110);ctx.restore()};
  const oldHuntDraw=window.PRINCESS_HUNT.draw;window.PRINCESS_HUNT.draw=function(){oldHuntDraw();if(!female())return;if(!window.PRINCESS_TERRAIN?.active())for(const a of [...beasts].sort((a,b)=>a.y-b.y))drawBeast(a);ctx.save();ctx.globalCompositeOperation='lighter';for(const s of bolts){ctx.strokeStyle='#dceeff';ctx.lineWidth=4;ctx.beginPath();ctx.moveTo(s.previousX-camera,s.previousY);ctx.lineTo(s.x-camera,s.y);ctx.stroke()}for(const e of echoes){if(e.age<e.delay)continue;const t=(e.age-e.delay)/.45;ctx.globalAlpha=(1-t)*.85;ctx.strokeStyle='#d2afff';ctx.lineWidth=3;ctx.beginPath();ctx.arc(e.target.x-camera,e.target.y,25+t*110,-2.5,.8);ctx.stroke()}
    // One shared mouth core, regardless of how many targets the Titan is
    // channeling. Its scale shrinks at point-blank range so it cannot cover
    // a nearby enemy, while the beam exits beyond the core at every distance.
    if(beams.length&&breath.naturalWidth){const age=special?.id===4?special.age:0,duration=special?.duration||5,primary=beams.reduce((best,b)=>dist(b,b.target)<dist(best,best.target)?b:best);let frameFloat=age<.66?age/.22:age<1.05?2+(age-.66)/.39:age<duration-.5?3+(((age-1.05)*1.3)%2):5;frameFloat=clamp(frameFloat,0,5);const frame=Math.floor(frameFloat),next=Math.min(5,frame+1),mix=frame===5?0:frameFloat-frame,cw=breath.naturalWidth/6,ch=breath.naturalHeight,primaryDistance=Math.max(1,dist(primary,primary.target)),coreScale=clamp(primaryDistance/250,.48,1),cellW=132*coreScale,cellH=cellW*ch/cw,x=primary.x-camera,y=primary.y,rotation=primary.angle||0,pulse=1+Math.sin(clock*26)*.035;ctx.save();ctx.translate(x,y);ctx.rotate(rotation);ctx.shadowColor='#8e7bff';ctx.shadowBlur=14;ctx.globalAlpha=.88*(1-mix);ctx.drawImage(breath,frame*cw,0,cw,ch,-cellW*.04,-cellH*.5,cellW*pulse,cellH*pulse);if(mix>.01){ctx.globalAlpha=.88*mix;ctx.drawImage(breath,next*cw,0,cw,ch,-cellW*.04,-cellH*.5,cellW*pulse,cellH*pulse)}ctx.restore()}
    for(const b of beams){const x=b.x-camera,y=b.y,tx=b.target.x-camera,ty=b.target.y,dx=tx-x,dy=ty-y,distance=Math.max(1,Math.hypot(dx,dy)),coreScale=clamp(distance/250,.48,1),exit=Math.min(82*coreScale,distance*.36),sx=x+dx/distance*exit,sy=y+dy/distance*exit,beamGradient=ctx.createLinearGradient(sx,sy,tx,ty);beamGradient.addColorStop(0,'#ffffff');beamGradient.addColorStop(.12,'#bdfbff');beamGradient.addColorStop(.48,'#9d70ff');beamGradient.addColorStop(1,'#5d24c9');ctx.save();ctx.globalAlpha=.78;ctx.lineCap='round';ctx.shadowColor='#8f6cff';ctx.shadowBlur=22;ctx.strokeStyle=beamGradient;ctx.lineWidth=30+Math.sin(clock*22)*5;ctx.beginPath();ctx.moveTo(sx,sy);ctx.lineTo(tx,ty);ctx.stroke();ctx.shadowBlur=0;ctx.globalAlpha=.95;ctx.strokeStyle='#efffff';ctx.lineWidth=7+Math.sin(clock*31)*1.5;ctx.beginPath();ctx.moveTo(sx,sy);ctx.lineTo(tx,ty);ctx.stroke();ctx.globalAlpha=.7;ctx.strokeStyle='#ffffff';ctx.lineWidth=2;ctx.setLineDash([18,34]);ctx.lineDashOffset=-clock*240;ctx.beginPath();ctx.moveTo(sx,sy);ctx.lineTo(tx,ty);ctx.stroke();ctx.setLineDash([]);for(let i=0;i<7;i++){const t=(clock*1.8+i/7)%1,px=sx+(tx-sx)*t,py=sy+(ty-sy)*t;ctx.globalAlpha=.35+.3*Math.sin(clock*18+i);ctx.fillStyle=i%2?'#b8a5ff':'#ecffff';ctx.beginPath();ctx.arc(px,py,2.2+Math.sin(clock*25+i)*1.2,0,7);ctx.fill()}ctx.restore();ctx.globalAlpha=1;ctx.fillStyle='#fff';ctx.beginPath();ctx.arc(tx,ty,8+Math.sin(clock*34)*3,0,7);ctx.fill()}ctx.restore()};
  const oldDraw=draw;draw=function(){oldDraw();if(!running)return;ctx.save();for(const e of guardFX){ctx.globalAlpha=1-e.age/.4;ctx.strokeStyle=e.gold?'#ffe7a3':'#bcf9ff';ctx.lineWidth=3;ctx.beginPath();ctx.arc(e.x-camera,e.y,30+e.age*100,0,7);ctx.stroke()}ctx.restore();if(!cinema)return;const u=cinema,s=Math.min(1,u.age/.6,(u.duration-u.age)/.7);ctx.save();ctx.setTransform(1,0,0,1,0,0);const g=ctx.createRadialGradient(W/2,H/2,100,W/2,H/2,W*.6);g.addColorStop(0,'rgba(0,0,0,.05)');g.addColorStop(1,`rgba(${u.female?'16,0,36':'20,9,0'},${s*.85})`);ctx.fillStyle=g;ctx.fillRect(0,70,W,H-140);ctx.globalCompositeOperation='lighter';ctx.strokeStyle=u.female?'#d1a2ff':'#ffe9ae';ctx.globalAlpha=s*.65;ctx.lineWidth=2;for(let i=0;i<18;i++){const x=(i*137+u.age*120)%W,y=80+(i*83)%(H-160);ctx.beginPath();ctx.moveTo(x,y);ctx.lineTo(x+70,y-30);ctx.stroke()}if(u.age<1.6){ctx.globalAlpha=s;ctx.font='bold 24px Georgia';ctx.textAlign='center';ctx.fillStyle=u.female?'#eddaff':'#fff0b8';ctx.fillText(u.female?'SOVEREIGN ECLIPSE':'HEAVENBREAKER',W/2,130)}ctx.restore()};
  const atmosphericDraw=draw;draw=function(){atmosphericDraw();if(!running||!cinema)return;const u=cinema,t=u.age,fade=Math.min(1,t/.6,(u.duration-t)/.7),viewY=window.PRINCESS_TERRAIN?.viewY||0;ctx.save();ctx.setTransform(1,0,0,1,0,0);ctx.beginPath();ctx.rect(0,70,W,H-140);ctx.clip();
    if(u.female){const x=W/2,y=165,r=52+Math.sin(t*2)*3;ctx.fillStyle='#08040f';ctx.globalAlpha=fade*.9;ctx.beginPath();ctx.arc(x,y,r,0,7);ctx.fill();ctx.globalCompositeOperation='lighter';ctx.strokeStyle='#d0a0ff';ctx.lineWidth=4;ctx.shadowColor='#a75aff';ctx.shadowBlur=18;ctx.stroke();for(let i=0;i<8;i++){const a=i*Math.PI/4+t*.25;ctx.lineWidth=2;ctx.beginPath();ctx.moveTo(x+Math.cos(a)*(r+8),y+Math.sin(a)*(r+8));ctx.lineTo(x+Math.cos(a)*(r+22),y+Math.sin(a)*(r+22));ctx.stroke()}if(t>4.65){const q=nearest(p);if(q){const growth=clamp((t-4.65)/.55,0,1);ctx.globalAlpha=(1-clamp((t-5.1)/.8,0,1))*fade;ctx.lineWidth=5;ctx.beginPath();ctx.ellipse(q.x-camera,q.y-viewY+15,35+growth*155,15+growth*65,0,0,7);ctx.stroke()}}}
    else{ctx.globalCompositeOperation='lighter';const q=nearest(p);if(q){const x=q.x-camera,y=q.y-viewY;for(let i=0;i<5;i++){const local=(t-.7-i*.14)%1.15;if(local<0||t>4.7)continue;const a=-.75+i*.38,progress=clamp(local/.65,0,1),d=180*(1-progress);ctx.save();ctx.translate(x+Math.sin(a)*d,y-130-d);ctx.rotate(a);ctx.globalAlpha=fade*(1-clamp((local-.65)/.5,0,1));ctx.fillStyle='#ffe9ab';ctx.beginPath();ctx.moveTo(-4,-38);ctx.lineTo(4,-38);ctx.lineTo(6,10);ctx.lineTo(0,36);ctx.lineTo(-6,10);ctx.closePath();ctx.fill();ctx.fillRect(-16,-26,32,4);ctx.restore()}if(t>=4.3){const progress=clamp((t-4.3)/.5,0,1);ctx.globalAlpha=fade;ctx.fillStyle='#fff5cf';ctx.save();ctx.translate(x,y-250*(1-progress));ctx.beginPath();ctx.moveTo(-7,-210);ctx.lineTo(7,-210);ctx.lineTo(12,-28);ctx.lineTo(0,15);ctx.lineTo(-12,-28);ctx.closePath();ctx.fill();ctx.fillRect(-36,-150,72,8);ctx.restore();if(t>4.8){ctx.globalAlpha=fade*(1-clamp((t-4.8)/1.2,0,1));ctx.strokeStyle='#ffdb8d';ctx.lineWidth=6;ctx.beginPath();ctx.ellipse(x,y+15,30+(t-4.8)*180,12+(t-4.8)*70,0,0,7);ctx.stroke()}}}}
    ctx.restore()};
  document.addEventListener('keydown',e=>{if(!running||paused||e.repeat)return;const k=e.key.toLowerCase();if(k==='f'){e.preventDefault();e.stopImmediatePropagation();startUltimate()}else if(female()&&(/[1-4]/.test(k)&&k.length===1||['q','r','e'].includes(k))){e.preventDefault();e.stopImmediatePropagation();if(k==='q')select(selected%4+1);else if(k==='r')startSpecial();else if(k==='e')startUltimate();else select(Number(k))}},true);
  canvas.addEventListener('pointerdown',e=>{if(female()&&e.button===2){e.preventDefault();e.stopImmediatePropagation();mouse.down=false;startSpecial()}},true);
  const oldUlt=ultimateAttack;ultimateAttack=function(...a){return female()?startUltimate():oldUlt(...a)};
  const tray=document.createElement('div');tray.style.cssText='position:absolute;left:12px;bottom:85px;z-index:10;display:flex;gap:5px;flex-wrap:wrap';const buttons=[];for(let i=1;i<=4;i++){const b=document.createElement('button');b.textContent=i+' '+names[i];b.style.cssText='background:#21192d;color:#eee;border:1px solid #8f70b7;padding:7px;font-size:10px';b.onclick=()=>select(i);tray.appendChild(b);buttons.push(b)}gameFrame.appendChild(tray);
  const ultButton=document.createElement('button');ultButton.style.cssText='position:absolute;right:22px;bottom:125px;z-index:10;background:#322243;color:#ffe9aa;border:1px solid #e4b8ff;padding:12px';ultButton.onclick=startUltimate;gameFrame.appendChild(ultButton);
  const specialButton=document.querySelector('#tempestButton');specialButton.setAttribute('aria-label','Active beast special');specialButton.addEventListener('pointerdown',e=>{e.preventDefault();e.stopImmediatePropagation();startSpecial()},true);
  function refreshButtons(){tray.style.display=female()&&running?'flex':'none';ultButton.hidden=!running;ultButton.textContent=ultCd>0?'ULT '+ultCd.toFixed(1):'ULTIMATE [F]';specialButton.hidden=!female()||!running;specialButton.textContent=special?'SPECIAL '+(special.duration-special.age).toFixed(1):cd>0?'SPECIAL '+cd.toFixed(1):'SPECIAL [R]';buttons.forEach((b,i)=>b.style.borderColor=selected===i+1?'#fff2b8':'#8f70b7')}
  refreshButtons();window.SHADOW_ROSTER={cfg,select,command,startSpecial,startUltimate,damage,meleeRangeLine,meleeInRange,enemyTarget,enemyProjectileTarget,bossTarget,isShadowTarget,damageShadow,summonShadow,queueWorldActors,get state(){return{selected,beasts,special,cinema,cd,ultCd,beams,bolts,echoes}}};
})();
