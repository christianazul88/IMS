/*
 * HERO FACE REMODEL
 *
 * The supplied face reference is translated into a small, readable 2.5D
 * game portrait here instead of shipping the personal photograph in the
 * build.  This keeps the likeness cues (short tousled dark hair, oval face,
 * straight brows, warm skin and black jacket) while every pose remains crisp
 * at the game's 64px character scale.
 *
 * Tuning points for future art passes:
 *   FACE_REMODEL.palette      - change the hero's colours
 *   facePose()                - change which facial features show per angle
 *   drawGun()                 - weapon silhouette and recoil
 *   paintHeavySword()         - blade size, guard and swing offsets
 * The animation timing still comes from HERO_ANIM/HERO_FRAME_RATE in
 * game.js, so the existing eight-direction movement and sword combo remain
 * synchronized with this remodel.
 */
(function(){
  const FACE_REMODEL={
    skin:'#e7ad87', skinHi:'#ffd0a8', hair:'#171821', hairHi:'#2b2d3a',
    jacket:'#111725', jacketHi:'#27354b', jacketLine:'#c24b5d', shirt:'#efe8dc',
    boot:'#080b12', sole:'#394354', eye:'#202032', iris:'#70d7e8', gold:'#f5c969'
  };
  const DIR_ANGLE={right:0,'down-right':Math.PI/4,down:Math.PI/2,'down-left':3*Math.PI/4,left:Math.PI,'up-left':-3*Math.PI/4,up:-Math.PI/2,'up-right':-Math.PI/4};
  const mirrorDirs=new Set(['left','down-left','up-left']);
  const backDirs=new Set(['up','up-left','up-right']);
  const sideDirs=new Set(['right','left']);

  function roundedRect(x,y,w,h,r){
    r=Math.min(r,Math.abs(w)/2,Math.abs(h)/2);ctx.beginPath();ctx.moveTo(x+r,y);ctx.lineTo(x+w-r,y);ctx.quadraticCurveTo(x+w,y,x+w,y+r);ctx.lineTo(x+w,y+h-r);ctx.quadraticCurveTo(x+w,y+h,x+w-r,y+h);ctx.lineTo(x+r,y+h);ctx.quadraticCurveTo(x,y+h,x,y+h-r);ctx.lineTo(x,y+r);ctx.quadraticCurveTo(x,y,x+r,y);ctx.closePath();
  }
  function facePose(dir){
    return {back:backDirs.has(dir),side:sideDirs.has(dir),mirror:mirrorDirs.has(dir),threeQuarter:!backDirs.has(dir)&&!sideDirs.has(dir)};
  }
  function drawHair(dir,back){
    ctx.fillStyle=FACE_REMODEL.hair;ctx.beginPath();ctx.ellipse(0,-18,15,13,0,0,Math.PI*2);ctx.fill();
    // Uneven spikes are the most recognizable cue from the supplied photo.
    ctx.fillStyle=FACE_REMODEL.hairHi;ctx.beginPath();
    ctx.moveTo(-14,-22);ctx.lineTo(-12,-31);ctx.lineTo(-7,-25);ctx.lineTo(-3,-34);ctx.lineTo(1,-26);ctx.lineTo(7,-33);ctx.lineTo(9,-25);ctx.lineTo(15,-29);ctx.lineTo(13,-16);ctx.lineTo(-13,-14);ctx.closePath();ctx.fill();
    if(back){ctx.strokeStyle='#3e4150';ctx.lineWidth=2;ctx.beginPath();ctx.moveTo(-8,-19);ctx.lineTo(-5,-8);ctx.moveTo(1,-23);ctx.lineTo(2,-7);ctx.moveTo(8,-20);ctx.lineTo(9,-9);ctx.stroke()}
  }
  function drawFace(dir,breath){
    const f=facePose(dir), side=f.side||f.threeQuarter;
    if(f.back){drawHair(dir,true);return}
    const ox=f.mirror?-1:0;
    ctx.fillStyle=FACE_REMODEL.skin;ctx.beginPath();ctx.ellipse(ox,-17+breath,12.5,13.5,0,0,Math.PI*2);ctx.fill();
    ctx.fillStyle=FACE_REMODEL.skinHi;ctx.globalAlpha=.38;ctx.beginPath();ctx.ellipse(ox+3,-21+breath,5,7,0,0,Math.PI*2);ctx.fill();ctx.globalAlpha=1;
    drawHair(dir,false);
    ctx.fillStyle=FACE_REMODEL.hair;ctx.fillRect(f.mirror?-14:7,-18,6,4);
    if(side){
      const ex=f.mirror?-6:6;ctx.strokeStyle=FACE_REMODEL.eye;ctx.lineWidth=2;ctx.beginPath();ctx.moveTo(ex-3,-19);ctx.lineTo(ex+2,-20);ctx.stroke();ctx.fillStyle=FACE_REMODEL.iris;ctx.beginPath();ctx.arc(ex,-18,1.5,0,Math.PI*2);ctx.fill();
      ctx.strokeStyle='#a35b58';ctx.lineWidth=1.3;ctx.beginPath();ctx.moveTo(ox+(f.mirror?-3:3),-11+breath);ctx.lineTo(ox+(f.mirror?3:-3),-11+breath);ctx.stroke();
    }else{
      ctx.strokeStyle=FACE_REMODEL.eye;ctx.lineWidth=2;ctx.beginPath();ctx.moveTo(-8,-20);ctx.lineTo(-2,-21);ctx.moveTo(2,-21);ctx.lineTo(8,-20);ctx.stroke();
      ctx.fillStyle=FACE_REMODEL.iris;ctx.beginPath();ctx.arc(-5,-18,1.6,0,Math.PI*2);ctx.arc(5,-18,1.6,0,Math.PI*2);ctx.fill();
      ctx.fillStyle='#855044';ctx.fillRect(-1,-15,2,4);ctx.strokeStyle='#9f5254';ctx.lineWidth=1.5;ctx.beginPath();ctx.moveTo(-4,-10);ctx.quadraticCurveTo(0,-8,4,-10);ctx.stroke();
    }
  }
  function drawLegs(state,frame){
    const run=state==='run',dash=state==='dash',sword=state==='sword';
    const stride=run?Math.sin((frame||0)*Math.PI/2)*5:0;
    ctx.fillStyle=FACE_REMODEL.boot;roundedRect(-11-stride,13,8,14,3);ctx.fill();roundedRect(3+stride,13,8,14,3);ctx.fill();
    ctx.fillStyle=FACE_REMODEL.sole;ctx.fillRect(-12-stride,25,10,3);ctx.fillRect(2+stride,25,10,3);
    if(dash){ctx.globalAlpha=.55;ctx.fillStyle='#63e9f1';ctx.fillRect(-17,22,9,3);ctx.fillRect(8,24,13,3);ctx.globalAlpha=1}
    if(sword&&frame>=3){ctx.strokeStyle='#e4b4a2';ctx.lineWidth=4;ctx.beginPath();ctx.moveTo(-10,1);ctx.lineTo(-17,-5);ctx.moveTo(9,1);ctx.lineTo(16,-6);ctx.stroke()}
  }
  function drawJacket(state,frame,breath){
    ctx.fillStyle=FACE_REMODEL.jacket;roundedRect(-17,-6+breath,34,23,6);ctx.fill();
    ctx.fillStyle=FACE_REMODEL.jacketHi;roundedRect(-14,-3+breath,28,17,4);ctx.fill();
    ctx.fillStyle=FACE_REMODEL.shirt;ctx.beginPath();ctx.moveTo(-5,-6+breath);ctx.lineTo(5,-6+breath);ctx.lineTo(3,10+breath);ctx.lineTo(-3,10+breath);ctx.closePath();ctx.fill();
    ctx.strokeStyle=FACE_REMODEL.jacketLine;ctx.lineWidth=1.6;ctx.beginPath();ctx.moveTo(-13,-1+breath);ctx.lineTo(-8,4+breath);ctx.lineTo(-13,9+breath);ctx.moveTo(13,-1+breath);ctx.lineTo(8,4+breath);ctx.lineTo(13,9+breath);ctx.stroke();
    ctx.strokeStyle='#4b6881';ctx.lineWidth=1;ctx.beginPath();ctx.moveTo(-14,4+breath);ctx.lineTo(-9,1+breath);ctx.moveTo(14,4+breath);ctx.lineTo(9,1+breath);ctx.stroke();
    ctx.fillStyle=FACE_REMODEL.gold;ctx.fillRect(-2,8+breath,4,5);
  }
  function drawGun(dir,state){
    const a=DIR_ANGLE[dir]??0,recoil=state==='fire'?.22:0;
    ctx.save();ctx.translate(0,-1);ctx.rotate(a);ctx.translate(7-recoil*5,0);
    ctx.fillStyle='#161d2a';roundedRect(-2,-3,22,6,2);ctx.fill();ctx.fillStyle='#69d9e7';ctx.fillRect(6,-2,10,2);ctx.fillStyle='#7a4b52';ctx.fillRect(1,2,7,8);ctx.fillStyle='#f6cc71';ctx.fillRect(18,-2,5,3);
    if(state==='fire'){ctx.globalAlpha=.9;ctx.fillStyle='#ffe8a0';ctx.beginPath();ctx.moveTo(22,0);ctx.lineTo(31,-5);ctx.lineTo(28,0);ctx.lineTo(31,5);ctx.closePath();ctx.fill()}
    ctx.restore();
  }
  function mirroredDirection(dir){
    return dir==='left'?'right':dir==='down-left'?'down-right':dir==='up-left'?'up-right':dir;
  }
  function drawRemodeledHero(frame,x,y,scaleX,scaleY,alpha){
    const dir=frame?.direction||HERO_ANIM.direction||'right',state=frame?.state||HERO_ANIM.state||'idle',f=facePose(dir),runFrame=frame?.frame||1;
    const phase=HERO_ANIM.t*(HERO_FRAME_RATE[state]||10),breath=state==='idle'?Math.sin(phase*.8)*.55:0;
    ctx.save();ctx.translate(x-camera,y);ctx.scale(scaleX,scaleY);ctx.globalAlpha=alpha;
    if(f.mirror)ctx.scale(-1,1);
    const back=f.back;
    drawLegs(state,runFrame);
    // The canvas is mirrored for left-facing poses, so aim the local gun with
    // the matching right-facing angle to avoid a second accidental flip.
    const gunDir=f.mirror?mirroredDirection(dir):dir;
    if(back&&p.weapon!=='sword')drawGun(gunDir,state);
    drawJacket(state,runFrame,breath);
    drawFace(dir,breath);
    if(!back&&p.weapon!=='sword')drawGun(gunDir,state);
    if(state==='hurt'){ctx.globalAlpha=.32;ctx.fillStyle='#ff415c';roundedRect(-19,-31,38,61,8);ctx.fill();ctx.globalAlpha=alpha}
    if(state==='dash'){ctx.globalAlpha=.35;ctx.strokeStyle='#79f2ff';ctx.lineWidth=2;ctx.beginPath();ctx.arc(0,-4,29,0,Math.PI*2);ctx.stroke();ctx.globalAlpha=alpha}
    ctx.restore();
  }
  // The existing drawHero() calls this function for every normal and dash
  // after-image.  Keeping the old signature means all gameplay timings and
  // hit boxes remain unchanged while the face/pose art is replaced.
  paintHeroSprite=function(image,x,y,scaleX,scaleY,alpha,angle){drawRemodeledHero(heroFrame(),x,y,scaleX,scaleY,alpha)};

  // A clean vector fallback is intentional: it prevents the large sword from
  // disappearing while its editable PNG is loading, and makes the blade size
  // consistent across all eight directions.
  paintHeavySword=function(image,x,y,size,alpha=1,frame=null){
    const dir=frame?.direction||HERO_ANIM.direction||'right',combo=frame?.combo||1,idx=Math.max(0,Math.min(4,(frame?.frame||1)-1));
    const swings=[-2.62,-2.05,-1.42,-.78,-.16,2.62,2.05,1.42,.78,.16,Math.PI,2.65,1.48,.26,.02];
    const swing=frame?.state==='sword'?swings[(combo-1)*5+idx]:-1.18;
    const base=p.swordAngle||DIR_ANGLE[dir]||0;ctx.save();ctx.translate(x-camera,y);ctx.rotate(base+swing);ctx.globalAlpha=alpha;
    const reach=size*.58,width=Math.max(11,size*.12);
    ctx.fillStyle='#f3ca72';ctx.fillRect(-22,-7,18,14);ctx.fillStyle='#1a2230';ctx.fillRect(-18,-width*.62,7,width*1.24);
    ctx.fillStyle='#dce9f1';ctx.beginPath();ctx.moveTo(-4,-width*.55);ctx.lineTo(reach-12,-width*.32);ctx.lineTo(reach,0);ctx.lineTo(reach-12,width*.32);ctx.lineTo(-4,width*.55);ctx.closePath();ctx.fill();
    ctx.fillStyle='#7ca5b7';ctx.fillRect(4,-2,reach-20,4);ctx.fillStyle='#fff3bd';ctx.globalAlpha=alpha*.8;ctx.fillRect(reach-19,-3,12,6);ctx.restore();
  };
  window.HERO_FACE_REMODEL=FACE_REMODEL;
})();
