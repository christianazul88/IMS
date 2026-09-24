/* THE ADAPTED ONE
 * This file owns the late-game hunter encounter. Units are game pixels and seconds.
 * Tune ADAPTED_MOVES below: wind = visible warning, active = damaging motion,
 * recover = guaranteed opening. Increasing difficulty never hides warnings.
 */
const ADAPTED_MOVES={
  quickPunch:{wind:.38,active:.12,recover:.5,reach:88,step:26,damage:10,arc:.7,label:'JAB — SIDESTEP'},
  heavyPunch:{wind:.7,active:.16,recover:.9,reach:105,step:48,damage:24,arc:.65,label:'HEAVY CROSS'},
  combo:{wind:.4,active:.12,recover:.23,reach:90,step:28,damage:10,arc:.72,label:'JAB · CROSS · DELAY'},
  relentless:{wind:.34,active:.12,recover:.16,reach:94,step:34,damage:11,arc:.76,label:'RELENTLESS FORM — READ THE RHYTHM'},
  dashPunch:{wind:.62,active:.3,recover:.82,reach:74,step:0,damage:24,arc:.7,label:'HUNTING LUNGE — SIDESTEP'},
  backhand:{wind:.56,active:.16,recover:.75,reach:112,step:10,damage:20,arc:1.8,label:'BACKHAND — GET OUT'},
  grab:{wind:.76,active:.2,recover:1.05,reach:65,step:75,damage:30,arc:.48,label:'GRAB — EVADE'},
  slam:{wind:.86,active:.16,recover:1.05,reach:145,step:0,damage:22,arc:Math.PI,label:'GROUND BREAKER'},
  blast:{wind:1.05,active:.14,recover:.78,reach:1100,step:0,damage:18,label:'HALO VOLLEY — GAP IN THE FAN'},
  judgment:{wind:1.5,active:.38,recover:1.2,reach:1200,step:0,damage:32,label:'JUDGMENT — LEAVE THE LINE'},
  guard:{wind:.22,active:.65,recover:.65,reach:0,step:0,damage:0,label:'ADAPTING GUARD — FLANK IT'}
};

// IMPORTANT: this PNG is NOT an evenly spaced grid. These are measured
// source row bounds [top, bottom], not height/8. The full feet AND halo must
// be inside each rectangle. Changing spriteScale cannot repair bad cropping.
const ADAPTED_ATLAS={width:1024,height:1536,columnWidth:256,
  rows:[[0,214],[214,395],[395,578],[578,785],[785,965],[944,1136],[1110,1338],[1360,1536]],
  // Ground-contact Y for each pose; all views share one floor anchor.
  feet:[212,390,576,783,960,1132,1331,1521],
  // Head-to-foot reference is 176 source pixels. Halo is extra height.
  bodyPixels:176
};
const ADAPTED_COMBAT_ROWS={idle:0,runA:1,runB:2,block:3,attack:4,dash:5,hurt:6,death:7};
function adaptedFrameRect(row,dir){let [top,bottom]=ADAPTED_ATLAS.rows[row];return{x:dir*256,y:top,w:256,h:bottom-top,feet:ADAPTED_ATLAS.feet[row]-top}}
// Two rows overlap in Y: the dash trail rises between the punch's feet, and
// the hurt halo rises between the dash's feet. Shared zigzag seams separate
// those decorations without trimming any head or foot. Coordinates are PNG
// pixels within one 256px column. Left-facing dash artwork mirrors the seam.
function adaptedSeam(which,dir){
  let points=which==='dash'?[[0,965],[65,965],[83,944],[165,951],[190,965],[256,965]]:[[0,1136],[78,1136],[92,1110],[172,1110],[188,1136],[256,1136]];
  return which==='dash'&&dir===3?points.map(([x,y])=>[256-x,y]).reverse():points;
}
function drawAdaptedFrame(g,tex,row,dir,x,y,scale){
  let r=adaptedFrameRect(row,dir),top=row===5?adaptedSeam('dash',dir):row===6?adaptedSeam('hurt',dir):[[0,r.y],[256,r.y]],bottom=row===4?adaptedSeam('dash',dir):row===5?adaptedSeam('hurt',dir):[[0,r.y+r.h],[256,r.y+r.h]];
  g.save();g.beginPath();let points=[...top,...bottom.slice().reverse()];points.forEach(([px,py],i)=>{let dx=x+px*scale,dy=y+(py-r.y)*scale;i?g.lineTo(dx,dy):g.moveTo(dx,dy)});g.closePath();g.clip();
  g.drawImage(tex,r.x,r.y,r.w,r.h,x,y,r.w*scale,r.h*scale);g.restore();
}
function adaptedDirectionIndex(dir){return{down:0,up:1,right:2,left:3}[dir]??0}
// Boss targeting is routed through the shadow roster. While the female hero
// has a living contract on the field, the Adapted One fights that shadow first
// and only returns to her when the court is empty.
function adaptedTarget(b){return window.SHADOW_ROSTER?.bossTarget?.(b)||p}
function adaptedDirectionFor(b){const t=adaptedTarget(b);let a=b.sequence&&b.stage!=='recover'?b.aimAngle:Math.atan2(t.y-b.y,t.x-b.x);return Math.abs(Math.cos(a))>Math.abs(Math.sin(a))?(Math.cos(a)<0?'left':'right'):(Math.sin(a)<0?'up':'down')}
function adaptedNewMemory(){return{meters:Object.fromEntries(ADAPTED_CATEGORIES.map(k=>[k,0])),counts:Object.fromEntries(ADAPTED_CATEGORIES.map(k=>[k,0])),lastGain:{},history:[],dodgeHistory:[],dodgeCounts:{up:0,down:0,left:0,right:0},totalActions:0,averageRange:300,distanceSamples:0,heals:0,lastCategory:''}}
function adaptedRecord(category,weight=1){
  let m=adaptedEnsureMemory();if(!m||!(category in m.meters)||boss.mode==='death')return;
  // Count attacks at emission, never once per projectile per airborne frame.
  // Rate limit memory so a shotgun's pellets don't cause instant adaptation.
  let elapsed=time-(m.lastGain[category]??-1),gain=Math.min(9*weight,Math.max(0,elapsed)*18);
  if(gain<=0)return;m.lastGain[category]=time;m.meters[category]=clamp(m.meters[category]+gain,0,100);
  m.counts[category]++;m.totalActions++;m.lastCategory=category;m.history.push({category,t:time});if(m.history.length>30)m.history.shift();
}
function adaptedRecordDodge(){let m=adaptedEnsureMemory();if(!m)return;let dx=p.dx||0,dy=p.dy||0,key=Math.abs(dx)>Math.abs(dy)?(dx<0?'left':'right'):(dy<0?'up':'down');m.dodgeHistory.push(key);if(m.dodgeHistory.length>8)m.dodgeHistory.shift();m.dodgeCounts={up:0,down:0,left:0,right:0};for(let k of m.dodgeHistory)m.dodgeCounts[k]++;}
function adaptedDodgeBias(){let m=adaptedEnsureMemory();if(!m||m.dodgeHistory.length<3)return'none';let [dir,count]=Object.entries(m.dodgeCounts).sort((a,b)=>b[1]-a[1])[0];return count/m.dodgeHistory.length>=.6?dir:'none'}
function adaptedDamageScale(category,source=p){
  let m=adaptedEnsureMemory();if(!m)return 1;if(boss.mode==='death')return 0;
  let scale=1-Math.min(.42,(m.meters[category]||0)*.0042);
  // Guard protects the FRONT only. Flank, switch weapons, or punish recovery.
  if(boss.mode==='guard'&&boss.stage!=='recover'&&Math.abs(swordAngleDelta(Math.atan2(source.y-boss.y,source.x-boss.x),boss.aimAngle))<1.2)scale*=.42;
  if(boss.mode==='recover'||boss.stage==='recover')scale*=1.22;
  return scale;
}
function adaptedBounds(){return window.PRINCESS_TERRAIN?.active()?window.PRINCESS_TERRAIN.bounds():{left:camera+86,right:camera+W-86,top:190,bottom:H-124};}
function adaptedArena(b){const a=adaptedBounds();b.x=clamp(b.x,a.left,a.right);b.y=clamp(b.y,a.top,a.bottom)}
function adaptedPrediction(b,lead=0){
  // The last movement key is not velocity: a stationary hero gets zero lead.
  const target=adaptedTarget(b);let dx=(keys.d||keys.ArrowRight?1:0)-(keys.a||keys.ArrowLeft?1:0),dy=(keys.s||keys.ArrowDown?1:0)-(keys.w||keys.ArrowUp?1:0),d=Math.hypot(dx,dy)||1;
  let x=target.x+(target===p?dx/d*p.speed*lead:0),y=target.y+(target===p?dy/d*p.speed*lead:0),bias=adaptedDodgeBias();
  if(target===p&&b.phaseIndex>=3&&bias!=='none'){let n=24;if(bias==='left')x-=n;if(bias==='right')x+=n;if(bias==='up')y-=n;if(bias==='down')y+=n}
  // Aim uses HERO bounds. Using boss-body bounds here made beams miss a hero
  // standing near the top or bottom edge even when the warning aimed at them.
  const bounds=arenaWorldBounds();return{x:clamp(x,bounds.left,bounds.right),y:clamp(y,bounds.top,bounds.bottom)};
}
function enterAdaptedBoss(openingPreview=false){
  phase='boss';enemies=[];pickups=[];shots=[];particles=[];rings=[];camera=L-100;
  // Start in the arena, not outside it waiting for the player clamp to teleport.
  p.x=camera+300;p.y=390;
  let hp=Math.max(1,Math.ceil(BOSS_HP.adapted*difficultySettings().bossMultiplier));
  boss={x:camera+860,y:355,hp,max:hp,r:35,art:'adapted',name:'THE ADAPTED ONE — OBSERVE',mode:'intro',timer:ADAPTED_ONE_CONFIG.introTime,hit:0,level:1,phaseIndex:1,direction:'left',haloAngle:0,haloPulse:0,attackIndex:0,attackName:'',attackHits:{},adapt:adaptedNewMemory(),blast:null,stage:'idle',aimAngle:Math.PI,sequence:null,cooldowns:{},lastAttacks:[],hitReact:0,reactCooldown:0,heroGrace:0,moveSpeed:0,trail:[],openingPreview:!!openingPreview};
  checkpoint='adapted';setMusic('boss');say('THE ADAPTED ONE — OBSERVE. ADAPT. HUNT.',2.6);sfx('warn');
}
function adaptedTriggerPhase(b,next){b.phaseIndex=next;b.mode='adapt';b.stage='idle';b.timer=ADAPTED_ONE_CONFIG.adaptPause;b.sequence=null;b.blast=null;b.haloPulse=1;b.name='THE ADAPTED ONE — '+['','OBSERVE','ADAPT','HUNT','PERFECT ADAPTATION'][next];say(next===2?'IT LEARNS — CHANGE YOUR RHYTHM':next===3?'THE HUNT — WATCH THE DELAY':'PERFECT ADAPTATION — PUNISH THE RECOVERY',2);burst(b.x,b.y,'#ffe39a',22);sfx('roar')}
function adaptedChooseAttack(b){
  const target=adaptedTarget(b);let d=dist(b,target),m=b.adapt,choices=[];const add=(name,weight)=>{if(!(b.cooldowns[name]>0)&&(ADAPTED_ATTACK_POOL[b.phaseIndex].includes(name)||name==='guard'))choices.push([name,weight]);};
  // Range eligibility eliminates punches into empty space. No attack repeats
  // when alternatives are ready; heavy moves have cooldowns. Repeated tactics bias the choice.
  if(d>145){add('dashPunch',d>350?9:5);add('blast',3+(m.meters.automatic+m.meters.ballistic)/40);if(b.phaseIndex===4)add('judgment',2)}
  else{add('quickPunch',2);add('heavyPunch',2);add('combo',3);if(b.phaseIndex>=2)add('relentless',b.phaseIndex+3);add('backhand',m.meters.heavyMelee>40?7:2);add('grab',m.meters.heavyMelee>60?4:1);add('slam',2)}
  if(b.phaseIndex>=2&&d>100&&(m.meters.ballistic>40||m.meters.automatic>40||m.meters.shotgun>40))add('guard',6);
  choices=choices.filter(([name])=>name!==b.lastAttacks.at(-1));
  if(!choices.length)return d>145?'dashPunch':'quickPunch';
  let roll=Math.random()*choices.reduce((sum,q)=>sum+q[1],0);for(let [name,w]of choices){roll-=w;if(roll<=0)return name}return choices[0][0];
}
function adaptedStartAttack(b,name){
  if(!ADAPTED_MOVES[name])name='dashPunch';
  b.mode=name;b.attackName=name;b.attackHits={};b.blast=null;b.dashActive=false;b.moveSpeed=0;
  let move={...ADAPTED_MOVES[name]};if(name==='dashPunch')move.wind=ADAPTED_ONE_CONFIG.dashWindup;
  if(name==='blast')move.wind=ADAPTED_ONE_CONFIG.blastCharge;if(name==='judgment')move.wind=ADAPTED_ONE_CONFIG.judgmentCharge;
  b.sequence=[move];
  if(name==='combo'){b.sequence.push({...ADAPTED_MOVES.quickPunch,wind:.26,step:36,damage:12,recover:.22});if(b.phaseIndex>=3)b.sequence.push({...ADAPTED_MOVES.heavyPunch,wind:.64,damage:25,recover:1.05});else b.sequence[1].recover=.85;}
  // Sekiro-like sustained pressure: five quick, individually telegraphed
  // beats, a deliberate hesitation, then a heavy finisher. The tiny recovery
  // between beats is for evade/deflect timing; the final recovery is the
  // player's earned damage window.
  if(name==='relentless')b.sequence=[
    {...ADAPTED_MOVES.relentless,wind:.3,step:38,damage:10,recover:.12},
    {...ADAPTED_MOVES.quickPunch,wind:.18,step:42,damage:9,recover:.1},
    {...ADAPTED_MOVES.backhand,wind:.28,active:.13,step:28,damage:12,recover:.13,arc:1.15},
    {...ADAPTED_MOVES.quickPunch,wind:.16,step:44,damage:10,recover:.09},
    {...ADAPTED_MOVES.relentless,wind:.22,step:38,damage:12,recover:.16},
    {...ADAPTED_MOVES.heavyPunch,wind:.68,active:.18,step:58,damage:27,recover:1.15,label:'PERILOUS FINISHER — EVADE'}
  ];
  b.sequenceIndex=0;b.lastAttacks.push(name);if(b.lastAttacks.length>3)b.lastAttacks.shift();
  b.cooldowns[name]=name==='guard'?7:name==='blast'?4:name==='judgment'?8:name==='slam'?4.5:name==='relentless'?3.4:1.1;
  adaptedBeginBeat(b);say(move.label,Math.max(.8,move.wind));sfx('warn');
}
function adaptedBeginBeat(b){
  b.move=b.sequence[b.sequenceIndex];b.stage='windup';b.timer=b.move.wind;b.attackDuration=b.move.wind+b.move.active+b.move.recover;
  b.attackHits={};b.dashActive=false;b.target=adaptedPrediction(b,b.phaseIndex>=3?.12:0);b.dashTarget=b.target;
  b.aimAngle=Math.atan2(b.target.y-b.y,b.target.x-b.x);b.dashAngle=b.aimAngle;b.startX=b.x;b.startY=b.y;
  if(b.mode==='dashPunch'){
    let d=Math.min(650,dist(b,b.target)),dx=Math.cos(b.aimAngle),dy=Math.sin(b.aimAngle),travel=Math.max(0,d-32);
    // Stop at the arena edge along the SAME ray as the warning; independent
    // X/Y clamping would bend the actual dash away from its telegraphed lane.
    const bounds=adaptedBounds();
    if(Math.abs(dx)>.0001)travel=Math.min(travel,((dx>0?bounds.right:bounds.left)-b.x)/dx);
    if(Math.abs(dy)>.0001)travel=Math.min(travel,((dy>0?bounds.bottom:bounds.top)-b.y)/dy);
    travel=Math.max(0,travel);b.endX=b.x+dx*travel;b.endY=b.y+dy*travel;b.move={...b.move,active:clamp(travel/ADAPTED_ONE_CONFIG.dashSpeed,.14,.62)};
  }
  else{b.endX=b.x+Math.cos(b.aimAngle)*b.move.step;b.endY=b.y+Math.sin(b.aimAngle)*b.move.step;}
  const bounds=adaptedBounds();b.endX=clamp(b.endX,bounds.left,bounds.right);b.endY=clamp(b.endY,bounds.top,bounds.bottom);
}
function adaptedHitTarget(b,target,key,damage,angle,knock=22){
  if(b.attackHits[key])return;b.attackHits[key]=true;
  if(target!==p){
    if(!window.SHADOW_ROSTER?.isShadowTarget?.(target))return false;
    window.SHADOW_ROSTER.damageShadow(target,damage,b);target.impactPause=Math.max(target.impactPause||0,.18);
    const bounds=arenaWorldBounds();target.x=clamp(target.x+Math.cos(angle)*knock,bounds.left,bounds.right);target.y=clamp(target.y+Math.sin(angle)*knock,bounds.top,bounds.bottom);
    burst(target.x,target.y,'#ffe39a',7);shake=Math.max(shake,.12);return true;
  }
  // A successful evade must prevent damage AND knockback.
  if(p.dashTime>0||b.heroGrace>0)return false;
  hurt(damage);b.heroGrace=.28;
  const bounds=arenaWorldBounds();p.x=clamp(p.x+Math.cos(angle)*knock,bounds.left,bounds.right);p.y=clamp(p.y+Math.sin(angle)*knock,bounds.top,bounds.bottom);
  burst(p.x,p.y,'#ffe39a',7);shake=Math.max(shake,.16);return true;
}
function adaptedHitHero(b,key,damage,angle,knock=22){return adaptedHitTarget(b,p,key,damage,angle,knock)}
// The same brief grace window covers the halo volley too, preventing several
// overlapping pellets from applying a surprise one-frame burst of damage.
const hurtBeforeAdaptedGrace=hurt;
hurt=function(damage){
  let b=boss&&boss.art==='adapted'?boss:null;
  if(b&&(b.heroGrace>0||p.dashTime>0))return;
  hurtBeforeAdaptedGrace(damage);if(b)b.heroGrace=.28;
};
function adaptedMeleeHit(b){const target=adaptedTarget(b),d=dist(b,target);if(d>b.move.reach+20)return;let a=Math.atan2(target.y-b.y,target.x-b.x);if(Math.abs(swordAngleDelta(a,b.aimAngle))>b.move.arc)return;adaptedHitTarget(b,target,'strike',b.move.damage,b.aimAngle,b.mode==='heavyPunch'?45:18)}
function adaptedSegmentDistance(q,ax,ay,bx,by){let dx=bx-ax,dy=by-ay,t=clamp(((q.x-ax)*dx+(q.y-ay)*dy)/(dx*dx+dy*dy||1),0,1);return Math.hypot(q.x-ax-dx*t,q.y-ay-dy*t)}
function adaptedRelease(b){
  b.stage='active';b.timer=b.move.active;b.dashActive=b.mode==='dashPunch';b.strikeFlash=.12;
  if(b.mode==='slam'){
    const target=adaptedTarget(b);if(dist(b,target)<b.move.reach+20)adaptedHitTarget(b,target,'slam',b.move.damage,b.aimAngle,35);
    // Six slow, parryable projectiles leave wide escape lanes.
    const slamTarget=adaptedTarget(b);for(let i=0;i<6;i++){const shot=bullet(b.x,b.y,b.aimAngle+Math.PI/6+i*Math.PI/3,'enemy',8,255,7,'adapted-shockwave');if(shot&&slamTarget!==p)shot.shadowTarget=slamTarget;}
    b.shockwave=.42;shake=Math.max(shake,.22);sfx('roar');
  }
  if(b.mode==='blast'){const target=adaptedTarget(b);for(let i=-2;i<=2;i++){const shot=bullet(b.x+Math.cos(b.aimAngle)*36,b.y+Math.sin(b.aimAngle)*36,b.aimAngle+i*.24,'enemy',b.move.damage,420+b.phaseIndex*25,8,'adapted-blast');if(shot&&target!==p)shot.shadowTarget=target;}sfx('roar')}
  if(b.mode==='judgment'){sfx('roar');shake=Math.max(shake,.22)}
  if(!['guard','slam','blast','judgment'].includes(b.mode))sfx('sword1');
}
function adaptedRecover(b){b.mode='recover';b.stage='recover';b.timer=.22;b.sequence=null;b.dashActive=false;b.moveSpeed=0}
function adaptedAttackUpdate(b,dt){
  b.timer-=dt;
  if(b.stage==='windup'){if(b.timer<=0)adaptedRelease(b);return}
  if(b.stage==='active'){
    let t=clamp(1-b.timer/b.move.active,0,1),oldX=b.x,oldY=b.y;
    if(!['guard','slam','blast','judgment'].includes(b.mode)){
      let ease=1-Math.pow(1-t,2);b.x=b.startX+(b.endX-b.startX)*ease;b.y=b.startY+(b.endY-b.startY)*ease;
      // Resolve pond boundaries BEFORE testing the punch's swept damage path.
      window.PRINCESS_TERRAIN?.constrainActor(b,{x:oldX,y:oldY});
      if(b.mode==='dashPunch'){
        b.trail.push({x:oldX,y:oldY,direction:b.direction,life:.14});if(b.trail.length>7)b.trail.shift();
        let fx=Math.cos(b.aimAngle)*30,fy=Math.sin(b.aimAngle)*30;
        const target=adaptedTarget(b);if(adaptedSegmentDistance(target,oldX+fx,oldY+fy,b.x+fx,b.y+fy)<49)adaptedHitTarget(b,target,'strike',b.move.damage,b.aimAngle,38);
      }else adaptedMeleeHit(b);
    }
    if(b.mode==='judgment'){const target=adaptedTarget(b);if(adaptedSegmentDistance(target,b.x,b.y,b.x+Math.cos(b.aimAngle)*1200,b.y+Math.sin(b.aimAngle)*1200)<35)adaptedHitTarget(b,target,'beam',b.move.damage,b.aimAngle,30)}
    if(b.timer<=0){b.stage='recover';b.timer=b.move.recover;b.dashActive=false}return;
  }
  if(b.stage==='recover'&&b.timer<=0){b.sequenceIndex++;if(b.sequenceIndex<b.sequence.length){adaptedBeginBeat(b);sfx('warn')}else adaptedRecover(b)}
}
function adaptedNeutralMove(b,dt){
  const target=adaptedTarget(b);let a=Math.atan2(target.y-b.y,target.x-b.x),d=dist(b,target),speed=205+(b.phaseIndex-1)*25;
  if(d<110)a+=Math.PI;else if(d<200)a+=(b.attackIndex%2?1:-1)*Math.PI/2;
  b.moveSpeed=speed;b.x+=Math.cos(a)*speed*dt;b.y+=Math.sin(a)*speed*dt;adaptedArena(b);
}
function adaptedBossUpdate(dt){
  let b=boss;if(!b||b.art!=='adapted')return;
  b.hit=Math.max(0,(b.hit||0)-dt);b.hitReact=Math.max(0,b.hitReact-dt);b.reactCooldown=Math.max(0,b.reactCooldown-dt);b.heroGrace=Math.max(0,b.heroGrace-dt);b.strikeFlash=Math.max(0,(b.strikeFlash||0)-dt);b.shockwave=Math.max(0,(b.shockwave||0)-dt);b.haloPulse=Math.max(0,b.haloPulse-dt);b.haloAngle+=dt*(b.mode==='adapt'?4:1.2);b.trail=b.trail.filter(q=>(q.life-=dt)>0);
  if(b.mode==='death'){b.timer-=dt;if(b.timer<=0)adaptedFinishOpening();return}
  for(let k of Object.keys(b.cooldowns))b.cooldowns[k]=Math.max(0,b.cooldowns[k]-dt);
  let m=b.adapt;const target=adaptedTarget(b);m.distanceSamples++;m.averageRange+=(dist(b,target)-m.averageRange)*Math.min(1,dt*1.2);
  for(let k of ADAPTED_CATEGORIES)if(time-(m.lastGain[k]??-999)>1.2)m.meters[k]=Math.max(0,m.meters[k]-ADAPTED_ONE_CONFIG.adaptDecay*dt);
  let desired=b.hp<=b.max*.15?4:b.hp<=b.max*.4?3:b.hp<=b.max*.75?2:1;
  // Finish committed attacks before a phase transition; it cannot cancel the
  // player's earned punish window. Death always wins over phase changes.
  if(desired>b.phaseIndex&&!b.sequence&&b.mode!=='adapt')adaptedTriggerPhase(b,desired);
  // Boss-only poise: react visibly to hits without permanently freezing under
  // an automatic weapon. Finisher stagger is capped, followed by poise recovery.
  let stagger=b.swordStagger||0;b.swordStagger=0;
  if((stagger>0||b.hit>0)&&b.reactCooldown<=0&&b.mode!=='intro'&&b.mode!=='adapt'){
    b.hitReact=stagger>=.5?.24:.065;b.reactCooldown=stagger>=.5?1.25:.42;
    if(stagger>=.5){b.mode='recover';b.sequence=null;b.stage='recover';b.timer=.68;b.dashActive=false;}
  }
  b.dismantleSlow=Math.max(0,(b.dismantleSlow||0)-dt);b.electricStun=Math.max(0,(b.electricStun||0)-dt);
  let step=dt*(b.dismantleSlow>0?.7:1)*(b.electricStun>0?.06:1);if(b.hitReact>0)step*=.45;
  b.impactPause=Math.max(0,(b.impactPause||0)-dt);b.impactTime=Math.max(0,(b.impactTime||0)-dt);
  if(b.impactTime>0&&b.stage!=='active'){b.x+=(b.impactVX||0)*dt;b.y+=(b.impactVY||0)*dt;adaptedArena(b)}
  b.direction=adaptedDirectionFor(b);
  if(b.mode==='intro'||b.mode==='adapt'){b.timer-=step;if(b.timer<=0){b.mode='neutral';b.stage='idle';b.timer=.35}return}
  if(b.mode==='neutral'||b.mode==='hunt'){b.timer-=step;adaptedNeutralMove(b,step);if(b.timer<=0){b.attackIndex++;adaptedStartAttack(b,adaptedChooseAttack(b))}return}
  if(b.mode==='recover'){b.timer-=step;if(b.timer<=0){b.mode=b.phaseIndex>=3?'hunt':'neutral';b.timer=.3;b.stage='idle'}return}
  adaptedAttackUpdate(b,step);adaptedArena(b);
}

function adaptedCombatPose(b){
  if(b.previewRow!==undefined)return{row:b.previewRow};
  if(b.mode==='death')return{row:7};
  if(b.hitReact>0&&!b.dashActive)return{row:6};
  if(b.mode==='adapt'||b.mode==='guard')return{row:3};
  if(b.sequence){if(b.stage==='windup')return{row:3};if(b.stage==='recover')return{row:b.sequenceIndex<b.sequence.length-1?3:0};return{row:b.mode==='dashPunch'?5:b.mode==='slam'?6:4};}
  if((b.mode==='neutral'||b.mode==='hunt')&&b.moveSpeed>0)return{row:1+Math.floor(time*10)%2};
  return{row:0};
}
function paintAdaptedPose(b,x=b.x,y=b.y,alpha=1){
  let tex=art['adapted-one-combat'],row=adaptedCombatPose(b).row,dir=adaptedDirectionIndex(b.direction),r=adaptedFrameRect(row,dir),scale=ADAPTED_ONE_CONFIG.heroBodyHeight*ADAPTED_ONE_CONFIG.sizeRatio/ADAPTED_ATLAS.bodyPixels;
  // No cell-size stretching or source inset. A shared scale + foot pivot
  // prevents heads/feet disappearing and stops each pose changing body size.
  let bob=row===0?Math.sin(time*3)*.8:row===1||row===2?Math.sin(time*18):0;
  let lean=b.stage==='active'&&row===4?Math.cos(b.aimAngle)*.04:0;
  ctx.save();ctx.translate(x-camera,y+24+bob);ctx.rotate(lean);ctx.globalAlpha=alpha*(b.mode==='death'?Math.min(1,b.timer/.65):1);
  if(tex&&tex.complete&&tex.naturalWidth)drawAdaptedFrame(ctx,tex,row,dir,-r.w*scale/2,-r.feet*scale,scale);
  else{let fallback=art['adapted-one'];if(fallback&&fallback.complete&&fallback.naturalWidth){let sw=fallback.naturalWidth/4,sh=fallback.naturalHeight,h=115;ctx.drawImage(fallback,dir*sw,0,sw,sh,-h*sw/sh/2,-h,h*sw/sh,h)}}
  ctx.restore();
}
function drawAdaptedBoss(b){
  if(!b||b.art!=='adapted')return;
  ctx.save();ctx.translate(b.x-camera,b.y+25);ctx.fillStyle='#05050899';ctx.beginPath();ctx.ellipse(0,0,38,9,0,0,Math.PI*2);ctx.fill();ctx.restore();
  for(let q of b.trail||[])paintAdaptedPose({...b,previewRow:5,direction:q.direction},q.x,q.y,q.life/.14*.24);
  let move=b.move,warning=b.sequence&&b.stage==='windup';
  ctx.save();ctx.translate(b.x-camera,b.y);ctx.rotate(b.aimAngle||0);
  if(warning&&move){let progress=1-b.timer/move.wind;ctx.strokeStyle='#ffbb78';ctx.fillStyle='#ff92561c';ctx.lineWidth=2;ctx.globalAlpha=.4+progress*.55;
    if(b.mode==='dashPunch'||b.mode==='judgment'){
      let d=b.mode==='judgment'?1200:Math.hypot(b.endX-b.x,b.endY-b.y)+42,w=b.mode==='judgment'?35:49;ctx.fillRect(0,-w,d,w*2);ctx.setLineDash([9,8]);ctx.strokeRect(0,-w,d,w*2);ctx.setLineDash([]);ctx.beginPath();ctx.arc(d,0,10+progress*10,0,Math.PI*2);ctx.stroke();
    }else if(b.mode==='slam'){ctx.beginPath();ctx.arc(0,0,move.reach+20,0,Math.PI*2);ctx.fill();ctx.stroke()}
    else if(b.mode==='blast'){for(let i=-2;i<=2;i++){ctx.beginPath();ctx.moveTo(38,0);ctx.lineTo(Math.cos(i*.24)*270,Math.sin(i*.24)*270);ctx.stroke()}}
    else if(b.mode!=='guard'){ctx.beginPath();ctx.moveTo(0,0);ctx.arc(0,0,move.reach+20,-move.arc,move.arc);ctx.closePath();ctx.fill();ctx.stroke()}
  }
  if(b.mode==='judgment'&&b.stage==='active'){ctx.strokeStyle='#ffd571';ctx.shadowColor='#ffd571';ctx.shadowBlur=14;ctx.lineWidth=30;ctx.beginPath();ctx.moveTo(24,0);ctx.lineTo(1200,0);ctx.stroke();ctx.strokeStyle='#fff9d9';ctx.lineWidth=10;ctx.stroke()}
  if(b.strikeFlash>0&&b.stage==='active'&&!['blast','judgment','guard','slam'].includes(b.mode)){ctx.strokeStyle='#ffeab5';ctx.globalAlpha=b.strikeFlash/.12;ctx.lineWidth=4;ctx.beginPath();ctx.moveTo(25,0);ctx.lineTo(move.reach,0);ctx.stroke()}
  ctx.restore();
  paintAdaptedPose(b);
  ctx.save();ctx.translate(b.x-camera,b.y);
  if(b.electricStun>0){ctx.globalCompositeOperation='lighter';ctx.strokeStyle='#bdfcff';ctx.lineWidth=2;ctx.globalAlpha=.55+.35*Math.sin(time*35)**2;for(let i=0;i<5;i++){ctx.beginPath();ctx.moveTo(-24+i*11,-86);ctx.lineTo(-34+i*15,-48+Math.sin(time*42+i)*13);ctx.lineTo(-23+i*10,-10);ctx.stroke()}}
  if(b.mode==='guard'&&b.stage!=='recover'){ctx.rotate(b.aimAngle);ctx.strokeStyle='#8ef6ed';ctx.lineWidth=3;ctx.beginPath();ctx.arc(0,-6,52,-1.05,1.05);ctx.stroke();ctx.rotate(-b.aimAngle)}
  // Shockwave timers can briefly be longer than the visual ring duration. Clamp the
  // presentation value so canvas never receives a negative arc radius and blanks the arena.
  if(b.shockwave>0){let shockProgress=Math.max(0,Math.min(1,1-b.shockwave/.42));ctx.strokeStyle='#ffe0a0';ctx.globalAlpha=Math.max(0,Math.min(1,b.shockwave/.42));ctx.lineWidth=4;ctx.beginPath();ctx.arc(0,0,shockProgress*165,0,Math.PI*2);ctx.stroke()}
  if(b.mode==='adapt'){ctx.strokeStyle='#ffdf86';ctx.lineWidth=2;ctx.beginPath();ctx.arc(0,-78,28,0,time*8);ctx.stroke()}
  if((b.stage==='recover'||b.mode==='recover')&&b.mode!=='death'){let followup=b.sequence&&b.sequenceIndex<b.sequence.length-1;ctx.fillStyle=followup?'#ffbb78':'#91eade';ctx.font='9px monospace';ctx.textAlign='center';ctx.fillText(followup?'FOLLOW-UP':'OPEN',0,-101)}
  ctx.restore();
}
