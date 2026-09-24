/* Procedural atomic pressure front. No expanding bitmap is drawn.
 * Collision radius and escape gap come directly from PRINCESS_HUNT.tuning.
 * Front width is constant in world pixels; fragments travel independently.
 * These fixed loop limits also bound the effect cost on mobile.
 */
(function(){
  const tau=Math.PI*2,T=window.PRINCESS_HUNT.tuning;
  function arc(x,y,r,a,z,color,width,alpha){if(r<=0)return;ctx.globalAlpha=alpha;ctx.strokeStyle=color;ctx.lineWidth=width;ctx.beginPath();ctx.arc(x,y,r,a,z);ctx.stroke();}
  function wave(h){
    const x=h.x-camera,y=h.y,r=h.r,fade=clamp((T.waveRadius-r)/110,0,1),a=h.gap+T.waveGap,z=h.gap+tau-T.waveGap;
    ctx.save();ctx.globalCompositeOperation='lighter';
    // Narrow leading pressure edge, a turbulent hot rim, and a cooler wake.
    arc(x,y,r,a,z,'#3396c5',38,.10*fade);arc(x,y,r-6,a,z,'#74ddf1',16,.23*fade);
    arc(x,y,r,a,z,'#f3feff',3,.93*fade);arc(x,y,r-18,a,z,'#e8bd7f',8,.20*fade);
    for(let band=0;band<3;band++){
      ctx.beginPath();for(let i=0;i<=112;i++){const q=a+(z-a)*i/112,noise=Math.sin(q*19-h.age*18+band)*3+Math.sin(q*37+h.age*24)*2,rr=r-band*7+noise;
        const px=x+Math.cos(q)*rr,py=y+Math.sin(q)*rr;if(i===0)ctx.moveTo(px,py);else ctx.lineTo(px,py);}
      ctx.strokeStyle=band===0?'#e4fdff':'#59c8f0';ctx.lineWidth=band===0?1.7:1;ctx.globalAlpha=fade*(.7-band*.18);ctx.stroke();
    }
    // Sparks launch from the moving rim, instead of stretching with it.
    for(let i=0;i<64;i++){
      const q=a+.03+(z-a-.06)*(i+.5)/64,cycle=(h.age*2.5+i*.618)%1,rr=Math.max(0,r-cycle*46),lift=Math.sin(cycle*Math.PI)*(8+i%13);
      const px=x+Math.cos(q)*rr,py=y+Math.sin(q)*rr-lift;
      ctx.globalAlpha=fade*(1-cycle)*.8;ctx.fillStyle=i%3?'#8fe5f5':'#ffdda1';ctx.fillRect(px,py,1.5+i%2,1.5+i%2);
      if(i%4===0){ctx.beginPath();ctx.moveTo(px,py);ctx.lineTo(px-Math.cos(q)*9,py-Math.sin(q)*9+4);ctx.strokeStyle='#8defff';ctx.lineWidth=1;ctx.stroke();}
    }
    ctx.globalCompositeOperation='source-over';
    for(let i=0;i<32;i++){
      const q=a+(z-a)*(i+.5)/32,lag=(h.age*1.4+i*.381)%1,rr=r-16-lag*48;
      if(rr<0)continue;ctx.globalAlpha=fade*(1-lag)*.18;ctx.fillStyle=i%2?'#a99a80':'#c6b899';ctx.beginPath();ctx.ellipse(x+Math.cos(q)*rr,y+Math.sin(q)*rr-8*lag,5+lag*10,3+lag*4,q,0,tau);ctx.fill();
    }
    // Clearly mark the actual harmless corridor without drawing energy in it.
    ctx.globalAlpha=.28*fade;ctx.strokeStyle='#b5e8c8';ctx.lineWidth=1;
    for(const edge of [a,z]){ctx.beginPath();ctx.moveTo(x+Math.cos(edge)*Math.max(0,r-24),y+Math.sin(edge)*Math.max(0,r-24));ctx.lineTo(x+Math.cos(edge)*(r+12),y+Math.sin(edge)*(r+12));ctx.stroke();}
    ctx.restore();
  }
  window.PRINCESS_HUNT.draw=function(){
    const b=boss;if(!b||b.art!=='adapted')return;ctx.save();
    if(b.mode==='shockwave'&&b.stage==='windup'){
      const t=clamp(1-b.timer/b.move.wind,0,1),x=b.x-camera,y=b.y;
      ctx.globalCompositeOperation='lighter';
      for(let i=0;i<26;i++){const a=i*2.399+time*.6,r=18+((1-t)*90+i*7)%94*(1-t),px=x+Math.cos(a)*r,py=y+Math.sin(a)*r;
        ctx.globalAlpha=.25+t*.6;ctx.strokeStyle=i%2?'#91e9ff':'#ffcb7a';ctx.lineWidth=1;ctx.beginPath();ctx.moveTo(px,py);ctx.lineTo(x+Math.cos(a)*(r+8),y+Math.sin(a)*(r+8));ctx.stroke();}
      arc(x,y,18+t*22,0,tau,'#abeaff',2,.3+t*.4);
      ctx.globalAlpha=t*.24;ctx.fillStyle='#d9f6ff';ctx.beginPath();ctx.ellipse(x,y,20+t*18,12+t*8,0,0,tau);ctx.fill();ctx.globalCompositeOperation='source-over';
    }
    for(const h of b.hazards||[]){if(h.age<0)continue;if(h.kind==='wave'){wave(h);continue;}
      const x=h.x-camera,y=h.y,t=clamp(h.age/h.warning,0,1),warning=h.age<h.warning;
      ctx.globalAlpha=warning?.14:.4;ctx.fillStyle=h.kind==='blink'?'#a6eaf5':'#fb9c64';ctx.beginPath();ctx.arc(x,y,h.r,0,tau);ctx.fill();
      arc(x,y,h.r,0,tau,warning?'#ffb36d':'#fff2b6',2,warning?.75:1-t*.5);
      if(warning)arc(x,y,h.r*t,0,tau,'#fff0bd',2,.7);
      if(h.kind==='rift'&&!warning){for(let i=0;i<7;i++){const a=i*2.399;ctx.globalAlpha=.6;ctx.strokeStyle='#fff1b5';ctx.beginPath();ctx.moveTo(x,y);ctx.lineTo(x+Math.cos(a)*h.r,y+Math.sin(a)*h.r-22);ctx.stroke();}}
    }ctx.restore();
  };
  // The teleport commits to its marked aim. A .16s arrival cue gives the
  // player a readable strike before collision, including at low frame rates.
  const attack=adaptedAttackUpdate;
  const oldPose=adaptedCombatPose;
  adaptedCombatPose=function(b){if(b.previewRow!==undefined)return oldPose(b);if(b.mode==='shockwave'||b.mode==='rift')return{row:b.stage==='active'?6:b.stage==='windup'?3:0};if(b.mode==='blinkPunch')return{row:b.stage==='active'?4:b.stage==='arrival'?5:b.stage==='windup'?3:0};return oldPose(b);};
  const paint=paintAdaptedPose;
  paintAdaptedPose=function(b,x=b.x,y=b.y,alpha=1){
    if(b.previewRow!==undefined)return paint(b,x,y,alpha);
    let sx=1,sy=1,lean=0;
    if(b.stage==='windup'&&b.move){const t=clamp(1-b.timer/b.move.wind,0,1);sx=1+t*.035;sy=1-t*.055;lean=Math.cos(b.aimAngle)*t*-.035;}
    if(b.stage==='active'){sx=1.045;sy=.97;lean=Math.cos(b.aimAngle)*.055;}
    ctx.save();ctx.translate(x-camera,y+24);ctx.rotate(lean);ctx.scale(sx,sy);ctx.translate(-(x-camera),-(y+24));paint(b,x,y,alpha);ctx.restore();
  };
  adaptedAttackUpdate=function(b,dt){
    if(b.mode!=='blinkPunch')return attack(b,dt);
    b.timer-=dt;if(b.timer>0)return;
    if(b.stage==='windup'){const from={x:b.x,y:b.y};b.x=b.teleportX;b.y=b.teleportY;window.PRINCESS_TERRAIN?.constrainActor(b,from);b.stage='arrival';b.timer=.16;b.trail.push({...from,direction:b.direction,life:.25});sfx('dash');}
    else if(b.stage==='arrival'){b.stage='active';b.timer=.13;b.strikeFlash=.16;adaptedMeleeHit(b);sfx('sword3');}
    else if(b.stage==='active'){b.stage='recover';b.timer=.8;}else adaptedRecover(b);
  };
})();
