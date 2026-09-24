/* Optional local QA tools. Only visible with ?dev=1; normal runs are unchanged.
 * The arsenal test uses the real fire(), sword combo, dash(), ultimates,
 * projectiles and boss AI. Training restores HP so every case can be observed.
 */
const ADAPTED_LAB_CASES=[
  ['Pistol','pistol',false,4],['Shotgun','shotgun',false,4],['Machine gun','machinegun',false,4],
  ['Sword combo + cleave','sword',false,5],['Pistol ultimate','pistol',true,3],
  ['Shotgun ultimate','shotgun',true,3],['Rocket gatling ultimate','machinegun',true,4],
  ['Dismantle (full 10 seconds)','sword',true,11],['Homing rocket pod','pistol',false,4,'pod'],
  ['Knight rocket support','pistol',false,4,'ally']
];
let adaptedLab={active:false,index:0,elapsed:0,results:[],current:null,training:false,preview:false,damageTaken:0};
function adaptedLabReset(){
  let wasRunning=running&&!paused;reset(true);running=true;paused=false;retrying=false;
  p.hasShotgun=true;p.hasMachineGun=true;p.hasCleave=true;p.damage=1;
  document.querySelector('#startPanel').style.display='none';document.querySelector('#pausePanel').hidden=true;
  boss.mode='neutral';boss.timer=.4;boss.phaseIndex=Number(document.querySelector('#labPhase')?.value||2);
  if(!wasRunning){last=performance.now();requestAnimationFrame(loop)}
}
function adaptedLabStartCase(){
  adaptedLabReset();let [label,weapon,ult,duration,support]=ADAPTED_LAB_CASES[adaptedLab.index];
  p.weapon=weapon;p.rapid=0;boss.x=camera+780;boss.y=370;p.x=boss.x-(weapon==='sword'?108:weapon==='shotgun'?140:260);p.y=370;
  if(support==='pod'){p.hasRocketPack=true;p.rocketCd=0;}
  if(support==='ally')ally={x:p.x-45,y:p.y+50,speed:120,move:0,fire:0,rocketFire:0,hasRocketLauncher:true,tx:p.x,ty:p.y,a:0};
  adaptedLab.current={label,weapon,ult,duration,support,startHP:boss.hp,damage:0,heroDamage:0,moves:new Set(),combo:new Set(),ultimateUsed:false,defeated:false};
  adaptedLab.elapsed=0;adaptedLab.training=true;adaptedLab.preview=false;
}
function adaptedLabRun(){adaptedLab.active=true;adaptedLab.index=0;adaptedLab.results=[];adaptedLabStartCase();}
function adaptedLabClearInput(){mouse.down=false;for(let k of ['w','a','s','d','ArrowUp','ArrowDown','ArrowLeft','ArrowRight'])keys[k]=false;}
function adaptedLabBefore(dt){
  if(!developerMode)return;if(adaptedLab.training&&running)p.hp=p.maxHp;
  if(!adaptedLab.active||!boss||boss.art!=='adapted')return;
  let q=adaptedLab.current,b=boss;adaptedLab.elapsed+=dt;adaptedLabClearInput();
  if(b.mode==='death'){q.defeated=true;return}
  mouse.x=b.x-camera;mouse.y=b.y;p.angle=Math.atan2(b.y-p.y,b.x-p.x);
  let d=dist(b,p),a=p.angle,want=q.weapon==='sword'?95:q.weapon==='shotgun'?140:240;
  if(d>want+25){keys.d=Math.cos(a)>.25;keys.a=Math.cos(a)<-.25;keys.s=Math.sin(a)>.25;keys.w=Math.sin(a)<-.25}
  else if(d<want-30){keys.d=Math.cos(a)<-.25;keys.a=Math.cos(a)>.25;keys.s=Math.sin(a)<-.25;keys.w=Math.sin(a)>.25}
  // Sidestep the committed line, do not teleport the player out of attacks.
  if(b.stage==='windup'&&b.timer<.27&&p.dash>0&&['dashPunch','judgment','heavyPunch','grab','slam'].includes(b.mode)){
    let side=p.y>H*.5?-1:1;p.lx=-Math.sin(b.aimAngle)*side;p.ly=Math.cos(b.aimAngle)*side;dash();
  }
  if(q.ult&&!q.ultimateUsed){q.ultimateUsed=ultimateAttack(true,q.weapon);}
  if(!q.ult&&!q.support){mouse.down=true;mouse.pressedAt=performance.now()-500;}
  if(b.attackName)q.moves.add(b.attackName);if(q.weapon==='sword'&&p.swordCombo)q.combo.add(p.swordCombo);
}
function adaptedLabAfter(){
  if(!developerMode)return;
  if(adaptedLab.active){let q=adaptedLab.current;q.heroDamage+=Math.max(0,p.maxHp-p.hp);if(boss&&boss.art==='adapted')q.damage=Math.max(q.damage,q.startHP-boss.hp);else q.defeated=true;
    if(adaptedLab.elapsed>=q.duration||q.defeated){adaptedLab.results.push({attack:q.label,damage:Number(q.damage.toFixed(1)),heroDamage:q.heroDamage,defeated:q.defeated,combo:[...q.combo].join('/'),moves:[...q.moves].join(', ')});adaptedLab.index++;if(adaptedLab.index<ADAPTED_LAB_CASES.length)adaptedLabStartCase();else{adaptedLab.active=false;adaptedLabClearInput();adaptedLab.training=false;pause(true);}}
  }
  let out=document.querySelector('#labResults');if(out)out.textContent=(adaptedLab.active?`RUNNING ${adaptedLab.index+1}/${ADAPTED_LAB_CASES.length}: ${adaptedLab.current.label} (${adaptedLab.elapsed.toFixed(1)}s)\n`:'')+adaptedLab.results.map(q=>`${q.attack}: ${q.damage} damage; received ${q.heroDamage}; ${q.defeated?'boss defeated':'boss survived'}${q.combo?`; combo ${q.combo}`:''}`).join('\n');
}
const updateBeforeAdaptedLab=update;
update=function(dt){if(developerMode&&!adaptedLab.active)dt*=Number(document.querySelector('#labSpeed')?.value||1);adaptedLabBefore(dt);updateBeforeAdaptedLab(dt);adaptedLabAfter();};

function adaptedLabGallery(){
  let panel=document.querySelector('#adaptedGallery');panel.hidden=false;
  let gc=panel.querySelector('canvas'),g=gc.getContext('2d'),tex=art['adapted-one-combat'];
  g.fillStyle='#10131f';g.fillRect(0,0,gc.width,gc.height);g.textAlign='center';g.font='13px monospace';
  let names=Object.keys(ADAPTED_COMBAT_ROWS),dirs=['FRONT','BACK','RIGHT','LEFT'];
  for(let row=0;row<8;row++)for(let col=0;col<4;col++){
    let x=col*230,y=row*155,r=adaptedFrameRect(row,col),scale=.57;
    g.fillStyle=(row+col)%2?'#1d2230':'#171b27';g.fillRect(x+3,y+3,224,149);
    g.strokeStyle='#4acbc966';g.beginPath();g.moveTo(x+15,y+132);g.lineTo(x+215,y+132);g.stroke();
    if(tex.complete&&tex.naturalWidth)drawAdaptedFrame(g,tex,row,col,x+115-r.w*scale/2,y+132-r.feet*scale,scale);
    g.fillStyle='#e9ddb5';g.fillText(`${names[row]} / ${dirs[col]}`,x+115,y+18);
  }
}
function installAdaptedLab(){
  if(!developerMode)return;
  let panel=document.createElement('details');panel.id='adaptedLab';panel.open=true;
  panel.innerHTML=`<summary>Adapted One test lab</summary>
  <div class="lab-controls"><label>Phase <select id="labPhase"><option value="1">1 Observe</option><option value="2" selected>2 Adapt</option><option value="3">3 Hunt</option><option value="4">4 Perfect</option></select></label>
  <label>Speed <select id="labSpeed"><option value="1">1×</option><option value="0.25">¼× slow motion</option></select></label>
  <label>Boss move <select id="labMove">${Object.keys(ADAPTED_MOVES).map(n=>`<option>${n}</option>`).join('')}</select></label>
  <label>Hero weapon <select id="labWeapon">${['pistol','shotgun','machinegun','sword'].map(n=>`<option>${n}</option>`).join('')}</select></label>
  <button id="labReplay">Replay boss move</button><button id="labEquip">Equip weapon</button><button id="labUltimate">Use selected ultimate</button>
  <button id="labRun">Run every hero attack</button><button id="labAtlas">Inspect all 32 poses</button><button id="labDuel">Reset normal duel</button>
  <label><input id="labTraining" type="checkbox">Training: restore hero HP</label></div><pre id="labResults">Choose a move or run the complete arsenal test.</pre>`;
  document.querySelector('.shell').appendChild(panel);
  let gallery=document.createElement('div');gallery.id='adaptedGallery';gallery.hidden=true;gallery.innerHTML='<button id="labCloseAtlas">Close pose atlas</button><p>Measured source rectangles • cyan lines = shared ground anchor</p><canvas width="920" height="1240" aria-label="All 32 Adapted One poses"></canvas>';document.body.appendChild(gallery);
  document.querySelector('#labRun').onclick=adaptedLabRun;
  document.querySelector('#labAtlas').onclick=adaptedLabGallery;
  document.querySelector('#labCloseAtlas').onclick=()=>gallery.hidden=true;
  document.querySelector('#labReplay').onclick=()=>{adaptedLab.active=false;adaptedLabClearInput();adaptedLabReset();adaptedLab.training=true;let name=document.querySelector('#labMove').value;boss.x=camera+740;boss.y=380;p.x=boss.x-(['dashPunch','blast','judgment'].includes(name)?320:110);p.y=380;adaptedStartAttack(boss,name)};
  document.querySelector('#labDuel').onclick=()=>{adaptedLab.active=false;adaptedLab.training=false;adaptedLabClearInput();adaptedLabReset();};
  document.querySelector('#labEquip').onclick=()=>{p.weapon=document.querySelector('#labWeapon').value;p.hasShotgun=p.hasMachineGun=true;};
  document.querySelector('#labUltimate').onclick=()=>{if(!running)adaptedLabReset();if(paused)pause(false);p.weapon=document.querySelector('#labWeapon').value;ultimateAttack(true,p.weapon);};
  document.querySelector('#labTraining').onchange=e=>adaptedLab.training=e.target.checked;
}
installAdaptedLab();
