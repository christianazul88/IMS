/* Full-map Adapted arena. All simulation positions are WORLD coordinates.
 * Only rendering/aiming uses the scrolling view. Other acts keep their renderer.
 */
(function(){
  // TUNE: keep 3x native map zoom; raise followRate for a tighter camera.
  const tuning={mapScale:3,characterScale:.72,heroFootRadius:9,bossFootRadius:17,
    propBaseFraction:.86,followRate:9,navCell:24,routeInterval:.35};
  const props=[];let mask=null,mapWidth=608,mapHeight=400,viewY=0,owner=null;
  let nav=null,links=null,field=null,cols=0,rows=0,nextRoute=0,routeGoal=-1,routeBuilds=0;
  const perf={updateMs:0,drawMs:0,maxUpdateMs:0,frames:0};
  const active=()=>phase==='boss'&&boss?.art==='adapted';
  const origin=()=>L-100;
  const width=()=>mapWidth*tuning.mapScale,height=()=>mapHeight*tuning.mapScale;
  const bounds=()=>({left:origin()+24,right:origin()+width()-24,top:20,bottom:height()-48});
  const crop=()=>({x:(camera-origin())/tuning.mapScale,y:viewY/tuning.mapScale,w:W/tuning.mapScale,h:H/tuning.mapScale});
  function screenPoint(x,y){return{x:origin()+x*tuning.mapScale-camera,y:y*tuning.mapScale-viewY};}
  function prepare(data,images,paint){
    props.length=0;mapWidth=data.crop.w;mapHeight=data.crop.h;
    const terrain=document.createElement('canvas');terrain.width=mapWidth;terrain.height=mapHeight;
    const g=terrain.getContext('2d',{willReadFrequently:true}),palette=new Set();
    // The base water layer supplies its own palette; no guessed rectangles.
    for(const tile of data.layers[0].tiles)paint(g,tile);
    const water=g.getImageData(0,0,mapWidth,mapHeight).data;
    for(let i=0;i<water.length;i+=4)if(water[i+3]>200)palette.add((water[i]<<16)|(water[i+1]<<8)|water[i+2]);
    g.clearRect(0,0,mapWidth,mapHeight);
    const groups=new Map();
    for(const layer of data.layers){if(!layer.visible)continue;
      for(const t of layer.tiles){
        const s=data.tilesets[t[2]];
        if(s.name!=='Objects'){paint(g,t);continue;}
        let sx=t[3]%s.columns*16,sy=Math.floor(t[3]/s.columns)*16;
        if(t[4]&2)[sx,sy]=[sy,sx];if(t[4]&8)sx=-sx;if(t[4]&4)sy=-sy;
        // A copied Tiled object retains its atlas-to-map offset, including flips.
        const key=[t[0]-sx,t[1]-sy,t[4]].join(',');
        if(!groups.has(key))groups.set(key,[]);groups.get(key).push(t);
      }
    }
    const pixels=g.getImageData(0,0,mapWidth,mapHeight).data;mask=new Uint8Array(mapWidth*mapHeight);
    for(let i=0;i<mask.length;i++){const k=i*4;mask[i]=pixels[k+3]<128||palette.has((pixels[k]<<16)|(pixels[k+1]<<8)|pixels[k+2])?1:0;}
    const removed=new Set();
    for(const tiles of groups.values()){
      // Split disconnected copies sharing an offset into independent scenery.
      const remaining=new Set(tiles);
      while(remaining.size){const part=[remaining.values().next().value];remaining.delete(part[0]);
        for(let i=0;i<part.length;i++)for(const t of remaining)if(Math.abs(t[0]-part[i][0])+Math.abs(t[1]-part[i][1])<=16){part.push(t);remaining.delete(t);}
        const x=Math.min(...part.map(t=>t[0])),y=Math.min(...part.map(t=>t[1]));
        const w=Math.max(...part.map(t=>t[0]))-x+16,h=Math.max(...part.map(t=>t[1]))-y+16;
        if(h<48)continue; // Flat bones, pebbles and ground clutter stay on ground.
        const image=document.createElement('canvas');image.width=w;image.height=h;
        const pg=image.getContext('2d');pg.translate(-x,-y);for(const t of part){paint(pg,t);removed.add(t);}
        pg.setTransform(1,0,0,1,0,0);pg.globalCompositeOperation='source-atop';pg.fillStyle='rgba(41,23,47,.42)';pg.fillRect(0,0,w,h);
        props.push({x,y,w,h,image,baseY:y+h*tuning.propBaseFraction});
      }
    }
    buildNavigation();
    return removed;
  }

  function wet(x,y){const mx=Math.floor(x/tuning.mapScale),my=Math.floor(y/tuning.mapScale);
    if(!mask)return false;return mx<0||my<0||mx>=mapWidth||my>=mapHeight||!!mask[my*mapWidth+mx];}
  const offsets=[[1,0],[-1,0],[0,1],[0,-1],[.707,.707],[-.707,.707],[.707,-.707],[-.707,-.707],[0,0]];
  function safe(x,y,r=9){
    if(x<r||x>width()-r||y<44||y>height()-24)return false;
    for(const [dx,dy]of offsets)if(wet(x+dx*r,y+dy*r))return false;return true;
  }
  function nearest(x,y,r=9){
    x=Math.max(r,Math.min(width()-r,x));y=Math.max(44,Math.min(height()-24,y));
    if(safe(x,y,r))return{x,y};
    // Spawn recovery only. Never run this expensive search for every path edge.
    for(let d=8;d<Math.max(width(),height());d+=8)for(let i=0;i<24;i++){
      const a=i*Math.PI/12,nx=x+Math.cos(a)*d,ny=y+Math.sin(a)*d;if(safe(nx,ny,r))return{x:nx,y:ny};
    }return{x,y};
  }
  function resolve(from,to,r){
    let q=safe(from.x,from.y,r)?{...from}:nearest(from.x,from.y,r);
    const dx=to.x-from.x,dy=to.y-from.y,n=Math.max(1,Math.ceil(Math.hypot(dx,dy)/4));
    for(let i=0;i<n;i++){const x=q.x+dx/n,y=q.y+dy/n;
      if(safe(x,y,r))q={x,y};else{if(safe(x,q.y,r))q.x=x;if(safe(q.x,y,r))q.y=y;}
    }return q;
  }
  function constrainActor(a,from){
    if(!active()||!mask||!a)return;const r=a===boss?tuning.bossFootRadius:tuning.heroFootRadius;
    const q=resolve({x:from.x-origin(),y:from.y+24},{x:a.x-origin(),y:a.y+24},r);
    a.x=origin()+q.x;a.y=q.y-24;
  }
  function buildNavigation(){
    const s=tuning.navCell;cols=Math.ceil(width()/s);rows=Math.ceil(height()/s);
    nav=new Uint8Array(cols*rows);links=new Uint8Array(cols*rows);field=new Int32Array(cols*rows);field.fill(-1);
    for(let y=0;y<rows;y++)for(let x=0;x<cols;x++)nav[y*cols+x]=safe((x+.5)*s,(y+.5)*s,tuning.bossFootRadius)?1:0;
    for(let i=0;i<nav.length;i++)if(nav[i])for(let k=0;k<4;k++){
      const [dx,dy]=offsets[k],x=i%cols,y=Math.floor(i/cols),nx=x+dx,ny=y+dy,j=ny*cols+nx;
      if(nx<0||nx>=cols||ny<0||ny>=rows||!nav[j])continue;
      // Precompute all edge clearance once; runtime BFS only visits integers.
      if(safe((x+.5+dx*.5)*s,(y+.5+dy*.5)*s,tuning.bossFootRadius))links[i]|=1<<k;
    }
    nextRoute=0;routeGoal=-1;
  }
  function cell(x,y){return Math.max(0,Math.min(rows-1,Math.floor(y/tuning.navCell)))*cols+Math.max(0,Math.min(cols-1,Math.floor(x/tuning.navCell)));}
  function dryCell(x,y){
    let id=cell(x,y);if(nav[id])return id;
    let best=Infinity,result=-1;
    for(let i=0;i<nav.length;i++)if(nav[i]){const d=Math.abs(i%cols-id%cols)+Math.abs(Math.floor(i/cols)-Math.floor(id/cols));if(d<best){best=d;result=i;}}
    return result;
  }
  function refreshRoute(){
    if(!nav||time<nextRoute)return;nextRoute=time+tuning.routeInterval;
    const goal=dryCell(p.x-origin(),p.y+24);if(goal===routeGoal)return;
    routeGoal=goal;routeBuilds++;field.fill(-1);if(goal<0)return;
    const q=new Int32Array(nav.length);let head=0,tail=1;q[0]=goal;field[goal]=0;
    while(head<tail){const id=q[head++];for(let k=0;k<4;k++)if(links[id]&(1<<k)){
      const [dx,dy]=offsets[k],next=id+dx+dy*cols;if(field[next]>=0)continue;field[next]=field[id]+1;q[tail++]=next;
    }}
  }
  const neutral=adaptedNeutralMove;
  adaptedNeutralMove=function(b,dt){
    if(!active()||!nav)return neutral(b,dt);
    refreshRoute();const start=dryCell(b.x-origin(),b.y+24),d=dist(b,p);
    if(d<170)return neutral(b,dt);
    let next=start;
    if(start>=0)for(let k=0;k<4;k++)if(links[start]&(1<<k)){const [dx,dy]=offsets[k],j=start+dx+dy*cols;if(field[j]>=0&&(field[next]<0||field[j]<field[next]))next=j;}
    const s=tuning.navCell,target=next===start?{x:p.x,y:p.y}:{x:origin()+(next%cols+.5)*s,y:(Math.floor(next/cols)+.5)*s-24};
    const a=Math.atan2(target.y-b.y,target.x-b.x),speed=205+(b.phaseIndex-1)*25+(d>380?65:0);
    b.moveSpeed=speed;b.x+=Math.cos(a)*speed*dt;b.y+=Math.sin(a)*speed*dt;adaptedArena(b);
  };
  function follow(dt=1){
    const tx=clamp(p.x-W*.5,origin(),origin()+Math.max(0,width()-W));
    const ty=clamp(p.y-H*.5,-70,Math.max(-70,height()-(H-74)));
    const t=1-Math.exp(-tuning.followRate*dt);camera+=(tx-camera)*t;viewY+=(ty-viewY)*t;
  }
  function ensure(){
    if(owner===boss)return;owner=boss;camera=origin();viewY=0;nextRoute=0;routeGoal=-1;
    // Each retry gets a clean camera and route cache; no positions are rebased.
  }
  const oldUpdate=update;
  update=function(dt){
    if(!active()){oldUpdate(dt);return;}
    const begin=performance.now();ensure();window.PRINCESS_MAP.renderAt(time);
    if(mouse.screenY!==undefined&&!adaptedLab?.active)mouse.y=mouse.screenY+viewY;
    const previous=[p,boss,ally,...enemies].filter(Boolean).map(a=>({a,x:a.x,y:a.y}));
    oldUpdate(dt);
    if(active()){for(const q of previous)constrainActor(q.a,q);follow(dt);}
    else{viewY=0;owner=null;if(mouse.screenY!==undefined)mouse.y=mouse.screenY;}
    const ms=performance.now()-begin;perf.updateMs=perf.updateMs*.95+ms*.05;perf.maxUpdateMs=Math.max(perf.maxUpdateMs,ms);perf.frames++;
  };
  function scaled(a,fn){ctx.save();const x=a.x-camera,y=a.y+24;ctx.translate(x,y);ctx.scale(tuning.characterScale,tuning.characterScale);ctx.translate(-x,-y);fn();ctx.restore();}
  const pose=paintAdaptedPose;
  paintAdaptedPose=function(b,x=b.x,y=b.y,alpha=1){if(active())scaled({x,y},()=>pose(b,x,y,alpha));else pose(b,x,y,alpha);};
  const oldDraw=draw;
  draw=function(){
    if(!active()){oldDraw();return;}
    const begin=performance.now();ensure();window.PRINCESS_MAP.renderAt(time);
    ctx.clearRect(0,0,W,H);ctx.fillStyle='#17131e';ctx.fillRect(0,0,W,H);
    ctx.save();ctx.beginPath();ctx.rect(0,70,W,H-144);ctx.clip();ctx.imageSmoothingEnabled=false;
    ctx.translate(0,-viewY);
    ctx.drawImage(window.PRINCESS_MAP.surface,origin()-camera,0,width(),height());
    ctx.fillStyle='rgba(41,23,47,.42)';ctx.fillRect(0,viewY+70,W,H-144);
    drawBloodStains();drawSwordDashGhosts();
    const queue=[];
    for(const q of props){
      const x=origin()+q.x*tuning.mapScale-camera,y=q.y*tuning.mapScale,w=q.w*tuning.mapScale,h=q.h*tuning.mapScale;
      if(x+w<0||x>W||y+h<viewY+70||y>viewY+H-74)continue; // Offscreen scenery costs no draw call.
      // Fade only foreground scenery overlapping the hero. Depth ordering is
      // preserved, while the player can still read attacks behind a canopy.
      const coversHero=q.baseY*tuning.mapScale>p.y+24&&p.x-camera>x-16&&p.x-camera<x+w+16&&p.y>y&&p.y-55<y+h;
      // A huge summon can legitimately pass behind a tree, but it must not be
      // swallowed whole by one. Foreground scenery remains in front, softened
      // just enough to preserve the creature's silhouette and its contact shadow.
      const coversSummon=(window.SHADOW_ROSTER?.state?.beasts||[]).some(a=>a&&!a.defeated&&q.baseY*tuning.mapScale>a.y+24&&a.x-camera>x-42&&a.x-camera<x+w+42&&a.y>y&&a.y-150<y+h);
      queue.push({y:q.baseY*tuning.mapScale,paint:()=>{ctx.save();ctx.globalAlpha=coversHero?.34:coversSummon?.22:1;ctx.drawImage(q.image,x,y,w,h);ctx.restore();}});
    }
    for(const e of enemies)queue.push({y:e.y+24,paint:()=>img(e.kind,e.x,e.y,e.r*2)});
    if(boss)queue.push({y:boss.y+24,paint:()=>drawAdaptedBoss(boss)});
    if(ally)queue.push({y:ally.y+24,paint:()=>scaled(ally,()=>{img('ally',ally.x,ally.y,48,ally.a);if(ally.hasRocketLauncher)img('rocket-launcher',ally.x,ally.y,30,ally.a);})});
    queue.push({y:p.y+24,paint:()=>scaled(p,drawHero)});
    // Summons register their bodies here rather than drawing as a late overlay.
    // They now share the hero/boss depth sort, so foreground trees and ruins
    // can cover them naturally when they walk behind the scenery.
    window.SHADOW_ROSTER?.queueWorldActors?.(queue,scaled);
    queue.sort((a,b)=>a.y-b.y);for(const q of queue)q.paint();
    for(const q of pickups)img(q.type==='heal'?'buff':q.type,q.x,q.y,40);
    for(const s of shots)if(s.x>camera-50&&s.x<camera+W+50&&s.y>viewY&&s.y<viewY+H)img(s.kind==='rocket'?'rocket':s.team==='enemy'?'adapted-bullet':'bullet',s.x,s.y,s.s*3,s.a);
    for(const q of particles){ctx.globalAlpha=Math.min(1,q.life*3);ctx.fillStyle=q.c;ctx.fillRect(q.x-camera-q.size/2,q.y-q.size/2,q.size,q.size);}ctx.globalAlpha=1;
    drawCleaveEchoes();drawShield();drawUltimateFx();window.PRINCESS_HUNT?.draw();
    ctx.restore();
    ctx.fillStyle='#090a12ee';ctx.fillRect(0,0,W,66);ctx.fillRect(0,H-74,W,74);
    bar(325,22,630,18,boss.hp,boss.max,'#e53d55',boss.name);
    ctx.font='11px monospace';ctx.textAlign='left';ctx.fillStyle='#fff3d3';ctx.fillText('THE ADAPTED ONE — CURSED LAND',22,22);
    const conductor=window.selectedHero==='shadow-summoner',roster=window.SHADOW_ROSTER?.state;
    bar(28,H-39,250,14,p.hp,p.maxHp,'#52d6df',conductor?'UMBRAL CONDUCTOR':'VANGUARD');
    const power=conductor?'CONTRACT '+(roster?.selected||1):p.weapon.toUpperCase(),cooldown=conductor?(roster?.ultCd||0):p.ultimateCooldown;
    ctx.fillStyle='#86f7ff';ctx.fillText('DASH '+('◆'.repeat(p.dash))+'  [SPACE]     '+power+'     '+(conductor?'ULT [E/F] ':'SPECIAL [E] ')+(cooldown>0?cooldown.toFixed(1)+'s':'READY'),28,H-56);
    const bx=boss.x-camera,by=boss.y-viewY;
    if(bx<30||bx>W-30||by<85||by>H-90){const a=Math.atan2(by-H/2,bx-W/2),x=clamp(bx,35,W-35),y=clamp(by,88,H-96);ctx.save();ctx.translate(x,y);ctx.rotate(a);ctx.fillStyle='#ffbb77';ctx.beginPath();ctx.moveTo(14,0);ctx.lineTo(-8,-9);ctx.lineTo(-8,9);ctx.closePath();ctx.fill();ctx.restore();}
    if(flash){ctx.fillStyle='rgba(255,70,80,'+(flash*.25)+')';ctx.fillRect(0,70,W,H-144);}
    const ms=performance.now()-begin;perf.drawMs=perf.drawMs*.95+ms*.05;
  };
  window.PRINCESS_TERRAIN={tuning,crop,prepare,props,wet,safe,resolve,nearest,constrainActor,active,bounds,follow,
    get viewY(){return active()?viewY:0;},get cameraX(){return camera;},
    stats:()=>({...perf,routeBuilds,navigationNodes:nav?.length||0,worldWidth:width(),worldHeight:height(),cameraX:camera-origin(),cameraY:viewY})};
  if(typeof developerMode!=='undefined'&&developerMode){
    const controls=document.querySelector('#adaptedLab .lab-controls');
    if(controls){
      const out=document.createElement('pre');out.id='terrainResults';out.style.whiteSpace='pre-wrap';controls.parentNode.appendChild(out);
      const add=(label,fn)=>{const button=document.createElement('button');button.textContent=label;button.onclick=fn;controls.appendChild(button);};
      add('Test whole-map water',()=>{window.PRINCESS_MAP.renderAt(time);let checks=0,failures=0;
        for(let y=60;y<height()-24;y+=60)for(let x=24;x<width()-24;x+=60)if(wet(x,y))for(const r of [9,17]){
          const start=nearest(x,y,r);for(const end of [{x,y},{x:x+240,y},{x,y:y+240}]){checks++;const q=resolve(start,end,r);if(!safe(q.x,q.y,r))failures++;}
        }
        out.textContent=(failures?'FAIL':'PASS')+': '+checks+' full-map water sweeps. '+JSON.stringify(window.PRINCESS_TERRAIN.stats());
      });
      let corner=0;
      add('Tour map corners',()=>{if(!active())return;const q=[[60,80],[width()-60,80],[width()-60,height()-80],[60,height()-80]][corner++%4],n=nearest(...q,9);
        p.x=origin()+n.x;p.y=n.y-24;follow(2);pause(true);document.querySelector('#pausePanel').hidden=true;draw();
        out.textContent='Corner '+corner+' — world '+Math.round(n.x)+','+Math.round(n.y)+'. Press P to resume. '+JSON.stringify(window.PRINCESS_TERRAIN.stats());
      });
      add('Frame timing',()=>{out.textContent=JSON.stringify(window.PRINCESS_TERRAIN.stats());});
    }
  }
})();
