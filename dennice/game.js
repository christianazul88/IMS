/* Princess Protocol: replaceable PNG art lives in assets/. */
const canvas=document.querySelector('#game'),ctx=canvas.getContext('2d'),W=1280,H=720,keys={},mouse={x:640,y:360,down:false,pressedAt:0};
// Adapted arena uses full-map world bounds; other fights retain screen bounds.
function arenaWorldBounds(){return window.PRINCESS_TERRAIN?.active()?window.PRINCESS_TERRAIN.bounds():{left:camera+46,right:camera+W-46,top:75,bottom:H-75};}
/* MAP THEMES — swap an entry to `map-ash.png`, `map-garden.png`, etc. when
 * you add those files. For now every act and The Adapted One use the shared
 * map.png asset. The renderer chooses the entry automatically. */
const MAP_THEMES={act1:'map',act2:'map',act3:'map',act4:'map',adapted:'map'};
// DIRECTIONAL HERO ART — these eight names map to the independent 2.5D
// poses. `up` is a real back-facing sprite, never a rotated front sprite.
const HERO_DIRECTIONS=['right','down-right','down','down-left','left','up-left','up','up-right'];
// Matches the five sword PNG poses in make-animated-sprites.js. It is also
// used by the loading fallback, so the weapon never blanks between frames.
const SWORD_FRAME_PATHS=[[-2.62,-2.05,-1.42,-.78,-.16],[2.62,2.05,1.42,.78,.16],[Math.PI,2.65,1.48,.26,.02]];
const directionalHeroFiles=[...HERO_DIRECTIONS.flatMap(dir=>['gun','sword'].flatMap(weapon=>[`hero-${weapon}-${dir}-idle-1`,...Array.from({length:4},(_,i)=>`hero-${weapon}-${dir}-run-${i+1}`)])),...HERO_DIRECTIONS.flatMap(dir=>Array.from({length:3},(_,combo)=>Array.from({length:5},(_,frame)=>`hero-sword-${dir}-combo-${combo+1}-${frame+1}`)).flat())];
// SEPARATE HEAVY-SWORD ART — replace only these PNGs when you draw your own
// giant weapon. The body animation and sword animation will stay aligned.
const swordSpriteFiles=[...HERO_DIRECTIONS.map(dir=>`sword-rest-${dir}`),...HERO_DIRECTIONS.flatMap(dir=>Array.from({length:3},(_,combo)=>Array.from({length:5},(_,frame)=>`sword-swing-${dir}-combo-${combo+1}-${frame+1}`)).flat())];
// Only The Adapted One is a playable boss now. Its projectile art is named
// explicitly; no generic boss/enemy sprite is loaded by the campaign.
// The new hero uses one atlas in duel-rework.js. Avoid fetching hundreds of
// retired hero/sword PNGs and the replaced shockwave sheet at every startup.
const files=['hero','princess','ally','shotgun','machinegun','rocket-launcher','rocket','buff','lifesteal','bullet','adapted-bullet','shield','rocket-buff','adapted-one','adapted-one-combat',...new Set(Object.values(MAP_THEMES)),'sword','sword-dismantle','hero-idle-1','princess-idle-1','princess-idle-2','princess-action-1','princess-action-2'];
const art=Object.fromEntries(files.map(n=>{let i=new Image();i.src=`assets/${n}.png`;return[n,i]}));
// Legacy renderer branches still ask for `boss-bullet`; alias that key to the
// renamed Adapted projectile without restoring any removed boss asset.
art['boss-bullet']=art['adapted-bullet'];
// CUSTOM MP3 FILES: put music in assets/, then set its filename below. Blank = built-in music.
const MUSIC_TRACKS={journey:'',boss:'',rescued:''},MUSIC_VOLUME=.42;
// EDITABLE END CREDITS — change, remove, or add lines here. Empty strings add breathing room.
const END_CREDITS=[
 'PRINCESS PROTOCOL','',
 'A COLORLESS VOW','',
 'HERO & PRINCESS','Your names here','',
 'GAME DESIGN','Your name here','',
 'ART & ANIMATION','Your name here','',
 'SOUND & MUSIC','Your name here','',
 'THANK YOU FOR BRINGING THE COLOR BACK',''
];
// ============================================================
// BOSS HP — edit these numbers to make the surviving Adapted One fight
// longer/shorter or easier/harder. The other values only support the
// developer lab's legacy diagnostics and are never reached by campaign play.
// ============================================================
const BOSS_HP={
 act1:230,act2:300,act3:380,act4:480,demon:700,
 adapted:420 // The only live campaign boss in the normal route.
};
/* DIFFICULTY — tune these values instead of editing every enemy or boss.
 * Difficulty remains selectable for the Adapted One and for developer-mode
 * combat sandboxes. The normal campaign has no enemy waves or interim bosses.
 */
const DIFFICULTIES={
 superEasy:{label:'SUPER EASY',enemyMultiplier:1,bossMultiplier:1,demonMultiplier:1,oneHitEnemies:true},
 easy:{label:'EASY',enemyMultiplier:1,bossMultiplier:1,demonMultiplier:1,oneHitEnemies:false},
 hard:{label:'HARD',enemyMultiplier:3,bossMultiplier:3,demonMultiplier:3,oneHitEnemies:false},
 noMercy:{label:'NO MERCY',enemyMultiplier:100,bossMultiplier:100,demonMultiplier:100,oneHitEnemies:false}
};
let difficultyKey='easy';
const difficultySettings=()=>DIFFICULTIES[difficultyKey]||DIFFICULTIES.easy;
const mapAssetName=()=>boss&&boss.art==='demon'?MAP_THEMES.demon:boss&&boss.art==='adapted'?MAP_THEMES.adapted:MAP_THEMES[`act${act+1}`]||'map';
// Kept as data for the developer lab's legacy diagnostics; the normal route
// never calls these entries because the campaign wrapper below bypasses them.
const LEVELS=[
 {name:'ACT I — THE ASHEN MARCH',boss:'THE GATE KNIGHT',art:'boss-knight',hp:BOSS_HP.act1,text:'Her absence drained every color from your days. The first road waits in the ash.'},
 {name:'ACT II — THE HOLLOW GARDEN',boss:'THE THORN BEAST',art:'boss-beast',hp:BOSS_HP.act2,text:'A garden that once carried her laughter opens a safer path through the gray.'},
 {name:'ACT III — THE MIRROR CAUSEWAY',boss:'THE GLASS ORACLE',art:'boss-oracle',hp:BOSS_HP.act3,text:'The mirrors lose their teeth. Your footsteps carry on.'},
 {name:'ACT IV — THE ECLIPSE CITADEL',boss:'THE WITCH OF ECLIPSE',art:'boss',hp:BOSS_HP.act4,text:'One final road remains before The Adapted One.'}];
// Legacy developer-lab tuning: the normal campaign disables this entire
// horde path, but the constants remain for isolated regression diagnostics.
const ACT1_MINION_TOTAL=300,ACT1_ACTIVE_CAP=36;
let L=1700;const roadLength=a=>1700+a*600,clamp=(v,a,b)=>Math.max(a,Math.min(b,v)),dist=(a,b)=>Math.hypot(a.x-b.x,a.y-b.y),hit=(a,b,r)=>{let x=a.x-b.x,y=a.y-b.y;return x*x+y*y<r*r};
let running=false,paused=false,last=0,time=0,camera=0,act=0,phase='travel',nextSpawn=450,act1Spawned=0,act1HordeNextSpawn=450,messageTimer=0,flash=0,shake=0,combo=0,comboTimer=0,muted=false,actClear=null,checkpoint=0,retrying=false,demonIntro=null;
let enemies=[],shots=[],pickups=[],particles=[],rings=[],boss=null,ally=null,rescue=null,track=null,trackName='',ultimate=null;
const LIFESTEAL_PCT=.18; // % of your dealt damage returned as HP once the lifesteal pickup (Act II) is collected
const p={x:150,y:360,hp:100,maxHp:100,speed:270,angle:0,fire:0,weapon:'pistol',damage:1,rapid:0,kills:0,dash:2,recharge:0,dashTime:0,dx:1,dy:0,lx:1,ly:0,hasShotgun:false,hasMachineGun:false,hasLifesteal:false,hasRocketPack:false,rocketCd:0,rocketRapid:0,ultimateCooldown:0};
// THE ADAPTED ONE — all boss memory is per-attempt and is reset with the
// opening fight. These categories are deliberately broader than weapons so
// the player can rotate tactics instead of being hard-countered forever.
const ADAPTED_CATEGORIES=['heavyMelee','ballistic','shotgun','automatic','explosive','projectile','healing'];
// Sprite size is measured from the hero's visible 47px body (not its padded PNG).
// All attack timing, frame rectangles and boss-only tuning live in adapted-one.js.
const ADAPTED_ONE_CONFIG={heroBodyHeight:47,sizeRatio:2,introTime:2.2,adaptPause:1.15,adaptDecay:5,dashWindup:.62,dashSpeed:1050,blastCharge:1.05,judgmentCharge:1.5};
function adaptedEnsureMemory(){if(boss&&boss.art==='adapted'&&!boss.adapt)boss.adapt=adaptedNewMemory();return boss&&boss.adapt}
function adaptedCategoryForWeapon(weapon=p.weapon,kind='bullet'){if(kind==='rocket'||weapon==='rocket')return'explosive';if(weapon==='sword')return'heavyMelee';if(weapon==='shotgun')return'shotgun';if(weapon==='machinegun')return'automatic';return'ballistic'}
// DEVELOPER MODE — append `?dev=1` to the game URL, or press F9 in a running
// game. E uses the current weapon's ultimate; F6/F7/F8 force sword/shotgun/
// machine-gun ultimates for quick visual checks without changing progression.
let developerMode=false;try{developerMode=new URLSearchParams(location.search).get('dev')==='1'}catch(e){}
/* HERO 2.5D TUNING — animation is driven by these timers, while the PNG
 * sheets stay replaceable. Change lift/scale below to exaggerate the depth. */
const HERO_ANIM={state:'idle',t:0,moving:false,fire:0,hurt:0,direction:'right',faceAngle:0};
const HERO_FRAME_RATE={idle:7,run:12,fire:16,dash:18,hurt:9,sword:12};
function resetHeroAnimation(){HERO_ANIM.state='idle';HERO_ANIM.t=0;HERO_ANIM.moving=false;HERO_ANIM.fire=0;HERO_ANIM.hurt=0;HERO_ANIM.direction='right';HERO_ANIM.faceAngle=0}
function reset(openingBoss=false){Object.assign(p,{x:150,y:360,hp:100,maxHp:100,fire:0,weapon:'pistol',damage:1,rapid:0,kills:0,dash:2,recharge:0,dashTime:0,dx:1,dy:0,lx:1,ly:0,hasShotgun:false,hasMachineGun:false,hasLifesteal:false,hasRocketPack:false,rocketCd:0,rocketRapid:0,ultimateCooldown:0});act=0;phase='travel';nextSpawn=450;act1Spawned=0;act1HordeNextSpawn=450;enemies=[];shots=[];pickups=[];particles=[];rings=[];boss=ally=rescue=null;actClear=null;demonIntro=null;ultimate=null;time=0;L=roadLength(0);checkpoint=openingBoss?'adapted':0;shieldReset();if(openingBoss)enterAdaptedBoss(true)}
function respawnAtCheckpoint(){enemies=[];shots=[];pickups=[];particles=[];rings=[];boss=ally=rescue=null;actClear=null;demonIntro=null;ultimate=null;time=0;Object.assign(p,{x:130,y:360,hp:p.maxHp,fire:0,rapid:0,dash:2,recharge:0,dashTime:0,dx:1,dy:0,lx:1,ly:0,rocketCd:0,rocketRapid:0,ultimateCooldown:0});shieldReset();if(checkpoint==='adapted'){act=3;L=roadLength(3);enterAdaptedBoss()}else if(checkpoint==='demon'){act=3;L=roadLength(3);enterDemonBoss()}else{act=checkpoint;L=roadLength(act);phase='travel';nextSpawn=420;act1Spawned=act===0?0:ACT1_MINION_TOTAL;act1HordeNextSpawn=act===0?420:L}}
function img(n,x,y,s,a=0,alpha=1){let frame=Math.floor(time*(n==='hero'||n==='boss'?9:5))%2+1,active=n==='hero'&&(mouse.down||p.dashTime>0||keys.w||keys.a||keys.s||keys.d),sprite=n==='hero'?`hero-${active?'action':'idle'}-${frame}`:n==='boss'?`boss-${boss&&boss.mode!=='pick'?'action':'idle'}-${frame}`:n==='princess'&&phase==='rescue'?`princess-action-${frame}`:n,i=art[sprite]||art[n]||null;ctx.save();ctx.translate(x-camera,y+Math.sin(time*5+x*.01)*(n==='hero'||n==='princess'?2:1));ctx.rotate(a);ctx.globalAlpha=alpha;if(i?.complete&&i.naturalWidth)ctx.drawImage(i,-s/2,-s/2,s,s);else{ctx.fillStyle='#ffe17a';ctx.beginPath();ctx.arc(0,0,s*.35,0,7);ctx.fill()}ctx.restore()}
function say(t,d=2){let e=document.querySelector('#message');e.textContent=t;e.style.opacity=1;messageTimer=d}
let ac,master,music,nextNote=0,step=0;function audio(){if(!ac){ac=new(window.AudioContext||window.webkitAudioContext)();master=ac.createGain();music=ac.createGain();master.gain.value=muted?0:1;music.gain.value=.34;music.connect(master);master.connect(ac.destination)}return ac}function tone(a,b,d,v,type='sine',delay=0,out=null){let c=audio();if(muted)return;let o=c.createOscillator(),g=c.createGain(),t=c.currentTime+delay;o.type=type;o.frequency.setValueAtTime(a,t);o.frequency.exponentialRampToValueAtTime(Math.max(1,b),t+d);g.gain.setValueAtTime(v,t);g.gain.exponentialRampToValueAtTime(.001,t+d);o.connect(g);g.connect(out||master);o.start(t);o.stop(t+d+.03)}function noise(d=.06,v=.06){let c=audio();if(muted)return;let b=c.createBuffer(1,c.sampleRate*d,c.sampleRate),x=b.getChannelData(0);for(let i=0;i<x.length;i++)x[i]=(Math.random()*2-1)*(1-i/x.length);let n=c.createBufferSource(),g=c.createGain();n.buffer=b;g.gain.value=v;n.connect(g);g.connect(master);n.start()}function sfx(x){if(x==='fire'){tone(720,140,.09,.11,'square');noise()}if(x==='machine'){tone(250,70,.05,.08,'sawtooth');noise(.03,.05)}if(x==='hit')tone(170,45,.13,.16,'sawtooth');if(x==='hurt'){tone(190,40,.28,.2,'sawtooth');noise(.15,.1)}if(x==='dash')tone(270,1500,.16,.16);if(x==='sword1'){tone(360,95,.13,.16,'sawtooth');noise(.045,.045)}if(x==='sword2'){tone(470,120,.15,.18,'square');tone(780,240,.09,.09,'triangle',.035);noise(.05,.05)}if(x==='sword3'){tone(180,52,.25,.24,'sawtooth');tone(620,180,.18,.14,'square',.035);noise(.12,.12)}if(x==='parry'){tone(980,380,.18,.2,'triangle');tone(1450,620,.1,.12,'square',.018);noise(.035,.045)}if(x==='ultimate'){tone(70,30,.48,.26,'sawtooth');tone(520,80,.24,.16,'square',.07);noise(.2,.11)}if(x==='dismantle'){tone(1800,420,.11,.09,'sawtooth');tone(900,160,.18,.08,'triangle',.04);noise(.035,.05)}if(x==='gatling'){tone(95,35,.4,.18,'sawtooth');tone(260,70,.25,.1,'square');noise(.13,.1)}if(x==='pickup')tone(470,1080,.22,.15,'triangle');if(x==='warn')tone(160,160,.35,.15,'square');if(x==='win')[440,554,659,880].forEach((n,i)=>tone(n,n,.25,.14,'triangle',i*.13));if(x==='roar'){tone(85,50,.55,.24,'sawtooth');tone(150,70,.4,.16,'square',.05)}}
// ATOMIC WAVE SFX — the supplied MP3 is triggered only on release, after a
// player gesture has created the AudioContext. Edit volume here if needed.
const adaptedAtomicWaveSfx=new Audio('assets/adapted-atomic-wave.mp3');
adaptedAtomicWaveSfx.preload='auto';adaptedAtomicWaveSfx.volume=.56;
const baseSfx=sfx;sfx=function(name){
  if(name==='adaptedAtomicWave'){
    if(!muted){const cue=typeof adaptedAtomicWaveSfx.cloneNode==='function'?adaptedAtomicWaveSfx.cloneNode():adaptedAtomicWaveSfx;cue.volume=adaptedAtomicWaveSfx.volume;try{cue.currentTime=0;const play=cue.play?.();if(play?.catch)play.catch(()=>{});}catch(e){}}
    return;
  }
  baseSfx(name);
};
function setMusic(n){if(trackName===n)return;trackName=n;if(track){track.pause();track=null}if(MUSIC_TRACKS[n]){track=new Audio(MUSIC_TRACKS[n]);track.loop=true;track.volume=MUSIC_VOLUME;track.muted=muted;track.play().catch(()=>{})}nextNote=0;step=0}function musicUpdate(){if(MUSIC_TRACKS[trackName]||!running||paused||muted)return;let c=audio();if(nextNote>c.currentTime+.08)return;nextNote=Math.max(nextNote,c.currentTime+.03);let set={journey:[147,165,196,220,196,165,147,123],boss:[110,131,147,123,110,147,165,123],rescued:[262,330,392,523,440,392,330,294]}[trackName];if(!set)return;let n=set[step++%8];tone(n,n,.28,.04,'triangle',nextNote-c.currentTime,music);if(step%2===0)tone(trackName==='boss'?55:73,73,.42,.06,'sine',nextNote-c.currentTime,music);nextNote+=trackName==='boss'?.34:.46}
function burst(x,y,c,count=8){for(let i=0;i<count&&particles.length<340;i++){let a=Math.random()*6.28,s=50+Math.random()*180;particles.push({x,y,vx:Math.cos(a)*s,vy:Math.sin(a)*s,life:.25+Math.random()*.45,c,size:2+Math.random()*4})}}function bullet(x,y,a,team,d=1,s=760,r=6,kind='bullet'){if(shots.length>=220)return null;let shot={x,y,vx:Math.cos(a)*s,vy:Math.sin(a)*s,team,d,s:r,life:2.2,a,kind,adaptCategory:team==='player'?adaptedCategoryForWeapon(p.weapon,kind):null};shots.push(shot);return shot}
function enemy(x,y,kind){let q={minion:[5,23,135],crawler:[7,20,210],wisp:[4,16,110],brute:[18,38,76],imp:[9,17,255]}[kind],d=difficultySettings(),hp=d.oneHitEnemies?1:Math.max(1,Math.ceil(q[0]*d.enemyMultiplier));enemies.push({x,y,kind,hp,max:hp,r:q[1],speed:q[2],attack:0,shot:Math.random(),hit:0,wave:Math.random()*7})}
function fire(){if(p.fire>0)return;adaptedRecord(adaptedCategoryForWeapon(p.weapon));HERO_ANIM.fire=.2;let a=p.angle,rate=p.rapid>0?.66:1;if(p.weapon==='shotgun'){for(let i=-3;i<=3;i++)bullet(p.x+Math.cos(a)*25,p.y+Math.sin(a)*25,a+i*.1,'player',p.damage,650,5);p.fire=.42*rate;sfx('fire')}else if(p.weapon==='machinegun'){bullet(p.x+Math.cos(a)*25,p.y+Math.sin(a)*25,a+(Math.random()-.5)*.08,'player',p.damage,920,4);p.fire=.07*rate;sfx('machine')}else if(p.weapon==='rocket'){p.weapon='pistol';return}else{bullet(p.x+Math.cos(a)*25,p.y+Math.sin(a)*25,a,'player',p.damage,820,5);p.fire=(p.rapid>0?.1:.17)*rate;sfx('fire')}burst(p.x+Math.cos(a)*28,p.y+Math.sin(a)*28,'#ffe68b',3)}
function enterBoss(){phase='boss';enemies=[];pickups=[];camera=L-100;let l=LEVELS[act],d=difficultySettings(),bossHp=Math.max(1,Math.ceil(l.hp*d.bossMultiplier));boss={x:L+760,y:360,hp:bossHp,max:bossHp,r:act===1?74:64,art:l.art,name:l.boss,mode:'intro',timer:2,attack:0,hit:0,level:1,swords:l.art==='boss-oracle'?[{ang:0,state:'orbit',x:0,y:0,vx:0,vy:0,bounces:0,life:0,hitCd:0},{ang:Math.PI,state:'orbit',x:0,y:0,vx:0,vy:0,bounces:0,life:0,hitCd:0}]:null};setMusic('boss');say(l.boss,2.4);sfx('warn')}
function bossDown(){if(!boss)return;sfx('win');if(boss.art==='adapted'){if(boss.mode==='death')return;boss.hp=0;boss.mode='death';boss.timer=2.6;boss.attackName='death';boss.blast=null;shots=[];enemies=[];pickups=[];say('THE ADAPTED ONE — ITS HALO BREAKS',2.6);shake=Math.max(shake,.7);return}if(boss.art==='demon'){boss=null;phase='rescue';rescue={timer:0,x:L+900,y:360};shots=[];enemies=[];pickups=[];setMusic('rescued');say('THE LAST CHAIN BREAKS',2.5);return}if(act===3){boss=null;enemies=[];pickups=[];shots=[];particles=[];enterAdaptedBoss();return}boss=null;enemies=[];pickups=[];shots=[];particles=[];phase='actClear';actClear={timer:2.8,total:2.8,cleared:act,next:act+1};setMusic('journey')}
function actClearUpdate(dt){actClear.timer-=dt;if(actClear.timer<=0){act++;checkpoint=act;L=roadLength(act);phase='travel';boss=null;enemies=[];pickups=[];shots=[];p.x=130;p.y=360;p.hp=Math.min(100,p.hp+35);nextSpawn=420;act1Spawned=act===0?0:ACT1_MINION_TOTAL;act1HordeNextSpawn=act===0?420:L;actClear=null;say(`${LEVELS[act].name} — ${LEVELS[act].text}`,4)}}
function demonIntroUpdate(dt){demonIntro.timer-=dt;if(demonIntro.timer<=0){demonIntro=null;enterDemonBoss()}}
function drawDemonIntro(){let t=demonIntro.timer,total=demonIntro.total,g=ctx.createLinearGradient(0,0,0,H);g.addColorStop(0,'#0a0508');g.addColorStop(1,'#210409');ctx.fillStyle=g;ctx.fillRect(0,0,W,H);let fade=Math.min(1,(total-t)*2);ctx.globalAlpha=fade;ctx.textAlign='center';ctx.fillStyle='#ff5a5a';ctx.font='46px Anton, sans-serif';ctx.fillText('THE WITCH OF ECLIPSE FALLS',W/2,H/2-52);ctx.fillStyle='#ffcac0';ctx.font='16px DM Mono, monospace';ctx.fillText('HER CROWN WAS ONLY A LOCK. THE PRINCESS HEARD YOU FIGHTING.',W/2,H/2-8);ctx.fillText('NOW THE THING BENEATH THE CITADEL HAS HEARD YOU TOO.',W/2,H/2+20);if(t<total-1.4){ctx.fillStyle='#ff8c8c';ctx.font='22px Anton, sans-serif';ctx.fillText('THE DEMON LORD RISES — BREAK THE FINAL CHAIN',W/2,H/2+68)}ctx.globalAlpha=1}
function enterDemonBoss(){phase='boss';enemies=[];pickups=[];shots=[];camera=L-100;let cx=L+540,d=difficultySettings(),demonHp=Math.max(1,Math.ceil(BOSS_HP.demon*d.demonMultiplier)),flankHp=Math.max(1,Math.ceil(190*d.bossMultiplier));boss={x:cx,y:190,fixedX:cx,fixedY:190,hp:demonHp,max:demonHp,r:118,art:'demon',name:'THE DEMON LORD — CORE SEALED',mode:'intro',timer:2.8,attack:0,hit:0,level:1,emergencyHealUsed:false,swords:null,coreLocked:true,parts:[{side:'left',socketX:cx-DEMON_NECK_TUNING.socketOffsetX,socketY:DEMON_NECK_TUNING.socketY,homeX:cx-DEMON_NECK_TUNING.homeOffsetX,homeY:DEMON_NECK_TUNING.homeY,x:cx-DEMON_NECK_TUNING.homeOffsetX,y:DEMON_NECK_TUNING.homeY,hp:flankHp,max:flankHp,r:62,alive:true,mode:'idle',timer:1.3,attack:0,hit:0,next:1.2,phase:0,hitCd:0},{side:'right',socketX:cx+DEMON_NECK_TUNING.socketOffsetX,socketY:DEMON_NECK_TUNING.socketY,homeX:cx+DEMON_NECK_TUNING.homeOffsetX,homeY:DEMON_NECK_TUNING.homeY,x:cx+DEMON_NECK_TUNING.homeOffsetX,y:DEMON_NECK_TUNING.homeY,hp:flankHp,max:flankHp,r:62,alive:true,mode:'idle',timer:2.1,attack:1,hit:0,next:1.8,phase:Math.PI,hitCd:0}]};if(ally){ally.hasRocketLauncher=true;ally.rocketFire=1.05;say('MOON KNIGHT LOADS THE SIEGEBREAKER',2.4)}setMusic('boss');say('THE THREEFOLD DEMON — BREAK THE OUTER HEADS',3);sfx('warn')}
function allyUpdate(dt){if(!ally)return;ally.fire-=dt;ally.rocketFire=Math.max(0,(ally.rocketFire||0)-dt);ally.move-=dt;let left=arenaWorldBounds().left+35,right=arenaWorldBounds().right-35;if(ally.move<=0){ally.tx=clamp((boss?boss.x:p.x+230)+(Math.random()-.5)*350,left,right);ally.ty=clamp(p.y+(Math.random()-.5)*320,arenaWorldBounds().top+25,arenaWorldBounds().bottom-25);ally.move=1.2+Math.random()*2}let a=Math.atan2(ally.ty-ally.y,ally.tx-ally.x),d=Math.hypot(ally.tx-ally.x,ally.ty-ally.y);ally.x+=Math.cos(a)*Math.min(d,ally.speed*dt);ally.y+=Math.sin(a)*Math.min(d,ally.speed*dt);ally.x=clamp(ally.x,left,right);ally.y=clamp(ally.y,arenaWorldBounds().top+15,arenaWorldBounds().bottom-15);let t=boss||enemies.reduce((z,e)=>!z||dist(e,ally)<dist(z,ally)?e:z,null);if(t&&ally.fire<=0){a=Math.atan2(t.y-ally.y,t.x-ally.x);ally.a=a;if(ally.hasRocketLauncher&&ally.rocketFire<=0){bullet(ally.x+Math.cos(a)*18,ally.y+Math.sin(a)*18,a,'ally',9,520,13,'rocket');ally.rocketFire=2.2;ally.fire=.42;sfx('roar');burst(ally.x,ally.y,'#ff9b5c',7)}else{bullet(ally.x,ally.y,a,'ally',1.4,720,4);ally.fire=.3;burst(ally.x,ally.y,'#8cffe8',2)}}}
// ============================================================
// THE ADAPTED ONE — late-game Soulslike hunter gate before the Demon Lord
// ============================================================
const ADAPTED_ATTACK_POOL={1:['quickPunch','heavyPunch','combo','dashPunch','blast'],2:['dashPunch','quickPunch','combo','relentless','slam','grab','backhand','blast'],3:['combo','relentless','slam','dashPunch','backhand','grab','blast'],4:['relentless','combo','slam','dashPunch','backhand','blast','judgment']};
function adaptedFinishOpening(){
  const preview=!!boss?.openingPreview;boss=null;enemies=[];shots=[];pickups=[];particles=[];ultimate=null;
  if(preview){phase='travel';act=0;L=roadLength(0);p.x=130;p.y=360;nextSpawn=420;act1Spawned=0;act1HordeNextSpawn=420;checkpoint=0;setMusic('journey');say('THE ADAPTED ONE FALLS — THE ASHEN MARCH BEGINS',4);return;}
  // The Adapted One is now the sole boss. There is no Demon Lord follow-up;
  // finishing this encounter goes straight to the existing rescue ending.
  phase='rescue';checkpoint='adapted';rescue={timer:0,x:L+900,y:360};setMusic('rescued');say('THE ADAPTED ONE FALLS — THE LAST HUNTER BREAKS',4);
}
const BOSS_PATTERNS={'boss-knight':['charge','sweep','guard'],'boss-beast':['thorns','pounce','roots'],'boss-oracle':['blink','spiral','gaze','blades'],'boss':['spray','charge','nova'],'demon':['armBite','omni','laserchase','rocketSalvo','cleave','summon','stomp','meteor']};
function demonPick(b){b.level=b.hp<b.max*.25?3:b.hp<b.max*.6?2:1;if(b.hp<b.max*.35&&!b.emergencyHealUsed){b.emergencyHealUsed=true;b.mode='regen';b.timer=2.6;b.healAmt=b.max*.18;b.healApplied=false;say('THE DEMON LORD DEVOURS THE DARK TO MEND');sfx('warn');return}let seq=BOSS_PATTERNS.demon;b.attack=(b.attack+1)%seq.length;let atk=seq[b.attack];b.mode=atk;if(atk==='armBite'){b.timer=2.25;b.biteNext=.25;b.biteCount=0;b.biteSide=-1;b.biteY=clamp(p.y,125,H-125);say('THE JAWS TRACK YOUR STEP');sfx('warn')}else if(atk==='omni'){b.timer=3-b.level*.15;say('DEMONIC BULLET BARRAGE');sfx('warn')}else if(atk==='laserchase'){b.timer=3.3+b.level*.2;b.beamPhase='aim';b.beamAimT=.85;b.beamAngle=Math.atan2(p.y-b.y,p.x-b.x);b.beamOn=false;b.beamSweep=(Math.random()<.5?-1:1)*(.7+b.level*.18);say('THE MAW TAKES AIM');sfx('warn')}else if(atk==='rocketSalvo'){b.timer=2.7;b.rocketNext=.72;b.rockets=0;say('INFERNAL ROCKET SALVO — KEEP MOVING');sfx('warn')}else if(atk==='cleave'){b.timer=1.5;b.cleaveDone=false;say('BLADE RUSH — DASH THROUGH');sfx('warn')}else if(atk==='summon'){b.timer=1.8;b.summoned=false;b.healAmt=b.max*.05;say('RISE, MY CHILDREN');sfx('warn')}else if(atk==='stomp'){b.timer=2.6;b.stompPhase='wind';b.stompT=.5;b.vx=0;b.vy=0;say('THE GROUND TREMBLES');sfx('warn')}else if(atk==='meteor'){b.timer=2.6;b.meteorPts=null;say('HELLFIRE FALLS');sfx('warn')}}
function swordsUpdate(b,dt){if(!b.swords)return;let left=camera+42,right=camera+W-42,top=92,bot=H-92;for(let s of b.swords){s.hitCd=Math.max(0,s.hitCd-dt);if(s.state==='orbit'){s.ang+=dt*2.4;s.x=b.x+Math.cos(s.ang)*72;s.y=b.y+Math.sin(s.ang)*72}else if(s.state==='thrown'){s.x+=s.vx*dt;s.y+=s.vy*dt;s.life-=dt;if(s.x<left){s.x=left;s.vx=Math.abs(s.vx);s.bounces++}if(s.x>right){s.x=right;s.vx=-Math.abs(s.vx);s.bounces++}if(s.y<top){s.y=top;s.vy=Math.abs(s.vy);s.bounces++}if(s.y>bot){s.y=bot;s.vy=-Math.abs(s.vy);s.bounces++}if(dist(s,p)<34&&s.hitCd<=0){hurt(14);s.hitCd=.5}if(s.bounces>=5||s.life<=0)s.state='return'}else if(s.state==='return'){let a=Math.atan2(b.y-s.y,b.x-s.x),d=Math.hypot(b.x-s.x,b.y-s.y),sp=Math.min(d,640*dt);s.x+=Math.cos(a)*sp;s.y+=Math.sin(a)*sp;if(dist(s,p)<34&&s.hitCd<=0){hurt(10);s.hitCd=.5}if(d<24)s.state='orbit'}}}
function bossUpdate(dt){let b=boss;b.hit=Math.max(0,b.hit-dt);if(!b.healGates)b.healGates=[.7,.4,.15].map(t=>({t,used:false}));for(let g of b.healGates)if(!g.used&&b.hp<=b.max*g.t){g.used=true;pickups.push({x:clamp(p.x+(Math.random()<.5?-150:150),camera+70,camera+W-70),y:clamp(p.y+(Math.random()<.5?-150:150),110,H-110),type:'heal'});say('A FRAGMENT OF VITALITY FALLS',2.2)}b.timer-=dt;if(b.mode==='intro'){if(b.timer<=0){b.mode='pick';b.timer=.5}return}if(b.mode==='pick'&&b.timer<=0){if(b.art==='demon'){demonPick(b)}else{let seq=BOSS_PATTERNS[b.art]||BOSS_PATTERNS['boss'];b.attack=(b.attack+1)%seq.length;b.level=b.hp<b.max*.48?2:1;let atk=seq[b.attack];b.mode=atk;if(atk==='spray'){b.timer=3.3;say('ECLIPSE BARRAGE');sfx('warn')}else if(atk==='charge'){b.timer=1.15;b.lane=clamp(p.y,110,H-110);b.vx=0;sfx('warn')}else if(atk==='pounce'){b.timer=1.15;b.lane=clamp(p.y,110,H-110);b.vx=0;say('BEAST POUNCE');sfx('warn')}else if(atk==='nova'){b.timer=1.15;say('NOVA — DOUBLE DASH OUT!');sfx('warn')}else if(atk==='sweep'){b.timer=1.4;b.sweepDone=false;say('BLADE SWEEP');sfx('warn')}else if(atk==='guard'){b.timer=1.6;b.guardShots=0;b.guardNext=.3;say('GUARDED LANCE');sfx('warn')}else if(atk==='thorns'){b.timer=1.1;say('THORN BURST');sfx('warn')}else if(atk==='roots'){b.timer=1.5;b.rootsFired=false;say('ROOTS RISE');sfx('warn')}else if(atk==='blink'){b.timer=2.4;b.blinkNext=0;b.blinkCount=0;say('MIRROR STEP');sfx('warn')}else if(atk==='spiral'){b.timer=2.6;b.spiralA=0;say('GLASS SPIRAL');sfx('warn')}else if(atk==='gaze'){b.timer=2.1;b.gazeNext=0;say('THE ORACLE GAZES');sfx('warn')}else if(atk==='blades'){b.timer=2.8;say('SPINNING BLADES');sfx('warn');for(let s of b.swords){let a=Math.atan2(p.y-b.y,p.x-b.x)+(Math.random()-.5)*.7;s.state='thrown';s.vx=Math.cos(a)*640;s.vy=Math.sin(a)*640;s.bounces=0;s.life=2.8}}}}
if(b.mode==='spray'){if(Math.floor((b.timer+dt)*5)!==Math.floor(b.timer*5)){let a=Math.atan2(p.y-b.y,p.x-b.x);for(let j=-2;j<=2;j++)bullet(b.x,b.y,a+j*.17,'enemy',8+b.level*2,250+b.level*30,9)}if(b.timer<=0){b.mode='pick';b.timer=.5}}
if(b.mode==='charge'||b.mode==='pounce'){b.y+=(b.lane-b.y)*Math.min(1,dt*7);if(b.timer<.38&&!b.vx){b.vx=(p.x<b.x?-1:1)*(b.level===2?1250:1030)*(b.mode==='pounce'?.75:1);burst(b.x,b.y,b.mode==='pounce'?'#7cd66a':'#ff4965',16)}if(b.vx){b.x+=b.vx*dt;if(dist(b,p)<b.r+22)hurt(b.mode==='pounce'?16:20);if(b.x<L+120||b.x>L+1150)b.vx*=-1}if(b.timer<=-.45){if(b.mode==='pounce'){for(let i=0;i<10;i++)bullet(b.x,b.y,i*Math.PI/5,'enemy',7,220,8);burst(b.x,b.y,'#7cd66a',18)}b.vx=0;b.mode='pick';b.timer=.6}}
if(b.mode==='nova'&&b.timer<=0){for(let i=0;i<18+b.level*4;i++)bullet(b.x,b.y,i*Math.PI/(9+b.level*2),'enemy',11,270,9);rings.push({x:b.x,y:b.y,life:1,c:'#ffe177'});b.mode='pick';b.timer=.6;shake=.5}
if(b.mode==='sweep'){b.x+=(clamp(p.x,L+140,L+1130)-b.x)*Math.min(1,dt*3);if(!b.sweepDone&&b.timer<.9){let a=Math.atan2(p.y-b.y,p.x-b.x);for(let j=-4;j<=4;j++)bullet(b.x+Math.cos(a)*40,b.y+Math.sin(a)*40,a+j*.16,'enemy',10+b.level*2,520,7);burst(b.x,b.y,'#ffce6a',10);b.sweepDone=true;shake=.35}if(b.timer<=0){b.mode='pick';b.timer=.6}}
if(b.mode==='guard'){b.guardNext-=dt;if(b.guardShots<3&&b.guardNext<=0){let a=Math.atan2(p.y-b.y,p.x-b.x);bullet(b.x,b.y,a,'enemy',9+b.level*2,900,6);b.guardShots++;b.guardNext=.35;sfx('hit')}if(b.timer<=0){b.mode='pick';b.timer=.6}}
if(b.mode==='thorns'){if(b.timer<=0){for(let i=0;i<16+b.level*4;i++)bullet(b.x,b.y,i*Math.PI/(8+b.level*2),'enemy',7,150+b.level*20,9);rings.push({x:b.x,y:b.y,life:1,c:'#8ee06a'});b.mode='pick';b.timer=.6;shake=.4}}
if(b.mode==='roots'){if(!b.rootsFired&&b.timer<.9){b.rootPts=[];for(let i=0;i<4;i++)b.rootPts.push({x:clamp(p.x+(Math.random()-.5)*260,L+140,L+1130),y:clamp(p.y+(Math.random()-.5)*220,110,H-110)});b.rootsFired=true}if(b.rootsFired&&b.timer<.45&&b.timer>.4){for(let pt of b.rootPts){for(let i=0;i<6;i++)bullet(pt.x,pt.y,i*Math.PI/3,'enemy',8,150,7);burst(pt.x,pt.y,'#8ee06a',10)}}if(b.timer<=0){b.mode='pick';b.timer=.6}}
if(b.mode==='blink'){b.blinkNext-=dt;if(b.blinkNext<=0&&b.blinkCount<3){b.x=clamp(L+250+Math.random()*750,L+140,L+1130);b.y=110+Math.random()*(H-220);let a=Math.atan2(p.y-b.y,p.x-b.x);for(let j=-1;j<=1;j++)bullet(b.x,b.y,a+j*.12,'enemy',9+b.level*2,680,7);burst(b.x,b.y,'#c9b6ff',12);b.blinkCount++;b.blinkNext=.55}if(b.timer<=0){b.mode='pick';b.timer=.6}}
if(b.mode==='spiral'){b.spiralA+=dt*(6+b.level*1.5);if(Math.floor((b.timer+dt)*14)!==Math.floor(b.timer*14)){bullet(b.x,b.y,b.spiralA,'enemy',7,300,7);bullet(b.x,b.y,b.spiralA+Math.PI,'enemy',7,300,7)}if(b.timer<=0){b.mode='pick';b.timer=.6}}
if(b.mode==='gaze'){b.gazeNext-=dt;if(b.gazeNext<=0){let a=Math.atan2(p.y-b.y,p.x-b.x);bullet(b.x,b.y,a,'enemy',10+b.level*2,980,6);b.gazeNext=.25;sfx('hit')}if(b.timer<=0){b.mode='pick';b.timer=.6}}
if(b.mode==='omni'){b.omniA=(b.omniA||0)+dt*1.1;if(Math.floor((b.timer+dt)*2.2)!==Math.floor(b.timer*2.2)){let spokes=14,gapEvery=Math.floor(spokes/(3+b.level));for(let i=0;i<spokes;i++){if(i%gapEvery===0)continue;bullet(b.x,b.y,b.omniA+i*(Math.PI*2/spokes),'enemy',9+b.level*2,250+b.level*20,8)}}if(b.timer<=0){b.mode='pick';b.timer=.7}}
if(b.mode==='armBite'){b.biteNext-=dt;if(b.biteNext<=0&&b.biteCount<2){b.biteSide=b.biteCount%2?-1:1;b.biteY=clamp(p.y,120,H-120);let jawX=b.x+b.biteSide*175;if(Math.abs(p.y-b.biteY)<64&&Math.abs(p.x-jawX)<150)hurt(20+b.level*3);for(let j=-2;j<=2;j++)bullet(jawX,b.biteY+j*16,b.biteSide<0?0:Math.PI,'enemy',8+b.level*2,420,9);burst(jawX,b.biteY,'#ff4a49',24);b.biteCount++;b.biteNext=.7;sfx('roar')}if(b.timer<=0){b.mode='pick';b.timer=.7}}
if(b.mode==='rocketSalvo'){b.rocketNext-=dt;if(b.rocketNext<=0&&b.rockets<2+b.level){let a=Math.atan2(p.y-b.y,p.x-b.x)+(Math.random()-.5)*.16;bullet(b.x+Math.cos(a)*95,b.y+Math.sin(a)*40,a,'enemy',18,390,14,'rocket');b.rockets++;b.rocketNext=.42;sfx('roar')}if(b.timer<=0){b.mode='pick';b.timer=.7}}
if(b.mode==='cleave'){b.x+=(clamp(p.x,L+180,L+1100)-b.x)*Math.min(1,dt*2.4);if(!b.cleaveDone&&b.timer<.62){let a=Math.atan2(p.y-b.y,p.x-b.x);for(let j=-3;j<=3;j++)bullet(b.x,b.y,a+j*.11,'enemy',13+b.level*2,650,11);burst(b.x,b.y,'#ff7050',24);shake=.45;b.cleaveDone=true}if(b.timer<=0){b.mode='pick';b.timer=.65}}
if(b.mode==='laserchase'){if(b.beamPhase==='aim'){b.beamAimT-=dt;b.beamAngle=Math.atan2(p.y-b.y,p.x-b.x);if(b.beamAimT<=0){b.beamPhase='fire';b.beamOn=true;shake=Math.max(shake,.35);sfx('roar')}}else if(b.beamPhase==='fire'){b.beamAngle+=b.beamSweep*dt;let toP=Math.atan2(p.y-b.y,p.x-b.x),ad=Math.abs(((toP-b.beamAngle+Math.PI*3)%(Math.PI*2))-Math.PI);b.beamHitCd=Math.max(0,(b.beamHitCd||0)-dt);if(ad<.1&&dist(b,p)<950&&b.beamHitCd<=0){hurt(6);b.beamHitCd=.16}}if(b.timer<=0){b.mode='pick';b.timer=.7;b.beamOn=false}}
if(b.mode==='stomp'){b.stompT-=dt;if(b.stompPhase==='wind'){if(b.stompT<=0){b.stompPhase='rush';b.stompT=.55;let a=Math.atan2(p.y-b.y,p.x-b.x);b.vx=Math.cos(a)*(560+b.level*70);b.vy=Math.sin(a)*(560+b.level*70);burst(b.x,b.y,'#ff5a3d',18)}}else if(b.stompPhase==='rush'){b.x+=b.vx*dt;b.y+=b.vy*dt;if(dist(b,p)<b.r+28)hurt(22);if(b.stompT<=0){b.vx=0;b.vy=0;b.stompPhase='land';for(let i=0;i<16;i++)bullet(b.x,b.y,i*Math.PI/8,'enemy',8,230,9);rings.push({x:b.x,y:b.y,life:1,c:'#ff6a3d'});burst(b.x,b.y,'#ff6a3d',26);shake=Math.max(shake,.5)}}if(b.timer<=0){b.mode='pick';b.timer=.8;b.vx=0;b.vy=0}}
if(b.mode==='meteor'){if(!b.meteorPts&&b.timer<2.3){b.meteorPts=[];for(let i=0;i<3+b.level;i++)b.meteorPts.push({x:clamp(p.x+(Math.random()-.5)*300,L+150,L+1140),y:clamp(p.y+(Math.random()-.5)*260,110,H-110),t:.9+Math.random()*.3})}if(b.meteorPts)for(let m of b.meteorPts)if(m.t>0){m.t-=dt;if(m.t<=0){for(let i=0;i<8;i++)bullet(m.x,m.y,i*Math.PI/4,'enemy',9,180,8);burst(m.x,m.y,'#ff8a3d',14);if(dist(m,p)<50)hurt(10)}}if(b.timer<=0){b.mode='pick';b.timer=.8}}
if(b.mode==='summon'){if(!b.summoned&&b.timer<1.3){for(let i=0;i<3+b.level;i++){let a=Math.random()*Math.PI*2;enemy(clamp(b.x+Math.cos(a)*190,L+150,L+1140),clamp(b.y+Math.sin(a)*190,110,H-110),'imp')}b.hp=Math.min(b.max,b.hp+b.healAmt);burst(b.x,b.y,'#7cff9e',22);b.summoned=true}if(b.timer<=0){b.mode='pick';b.timer=.8}}
if(b.mode==='regen'){if(b.timer>1.9&&!b.healApplied){b.hp=Math.min(b.max,b.hp+b.healAmt);b.healApplied=true;burst(b.x,b.y,'#8cffaf',32);shake=Math.max(shake,.4)}if(b.timer<=0){b.mode='pick';b.timer=.8;b.healApplied=false}}
if(b.mode==='blades'){if(b.timer<=0){b.mode='pick';b.timer=.6}}
if(b.art==='demon'&&b.mode!=='stomp'){b.x+=Math.sin(time*.6)*44*dt;b.y+=Math.cos(time*.47)*30*dt}
swordsUpdate(b,dt);
b.x=clamp(b.x,L+120,L+1150);b.y=clamp(b.y,105,H-105)}
// Summoner aggro is injected through SHADOW_ROSTER.enemyTarget. The default
// target remains p, so the Vanguard and all pre-summon behavior are unchanged.
function enemiesUpdate(dt){for(let e of enemies){e.hit=Math.max(0,e.hit-dt);e.attack-=dt;e.shot-=dt;e.impactPause=Math.max(0,(e.impactPause||0)-dt);e.impactTime=Math.max(0,(e.impactTime||0)-dt);if(e.impactTime>0){e.x+=(e.impactVX||0)*dt;e.y+=(e.impactVY||0)*dt;e.impactVX*=Math.pow(.001,dt/.16);e.impactVY*=Math.pow(.001,dt/.16)}if(e.impactPause>0)continue;let target=window.SHADOW_ROSTER?.enemyTarget?.(e)||p,a=Math.atan2(target.y-e.y,target.x-e.x),d=dist(e,target),targetRadius=target===p?27:(target.r||28);if(e.kind==='wisp'){e.x+=Math.cos(a)*e.speed*dt;e.y+=Math.sin(a)*e.speed*dt+Math.sin(time*5+e.wave)*30*dt;if(e.shot<=0){let shot=bullet(e.x,e.y,a,'enemy',7,310,7);if(shot&&target!==p)shot.shadowTarget=target;e.shot=1.5}}else if(d>e.r+targetRadius){e.x+=Math.cos(a)*e.speed*dt;e.y+=Math.sin(a)*e.speed*dt}else if(e.attack<=0){if(target===p)hurt(e.kind==='brute'?15:8);else window.SHADOW_ROSTER?.damageShadow?.(target,e.kind==='brute'?15:8,e);e.attack=e.kind==='brute'?1.1:.72}}}
function shotsUpdate(dt){let live=[];for(let s of shots){s.x+=s.vx*dt;s.y+=s.vy*dt;s.life-=dt;if(s.life<=0||s.x<arenaWorldBounds().left-130||s.x>arenaWorldBounds().right+130||s.y<arenaWorldBounds().top-155||s.y>arenaWorldBounds().bottom+155)continue;if(s.team!=='enemy'){for(let e of enemies)if(hit(s,e,s.s+e.r)){e.hp-=s.d;if(s.team==='player'&&p.hasLifesteal)p.hp=Math.min(p.maxHp,p.hp+s.d*LIFESTEAL_PCT);e.hit=.12;s.life=0;burst(s.x,s.y,'#ffd273',4);sfx('hit');break}if(s.life&&boss&&hit(s,boss,s.s+boss.r)){boss.hp-=s.d;if(s.team==='player'&&p.hasLifesteal)p.hp=Math.min(p.maxHp,p.hp+s.d*LIFESTEAL_PCT);boss.hit=.12;s.life=0;burst(s.x,s.y,'#ffe077',5);sfx('hit');if(boss.hp<=0)bossDown()}}else{let shadow=s.shadowTarget&&window.SHADOW_ROSTER?.isShadowTarget?.(s.shadowTarget)?s.shadowTarget:null,victim=shadow||p;if(hit(s,victim,s.s+(victim===p?20:(victim.r||28)))){if(victim===p)hurt(s.d);else window.SHADOW_ROSTER?.damageShadow?.(victim,s.d,s);s.life=0}}if(s.life>0)live.push(s)}shots=live;enemies=enemies.filter(e=>{if(e.hp>0)return true;p.kills++;combo++;comboTimer=1.5;burst(e.x,e.y,'#ff874d',13);if(Math.random()<.34){let pool=p.hasRocketPack?['heal','rapid','damage','rocketrapid']:['heal','rapid','damage'];pickups.push({x:e.x,y:e.y,type:pool[Math.floor(Math.random()*pool.length)]})}return false})}
function pickupUpdate(){for(let i=pickups.length-1;i>=0;i--){let q=pickups[i];if(dist(q,p)<45){if(q.type==='shotgun'){p.hasShotgun=true;p.weapon='shotgun';say('SCATTERFIRE ACQUIRED [2]')}if(q.type==='machinegun'){p.hasMachineGun=true;p.weapon='machinegun';say('STARLING MACHINE GUN ACQUIRED [3]')}if(q.type==='rocket'&&ally){ally.hasRocketLauncher=true;ally.rocketFire=0;say('MOON KNIGHT CLAIMS THE SIEGEBREAKER',3.2)}if(q.type==='heal'){p.hp=Math.min(100,p.hp+28);say('VITALITY +28')}if(q.type==='rapid'){p.rapid=10;say('OVERDRIVE')}if(q.type==='damage'){p.damage=Math.min(4,p.damage+1);say('POWER CORE +1')}if(q.type==='lifesteal'){p.hasLifesteal=true;say(`LIFEDRINKER ACQUIRED — ${Math.round(LIFESTEAL_PCT*100)}% OF DAMAGE DEALT HEALS YOU`,3.4)}if(q.type==='rocketpack'){p.hasRocketPack=true;p.rocketCd=Math.min(p.rocketCd,.6);say('SIEGE POD ACQUIRED — AUTO-ROCKETS ONLINE',3.2)}if(q.type==='rocketrapid'){p.rocketRapid=ROCKET_RAPID_DURATION;say('ROCKET OVERDRIVE')}if(q.type==='ally'){ally={x:camera+W*.68,y:H*.35,tx:0,ty:0,move:0,speed:220,a:0,fire:.2,hasRocketLauncher:false,rocketFire:0};say('MOON KNIGHT ROVES THE FIELD')}sfx('pickup');pickups.splice(i,1)}}if(phase==='travel'){if(act===0&&!p.hasShotgun&&p.x>440&&!pickups.some(q=>q.type==='shotgun'))pickups.push({x:560,y:245,type:'shotgun'});if(act===1&&!ally&&p.x>310&&!pickups.some(q=>q.type==='ally'))pickups.push({x:430,y:530,type:'ally'});if(act===1&&!p.hasLifesteal&&p.x>720&&!pickups.some(q=>q.type==='lifesteal'))pickups.push({x:830,y:470,type:'lifesteal'});if(act===2&&!p.hasMachineGun&&p.x>470&&!pickups.some(q=>q.type==='machinegun'))pickups.push({x:590,y:330,type:'machinegun'});if(act===2&&!p.hasRocketPack&&p.x>900&&!pickups.some(q=>q.type==='rocketpack'))pickups.push({x:1020,y:520,type:'rocketpack'})}}
function hurt(n){if(p.dashTime>0)return;HERO_ANIM.hurt=.38;p.hp-=n;flash=.4;shake=Math.max(shake,n/24);burst(p.x,p.y,'#ff5266',10);sfx('hurt');if(p.hp<=0){running=false;retrying=true;document.querySelector('#startPanel').style.display='flex';document.querySelector('#startPanel h2').textContent='THE COLOR FADES';document.querySelector('#startPanel p').textContent=`The Citadel pushed you back into the gray. Rise again from ${checkpoint==='demon'?'THE DEMON LORD':checkpoint==='adapted'?'THE ADAPTED ONE':LEVELS[checkpoint].name}.`;document.querySelector('#startButton').textContent='RISE AGAIN'}}
function rescueUpdate(dt){let r=rescue;r.timer+=dt,meet=L+650;if(r.timer<2.3)p.x=Math.min(meet-28,p.x+185*dt);else if(r.timer<4.7){r.x=Math.max(meet+28,r.x-160*dt);burst(meet,H/2,'#ffe681',3)}else if(r.timer<9){let d=r.timer-4.7;p.x=meet-27+Math.sin(d*4)*7;r.x=meet+27-Math.sin(d*4)*7;p.y=360+Math.abs(Math.sin(d*4))*10;r.y=360+Math.abs(Math.sin(d*4+3.14))*10;burst(meet,335,['#ff6578','#66e8f4','#ffe36e','#b48cff'][Math.floor(Math.random()*4)],2)}else{phase='credits';rescue={timer:0};say('A NEW DAY BEGINS',2)}}
function creditsUpdate(dt){rescue.timer+=dt;if(rescue.timer>END_CREDITS.length*1.25+8){running=false;document.querySelector('#startPanel').style.display='flex';document.querySelector('#startPanel h2').textContent='COLOR RETURNS';document.querySelector('#startPanel p').textContent=`You found each other. The world is bright again — and ${p.kills} shadows have fallen.`;document.querySelector('#startButton').textContent='PLAY AGAIN'}}
function update(dt){time+=dt;musicUpdate();messageTimer-=dt;if(messageTimer<=0)document.querySelector('#message').style.opacity=0;flash=Math.max(0,flash-dt*3);shake=Math.max(0,shake-dt*4);camera=phase==='travel'?clamp(p.x-W*.28,0,L-W*.3):window.PRINCESS_TERRAIN?.active()?window.PRINCESS_TERRAIN.cameraX:L-100;if(phase==='demonIntro'){demonIntroUpdate(dt);particlesUpdate(dt);return}if(phase==='actClear'){actClearUpdate(dt);particlesUpdate(dt);return}if(phase==='rescue'){rescueUpdate(dt);particlesUpdate(dt);return}if(phase==='credits'){creditsUpdate(dt);return}p.angle=Math.atan2(mouse.y-p.y,mouse.x+camera-p.x);let x=(keys.d||keys.ArrowRight?1:0)-(keys.a||keys.ArrowLeft?1:0),y=(keys.s||keys.ArrowDown?1:0)-(keys.w||keys.ArrowUp?1:0),z=Math.hypot(x,y)||1;if(x||y){p.lx=x/z;p.ly=y/z}p.dashTime=Math.max(0,p.dashTime-dt);if(p.dashTime>0){p.x+=p.dx*940*dt;p.y+=p.dy*940*dt;burst(p.x,p.y,'#76edf4',2)}else{p.x+=x/z*p.speed*dt;p.y+=y/z*p.speed*dt}p.y=clamp(p.y,arenaWorldBounds().top,arenaWorldBounds().bottom);if(phase==='travel'){p.x=clamp(p.x,70,L-80)}else{p.x=clamp(p.x,arenaWorldBounds().left,arenaWorldBounds().right)}p.fire=Math.max(0,p.fire-dt);p.rapid=Math.max(0,p.rapid-dt);if(p.dash<2){p.recharge-=dt;if(p.recharge<=0){p.dash++;p.recharge=p.dash<2?.7:0}}mouse.hold=mouse.down?mouse.hold+dt:0;if(mouse.down)fire();if(phase==='travel'){while(nextSpawn<p.x+790&&nextSpawn<L-80&&enemies.length<22){let a=act===0?['minion','crawler']:act===1?['crawler','wisp','brute']:['wisp','brute','minion'];enemy(nextSpawn,100+Math.random()*(H-200),a[Math.floor(Math.random()*a.length)]);nextSpawn+=135+Math.random()*170}if(p.x>=L-95&&enemies.length===0)enterBoss()}enemiesUpdate(dt);if(boss)bossUpdate(dt);allyUpdate(dt);shotsUpdate(dt);pickupUpdate();particlesUpdate(dt);comboTimer-=dt;if(comboTimer<=0)combo=0}function particlesUpdate(dt){particles=particles.filter(q=>(q.life-=dt)>0);for(let q of particles){q.x+=q.vx*dt;q.y+=q.vy*dt}rings=rings.filter(q=>(q.life-=dt)>0)}
function drawCage(cx,cy){ctx.save();ctx.translate(cx-camera,cy);ctx.fillStyle='#150c1c';ctx.beginPath();ctx.ellipse(0,64,70,16,0,0,7);ctx.fill();ctx.fillStyle='#241531';ctx.fillRect(-64,54,128,16);ctx.strokeStyle='#7be3ff3d';ctx.lineWidth=14;ctx.strokeRect(-62,-62,124,124);ctx.strokeStyle='#d8c9e6';ctx.lineWidth=5;ctx.beginPath();for(let i=-50;i<=50;i+=17){ctx.moveTo(i,-54);ctx.lineTo(i,54)}ctx.moveTo(-56,-36);ctx.lineTo(56,-36);ctx.moveTo(-56,26);ctx.lineTo(56,26);ctx.stroke();ctx.strokeStyle='#fff6de88';ctx.lineWidth=2;ctx.strokeRect(-58,-58,116,116);ctx.restore()}
function drawSwords(b){if(!b.swords)return;for(let s of b.swords){let angle=s.state==='orbit'?s.ang+Math.PI/2:Math.atan2(s.vy||.01,s.vx||.01)+Math.PI/2;img('sword',s.x,s.y,46,angle)}}
function drawBeamTelegraph(b){let len=1000,ox=b.x+Math.sin(time*.8)*4,oy=b.art==='demon'?b.y+18:b.y-b.r*1.0;ctx.save();ctx.translate(ox-camera,oy);ctx.rotate(b.beamAngle);ctx.strokeStyle=`rgba(255,60,70,${.35+Math.sin(time*14)*.15})`;ctx.lineWidth=4;ctx.setLineDash([14,10]);ctx.beginPath();ctx.moveTo(0,0);ctx.lineTo(len,0);ctx.stroke();ctx.setLineDash([]);ctx.restore()}
function drawMeteors(b){if(!b.meteorPts)return;for(let m of b.meteorPts)if(m.t>0){ctx.save();ctx.globalAlpha=.5+Math.sin(time*10)*.2;ctx.strokeStyle='#ff5a3d';ctx.lineWidth=4;ctx.beginPath();ctx.arc(m.x-camera,m.y,34,0,7);ctx.stroke();ctx.restore()}}
function drawBeam(b){let len=1100,frame=Math.floor(time*14)%2+1,tex=art[`beam-${frame}`],ox=b.x+Math.sin(time*.8)*4,oy=b.art==='demon'?b.y+18:b.y-b.r*1.0;ctx.save();ctx.translate(ox-camera,oy);ctx.rotate(b.beamAngle);if(tex&&tex.complete&&tex.naturalWidth)ctx.drawImage(tex,0,-42,len,84);ctx.restore()}
function bar(x,y,w,h,v,m,c,label){ctx.fillStyle='#090a12c9';ctx.fillRect(x,y,w,h);ctx.fillStyle=c;ctx.fillRect(x,y,w*clamp(v/m,0,1),h);ctx.strokeStyle='#ffe5a1';ctx.strokeRect(x,y,w,h);if(label){ctx.fillStyle='#fff3d3';ctx.font='12px monospace';ctx.fillText(label,x,y-6)}}
function drawCredits(){let t=rescue.timer,g=ctx.createLinearGradient(0,0,0,H);g.addColorStop(0,'#312257');g.addColorStop(.48,'#fa8561');g.addColorStop(.67,'#f6be65');g.addColorStop(.675,'#316c9a');g.addColorStop(1,'#102a57');ctx.fillStyle=g;ctx.fillRect(0,0,W,H);ctx.fillStyle='#ffda70';ctx.globalAlpha=Math.min(1,t/3);ctx.beginPath();ctx.arc(900,238,85,0,7);ctx.fill();ctx.globalAlpha=1;ctx.fillStyle='#4c285a';ctx.beginPath();ctx.moveTo(0,410);for(let x=0;x<=W;x+=55)ctx.lineTo(x,405+Math.sin(x*.018)*22);ctx.lineTo(W,520);ctx.lineTo(0,520);ctx.fill();ctx.fillStyle='#1c5e91';ctx.fillRect(0,465,W,H-465);for(let y=485;y<H;y+=26){ctx.strokeStyle=`rgba(255,225,155,${.13+Math.sin(time*2+y)*.08})`;ctx.beginPath();ctx.moveTo(0,y+Math.sin(time*2+y*.05)*5);ctx.lineTo(W,y+Math.sin(time*2+y*.05)*5);ctx.stroke()}let sitY=462+Math.sin(time*2)*2;ctx.fillStyle='#1c1731';ctx.beginPath();ctx.ellipse(530,sitY+48,58,10,0,0,7);ctx.ellipse(640,sitY+48,58,10,0,0,7);ctx.fill();ctx.fillStyle='#302449';ctx.fillRect(505,sitY,44,45);ctx.fillRect(618,sitY,43,45);ctx.beginPath();ctx.arc(527,sitY-10,19,0,7);ctx.arc(640,sitY-10,19,0,7);ctx.fill();ctx.strokeStyle='#39264d';ctx.lineWidth=7;ctx.beginPath();ctx.moveTo(532,sitY+34);ctx.lineTo(575,sitY+48);ctx.lineTo(622,sitY+35);ctx.stroke();ctx.fillStyle='#ffe0a1';ctx.font='24px sans-serif';ctx.textAlign='center';ctx.fillText('AT LAST, THEY WATCH THE COLOR RETURN.',W/2,90);let y=H-(t*52)+30;ctx.font='22px monospace';for(let i=0;i<END_CREDITS.length;i++){let line=END_CREDITS[i];ctx.fillStyle=i===0?'#fff1bd':'#fff7df';ctx.font=i===0?'34px sans-serif':'22px monospace';ctx.fillText(line,W/2,y+i*42)}ctx.fillStyle='#ffffffaa';ctx.font='13px monospace';ctx.fillText('PRESS ESC OR P TO PAUSE',W/2,H-22)}
function drawActClear(){let t=actClear.timer,total=actClear.total,g=ctx.createLinearGradient(0,0,0,H);g.addColorStop(0,'#0c0713');g.addColorStop(1,'#1c0f24');ctx.fillStyle=g;ctx.fillRect(0,0,W,H);let fade=Math.min(1,(total-t)*2.2);ctx.globalAlpha=fade;ctx.textAlign='center';ctx.fillStyle='#ffd671';ctx.font='52px Anton, sans-serif';ctx.fillText('AREA CLEARED',W/2,H/2-36);ctx.fillStyle='#fff3d3';ctx.font='16px DM Mono, monospace';ctx.fillText(LEVELS[actClear.cleared].name,W/2,H/2+8);if(actClear.next<LEVELS.length){ctx.fillStyle='#c9bfd8';ctx.font='14px DM Mono, monospace';ctx.fillText(`TRAVELING TO ${LEVELS[actClear.next].name}...`,W/2,H/2+52)}ctx.globalAlpha=1}
function draw(){if(phase==='credits'){drawCredits();return}if(phase==='actClear'){drawActClear();return}if(phase==='demonIntro'){drawDemonIntro();return}ctx.clearRect(0,0,W,H);ctx.save();if(shake)ctx.translate((Math.random()-.5)*shake*16,(Math.random()-.5)*shake*16);let g=ctx.createLinearGradient(0,0,0,H);g.addColorStop(0,'#17172b');g.addColorStop(1,'#20111e');ctx.fillStyle=g;ctx.fillRect(0,0,W,H);let mapName=mapAssetName(),map=art[mapName]||art.map;let arenaCfg=phase==='boss'&&boss&&window.ARENA_MAPS&&window.ARENA_MAPS[boss.art]&&window.ARENA_MAP_CROPS&&window.ARENA_MAP_CROPS[boss.art],arenaMap=arenaCfg&&window.ARENA_MAPS[boss.art],arenaMapActive=!!(arenaCfg&&arenaMap&&arenaMap.complete&&arenaMap.naturalWidth);if(arenaMapActive){let sx=clamp(Number(arenaCfg.x)||0,0,arenaMap.naturalWidth-1),sy=clamp(Number(arenaCfg.y)||0,0,arenaMap.naturalHeight-1),sw=Math.min(Number(arenaCfg.w)||arenaMap.naturalWidth-sx,arenaMap.naturalWidth-sx),sh=Math.min(Number(arenaCfg.h)||arenaMap.naturalHeight-sy,arenaMap.naturalHeight-sy);ctx.drawImage(arenaMap,sx,sy,sw,sh,0,0,W,H)}else if(map.complete&&map.naturalWidth)ctx.drawImage(map,camera,0,W,H,0,0,W,H);let colorReturn=phase==='rescue'?clamp((rescue.timer-4.7)/2.2,0,1):0;ctx.filter=`grayscale(${1-colorReturn}) saturate(${.35+colorReturn*.65})`;ctx.globalAlpha=.42;ctx.fillStyle=phase==='travel'?'#39233b':'#29172f';ctx.fillRect(0,70,W,H-140);ctx.globalAlpha=1;ctx.filter='none';for(let q of pickups){img(q.type==='machinegun'?'machinegun':q.type==='shotgun'?'shotgun':q.type==='ally'?'ally':q.type==='lifesteal'?'lifesteal':q.type==='rocketpack'?'rocket-launcher':q.type==='rocketrapid'?'rocket-buff':'buff',q.x,q.y,56,Math.sin(time*3)*.12);ctx.fillStyle='#ffe072';ctx.font='11px monospace';ctx.textAlign='center';ctx.fillText(q.type.toUpperCase(),q.x-camera,q.y+43)}for(let e of enemies){img(e.kind,e.x,e.y,e.r*2,Math.sin(time*4+e.wave)*.04,e.hit?.4:1);bar(e.x-camera-e.r,e.y-e.r-13,e.r*2,5,e.hp,e.max,'#ed5560')}if(boss){if(boss.art==='boss'){drawCage(L+1010,230);img('princess',L+1010,230,64,0,.55)}if(boss.beamPhase==='aim')drawBeamTelegraph(boss);if(boss.beamOn)drawBeam(boss);if(boss.mode==='meteor')drawMeteors(boss);if(boss.art==='demon'){drawDemon(boss)}else{img(boss.art,boss.x,boss.y,boss.r*2.5,Math.sin(time)*.035,boss.hit?.5:1)}drawSwords(boss);bar(boss.art==='demon'?205:325,22,boss.art==='demon'?870:630,boss.art==='demon'?22:18,boss.hp,boss.max,boss.art==='demon'?'#c0203f':'#e53d55',boss.name)}
if(boss&&boss.art==='demon'){ctx.save();ctx.globalAlpha=.14+Math.sin(time*1.5)*.04;let vg=ctx.createRadialGradient(W/2,H/2,H*.25,W/2,H/2,H*.78);vg.addColorStop(0,'rgba(0,0,0,0)');vg.addColorStop(1,'rgba(120,0,10,.55)');ctx.fillStyle=vg;ctx.fillRect(0,0,W,H);ctx.restore()}if(phase==='rescue'){img('princess',rescue.x,rescue.y,80);ctx.fillStyle='#fff4da';ctx.font='30px sans-serif';ctx.textAlign='center';ctx.fillText(rescue.timer<4.7?'SHE SEES YOU':'TOGETHER, COLOR RETURNS',W/2,130)}for(let s of shots)img(s.kind==='rocket'?'rocket':s.team==='enemy'?'boss-bullet':'bullet',s.x,s.y,s.s*3,s.a);for(let q of particles){ctx.globalAlpha=Math.min(1,q.life*3);ctx.fillStyle=q.c;ctx.fillRect(q.x-camera-q.size/2,q.y-q.size/2,q.size,q.size)}ctx.globalAlpha=1;if(ally){img('ally',ally.x,ally.y,48,ally.a);ctx.fillStyle='#9fffee';ctx.font='10px monospace';ctx.textAlign='center';ctx.fillText('MOON KNIGHT',ally.x-camera,ally.y+34)}if(p.dashTime>0){ctx.strokeStyle='#8effff';ctx.lineWidth=3;ctx.beginPath();ctx.arc(p.x-camera,p.y,38,0,7);ctx.stroke()}img('hero',p.x,p.y,64,p.angle);ctx.filter='none';ctx.fillStyle='#090a12bb';ctx.fillRect(0,0,W,66);ctx.fillStyle='#1b1320';ctx.fillRect(0,H-74,W,74);ctx.textAlign='left';ctx.fillStyle='#fff3d3';ctx.font='11px monospace';ctx.fillText(boss&&boss.art==='demon'?'THE FINAL DESCENT':boss&&boss.art==='adapted'?'THE ADAPTED ONE':LEVELS[act].name,22,21);ctx.fillStyle='#4fd6df44';ctx.fillRect(22,28,220,8);ctx.fillStyle='#4fd6df';ctx.fillRect(22,28,220*(phase==='travel'?p.x/L:1),8);bar(28,H-39,250,14,p.hp,100,'#52d6df',window.selectedHero==='shadow-summoner'?'UMBRAL CONDUCTOR':'VANGUARD');ctx.font='13px monospace';ctx.fillStyle='#fff3d3';ctx.fillText(`${window.selectedHero==='shadow-summoner'?'SHADOW COMMANDS':p.weapon.toUpperCase()} • POWER ${p.damage}${p.hasLifesteal?' • LIFESTEAL':''}${p.hasRocketPack?' • SIEGE POD':''}`,28,H-57);ctx.fillStyle=p.dash?'#86f7ff':'#766f7d';ctx.fillText(`DASH ${'◆'.repeat(p.dash)}${'◇'.repeat(2-p.dash)} [SPACE]`,28,H-78);ctx.textAlign='right';ctx.fillStyle='#fff3d3';ctx.fillText(`SHADOWS VANQUISHED ${p.kills}`,W-25,H-28);if(ally){ctx.fillStyle='#9fffee';ctx.fillText('MOON KNIGHT: ROVING',W-25,H-48)}if(difficultyKey==='noMercy'){ctx.fillStyle=shield.cooldown>0?'#766f7d':'#7be3ff';ctx.fillText(shield.cooldown>0?`SHIELD DOWN ${shield.cooldown.toFixed(1)}s`:`SHIELD ${Math.round(shield.hp)}/${shield.max}`,W-25,H-68)}if(flash){ctx.fillStyle=`rgba(255,70,80,${flash*.35})`;ctx.fillRect(0,0,W,H)}ctx.restore()}
function loop(n){if(!running||paused)return;let dt=Math.min(.033,(n-last)/1000||0);last=n;update(dt);draw();requestAnimationFrame(loop)}function dash(){if(!running||paused||!p.dash)return;p.dash--;p.recharge=.7;p.dashTime=.19;p.dx=p.lx;p.dy=p.ly;adaptedRecordDodge();burst(p.x,p.y,'#65dce6',14);sfx('dash')}function pause(v){if(!running)return;paused=v;document.querySelector('#pausePanel').hidden=!v;document.querySelector('#pauseButton').textContent=v?'▶ RESUME':'Ⅱ PAUSE';if(track){if(v)track.pause();else track.play().catch(()=>{})}if(!v){last=performance.now();requestAnimationFrame(loop)}}
document.addEventListener('keydown',e=>{if(['ArrowUp','ArrowDown','ArrowLeft','ArrowRight',' '].includes(e.key))e.preventDefault();if(e.key==='p'||e.key==='P'||e.key==='Escape')return pause(!paused);keys[e.key]=true;if(e.key===' ')dash();if(e.key==='1')p.weapon='pistol';if(e.key==='2'&&p.hasShotgun)p.weapon='shotgun';if(e.key==='3'&&p.hasMachineGun)p.weapon='machinegun'});document.addEventListener('keyup',e=>keys[e.key]=false);function aimAtPointer(e){let r=canvas.getBoundingClientRect(),scale=Math.max(r.width/W,r.height/H),offX=(W*scale-r.width)/2,offY=(H*scale-r.height)/2;mouse.x=((e.clientX-r.left)+offX)/scale;mouse.screenY=((e.clientY-r.top)+offY)/scale;mouse.y=mouse.screenY+(window.PRINCESS_TERRAIN?.viewY||0)}canvas.addEventListener('pointermove',aimAtPointer);canvas.addEventListener('pointerdown',e=>{aimAtPointer(e);mouse.down=true;if(running&&!paused&&p.weapon==='sword')fire()});document.addEventListener('pointerup',()=>mouse.down=false);window.addEventListener('blur',()=>{mouse.down=false;if(running)pause(true)});document.addEventListener('visibilitychange',()=>{if(document.hidden&&running)pause(true)});document.querySelector('#pauseButton').onclick=()=>pause(!paused);document.querySelector('#resumeButton').onclick=()=>pause(false);document.querySelector('#muteButton').onclick=()=>{muted=!muted;if(master)master.gain.value=muted?0:1;if(track)track.muted=muted;document.querySelector('#muteButton').textContent=muted?'🔇':'🔊'};document.querySelector('#startButton').onclick=()=>{let cont=retrying;difficultyKey=document.querySelector('#difficultySelect')?.value||difficultyKey;if(cont){respawnAtCheckpoint()}else{reset(true)}retrying=false;running=true;paused=false;setMusic((!cont||checkpoint==='adapted'||checkpoint==='demon')?'boss':'journey');audio().resume();document.querySelector('#startPanel').style.display='none';last=performance.now();say(cont?(checkpoint==='adapted'?'THE ADAPTED ONE — CHECKPOINT':checkpoint==='demon'?'THE DEMON LORD — CHECKPOINT':`${LEVELS[act].name} — CHECKPOINT`):'THE ADAPTED ONE — OBSERVE. ADAPT. HUNT.',4);requestAnimationFrame(loop)};document.querySelector('#fullscreenButton').onclick=()=>{let f=gameFrame.requestFullscreen||gameFrame.webkitRequestFullscreen;if(f)f.call(gameFrame)};document.addEventListener('gesturestart',e=>e.preventDefault());document.addEventListener('gesturechange',e=>e.preventDefault());document.addEventListener('gestureend',e=>e.preventDefault());let lastTouchEnd=0;document.addEventListener('touchend',e=>{let now=Date.now();if(now-lastTouchEnd<=350)e.preventDefault();lastTouchEnd=now},{passive:false});
// Extra pinch-zoom backstop: some Android/Chrome builds ignore both the
// viewport meta tag and touch-action CSS for pinch gestures, so a raw
// multi-touch touchmove/touchstart is blocked here regardless of target.
// Single-finger touches (movement pad, fire stick, dash button) are
// untouched since only e.touches.length>1 (a pinch) triggers preventDefault.
function blockPinch(e){if(e.touches&&e.touches.length>1)e.preventDefault()}
document.addEventListener('touchstart',blockPinch,{passive:false});
document.addEventListener('touchmove',blockPinch,{passive:false});
const uiDefaults={controlScale:100,controlOpacity:100,moveX:24,moveY:28,fireX:24,fireY:30,dashX:114,dashY:30,aimSensitivity:100};let mobileUi={...uiDefaults,swap:false};try{mobileUi={...mobileUi,...JSON.parse(localStorage.getItem('pp-mobile-ui')||'{}')}}catch(e){}
let editingControls=false,dragState=null;
function dragSign(kind){return kind==='pad'?(mobileUi.swap?-1:1):(mobileUi.swap?1:-1)}
function startDrag(e,xKey,yKey,kind,xMin,xMax,yMin,yMax){dragState={xKey,yKey,kind,startX:e.clientX,startY:e.clientY,baseX:mobileUi[xKey],baseY:mobileUi[yKey],xMin,xMax,yMin,yMax}}
function updateDrag(e){if(!dragState)return;let sx=dragSign(dragState.kind);mobileUi[dragState.xKey]=clamp(dragState.baseX+(e.clientX-dragState.startX)*sx,dragState.xMin,dragState.xMax);mobileUi[dragState.yKey]=clamp(dragState.baseY-(e.clientY-dragState.startY),dragState.yMin,dragState.yMax);applyMobileUi()}
function endDrag(){if(!dragState)return;dragState=null;localStorage.setItem('pp-mobile-ui',JSON.stringify(mobileUi))}
const pad=document.querySelector('#movePad'),knob=pad.querySelector('i');function touch(e){let r=pad.getBoundingClientRect(),x=clamp(e.clientX-r.left-r.width/2,-36,36),y=clamp(e.clientY-r.top-r.height/2,-36,36);knob.style.transform=`translate(${x}px,${y}px)`;keys.a=x<-9;keys.d=x>9;keys.w=y<-9;keys.s=y>9}
pad.addEventListener('pointerdown',e=>{pad.setPointerCapture(e.pointerId);if(editingControls){startDrag(e,'moveX','moveY','pad',0,140,0,170);return}touch(e)});
pad.addEventListener('pointermove',e=>{if(editingControls){updateDrag(e);return}touch(e)});
pad.addEventListener('pointerup',()=>{if(editingControls){endDrag();return}knob.style.transform='';keys.a=keys.d=keys.w=keys.s=false});
pad.addEventListener('pointercancel',()=>{endDrag();knob.style.transform='';keys.a=keys.d=keys.w=keys.s=false});
let fb=document.querySelector('#fireButton');
fb.addEventListener('pointerdown',e=>{fb.setPointerCapture(e.pointerId);if(editingControls){startDrag(e,'fireX','fireY','right',0,140,0,170);return}mouse.down=true});
fb.addEventListener('pointermove',e=>{if(editingControls){updateDrag(e);return}let r=fb.getBoundingClientRect(),a=Math.atan2((e.clientY-r.top-r.height/2)*mobileUi.aimSensitivity/100,e.clientX-r.left-r.width/2);mouse.screenY=undefined;mouse.x=p.x-camera+Math.cos(a)*320;mouse.y=p.y+Math.sin(a)*320});
fb.addEventListener('pointerup',()=>{if(editingControls){endDrag();return}mouse.down=false});
fb.addEventListener('pointercancel',()=>{endDrag();mouse.down=false});
let dashBtn=document.querySelector('#dashButton');
dashBtn.addEventListener('pointerdown',e=>{e.preventDefault();dashBtn.setPointerCapture(e.pointerId);if(editingControls){startDrag(e,'dashX','dashY','right',0,200,0,170);return}dash()});
dashBtn.addEventListener('pointermove',e=>{if(editingControls)updateDrag(e)});
dashBtn.addEventListener('pointerup',()=>{if(editingControls)endDrag()});
dashBtn.addEventListener('pointercancel',()=>{if(editingControls)endDrag()});
function applyMobileUi(){gameFrame.style.setProperty('--control-scale',mobileUi.controlScale/100);gameFrame.style.setProperty('--control-opacity',mobileUi.controlOpacity/100);gameFrame.style.setProperty('--move-x',`${mobileUi.moveX}px`);gameFrame.style.setProperty('--move-y',`${mobileUi.moveY}px`);gameFrame.style.setProperty('--fire-x',`${mobileUi.fireX}px`);gameFrame.style.setProperty('--fire-y',`${mobileUi.fireY}px`);gameFrame.style.setProperty('--dash-x',`${mobileUi.dashX}px`);gameFrame.style.setProperty('--dash-y',`${mobileUi.dashY}px`);document.querySelector('#touchControls').classList.toggle('swapped',!!mobileUi.swap);for(let k in uiDefaults){let input=document.querySelector(`#${k}`);if(input)input.value=mobileUi[k]}}
for(let k in uiDefaults){document.querySelector(`#${k}`).addEventListener('input',e=>{mobileUi[k]=Number(e.target.value);localStorage.setItem('pp-mobile-ui',JSON.stringify(mobileUi));applyMobileUi()})}
document.querySelector('#swapSidesButton').onclick=()=>{mobileUi.swap=!mobileUi.swap;localStorage.setItem('pp-mobile-ui',JSON.stringify(mobileUi));applyMobileUi()};
document.querySelector('#resetControlsButton').onclick=()=>{mobileUi={...uiDefaults,swap:false};localStorage.removeItem('pp-mobile-ui');applyMobileUi()};
document.querySelector('#mobileSettingsButton').onclick=()=>{document.querySelector('#mobileSettingsPanel').hidden=false;editingControls=true;document.querySelector('#touchControls').classList.add('editing')};
document.querySelector('#closeSettingsButton').onclick=()=>{document.querySelector('#mobileSettingsPanel').hidden=true;editingControls=false;dragState=null;document.querySelector('#touchControls').classList.remove('editing')};
applyMobileUi();draw();
// The Siegebreaker is an ally weapon: Moon Knight claims it in the final descent.
const drawSceneBase=draw;draw=function(){drawSceneBase();if(ally&&ally.hasRocketLauncher){ctx.save();img('rocket-launcher',ally.x+Math.cos(ally.a)*24,ally.y+Math.sin(ally.a)*24,30,ally.a);ctx.restore()}};
const respawnAtCheckpointBase=respawnAtCheckpoint;respawnAtCheckpoint=function(){let savedAlly=checkpoint==='demon'&&ally;respawnAtCheckpointBase();if(savedAlly&&boss){ally={x:camera+W*.68,y:H*.35,tx:0,ty:0,move:0,speed:220,a:0,fire:.2,hasRocketLauncher:true,rocketFire:1.05};say('MOON KNIGHT RETURNS WITH THE SIEGEBREAKER',2.4)}};
// Three-head final boss extension. The sockets stay fixed; only each side head's
// neck/head endpoint stretches toward the player during its own attack cycle.
/*
 * DEMON NECK TUNING — edit these values when you want to retarget the artwork.
 * The circled red bars in demon-body-sockets.png are the intended neck sources.
 * That PNG is rendered at x=b.x-camera-580, width=1160, y=b.y-114, height=210.
 * The current anchors map those bars to ±403 world pixels and y=232.
 * `socketOffsetX` / `socketY` move the fixed sources; `homeOffsetX` / `homeY`
 * move the resting head centers; `headAttachInset` controls how far the neck
 * tucks underneath each head (larger = deeper overlap, never a visible gap).
 * `chaseSeconds` is deliberately 3: the attacking head continuously retargets
 * the hero for three straight seconds before its short return-to-home phase.
 */
const DEMON_NECK_TUNING={socketOffsetX:403,socketY:232,homeOffsetX:320,homeY:245,headAttachInset:.70,maxReach:430,chaseSeconds:3,returnSeconds:.85,segmentSpacing:34,curveBend:34,curveSpeed:7};
// Add another name here and a matching branch in demonPartsUpdate() to create
// a new flank-neck attack. Left/right heads use the same list with offset timers.
const DEMON_NECK_ATTACKS=['lunge','spit','sweep','rake','burst'];
function demonPartDown(b,part){if(!part.alive)return;part.alive=false;part.mode='dead';part.x=part.homeX;part.y=part.homeY;burst(part.x,part.y,'#ff5b55',34);pickups.push({x:part.x,y:part.y,type:'rapid'});say(`${part.side.toUpperCase()} HEAD SHATTERED — ATTACK SPEED +`,2.6);sfx('win');if(b.parts.every(q=>!q.alive)){b.coreLocked=false;b.name='THE DEMON LORD — CORE EXPOSED';say('THE OUTER HEADS ARE GONE — THE CORE IS EXPOSED',3.2);burst(b.x,b.y,'#ffe36e',45)}}
function demonPartsUpdate(dt){let b=boss;if(!b||b.art!=='demon'||!b.parts)return;for(let part of b.parts){if(!part.alive)continue;part.hit=Math.max(0,part.hit-dt);part.hitCd=Math.max(0,(part.hitCd||0)-dt);part.timer-=dt;part.next-=dt;part.impactPause=Math.max(0,(part.impactPause||0)-dt);part.impactTime=Math.max(0,(part.impactTime||0)-dt);if(part.impactTime>0){part.impactVX*=Math.pow(.001,dt/.16);part.impactVY*=Math.pow(.001,dt/.16)}if(part.impactPause>0)continue;if(part.mode==='idle'&&part.timer<=0){part.attack=(part.attack+1)%DEMON_NECK_ATTACKS.length;part.mode=DEMON_NECK_ATTACKS[part.attack];let cfg=DEMON_NECK_TUNING;part.timer=part.mode==='lunge'?cfg.chaseSeconds+cfg.returnSeconds:part.mode==='spit'?2.05:part.mode==='sweep'?2.15:part.mode==='rake'?2.35:2.2;part.next=.18;part.returnStarted=false;part.neckProgress=0;part.targetY=clamp(p.y,125,H-125);part.targetX=clamp(p.x,camera+80,camera+W-80);let call=part.mode==='lunge'?`${part.side.toUpperCase()} JAW HUNTS YOU FOR 3 SECONDS`:part.mode==='spit'?`${part.side.toUpperCase()} HEAD SPITS A CURVE`:part.mode==='sweep'?`${part.side.toUpperCase()} NECK SWEEPS THE LANE`:part.mode==='rake'?`${part.side.toUpperCase()} JAW RAKES A CROSSFIRE`:`${part.side.toUpperCase()} HEAD UNLEASHES A BLOOM`;say(call,1.5);sfx('warn')}if(part.mode==='lunge'){if(dist(part,p)<part.r+38&&part.hitCd<=0){hurt(16+b.level*2);part.hitCd=.55}if(part.timer<=0){part.x=part.homeX;part.y=part.homeY;part.mode='idle';part.timer=.55;part.returnStarted=false}}if(part.mode==='spit'||part.mode==='sweep'||part.mode==='rake'||part.mode==='burst'){if(part.next<=0&&part.timer>.35){let a=Math.atan2(p.y-part.y,p.x-part.x),count=part.mode==='spit'?3:part.mode==='sweep'?7:part.mode==='rake'?6:8;if(part.mode==='burst'){for(let j=0;j<count;j++)bullet(part.x,part.y,a+Math.PI*2*j/count+time*.18,'enemy',8+b.level*2,340+b.level*25,7);bullet(part.x,part.y,a,'enemy',10+b.level*2,510,7)}else{for(let j=0;j<count;j++){let spread=part.mode==='spit'?.14:part.mode==='sweep'?.10:.18;let offset=part.mode==='rake'?(j<3?-1:1)*(j%3+1)*spread:(j-(count-1)/2)*spread;bullet(part.x,part.y,a+offset,'enemy',part.mode==='rake'?9+b.level*2:7+b.level*2,part.mode==='rake'?440:300+b.level*35,7)}}part.next=part.mode==='spit'?.48:part.mode==='sweep'?.27:part.mode==='rake'?.62:.9;sfx('hit')}if(part.timer<=0){part.mode='idle';part.timer=.55;part.x=part.homeX;part.y=part.homeY;part.returnStarted=false}}}}
 function demonRetargetParts(dt){if(!boss||boss.art!=='demon'||boss.mode==='intro')return;let cfg=DEMON_NECK_TUNING;for(let part of boss.parts||[]){if(!part.alive)continue;if(part.impactPause>0)continue;if(part.mode!=='lunge'){part.x=part.homeX;part.y=part.homeY;part.neckProgress=0;continue}if(part.timer>cfg.returnSeconds){let chaseT=clamp((cfg.chaseSeconds-(part.timer-cfg.returnSeconds))/cfg.chaseSeconds,0,1),dx=p.x-part.socketX,dy=p.y-part.socketY,d=Math.hypot(dx,dy)||1;if(d>cfg.maxReach){dx=dx/d*cfg.maxReach;dy=dy/d*cfg.maxReach}part.targetX=part.socketX+dx;part.targetY=part.socketY+dy;let ease=chaseT*chaseT*(3-2*chaseT),desiredX=part.homeX+(part.targetX-part.homeX)*ease,desiredY=part.homeY+(part.targetY-part.homeY)*ease;part.x+=((desiredX-part.x)*clamp(dt*13,0,1));part.y+=((desiredY-part.y)*clamp(dt*13,0,1));part.neckProgress=chaseT;part.returnStarted=false}else{if(!part.returnStarted){part.returnStarted=true;part.returnX=part.x;part.returnY=part.y}let returnT=clamp(1-part.timer/cfg.returnSeconds,0,1),ease=returnT*returnT*(3-2*returnT);part.x=part.returnX+(part.homeX-part.returnX)*ease;part.y=part.returnY+(part.homeY-part.returnY)*ease;part.neckProgress=1-returnT}}}
const bossUpdateBase=bossUpdate;bossUpdate=function(dt){if(boss&&boss.art==='demon'&&boss.mode!=='intro')demonPartsUpdate(dt);demonRetargetParts(dt);let out=bossUpdateBase(dt);if(boss&&boss.art==='demon'){boss.x=boss.fixedX;boss.y=boss.fixedY}return out};
const shotsUpdateBase=shotsUpdate;shotsUpdate=function(dt){let b=boss;if(!b||b.art!=='demon'||!b.parts)return shotsUpdateBase(dt);for(let s of shots){if(s.team==='enemy'||s.life<=0)continue;for(let part of b.parts){if(part.alive&&hit(s,part,s.s+part.r)){part.hp-=s.d;s.life=0;part.hit=.12;burst(s.x,s.y,'#ffe077',6);sfx('hit');if(part.hp<=0)demonPartDown(b,part);break}}}let oldX=b.x;if(b.coreLocked)b.x=9999999;shotsUpdateBase(dt);b.x=oldX;for(let s of shots){if(s.team==='enemy'||s.life<=0)continue;for(let part of b.parts)if(part.alive&&hit(s,part,s.s+part.r)){part.hp-=s.d;s.life=0;part.hit=.12;burst(s.x,s.y,'#ffe077',6);if(part.hp<=0)demonPartDown(b,part);break}}};
function drawDemon(b){let frame=Math.floor(time*8)%6+1,coreState=b.mode==='laserchase'?'laser':b.mode==='intro'?'idle':'attack',coreSize=300,tilt=clamp(Math.atan2(p.y-b.y,p.x-b.x),-1,1)*.035,body=art['demon-body-sockets'],cfg=DEMON_NECK_TUNING;if(body&&body.complete&&body.naturalWidth){ctx.save();ctx.drawImage(body,b.x-camera-580,b.y-114,1160,210);ctx.restore()}for(let part of b.parts||[]){if(!part.alive)continue;let dx=part.x-part.socketX,dy=part.y-part.socketY,len=Math.hypot(dx,dy);if(len<1)continue;let ux=dx/len,uy=dy/len,endX=part.x-ux*part.r*cfg.headAttachInset,endY=part.y-uy*part.r*cfg.headAttachInset,sx=endX-part.socketX,sy=endY-part.socketY,pathLen=Math.hypot(sx,sy);if(pathLen<1)continue;let nx=-sy/pathLen,ny=sx/pathLen,bend=cfg.curveBend*(.3+(part.neckProgress||0)*.9)*Math.sin(time*cfg.curveSpeed+(part.phase||0)),mx=(part.socketX+endX)/2+nx*bend,my=(part.socketY+endY)/2+ny*bend,tex=art['demon-neck-'+(part.mode==='idle'?'idle':'expand')+'-'+frame];if(!tex||!tex.complete||!tex.naturalWidth)continue;let point=t=>{let u=1-t;return{x:u*u*part.socketX+2*u*t*mx+t*t*endX,y:u*u*part.socketY+2*u*t*my+t*t*endY}},steps=Math.max(4,Math.ceil(pathLen/cfg.segmentSpacing));/* Each 58px PNG overlaps the next along this curve, so the socket and head stay joined during every frame. */for(let i=0;i<=steps;i++){let t=i/steps,q=point(t),q2=point(Math.min(1,t+.05)),a=Math.atan2(q2.y-q.y,q2.x-q.x);ctx.save();ctx.translate(q.x-camera,q.y);ctx.rotate(a-Math.PI/2);ctx.drawImage(tex,-19,-29,38,58);ctx.restore()}}img('demon-core-'+coreState+'-'+frame,b.x,b.y,coreSize,tilt,b.hit?.5:1);let k=coreSize/128,gx=clamp((p.x-b.x)/430,-1,1),gy=clamp((p.y-b.y)/260,-1,1);ctx.save();ctx.translate(b.x-camera,b.y);ctx.rotate(tilt);ctx.fillStyle='#17040b';for(let ex of [-15,15]){ctx.beginPath();ctx.arc((ex+gx*2.5)*k,(-13+gy*1.5)*k,3*k,0,7);ctx.fill()}ctx.restore();for(let part of b.parts||[]){if(!part.alive)continue;let state=part.mode==='lunge'||part.mode==='spit'||part.mode==='sweep'||part.mode==='rake'||part.mode==='burst'?'attack':'idle';img('demon-flank-'+state+'-'+frame,part.x,part.y,part.r*2.05,part.side==='left'?.08:-.08,part.hit?.5:1);bar(part.x-camera-part.r,part.y-part.r-18,part.r*2,7,part.hp,part.max,part.side==='left'?'#ff6b52':'#ff9f4d',part.side.toUpperCase()+' HEAD')}}

// ============================================================
// NO MERCY SHIELD — a rotating deflector shield the hero carries only on
// No Mercy difficulty. It absorbs incoming damage up to SHIELD_MAX total,
// then goes fully offline for SHIELD_COOLDOWN seconds before recharging
// back to full. Tune both constants below to taste.
// ============================================================
const SHIELD_MAX=60,SHIELD_COOLDOWN=8;
let shield={hp:SHIELD_MAX,cooldown:0};
function shieldReset(){shield.hp=SHIELD_MAX;shield.cooldown=0}
function shieldUpdate(dt){if(difficultyKey!=='noMercy')return;if(shield.cooldown>0){shield.cooldown=Math.max(0,shield.cooldown-dt);if(shield.cooldown<=0){shield.hp=SHIELD_MAX;burst(p.x,p.y,'#7be3ff',18);say('SHIELD RESTORED',1.4);sfx('pickup')}}}
const hurtBase=hurt;
hurt=function(n){
  if(p.dashTime>0)return;
  if(difficultyKey==='noMercy'&&shield.cooldown<=0&&shield.hp>0){
    let absorbed=Math.min(shield.hp,n),remain=n-absorbed;
    shield.hp-=absorbed;
    burst(p.x,p.y,'#7be3ff',8);
    sfx('dash');
    if(shield.hp<=0){shield.cooldown=SHIELD_COOLDOWN;shake=Math.max(shake,.3);say('SHIELD DOWN — BRACE YOURSELF',1.8);sfx('warn')}
    if(remain>0)hurtBase(remain);
    return;
  }
  hurtBase(n);
};
function drawShield(){
  if(difficultyKey!=='noMercy'||!running)return;
  let sx=p.x-camera,sy=p.y;
  if(shield.cooldown>0){
    ctx.save();ctx.globalAlpha=.3;ctx.strokeStyle='#5a6b78';ctx.lineWidth=3;ctx.setLineDash([6,8]);ctx.beginPath();ctx.arc(sx,sy,50,0,7);ctx.stroke();ctx.setLineDash([]);ctx.restore();
    return;
  }
  if(shield.hp<=0)return;
  let img_=art.shield,ang=time*2.6;
  ctx.save();ctx.translate(sx,sy);ctx.rotate(ang);ctx.globalAlpha=.55+.4*(shield.hp/SHIELD_MAX);
  if(img_&&img_.complete&&img_.naturalWidth)ctx.drawImage(img_,-48,-48,96,96);
  else{ctx.strokeStyle='#7be3ff';ctx.lineWidth=4;ctx.beginPath();ctx.arc(0,0,46,0,7);ctx.stroke()}
  ctx.restore();
  ctx.save();ctx.strokeStyle='#bdf3ffcc';ctx.lineWidth=3;ctx.beginPath();ctx.arc(sx,sy,53,-Math.PI/2,-Math.PI/2+Math.PI*2*clamp(shield.hp/SHIELD_MAX,0,1));ctx.stroke();ctx.restore();
}

// ============================================================
// AUTO ROCKET POD — a player buff pickup ('rocketpack'). Once acquired the
// hero automatically fires a slow, homing rocket at the nearest live threat
// on a fixed cooldown, entirely independent of the hero's held gun (pistol/
// shotgun/machine gun keep firing normally with the mouse/fire button).
// A separate 'rocketrapid' pickup temporarily shortens the rocket cooldown.
// Tune the constants below to rebalance.
// ============================================================
const ROCKET_BASE_CD=1.5,ROCKET_RAPID_CD=.6,ROCKET_RAPID_DURATION=8,ROCKET_DAMAGE=7,ROCKET_SPEED=460,ROCKET_TURN_RATE=3.4,ROCKET_RANGE=980;
function homingCandidates(){
  let list=enemies.slice();
  if(boss){
    if(boss.art==='demon'){
      if(boss.parts)for(let part of boss.parts)if(part.alive)list.push(part);
      if(!boss.coreLocked)list.push(boss);
    }else list.push(boss);
  }
  return list;
}
function findHomingTarget(x,y){
  let best=null,bd=ROCKET_RANGE;
  for(let t of homingCandidates()){let d=dist({x,y},t);if(d<bd){bd=d;best=t}}
  return best;
}
function targetAlive(t){
  if(!t)return false;
  if(t===boss)return!!(boss&&boss.hp>0);
  if(boss&&boss.parts&&boss.parts.includes(t))return t.alive&&t.hp>0;
  return enemies.includes(t)&&t.hp>0;
}
function playerRocketUpdate(dt){
  if(!p.hasRocketPack||phase==='rescue'||phase==='credits'||phase==='actClear'||phase==='demonIntro')return;
  p.rocketRapid=Math.max(0,p.rocketRapid-dt);
  p.rocketCd=Math.max(0,p.rocketCd-dt);
  if(p.rocketCd>0)return;
  let target=findHomingTarget(p.x,p.y);
  if(!target)return;
  let a=Math.atan2(target.y-p.y,target.x-p.x);
  if(shots.length<220){
    shots.push({x:p.x+Math.cos(a)*22,y:p.y+Math.sin(a)*22,vx:Math.cos(a)*ROCKET_SPEED,vy:Math.sin(a)*ROCKET_SPEED,team:'player',d:ROCKET_DAMAGE,s:12,life:3.4,a,kind:'rocket',homing:true,target,turnRate:ROCKET_TURN_RATE});
    burst(p.x+Math.cos(a)*26,p.y+Math.sin(a)*26,'#ffb15c',5);
    sfx('fire');
  }
  p.rocketCd=p.rocketRapid>0?ROCKET_RAPID_CD:ROCKET_BASE_CD;
}
const shotsUpdateHoming=shotsUpdate;
shotsUpdate=function(dt){
  for(let s of shots){
    if(!s.homing)continue;
    if(!targetAlive(s.target))s.target=findHomingTarget(s.x,s.y);
    if(s.target){
      let desired=Math.atan2(s.target.y-s.y,s.target.x-s.x),cur=Math.atan2(s.vy,s.vx),diff=((desired-cur+Math.PI*3)%(Math.PI*2))-Math.PI,turn=clamp(diff,-s.turnRate*dt,s.turnRate*dt),newA=cur+turn,speed=Math.hypot(s.vx,s.vy)||ROCKET_SPEED;
      s.vx=Math.cos(newA)*speed;s.vy=Math.sin(newA)*speed;s.a=newA;
    }
  }
  shotsUpdateHoming(dt);
};

// Wire the new systems into the existing update/draw loops without touching
// their dense bodies — same override pattern already used elsewhere in this
// file (see drawSceneBase / respawnAtCheckpointBase above).
const updateWithExtras=update;
update=function(dt){
  updateWithExtras(dt);
  if(running){shieldUpdate(dt);playerRocketUpdate(dt)}
};
const drawWithExtras=draw;
draw=function(){
  drawWithExtras();
  drawShield();
};

// ============================================================
// HERO 2.5D PRESENTATION
// The gameplay remains a side-on canvas, but the hero now has a contact
// shadow, height/lift, squash-and-stretch, and dash afterimages. The PNG
// frame names below are the editable art contract for future replacements.
// ============================================================
function heroAnimationStep(dt){
  HERO_ANIM.moving=!!(keys.d||keys.ArrowRight||keys.a||keys.ArrowLeft||keys.w||keys.ArrowUp||keys.s||keys.ArrowDown);
  HERO_ANIM.fire=Math.max(0,HERO_ANIM.fire-dt);
  HERO_ANIM.hurt=Math.max(0,HERO_ANIM.hurt-dt);
  // Walking faces the travel direction; aiming/firing and sword swings lock to
  // the attack direction. This is what selects the true back/side/front PNG.
  HERO_ANIM.faceAngle=p.swordAnim>0?p.swordAngle:(mouse.down||!HERO_ANIM.moving?p.angle:Math.atan2(p.ly,p.lx));
  HERO_ANIM.direction=HERO_DIRECTIONS[(Math.round(HERO_ANIM.faceAngle/(Math.PI/4))+8)%8];
  let next=HERO_ANIM.hurt>0?'hurt':p.dashTime>0?'dash':p.swordAnim>0?'sword':HERO_ANIM.fire>0?'fire':HERO_ANIM.moving?'run':'idle';
  if(next!==HERO_ANIM.state){HERO_ANIM.state=next;HERO_ANIM.t=0}else HERO_ANIM.t+=dt;
}
function heroFrame(){
  let state=HERO_ANIM.state,direction=HERO_ANIM.direction,weapon=p.weapon==='sword'?'sword':'gun';
  if(state==='sword'){let combo=clamp(p.swordCombo||1,1,3),progress=clamp(1-(p.swordAnim||0)/swordDuration(),0,.999),frame=Math.floor(progress*5)+1;return {state,direction,combo,frame,img:art[`hero-sword-${direction}-combo-${combo}-${frame}`]||art['hero-idle-1']}}
  let motion=state==='run'?'run':'idle',count=motion==='run'?4:1,frame=Math.floor(HERO_ANIM.t*(HERO_FRAME_RATE[state]||10))%count+1;
  return {state,direction,combo:0,frame,img:art[`hero-${weapon}-${direction}-${motion}-${frame}`]||art['hero-idle-1']};
}
function paintHeroSprite(image,x,y,scaleX,scaleY,alpha,angle){
  ctx.save();ctx.translate(x-camera,y);ctx.rotate(angle);ctx.scale(scaleX,scaleY);ctx.globalAlpha=alpha;
  if(image&&image.complete&&image.naturalWidth)ctx.drawImage(image,-32,-32,64,64);
  else{ctx.fillStyle='#ffe17a';ctx.beginPath();ctx.arc(0,0,22,0,7);ctx.fill()}
  ctx.restore();
}
function swordSpriteForFrame(frame){return art[frame.state==='sword'?`sword-swing-${frame.direction}-combo-${frame.combo}-${frame.frame}`:`sword-rest-${frame.direction}`]}
function paintHeavySword(image,x,y,size,alpha=1,frame=null){
  ctx.save();ctx.translate(x-camera,y);ctx.globalAlpha=alpha;
  if(image&&image.complete&&image.naturalWidth)ctx.drawImage(image,-size/2,-size/2,size,size);
  // Do not leave a blank hand while a specific attack frame is still loading.
  // This fallback follows the active combo pose, so it is only visually
  // different from a hand-drawn PNG, never a disappearing attack sword.
  else{let swing=frame?.state==='sword'?SWORD_FRAME_PATHS[frame.combo-1][frame.frame-1]:-1.18;ctx.rotate(p.swordAngle+swing);ctx.fillStyle='#111724';ctx.fillRect(0,-10,size*.48,20);ctx.fillStyle='#dce8ef';ctx.fillRect(8,-6,size*.43,12);ctx.fillStyle='#ffcf63';ctx.fillRect(-8,-20,7,40)}
  ctx.restore();
}
function drawHero(){
  let a=heroFrame(),s=a.state,phaseT=HERO_ANIM.t*(HERO_FRAME_RATE[s]||10),bob=s==='run'?Math.sin(phaseT*.7)*2:0,lift=s==='dash'?12:s==='run'?Math.max(0,Math.sin(phaseT*.7))*4:0;
  let sx=p.x-camera,sy=p.y;
  // This ellipse is the ground contact point. It should remain on the floor
  // while the sprite lifts, which is the key depth cue for the 2.5D treatment.
  ctx.save();ctx.globalAlpha=.22+(s==='dash'?.08:0);ctx.fillStyle='#050813';ctx.beginPath();ctx.ellipse(sx,p.y+25,30+(s==='dash'?10:0),8,0,0,7);ctx.fill();ctx.globalAlpha=.5;ctx.strokeStyle='#55e6ed';ctx.lineWidth=1.5;ctx.beginPath();ctx.ellipse(sx,p.y+24,22+(s==='dash'?9:0),4,0,0,7);ctx.stroke();ctx.restore();
  let scaleX=s==='dash'?1.16:s==='run'?1.04:s==='sword'?1.08:1,scaleY=s==='dash'?.82:s==='run'?.96:s==='sword'?.98:1;
  let swordImage=p.weapon==='sword'?swordSpriteForFrame(a):null;
  // The giant sword is its own transparent image layer.  Up-facing rest
  // poses put it behind the cloak; attacking poses put it in front so every
  // combo frame reads clearly. Change these two sizes to tune weapon scale.
  let swordSize=s==='sword'?260:210,swordBehind=s!=='sword'&&a.direction.startsWith('up');
  // Direction is baked into the selected PNG. Do not rotate it here, or an
  // upward back sprite would incorrectly spin into a front-facing pose.
  let angle=0;
  if(swordBehind)paintHeavySword(swordImage||null,p.x,p.y-lift+bob,swordSize,1,a);
  if(s==='dash')for(let i=3;i>0;i--)paintHeroSprite(a.img,p.x-p.dx*i*9,p.y-lift+p.dy*i*4,scaleX,scaleY,.08*i,angle);
  paintHeroSprite(a.img,p.x,p.y-lift+bob,scaleX,scaleY,1,angle);
  // Always call the painter while the sword is equipped. It supplies the
  // directional fallback when an individual editable PNG is absent/loading.
  if(p.weapon==='sword'&&!swordBehind)paintHeavySword(swordImage||null,p.x,p.y-lift+bob,swordSize,1,a);
  // A slim cyan rim makes the hero read against the dark map without
  // baking a background into the replaceable sprite PNG.
  if(s==='dash'||s==='hurt'){ctx.save();ctx.globalAlpha=s==='dash'?.55:.38;ctx.strokeStyle=s==='dash'?'#75f4ff':'#ff6678';ctx.lineWidth=2;ctx.beginPath();ctx.arc(sx,sy-lift+bob,34,0,7);ctx.stroke();ctx.restore()}
}
const imgBeforeHero=img;
img=function(n,x,y,s,a=0,alpha=1){if(n==='hero'){drawHero();return}return imgBeforeHero(n,x,y,s,a,alpha)};
const updateWithHero=update;
update=function(dt){updateWithHero(dt);heroAnimationStep(dt)};
const resetWithHero=reset;
reset=function(...args){resetWithHero(...args);resetHeroAnimation()};
const respawnWithHero=respawnAtCheckpoint;
respawnAtCheckpoint=function(){respawnWithHero();resetHeroAnimation()};

// ============================================================
// HEAVY SWORD — switch with Q (cycle) or 4 (direct select). The combo uses
// three committed swings: wider and stronger on the final blow. Tuning these
// numbers changes melee feel without touching the directional PNG artwork.
// ============================================================
const SWORD_COMBOS=[
  // Cut 1 reads left-to-right; cut 2 deliberately reverses it. The last
  // attack is a narrow, fast forward burst: a readable DMC-inspired
  // stinger/dash finish rather than another wide crescent. Durations are
  // deliberately snappy, but still leave five readable frames.
  {damage:3,range:96,arc:1.42,cooldown:.14,lunge:10,duration:.26},
  {damage:4,range:110,arc:1.68,cooldown:.16,lunge:16,duration:.28},
  {damage:8,range:44,arc:.42,cooldown:.28,lunge:10,duration:.38,pierceSpeed:980,pierceTime:.22,pierceRadius:44}
];
// SIEGEBREAKER — melee must pay off for standing inside boss threat range.
// This multiplier applies only to a boss/core, never ordinary enemies.
const SWORD_BOSS_DAMAGE_MULTIPLIER=2.5;
const SWORD_FINISHER_STAGGER=.55;
// Every normal sword hit kicks targets away from the hero. Bosses use a
// smaller shove so they stay in the arena, while minions get a clearer launch.
const SWORD_MINION_KNOCKBACK=190,SWORD_BOSS_KNOCKBACK=48,SWORD_KNOCKBACK_TIME=.14;
function currentSwordConfig(){let cfg={...SWORD_COMBOS[(p.swordCombo||1)-1]};if(p.hasCleave){cfg.damage+=2;cfg.range+=28;cfg.arc+=.34}return cfg}
function swordDuration(){return SWORD_COMBOS[(p.swordCombo||1)-1].duration||.4}
const swordAngleDelta=(a,b)=>Math.atan2(Math.sin(a-b),Math.cos(a-b));
function swordCanReach(target,cfg){let dx=target.x-p.x,dy=target.y-p.y,d=Math.hypot(dx,dy),radius=target.r||18;if(d>cfg.range+radius)return false;return Math.abs(swordAngleDelta(Math.atan2(dy,dx),p.swordAngle))<=cfg.arc*.5}
let bloodStains=[],cleaveEchoes=[];
// Short-lived ghosts make the finisher read as a committed dash instead of a
// teleport. Increase lifetime or spacing here for a longer trail.
let swordDashGhosts=[],swordParryFlashes=[];
function applySwordKnockback(target,angle,isBoss=false){let force=isBoss?SWORD_BOSS_KNOCKBACK:SWORD_MINION_KNOCKBACK;target.impactPause=Math.max(target.impactPause||0,isBoss?.045:.025);target.impactTime=Math.max(target.impactTime||0,SWORD_KNOCKBACK_TIME);target.impactVX=Math.cos(angle)*force;target.impactVY=Math.sin(angle)*force}
function bloodBurst(x,y,count=8){
  // Gore lands as persistent floor stains; only the airborne droplets expire.
  for(let i=0;i<count&&particles.length<340;i++){let a=Math.random()*6.28,s=45+Math.random()*170;particles.push({x,y,vx:Math.cos(a)*s,vy:Math.sin(a)*s-35,life:.28+Math.random()*.5,c:Math.random()<.45?'#ff4d5d':'#a5122e',size:2+Math.random()*4})}
  for(let i=0;i<Math.ceil(count*.6);i++){let a=Math.random()*6.28,d=Math.random()*22;bloodStains.push({x:x+Math.cos(a)*d,y:y+12+Math.sin(a)*d*.38,r:2+Math.random()*5,c:Math.random()<.32?'#ef4054':'#7b1024'});if(bloodStains.length>230)bloodStains.shift()}
}
function swordDamage(target,damage,isBoss=false){
  // Normal minions retain their original sword damage. The true boss/core
  // takes Siegebreaker damage; only the third hit briefly staggers it.
  let bossCore=isBoss&&target===boss,actualDamage=bossCore?damage*SWORD_BOSS_DAMAGE_MULTIPLIER:damage;if(bossCore&&boss.art==='adapted')actualDamage*=adaptedDamageScale('heavyMelee');
  target.hp-=actualDamage;target.hit=.18;applySwordKnockback(target,p.swordAngle,isBoss);burst(target.x,target.y,isBoss?'#ffe177':'#72e8f2',isBoss?8:6);bloodBurst(target.x,target.y,target.hp<=0?16:7);
  if(bossCore){let stagger=p.swordCombo===3?SWORD_FINISHER_STAGGER:.10;boss.swordStagger=Math.max(boss.swordStagger||0,stagger);if(p.swordCombo===3){say('SIEGEBREAKER — BOSS STAGGERED',.8);burst(target.x,target.y,'#fff0a0',22)}}
  if(p.hasLifesteal)p.hp=Math.min(p.maxHp,p.hp+actualDamage*LIFESTEAL_PCT);sfx('hit')
}
function swordTargets(cfg,damage,hitSet=null,pierceRadius=0){
  let struck=[],canHit=target=>pierceRadius?Math.hypot(target.x-p.x,target.y-p.y)<=pierceRadius+(target.r||18):swordCanReach(target,cfg);
  let hit=(target,isBoss=false,onDown=null)=>{if(target.hp<=0||hitSet&&hitSet.has(target)||!canHit(target))return;if(hitSet)hitSet.add(target);swordDamage(target,damage,isBoss);struck.push(target);if(target.hp<=0&&onDown)onDown()};
  for(let enemy of enemies)hit(enemy);
  if(boss){
    if(boss.art==='demon'&&boss.parts){for(let part of boss.parts)if(part.alive)hit(part,true,()=>demonPartDown(boss,part));if(!boss.coreLocked)hit(boss,true,()=>bossDown())}
    else hit(boss,true,()=>bossDown());
  }
  return struck;
}
function startCleaveEcho(cfg,damage,alreadyHit){
  if(!p.hasCleave||!alreadyHit||!alreadyHit.length)return;
  // SVEN-STYLE CLEAVE: three heavy weapon echoes follow the initial target
  // line. They are slower than the swing, hit only secondary victims, and
  // cripple those victims briefly. Tune the values here to change the passive.
  cleaveEchoes.push({x:p.x,y:p.y,a:p.swordAngle,delay:.12,age:0,duration:.78,range:Math.max(190,cfg.range*1.95),arc:Math.min(2.05,cfg.arc+.54),damage:Math.max(1,Math.ceil(damage*.66)),hits:new Set(alreadyHit)});
  if(cleaveEchoes.length>6)cleaveEchoes.shift();
}
function swordAttack(queueInput=false){
  // A second click queues a follow-up, while a lone click never does. Held
  // FIRE opts into queueing only after a short hold, preventing accidental
  // reverse swings when a normal desktop click spans a render frame.
  if(p.swordPierce){if(queueInput)p.swordQueued=true;return}
  if(p.swordAnim>0){if(queueInput)p.swordQueued=true;return}
  if(p.fire>0)return;
  p.swordCombo=p.swordChain>0?(p.swordCombo%3)+1:1;
  let cfg=currentSwordConfig();
  adaptedRecord('heavyMelee');
  p.swordAngle=mouse.down?p.angle:(HERO_ANIM.moving?Math.atan2(p.ly,p.lx):p.angle);
  p.swordAnim=cfg.duration;p.swordChain=.58;p.swordQueued=false;p.fire=cfg.cooldown*(p.rapid>0?.72:1);
  if(cfg.lunge){p.x+=Math.cos(p.swordAngle)*cfg.lunge;p.y=clamp(p.y+Math.sin(p.swordAngle)*cfg.lunge,arenaWorldBounds().top,arenaWorldBounds().bottom);p.x=phase==='travel'?clamp(p.x,70,L-80):clamp(p.x,arenaWorldBounds().left,arenaWorldBounds().right)}
  let damage=cfg.damage*p.damage;
  if(p.swordCombo===3){p.swordPierce={time:cfg.pierceTime,total:cfg.pierceTime,cfg,damage,hits:new Set(),victims:[]};swordDashGhosts.push({x:p.x,y:p.y,a:p.swordAngle,life:.24});sfx('sword3')}
  else startCleaveEcho(cfg,damage,swordTargets(cfg,damage));
  burst(p.x+Math.cos(p.swordAngle)*40,p.y+Math.sin(p.swordAngle)*40,p.swordCombo===3?'#ffe08a':'#65e7f2',12);
  if(p.swordCombo<3)sfx(p.swordCombo===1?'sword1':'sword2');
}
const fireWithSword=fire;
fire=function(){if(p.weapon==='sword'){swordAttack(mouse.down&&performance.now()-mouse.pressedAt>=120);return}fireWithSword()};
function switchWeapon(){
  let set=['pistol'];if(p.hasShotgun)set.push('shotgun');if(p.hasMachineGun)set.push('machinegun');if(p.hasSword!==false)set.push('sword');
  let next=(set.indexOf(p.weapon)+1)%set.length;p.weapon=set[next];p.swordChain=0;p.swordAnim=0;say(p.weapon==='sword'?'HEAVY SWORD READY — THREE-HIT COMBO':'WEAPON: '+p.weapon.toUpperCase(),1.2);sfx('pickup');
}
const drawHeroWithSword=drawHero;
function drawBloodStains(){ctx.save();for(let stain of bloodStains){let x=stain.x-camera;if(x<-20||x>W+20)continue;ctx.globalAlpha=.82;ctx.fillStyle=stain.c;ctx.beginPath();ctx.ellipse(x,stain.y,stain.r*1.45,stain.r*.56,0,0,Math.PI*2);ctx.fill();ctx.globalAlpha=.48;ctx.beginPath();ctx.arc(x+stain.r*1.2,stain.y+1,Math.max(1,stain.r*.25),0,Math.PI*2);ctx.fill()}ctx.restore()}
function drawCleaveEchoes(){
  // Each echo is a ghost sword, not a generic crescent. Delayed ghosts make
  // the passive's slow secondary sweep readable in the middle of a mob.
  for(let echo of cleaveEchoes){if(echo.delay>0)continue;let t=echo.age/echo.duration,fade=Math.sin(Math.min(1,t)*Math.PI);ctx.save();ctx.translate(echo.x-camera,echo.y);ctx.rotate(echo.a);
    for(let ghost=2;ghost>=0;ghost--){let lag=ghost*.13,local=(echo.age-lag)/echo.duration;if(local<=0)continue;let head=Math.min(1,local)*echo.range,alpha=fade*(ghost===0?.78:.34);ctx.globalAlpha=alpha;ctx.fillStyle=ghost===0?'#ffe48b':'#4edff0';ctx.beginPath();ctx.moveTo(head+20,0);ctx.lineTo(head-10,-9);ctx.lineTo(head-48,-7);ctx.lineTo(head-56,0);ctx.lineTo(head-48,7);ctx.lineTo(head-10,9);ctx.closePath();ctx.fill();ctx.fillStyle='#fff5cf';ctx.globalAlpha=alpha*.8;ctx.fillRect(head-35,-2,43,4);ctx.fillStyle='#e64d58';ctx.globalAlpha=alpha;ctx.fillRect(head-41,-13,5,26)}
  ctx.restore()}
}
function drawSwordDashGhosts(){
  // Finisher trail: several translucent weapon silhouettes lag behind the
  // forward burst, so the dash has a clear start, travel, and impact beat.
  for(let ghost of swordDashGhosts){let alpha=Math.max(0,ghost.life/.24)*.34;ctx.save();ctx.translate(ghost.x-camera,ghost.y);ctx.rotate(ghost.a);ctx.globalAlpha=alpha;ctx.strokeStyle='#ffe9a1';ctx.lineWidth=10;ctx.beginPath();ctx.moveTo(14,0);ctx.lineTo(78,0);ctx.stroke();ctx.strokeStyle='#6ee8f4';ctx.lineWidth=3;ctx.beginPath();ctx.moveTo(16,-8);ctx.lineTo(82,-8);ctx.stroke();ctx.restore()}
  for(let flash of swordParryFlashes){let alpha=Math.max(0,flash.life/.16);ctx.save();ctx.translate(flash.x-camera,flash.y);ctx.rotate(flash.a);ctx.globalAlpha=alpha*.8;ctx.strokeStyle='#fff0a0';ctx.lineWidth=4;ctx.beginPath();ctx.arc(0,0,42,-.9,.9);ctx.stroke();ctx.restore()}
}
drawHero=function(){drawBloodStains();drawCleaveEchoes();drawSwordDashGhosts();drawHeroWithSword()};
const updateWithSword=update;
update=function(dt){
  p.swordAnim=Math.max(0,(p.swordAnim||0)-dt);p.swordChain=Math.max(0,(p.swordChain||0)-dt);
  for(let ghost of swordDashGhosts)ghost.life-=dt;swordDashGhosts=swordDashGhosts.filter(g=>g.life>0);
  for(let flash of swordParryFlashes)flash.life-=dt;swordParryFlashes=swordParryFlashes.filter(f=>f.life>0);
  updateWithSword(dt);
  // A quick second/third mouse click is consumed only after the active cut
  // ends, so tap combos and held FIRE both show every full sword frame.
  if(!p.swordAnim&&!p.swordPierce&&p.swordQueued)swordAttack();
};
const resetWithSword=reset;
reset=function(...args){resetWithSword(...args);Object.assign(p,{hasSword:true,hasCleave:false,swordCombo:0,swordChain:0,swordAnim:0,swordAngle:0,swordPierce:null,swordQueued:false});bloodStains=[];cleaveEchoes=[];swordDashGhosts=[];swordParryFlashes=[]};
const respawnWithSword=respawnAtCheckpoint;
respawnAtCheckpoint=function(){respawnWithSword();Object.assign(p,{hasSword:true,hasCleave:p.hasCleave,swordCombo:0,swordChain:0,swordAnim:0,swordAngle:0,swordPierce:null,swordQueued:false});bloodStains=[];cleaveEchoes=[];swordDashGhosts=[];swordParryFlashes=[]};
document.addEventListener('keydown',e=>{if(e.key==='q'||e.key==='Q'){e.preventDefault();if(running&&!paused)switchWeapon()}if(e.key==='4'&&running&&!paused&&p.weapon!=='shadow'){p.weapon='sword';p.swordChain=0;say('HEAVY SWORD READY — THREE-HIT COMBO',1.2)}});
let weaponBtn=document.querySelector('#weaponButton');
weaponBtn.addEventListener('pointerdown',e=>{e.preventDefault();e.stopPropagation();if(running&&!paused)switchWeapon()});
// Capture runs before the legacy canvas handler below. A normal click starts
// one cut; another click while cutting queues exactly one follow-up. Holding
// the button uses the 120ms threshold in fire() to opt into the same queue.
canvas.addEventListener('pointerdown',e=>{if(!running||paused||p.weapon!=='sword')return;aimAtPointer(e);p.angle=Math.atan2(mouse.y-p.y,mouse.x+camera-p.x);mouse.down=true;mouse.pressedAt=performance.now();swordAttack(true)},true);
const drawWithWeaponHint=draw;
draw=function(){
  drawWithWeaponHint();
  if(!running||phase==='credits'||phase==='actClear'||phase==='demonIntro')return;
  ctx.save();ctx.font='10px monospace';ctx.fillStyle=p.weapon==='sword'?'#ffe48b':'#9eeaf0';ctx.textAlign='left';
  ctx.fillText(p.weapon==='sword'?`HEAVY SWORD • COMBO ${p.swordCombo||0}/3 • [Q / 4] SWITCH`:'[Q / 4] SWITCH TO HEAVY SWORD',300,H-57);ctx.restore();
  for(let q of pickups)if(q.type==='cleave')img('sword',q.x,q.y,54,Math.sin(time*3)*.12);
};
// Blood is a visual-only response layered on top of the existing hit logic,
// so it also covers ally bullets and Demon Lord flank hits without changing HP.
const shotsWithBlood=shotsUpdate;
shotsUpdate=function(dt){
  let tracked=enemies.slice(),trackedParts=boss&&boss.parts?boss.parts.slice():[],trackedBoss=boss;
  let before=new Map(tracked.map(e=>[e,e.hp]));for(let part of trackedParts)before.set(part,part.hp);if(trackedBoss)before.set(trackedBoss,trackedBoss.hp);
  shotsWithBlood(dt);
  for(let [target,hp] of before){if(target.hp<hp)bloodBurst(target.x,target.y,target.hp<=0?16:7)}
};
const pickupWithCleave=pickupUpdate;
pickupUpdate=function(){
  for(let q of pickups)if(q.type==='cleave'&&dist(q,p)<45&&!p.hasCleave){p.hasCleave=true;say('CLEAVE SIGIL ACQUIRED — ECHOES CLEAVE BEYOND THE HIT',2.8);burst(q.x,q.y,'#ffe58b',18)}
  pickupWithCleave();
  if(phase==='travel'&&act===1&&!p.hasCleave&&p.x>1080&&!pickups.some(q=>q.type==='cleave'))pickups.push({x:1180,y:250,type:'cleave'});
};

// ============================================================
// SWORD FINISHER + CLEAVE ECHO
// The third hit travels along `p.swordAngle` as a straight thrust.  Its
// speed/time are in SWORD_COMBOS above.  Cleave echoes deliberately expand
// more slowly than the swing, giving the player a readable secondary hit.
// ============================================================
function swordPierceUpdate(dt){
  let pierce=p.swordPierce;if(!pierce)return;
  let travel=Math.min(dt,pierce.time)*pierce.cfg.pierceSpeed;
  p.x+=Math.cos(p.swordAngle)*travel;
  p.y=clamp(p.y+Math.sin(p.swordAngle)*travel,arenaWorldBounds().top,arenaWorldBounds().bottom);
  p.x=phase==='travel'?clamp(p.x,70,L-80):clamp(p.x,arenaWorldBounds().left,arenaWorldBounds().right);
  swordDashGhosts.push({x:p.x,y:p.y,a:p.swordAngle,life:.24});if(swordDashGhosts.length>8)swordDashGhosts.shift();
  for(let target of swordTargets(pierce.cfg,pierce.damage,pierce.hits,pierce.cfg.pierceRadius))pierce.victims.push(target);
  pierce.time-=dt;
  if(pierce.time<=0){startCleaveEcho(pierce.cfg,pierce.damage,pierce.victims);p.swordPierce=null;burst(p.x,p.y,'#ffe08a',18)}
}
function echoCanHit(target,echo,inner,outer){
  let dx=target.x-echo.x,dy=target.y-echo.y,d=Math.hypot(dx,dy),r=target.r||18;
  if(d>outer+r||d<inner-r)return false;
  return Math.abs(swordAngleDelta(Math.atan2(dy,dx),echo.a))<=echo.arc*.5;
}
function echoDamageTarget(target,echo,isBoss=false,onDown=null){
  if(target.hp<=0||echo.hits.has(target))return;
  swordDamage(target,echo.damage,isBoss);echo.hits.add(target);target.cleaveSlow=isBoss?0:Math.max(target.cleaveSlow||0,.9);
  burst(target.x,target.y,'#ffd568',7);if(target.hp<=0&&onDown)onDown();
}
function cleaveEchoUpdate(dt){
  for(let i=cleaveEchoes.length-1;i>=0;i--){
    let echo=cleaveEchoes[i];if(echo.delay>0){echo.delay-=dt;continue}
    let before=echo.age/echo.duration;echo.age+=dt;let after=Math.min(1,echo.age/echo.duration),inner=Math.max(0,echo.range*before-18),outer=echo.range*after+18;
    for(let enemy of enemies)if(echoCanHit(enemy,echo,inner,outer))echoDamageTarget(enemy,echo);
    if(boss){
      if(boss.art==='demon'&&boss.parts){for(let part of boss.parts)if(part.alive&&echoCanHit(part,echo,inner,outer))echoDamageTarget(part,echo,true,()=>demonPartDown(boss,part));if(!boss.coreLocked&&echoCanHit(boss,echo,inner,outer))echoDamageTarget(boss,echo,true,()=>bossDown())}
      else if(echoCanHit(boss,echo,inner,outer))echoDamageTarget(boss,echo,true,()=>bossDown());
    }
    if(echo.age>=echo.duration)cleaveEchoes.splice(i,1);
  }
}
const enemiesWithCleaveSlow=enemiesUpdate;
enemiesUpdate=function(dt){
  // Enemy speed is restored after the original movement code so the slow is
  // temporary and cannot permanently mutate a spawned enemy's base speed.
  let slowed=[];for(let enemy of enemies){enemy.cleaveSlow=Math.max(0,(enemy.cleaveSlow||0)-dt);enemy.dismantleSlow=Math.max(0,(enemy.dismantleSlow||0)-dt);let factor=enemy.cleaveSlow>0?.35:1;if(enemy.dismantleSlow>0)factor*=DISMANTLE_SLOW_FACTOR;if(factor<1){slowed.push([enemy,enemy.speed]);enemy.speed*=factor}}
  enemiesWithCleaveSlow(dt);for(let [enemy,speed] of slowed)enemy.speed=speed;
};
const updateWithCleaveEcho=update;
update=function(dt){updateWithCleaveEcho(dt);swordPierceUpdate(dt);cleaveEchoUpdate(dt)};

// A successful finisher opens a brief safe melee window. Existing bullets
// remain active, but the boss stops choosing or advancing its next pattern.
const bossUpdateWithSwordStagger=bossUpdate;
bossUpdate=function(dt){
  let slow=1;
  if(boss){boss.dismantleSlow=Math.max(0,(boss.dismantleSlow||0)-dt);if(boss.dismantleSlow>0)slow=DISMANTLE_SLOW_FACTOR}
  if(boss&&(boss.swordStagger||0)>0){boss.swordStagger=Math.max(0,boss.swordStagger-dt);boss.hit=Math.max(0,boss.hit-dt);return}
  bossUpdateWithSwordStagger(dt*slow);
};

// SWORD PARRY — active cuts deflect ordinary enemy bullets in a narrow,
// front-facing cone. It is deliberately not a full shield: shots from behind,
// rockets, and bullets outside the blade reach still punish the player.
// Tune `guardRadius` or `guardArc` here if you want a stricter/safer parry.
const SWORD_GUARD_RADIUS=104,SWORD_GUARD_ARC=.95;
function swordDeflectEnemyBullets(){
  if(p.weapon!=='sword'||!(p.swordAnim>0||p.swordPierce))return;
  let parried=0,guardAngle=p.swordAngle+Math.PI;
  for(let shot of shots){
    if(shot.team!=='enemy'||shot.kind==='rocket'||shot.life<=0)continue;
    let dx=shot.x-p.x,dy=shot.y-p.y,d=Math.hypot(dx,dy);if(d>SWORD_GUARD_RADIUS)continue;
    let incoming=Math.atan2(shot.vy,shot.vx),approaching=shot.vx*(-dx)+shot.vy*(-dy)>0;
    if(!approaching||Math.abs(swordAngleDelta(incoming,guardAngle))>SWORD_GUARD_ARC)continue;
    shot.team='player';shot.vx=-shot.vx;shot.vy=-shot.vy;shot.a=Math.atan2(shot.vy,shot.vx);shot.d=Math.max(1,(shot.d||1)*1.8);shot.life=Math.min(shot.life,1.35);parried++;swordParryFlashes.push({x:p.x,y:p.y,a:p.swordAngle,life:.16});burst(shot.x,shot.y,'#fff0a0',8);
  }
  if(parried)sfx('parry');
}
const shotsWithSwordGuard=shotsUpdate;
shotsUpdate=function(dt){swordDeflectEnemyBullets();shotsWithSwordGuard(dt)};

// ============================================================
// WEAPON ULTIMATES
// E (desktop) or the mobile ULT button spends the current weapon's ultimate.
// The developer switch (`?dev=1` or F9) makes it easy to preview every style:
// F6 = sword, F7 = shotgun, F8 = machine gun. These are original, readable
// homages rather than copied character assets or animation frames.
// ============================================================
// Dismantle tuning: lanes fill in at 10000x travel speed, while 260 cuts are
// staged across the ten-second storm. Lower the multiplier to reveal travel.
// Every impact also uses the reaction timings below: a tiny hit-stop plus a
// short recoil pulse makes both minions and bosses visibly acknowledge a cut.
const ULTIMATE_COOLDOWN=8,DISMANTLE_DURATION=10,DISMANTLE_SPEED_MULTIPLIER=10000,DISMANTLE_SLASH_COUNT=260;
// "Million-hit" power is applied as damage per legal target, not as a
// million simultaneous sprites. That keeps the effect instant and brutal
// without turning a phone into a million-object particle simulation.
const DISMANTLE_DAMAGE_MULTIPLIER=1000000,DISMANTLE_MINION_PAUSE=.075,DISMANTLE_BOSS_PAUSE=.14,DISMANTLE_MINION_RECOIL=165,DISMANTLE_BOSS_RECOIL=42,DISMANTLE_SLOW_FACTOR=.18,DISMANTLE_SLOW_DURATION=.22;
function ultimateLabel(weapon){return weapon==='sword'?'DISMANTLE STORM':weapon==='machinegun'?'GATLING ROCKET BLOOM':weapon==='shotgun'?'HELLBREAKER SCATTER':'DEAD-EYE VOLLEY'}
function ultimateAttack(force=false,weaponOverride=null){
  if(!running||paused||ultimate)return false;
  if(!force&&p.ultimateCooldown>0){say(`ULTIMATE RECHARGING — ${p.ultimateCooldown.toFixed(1)}s`,.8);return false}
  let weapon=weaponOverride||p.weapon;adaptedRecord(adaptedCategoryForWeapon(weapon),2);p.ultimateCooldown=force?0.5:ULTIMATE_COOLDOWN;
  if(weapon==='sword'){
    ultimate={kind:'dismantle',weapon,age:0,duration:DISMANTLE_DURATION,next:0,spawned:0,max:DISMANTLE_SLASH_COUNT,slashes:[],damage:.65,ox:p.x,oy:p.y,baseAngle:p.angle};
    p.swordAnim=0;p.swordPierce=null;p.swordQueued=false;say('DISMANTLE STORM — TEN SECONDS OF CUTS',1.1);sfx('ultimate');sfx('dismantle');
  }else{
    let kind=weapon==='machinegun'?'gatling':weapon==='shotgun'?'scatter':'volley';
    ultimate={kind,weapon,age:0,next:0,spawned:0,duration:kind==='gatling'?1.22:kind==='scatter'?.7:.82,max:kind==='gatling'?28:kind==='scatter'?36:18,originAngle:p.angle};
    say(`${ultimateLabel(weapon)} — HOLD THE LINE`,1.1);sfx(weapon==='machinegun'?'gatling':'ultimate');
  }
  return true;
}
function ultimateDismantleHit(target,slash,isBoss=false,onDown=null){
  if(!target||target.hp<=0||slash.hits.has(target))return;
  slash.hits.add(target);let amount=slash.damage*DISMANTLE_DAMAGE_MULTIPLIER*(isBoss?SWORD_BOSS_DAMAGE_MULTIPLIER:1);if(target===boss&&boss.art==='adapted'){amount=Math.min(amount,BOSS_HP.adapted*.012)*adaptedDamageScale('heavyMelee');adaptedRecord('heavyMelee',.15)}let dx=target.x-slash.x,dy=target.y-slash.y,d=Math.hypot(dx,dy)||1;
  target.hp-=amount;target.hit=Math.max(target.hit||0,.16);target.dismantleSlow=Math.max(target.dismantleSlow||0,DISMANTLE_SLOW_DURATION);target.impactPause=Math.max(target.impactPause||0,isBoss?DISMANTLE_BOSS_PAUSE:DISMANTLE_MINION_PAUSE);target.impactTime=Math.max(target.impactTime||0,isBoss?.18:.16);target.impactVX=(dx/d)*(isBoss?DISMANTLE_BOSS_RECOIL:DISMANTLE_MINION_RECOIL);target.impactVY=(dy/d)*(isBoss?DISMANTLE_BOSS_RECOIL:DISMANTLE_MINION_RECOIL);burst(target.x,target.y,isBoss?'#fff0b0':'#e56bff',isBoss?12:7);bloodBurst(target.x,target.y,target.hp<=0?14:5);
  if(isBoss){target.swordStagger=Math.max(target.swordStagger||0,DISMANTLE_BOSS_PAUSE);if(boss)boss.dismantleSlow=Math.max(boss.dismantleSlow||0,DISMANTLE_SLOW_DURATION);shake=Math.max(shake,.12)}
  if(target.hp<=0&&onDown)onDown();if(p.hasLifesteal)p.hp=Math.min(p.maxHp,p.hp+amount*LIFESTEAL_PCT);sfx('hit');
}
function dismantleSlashHits(slash,target){let dx=target.x-slash.x,dy=target.y-slash.y,along=dx*Math.cos(slash.a)+dy*Math.sin(slash.a),side=Math.abs(-dx*Math.sin(slash.a)+dy*Math.cos(slash.a));return along>-slash.length*.5-(target.r||18)&&along<slash.length*.5+(target.r||18)&&side<slash.width+(target.r||18)}
function ultimateDismantleUpdate(dt){
  let u=ultimate;u.age+=dt;
  // The storm is also crowd control: every live threat remains slowed while
  // cuts are being staged, even if a particular lane misses it this frame.
  for(let enemy of enemies)enemy.dismantleSlow=Math.max(enemy.dismantleSlow||0,DISMANTLE_SLOW_DURATION);
  if(boss){boss.dismantleSlow=Math.max(boss.dismantleSlow||0,DISMANTLE_SLOW_DURATION);for(let part of boss.parts||[])if(part.alive)part.dismantleSlow=Math.max(part.dismantleSlow||0,DISMANTLE_SLOW_DURATION)}
  while(u.spawned<u.max&&u.age>=u.next){let i=u.spawned++,a=i===0?u.baseAngle:u.baseAngle+i*(Math.PI*2/19)+(Math.random()-.5)*.18,x=i===0?u.ox:camera+44+Math.random()*(W-88),y=i===0?u.oy:(window.PRINCESS_TERRAIN?.viewY||0)+88+Math.random()*(H-176);u.slashes.push({x,y,a,length:980+Math.random()*430,width:7+Math.random()*7,age:0,life:.34,hits:new Set(),damage:u.damage});u.next+=u.duration/u.max;if(i%4===0)sfx('dismantle')}
  for(let i=u.slashes.length-1;i>=0;i--){let slash=u.slashes[i];slash.age+=dt;let active=slash.age<.13;if(active){for(let enemy of enemies)if(dismantleSlashHits(slash,enemy))ultimateDismantleHit(enemy,slash);if(boss){if(boss.art==='demon'&&boss.parts){for(let part of boss.parts)if(part.alive&&dismantleSlashHits(slash,part))ultimateDismantleHit(part,slash,true,()=>demonPartDown(boss,part));if(!boss.coreLocked&&dismantleSlashHits(slash,boss))ultimateDismantleHit(boss,slash,true,()=>bossDown())}else if(dismantleSlashHits(slash,boss))ultimateDismantleHit(boss,slash,true,()=>bossDown())}}if(slash.age>=slash.life)u.slashes.splice(i,1)}
  if(u.age>=u.duration&&!u.slashes.length)ultimate=null;
}
function ultimateProjectile(u,a,kind,damage,speed,radius,life){if(shots.length>=220)return;let target=kind==='rocket'?findHomingTarget(p.x,p.y):null;shots.push({x:p.x+Math.cos(a)*28,y:p.y+Math.sin(a)*28,vx:Math.cos(a)*speed,vy:Math.sin(a)*speed,team:'player',d:damage*p.damage,s:radius,life,a,kind,adaptCategory:adaptedCategoryForWeapon(u.weapon,kind),homing:!!target,target,turnRate:kind==='rocket'?5.6:0});}
function ultimateRangedUpdate(dt){
  let u=ultimate;u.age+=dt;let interval=u.duration/u.max;
  while(u.spawned<u.max&&u.age>=u.next){let i=u.spawned++,t=u.max<=1?0:i/(u.max-1),spread=u.kind==='gatling'?.82:u.kind==='scatter'?1.15:.48,a=p.angle+(Math.random()-.5)*spread;
    if(u.kind==='gatling')ultimateProjectile(u,a,'rocket',2.3,570,11,3.2);else if(u.kind==='scatter')ultimateProjectile(u,a,'bullet',1.35,760,5,1.8);else ultimateProjectile(u,a,'bullet',2.2,1040,4,1.5);
    if(i%3===0)burst(p.x+Math.cos(a)*30,p.y+Math.sin(a)*30,u.kind==='gatling'?'#ff9b5c':'#ffe69a',3);u.next+=interval;
  }
  if(u.age>=u.duration&&u.spawned>=u.max)ultimate=null;
}
function ultimateUpdate(dt){if(!ultimate)return;if(ultimate.kind==='dismantle')ultimateDismantleUpdate(dt);else ultimateRangedUpdate(dt)}
function drawUltimateFx(){
  if(!ultimate)return;ctx.save();ctx.beginPath();ctx.rect(0,70+(window.PRINCESS_TERRAIN?.viewY||0),W,H-144);ctx.clip();
  if(ultimate.kind==='dismantle')for(let slash of ultimate.slashes){let fade=Math.max(0,1-slash.age/slash.life),travel=clamp(slash.age/(.13/DISMANTLE_SPEED_MULTIPLIER),0,1),len=slash.length*travel;ctx.save();ctx.translate(slash.x-camera,slash.y);ctx.rotate(slash.a);let tex=art['sword-dismantle'];if(tex&&tex.complete&&tex.naturalWidth){let size=clamp(86+len*.08,96,166);ctx.globalAlpha=fade*.72;ctx.filter='invert(1)';ctx.drawImage(tex,-size/2,-size/2,size,size);ctx.filter='none'}ctx.globalAlpha=fade*.5;ctx.strokeStyle='#f6e5ff';ctx.lineWidth=5;ctx.beginPath();ctx.moveTo(-len*.5,0);ctx.lineTo(len*.5,0);ctx.stroke();ctx.globalAlpha=fade*.2;ctx.strokeStyle='#ff69d4';ctx.lineWidth=13;ctx.beginPath();ctx.moveTo(-len*.42,-4);ctx.lineTo(len*.42,-4);ctx.stroke();ctx.restore()}
  else{let pulse=.5+.5*Math.sin(time*28);ctx.save();ctx.translate(p.x-camera,p.y);ctx.globalAlpha=.22+pulse*.12;ctx.strokeStyle=ultimate.kind==='gatling'?'#ff9b5c':'#ffe69a';ctx.lineWidth=4;ctx.beginPath();ctx.arc(0,0,30+pulse*20,0,Math.PI*2);ctx.stroke();ctx.restore()}
  ctx.restore();
}
const updateWithUltimate=update;
update=function(dt){p.ultimateCooldown=Math.max(0,(p.ultimateCooldown||0)-dt);updateWithUltimate(dt);ultimateUpdate(dt)};
const drawWithUltimate=draw;
draw=function(){drawWithUltimate();if(!running||phase==='credits'||phase==='actClear'||phase==='demonIntro')return;drawUltimateFx();ctx.save();ctx.font='10px monospace';ctx.textAlign='left';ctx.fillStyle=p.ultimateCooldown>0?'#aaa4b2':'#ff9fe1';ctx.fillText(`ULT [E] • ${p.ultimateCooldown>0?`${p.ultimateCooldown.toFixed(1)}s`:'READY'}`,300,H-42);if(developerMode){ctx.fillStyle='#9fffee';ctx.fillText('DEV MODE • F6 SWORD  F7 SHOTGUN  F8 MACHINE',520,22)}ctx.restore()};
document.addEventListener('keydown',e=>{if(e.key==='F9'){developerMode=!developerMode;say(developerMode?'DEVELOPER MODE ON — ULTIMATE TEST KEYS ACTIVE':'DEVELOPER MODE OFF',1.4);return}if(!running||paused)return;if(e.key==='e'||e.key==='E'){e.preventDefault();ultimateAttack();return}if(!developerMode)return;if(e.key==='F6'){p.weapon='sword';ultimateAttack(true,'sword')}if(e.key==='F7'){p.weapon='shotgun';p.hasShotgun=true;ultimateAttack(true,'shotgun')}if(e.key==='F8'){p.weapon='machinegun';p.hasMachineGun=true;ultimateAttack(true,'machinegun')}});
let ultimateBtn=document.querySelector('#ultimateButton');if(ultimateBtn)ultimateBtn.addEventListener('pointerdown',e=>{e.preventDefault();e.stopPropagation();if(running&&!paused)ultimateAttack()});

// ACT I HORDE — the legacy travel loop keeps its 22-enemy safety cap. This
// wrapper counts every Act I spawn toward a 300-minion budget and feeds extra
// waves while the active field stays below 36, so the player gets the requested
// horde without asking the browser to simulate 300 enemies at once.
const enemyWithAct1HordeCount=enemy;
enemy=function(x,y,kind){if(act===0){if(act1Spawned>=ACT1_MINION_TOTAL)return;act1Spawned++}enemyWithAct1HordeCount(x,y,kind)};
const enterBossWithAct1HordeGate=enterBoss;
enterBoss=function(force=false){if(!force&&phase==='travel'&&act===0&&act1Spawned<ACT1_MINION_TOTAL)return;enterBossWithAct1HordeGate()};
const updateWithAct1Horde=update;
update=function(dt){
  updateWithAct1Horde(dt);
  if(!adaptedOnlyCampaign&&phase==='travel'&&act===0&&nextSpawn!==Infinity){while(act1Spawned<ACT1_MINION_TOTAL&&enemies.length<ACT1_ACTIVE_CAP&&act1HordeNextSpawn<p.x+790&&act1HordeNextSpawn<L-80){let types=['minion','crawler'];enemy(act1HordeNextSpawn,100+Math.random()*(H-200),types[Math.floor(Math.random()*types.length)]);act1HordeNextSpawn+=Math.max(3,(L-650)/(ACT1_MINION_TOTAL-1))}}
};
const drawWithAct1HordeHud=draw;
draw=function(){drawWithAct1HordeHud();if(adaptedOnlyCampaign||!running||phase!=='travel'||act!==0)return;ctx.save();ctx.font='10px monospace';ctx.textAlign='right';ctx.fillStyle='#ffb0c2';ctx.fillText(`ACT I HORDE ${act1Spawned}/${ACT1_MINION_TOTAL}`,W-25,22);ctx.restore()};

// Opening-boss hooks are appended after the legacy wrappers so all existing
// encounters retain their original update order.
const bossUpdateBeforeAdaptedOne=bossUpdate;
bossUpdate=function(dt){if(boss&&boss.art==='adapted'){adaptedBossUpdate(dt);return}bossUpdateBeforeAdaptedOne(dt)};
const shotsBeforeAdaptedOne=shotsUpdate;
shotsUpdate=function(dt){let scaled=[];if(boss&&boss.art==='adapted')for(let shot of shots)if(shot.team!=='enemy'){let category=shot.adaptCategory||adaptedCategoryForWeapon(shot.team==='ally'?'pistol':p.weapon,shot.kind),base=shot.d;if(shot.kind==='rocket'&&!shot.adaptSeen){adaptedRecord('explosive',.6);shot.adaptSeen=true}shot.d*=adaptedDamageScale(category,shot);scaled.push([shot,base])}shotsBeforeAdaptedOne(dt);for(let pair of scaled)pair[0].d=pair[1]};
const pickupBeforeAdaptedOne=pickupUpdate;
pickupUpdate=function(){let heals=pickups.filter(q=>q.type==='heal').length;pickupBeforeAdaptedOne();if(heals>pickups.filter(q=>q.type==='heal').length)adaptedRecord('healing')};

// Adapted One render hook: the legacy renderer has no `adapted` PNG entry, so
// suppress its fallback circle and paint the four-view transparent sheet here.
const imgBeforeAdaptedOne=img;
img=function(n,...args){if(n==='adapted')return;if(n==='boss-bullet')n='adapted-bullet';return imgBeforeAdaptedOne(n,...args)};
const drawBeforeAdaptedOne=draw;
draw=function(){drawBeforeAdaptedOne();if(!running)return;if(boss&&boss.art==='adapted')drawAdaptedBoss(boss);ctx.save();ctx.fillStyle='#4fd6df';ctx.fillRect(22,28,220*(phase==='travel'?p.x/L:1),8);if(developerMode&&boss&&boss.art==='adapted'){let m=boss.adapt||adaptedNewMemory();ctx.font='10px monospace';ctx.textAlign='left';ctx.fillStyle='#ffe39a';ctx.fillText(`ADAPTED • PHASE ${boss.phaseIndex} • ${boss.mode.toUpperCase()} • DODGE ${adaptedDodgeBias()}`,340,90);ctx.fillStyle='#d9c9a4';ctx.fillText(`MELEE ${Math.round(m.meters.heavyMelee)}  BALLISTIC ${Math.round(m.meters.ballistic)}  SHOTGUN ${Math.round(m.meters.shotgun)}  AUTO ${Math.round(m.meters.automatic)}`,340,104);ctx.fillText(`EXPLOSIVE ${Math.round(m.meters.explosive)}  RANGE ${Math.round(m.averageRange)}  ACTIONS ${m.totalActions}`,340,118)}ctx.restore()};

// CAMPAIGN ROUTE — the old start handler still calls reset(true) for the
// developer lab's direct boss preview. In the real campaign, immediately hand
// that preview back to Act I travel; Act IV later enters the full encounter.
const campaignStartButton=document.querySelector('#startButton'),campaignStartOriginal=campaignStartButton?.onclick;
if(campaignStartButton&&campaignStartOriginal){campaignStartButton.onclick=function(event){const wasRetry=retrying;campaignStartOriginal.call(this,event);if(!wasRetry&&!developerMode&&boss?.art==='adapted'){boss.openingPreview=true;adaptedFinishOpening();}}}

// SINGLE-BOSS CAMPAIGN ----------------------------------------------------
// Normal play keeps the four map acts as a journey, but removes every
// ordinary enemy and every legacy boss encounter. Only the final Adapted One
// is spawned. The `force` escape hatch intentionally remains for the dev lab
// and smoke tests, so those tools can still inspect old routines in isolation.
let adaptedOnlyCampaign=false;
const enemyBeforeAdaptedOnly=enemy;
enemy=function(x,y,kind){if(adaptedOnlyCampaign)return;return enemyBeforeAdaptedOnly(x,y,kind)};
const enterBossBeforeAdaptedOnly=enterBoss;
enterBoss=function(force=false){
  if(force||!adaptedOnlyCampaign)return enterBossBeforeAdaptedOnly(force);
  enemies=[];pickups=[];shots=[];boss=null;
  if(act>=3){enterAdaptedBoss();return}
  phase='actClear';actClear={timer:1.35,total:1.35,cleared:act,next:act+1};setMusic('journey');say(`${LEVELS[act].name} — AREA CLEARED`,2.2);
};
const campaignStartWithBossFilter=campaignStartButton?.onclick;
if(campaignStartButton&&campaignStartWithBossFilter){campaignStartButton.onclick=function(event){adaptedOnlyCampaign=!developerMode;return campaignStartWithBossFilter.call(this,event)}}
