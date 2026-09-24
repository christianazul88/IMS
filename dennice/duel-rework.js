/* THE LAST HUNT — direct encounter and hero combat tuning.
 * Loaded last: this is the single entry point for the revised playable duel.
 * Dismantle's original mechanics remain in game.js.
 */
(function(){
  // Earthsplitter is a ten-second special: the opening blow creates a living
  // fault that keeps pulsing down the aimed lane. Tune fieldPulse, not its
  // duration, when balancing how often it can damage/stagger a target.
  const cfg={bodyHeight:66,slamCooldown:13,slamRange:245,slamDamage:16,slamFieldDuration:10,fieldPulse:.8,strikeAt:.34};
  // The new full-body hero is taller than the old chibi. Keep the boss at 2x
  // visible body height; the halo and weapon extend beyond that measurement.
  ADAPTED_ONE_CONFIG.heroBodyHeight=66;
  const heroSheet=new Image();heroSheet.src='assets/hero-vanguard-atlas.png';
  art['hero-vanguard-atlas']=heroSheet;
  let strike=null,slam=null,slamField=null,slamCooldown=0,fx=[],motion=0;
  Object.assign(SWORD_COMBOS[0],{duration:.29,damage:3.4,range:112,arc:1.75,lunge:0,cooldown:.18});
  Object.assign(SWORD_COMBOS[1],{duration:.32,damage:4.5,range:126,arc:1.95,lunge:0,cooldown:.2});
  Object.assign(SWORD_COMBOS[2],{duration:.42,damage:8,range:44,arc:.5,lunge:0,pierceTime:.19,pierceSpeed:880,cooldown:.3});
  const oldReset=reset;
  reset=function(){oldReset(false);act=3;L=roadLength(3);adaptedOnlyCampaign=true;enterAdaptedBoss(false);equip();};
  function equip(){p.weapon='sword';p.hasSword=true;p.hasShotgun=true;p.hasMachineGun=true;p.speed=290;p.damage=1;p.swordAnim=0;p.swordPierce=null;p.swordQueued=false;strike=null;slam=null;slamField=null;slamCooldown=0;fx=[];}
  const oldRespawn=respawnAtCheckpoint;
  respawnAtCheckpoint=function(){checkpoint='adapted';oldRespawn();equip();};
  // Replace the old campaign redirect while retaining arena-map's load gate.
  const button=document.querySelector('#startButton');
  button.onclick=async function(){
    if(button.disabled)return;button.disabled=true;button.textContent='PREPARING THE HUNT';
    const required=[heroSheet,art['adapted-one-combat']];
    await Promise.all(required.map(im=>im.decode?im.decode().catch(()=>{}):Promise.resolve()));
    const deadline=performance.now()+12000;
    while(window.PRINCESS_MAP&&!window.PRINCESS_MAP.stats().ready&&performance.now()<deadline){window.PRINCESS_MAP.renderAt(0);await new Promise(r=>setTimeout(r,80));}
    button.disabled=false;button.textContent='FACE THE ADAPTED ONE';
    if(required.some(im=>!im.naturalWidth)||window.PRINCESS_MAP&&!window.PRINCESS_MAP.stats().ready){say('ART IS STILL LOADING — PLEASE RETRY',4);return;}
    difficultyKey=document.querySelector('#difficultySelect').value;reset();retrying=false;running=true;paused=false;
    document.querySelector('#startPanel').style.display='none';document.querySelector('#pausePanel').hidden=true;
    audio().resume();last=performance.now();requestAnimationFrame(loop);
  };
  document.querySelector('#startPanel h2').textContent='THE LAST HUNT';
  document.querySelector('#startPanel p').textContent='Face The Adapted One. Break its guard, evade the returning shockwave, and punish each recovery. Your weapons are ready.';
  button.textContent='FACE THE ADAPTED ONE';
  document.querySelector('#startPanel small').textContent='Move WASD · Aim mouse · Click: sword combo · Space: evade · R / right click: Earthsplitter · E: Dismantle · Q: switch weapon';
  // Attack is committed on input but collision happens at the strike frame.
  swordAttack=function(queueInput=false){
    if(slam||p.dashTime>0)return;
    if(strike||p.swordAnim>0||p.swordPierce){if(queueInput)p.swordQueued=true;return;}
    if(p.fire>0)return;
    p.swordCombo=p.swordChain>0?p.swordCombo%3+1:1;
    const c=currentSwordConfig();p.swordAngle=p.angle;p.swordAnim=c.duration;p.swordChain=.78;p.swordQueued=false;p.fire=c.cooldown;
    strike={age:0,cfg:c,done:false,combo:p.swordCombo};adaptedRecord('heavyMelee');
  };
  function earthsplitter(){
    if(p.weapon==='shadow')return;
    if(!running||paused||slamCooldown>0||slam||strike||p.swordAnim>0)return;
    p.weapon='sword';p.swordAngle=p.angle;slam={age:0,a:p.angle,x:p.x,y:p.y,done:false};slamCooldown=cfg.slamCooldown;
    adaptedRecord('heavyMelee',1.5);sfx('warn');
  }
  canvas.addEventListener('contextmenu',e=>e.preventDefault());
  canvas.addEventListener('pointerdown',e=>{if(e.button===2){if(p.weapon==='shadow')return;e.preventDefault();e.stopImmediatePropagation();mouse.down=false;strike=null;p.swordAnim=0;p.swordQueued=false;p.fire=0;aimAtPointer(e);p.angle=Math.atan2(mouse.y-p.y,mouse.x+camera-p.x);earthsplitter();}},true);
  document.addEventListener('keydown',e=>{if(e.key.toLowerCase()==='r'&&!e.repeat&&p.weapon!=='shadow')earthsplitter();});
  const skillButton=document.createElement('button');skillButton.id='earthsplitterButton';skillButton.textContent='SLAM';skillButton.setAttribute('aria-label','Earthsplitter ground attack');
  skillButton.hidden=true;
  skillButton.style.cssText='position:absolute;right:22px;bottom:180px;z-index:8;background:#261b24;color:#ffdfaa;border:1px solid #c7a16b;padding:14px;touch-action:none';
  gameFrame.appendChild(skillButton);skillButton.addEventListener('pointerdown',e=>{e.preventDefault();earthsplitter();});
  const oldDash=dash;dash=function(){if(!running||paused||!p.dash)return;strike=null;slam=null;p.swordAnim=0;p.swordPierce=null;p.swordQueued=false;oldDash();};
  const oldUlt=ultimateAttack;ultimateAttack=function(force=false,weapon=null){const out=oldUlt(force,weapon);if(out){strike=null;slam=null;}return out;};
  // Ranged alternatives become a deliberate bolt, close scatter and rapid
  // volley. Each has a readable recoil beat and a different damage/rate trade.
  fire=function(){
    if(p.weapon==='sword'){swordAttack(mouse.down&&performance.now()-mouse.pressedAt>=120);return;}
    if(p.fire>0||p.dashTime>0||slam)return;
    const a=p.angle,rapid=p.rapid>0?.72:1;adaptedRecord(adaptedCategoryForWeapon(p.weapon));HERO_ANIM.fire=.13;
    const count=p.weapon==='shotgun'?5:1,spread=p.weapon==='shotgun'?.13:0;
    for(let i=0;i<count;i++)bullet(p.x+Math.cos(a)*24,p.y+Math.sin(a)*24,a+(i-(count-1)/2)*spread,'player',p.damage*(p.weapon==='pistol'?2.4:p.weapon==='shotgun'?1.25:.7),p.weapon==='machinegun'?1000:820,p.weapon==='pistol'?6:4);
    p.fire=(p.weapon==='pistol'?.3:p.weapon==='shotgun'?.52:.085)*rapid;sfx(p.weapon==='machinegun'?'machine':'fire');
  };
  const oldImg=img;img=function(n,x,y,size,a=0,alpha=1){
    if(n!=='bullet')return oldImg(n,x,y,size,a,alpha);
    ctx.save();ctx.translate(x-camera,y);ctx.rotate(a);ctx.globalCompositeOperation='lighter';ctx.globalAlpha=alpha;
    const g=ctx.createLinearGradient(-24,0,8,0);g.addColorStop(0,'rgba(255,177,87,0)');g.addColorStop(.8,'#ffbd70');g.addColorStop(1,'#fff6d7');ctx.fillStyle=g;ctx.beginPath();ctx.moveTo(-24,0);ctx.lineTo(4,-3);ctx.lineTo(10,0);ctx.lineTo(4,3);ctx.closePath();ctx.fill();ctx.restore();
  };
  const oldUpdate=update;
  update=function(dt){
    oldUpdate(dt);if(!running||paused)return;motion+=dt;slamCooldown=Math.max(0,slamCooldown-dt);
    if(strike){const s=strike;s.age+=dt;if(!s.done&&s.age>=s.cfg.duration*cfg.strikeAt){
      s.done=true;const damage=s.cfg.damage*p.damage;
      if(s.combo===3)p.swordPierce={time:s.cfg.pierceTime,total:s.cfg.pierceTime,cfg:s.cfg,damage,hits:new Set(),victims:[]};
      else startCleaveEcho(s.cfg,damage,swordTargets(s.cfg,damage));
      fx.push({kind:'cut',x:p.x,y:p.y,a:p.swordAngle,age:0,reverse:s.combo===2,r:s.cfg.range});sfx('sword'+s.combo);
    }if(s.age>=s.cfg.duration)strike=null;}
    if(slam){slam.age+=dt;if(!slam.done&&slam.age>=.38){slam.done=true;fx.push({kind:'slam',x:slam.x,y:slam.y,a:slam.a,age:0,r:cfg.slamRange});slamField={age:0,duration:cfg.slamFieldDuration,next:cfg.fieldPulse,x:slam.x,y:slam.y,a:slam.a};
      const b=boss;if(b&&b.mode!=='death'){const d=dist(b,slam),a=Math.atan2(b.y-slam.y,b.x-slam.x);if(d<cfg.slamRange+b.r&&Math.abs(swordAngleDelta(a,slam.a))<.48){b.hp-=cfg.slamDamage*adaptedDamageScale('heavyMelee');b.hit=.18;b.swordStagger=.3;bloodBurst(b.x,b.y,9);if(b.hp<=0)bossDown();}}
      shake=Math.max(shake,.3);sfx('sword3');}if(slam.age>.72)slam=null;}
    if(slamField){slamField.age+=dt;if(slamField.age>=slamField.next&&slamField.age<slamField.duration){slamField.next+=cfg.fieldPulse;fx.push({kind:'pulse',x:slamField.x,y:slamField.y,a:slamField.a,age:0,r:cfg.slamRange});const b=boss;if(b&&b.mode!=='death'){const d=dist(b,slamField),a=Math.atan2(b.y-slamField.y,b.x-slamField.x);if(d<cfg.slamRange+b.r&&Math.abs(swordAngleDelta(a,slamField.a))<.52){b.hp-=3.2*adaptedDamageScale('heavyMelee');b.hit=.14;b.swordStagger=Math.max(b.swordStagger||0,.18);b.dismantleSlow=Math.max(b.dismantleSlow||0,.22);bloodBurst(b.x,b.y,4);if(b.hp<=0)bossDown();}}shake=Math.max(shake,.08);sfx('sword1')}if(slamField.age>=slamField.duration){slamField=null;say('THE FAULT CLOSES',.8)}}
    for(const f of fx)f.age+=dt;fx=fx.filter(f=>f.age<(f.kind==='slam'?.65:f.kind==='pulse'?.5:.22));
    skillButton.textContent=slamCooldown>0?'SLAM '+slamCooldown.toFixed(1):'SLAM [R]';skillButton.hidden=phase!=='boss';
  };
  // Atlas rows: ready, left stride, right stride, attack. Four authored views
  // are mirrored/interpolated into eight facing sectors. Feet stay anchored.
  function pose(){const a=p.swordAnim>0?p.swordAngle:HERO_ANIM.faceAngle;const sector=(Math.round(a/(Math.PI/4))+8)%8;
    return {col:[1,0,0,0,1,3,2,3][sector],mirror:[false,false,false,true,true,true,false,false][sector],a};}
  drawHero=function(){
    const q=pose(),moving=HERO_ANIM.moving,attack=p.swordAnim>0||!!slam,progress=p.swordAnim>0?1-p.swordAnim/swordDuration():0;
    const row=attack&&progress>.24?3:moving?1+Math.floor(motion*11)%2:0;
    const bob=moving?Math.sin(motion*22)*1.3:Math.sin(motion*3)*.5,lift=p.dashTime>0?6:slam?Math.sin(Math.min(1,slam.age/.4)*Math.PI)*10:0;
    ctx.save();ctx.translate(p.x-camera,p.y);ctx.fillStyle='#07080aaa';ctx.beginPath();ctx.ellipse(0,21,19,6,0,0,Math.PI*2);ctx.fill();
    function body(alpha,dx=0,dy=0){ctx.save();ctx.translate(dx,dy-lift+bob);ctx.globalAlpha=alpha;ctx.scale(q.mirror?-1:1,1);ctx.rotate(attack?Math.sin(progress*Math.PI)*.09*(p.swordCombo===2?-1:1):p.dashTime>0?.15:0);
      if(heroSheet.complete&&heroSheet.naturalWidth){const cw=heroSheet.naturalWidth/4,ch=heroSheet.naturalHeight/4;ctx.imageSmoothingEnabled=true;ctx.drawImage(heroSheet,q.col*cw,row*ch,cw,ch,-37,-50,74,74);
        // A short blend at the stride boundary softens the two authored run
        // poses while the foot anchor and independently animated sword stay fixed.
        if(moving&&!attack){const fraction=(motion*11)%1,blend=Math.max(0,(fraction-.72)/.28);if(blend>0){ctx.globalAlpha=alpha*blend;ctx.drawImage(heroSheet,q.col*cw,(row===1?2:1)*ch,cw,ch,-37,-50,74,74);}}
      }ctx.restore();}
    if(p.dashTime>0)for(let i=3;i>0;i--)body(.13*(4-i),-p.dx*i*12,-p.dy*i*12);
    body(HERO_ANIM.hurt>0?.72:1);
    if(p.weapon==='sword'){
      let swing=-.8;if(attack){const t=clamp(progress/.65,0,1);swing=p.swordCombo===3?0:p.swordCombo===2?1.4-2.8*t:-1.4+2.8*t;}if(slam)swing=-Math.PI/2+Math.min(1,slam.age/.4)*Math.PI/2;
      ctx.translate(0,-8-lift);ctx.rotate(q.a+swing);const length=attack?76:61;
      ctx.fillStyle='#171b25';ctx.fillRect(-9,-4,18,8);ctx.fillStyle='#d9aa60';ctx.fillRect(7,-11,5,22);
      const g=ctx.createLinearGradient(10,-7,10,7);g.addColorStop(0,'#f9f1d5');g.addColorStop(.42,'#91a7b8');g.addColorStop(.5,'#eaf7ff');g.addColorStop(1,'#38465a');ctx.fillStyle=g;
      ctx.beginPath();ctx.moveTo(12,-7);ctx.lineTo(length-12,-5);ctx.lineTo(length,0);ctx.lineTo(length-12,5);ctx.lineTo(12,7);ctx.closePath();ctx.fill();
      ctx.strokeStyle='#e6bd79';ctx.lineWidth=1;ctx.stroke();
    }else{ctx.translate(0,-9);ctx.rotate(p.angle);ctx.fillStyle='#263344';ctx.fillRect(6,-5,31,10);ctx.fillStyle='#d5b272';ctx.fillRect(11,-3,29,3);if(HERO_ANIM.fire>0){ctx.fillStyle='#fff0b1';ctx.beginPath();ctx.moveTo(41,-4);ctx.lineTo(55,0);ctx.lineTo(41,4);ctx.fill();}}
    ctx.restore();
  };
  const huntDraw=window.PRINCESS_HUNT.draw;
  window.PRINCESS_HUNT.draw=function(){huntDraw();ctx.save();ctx.globalCompositeOperation='lighter';if(slamField){const fade=1-slamField.age/slamField.duration;ctx.strokeStyle='#ff9858';ctx.globalAlpha=.22+.13*Math.sin(slamField.age*14)**2;ctx.lineWidth=4;ctx.beginPath();for(let i=0;i<=24;i++){const d=i/24*cfg.slamRange,x=slamField.x-camera+Math.cos(slamField.a)*d,y=slamField.y+Math.sin(slamField.a)*d+Math.sin(i*2.3+slamField.age*6)*7*fade;i?ctx.lineTo(x,y):ctx.moveTo(x,y)}ctx.stroke()}for(const f of fx){const life=f.kind==='slam'?.65:f.kind==='pulse'?.5:.22,t=f.age/life;ctx.globalAlpha=(1-t)*.65;ctx.strokeStyle=f.kind==='cut'?'#d3eeff':f.kind==='pulse'?'#fff0a6':'#ffc276';ctx.lineWidth=f.kind==='cut'?2:3;ctx.beginPath();
    if(f.kind==='cut')ctx.arc(f.x-camera,f.y,f.r*.75,f.a-.85,f.a+.85);
    else{for(let i=0;i<=22;i++){const d=i/22*f.r,x=f.x-camera+Math.cos(f.a)*d,y=f.y+Math.sin(f.a)*d+Math.sin(i*2.7)*10*(1-t);if(i===0)ctx.moveTo(x,y);else ctx.lineTo(x,y);}}ctx.stroke();}ctx.restore();};
  window.DUEL_REWORK={cfg,earthsplitter,get state(){return{strike,slam,slamField,slamCooldown,fx:fx.length};}};
})();
