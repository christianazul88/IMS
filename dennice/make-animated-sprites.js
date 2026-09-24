// Low-resolution starter sprites. Replace any PNG in assets/ with your own art later.
const fs=require('fs'), path=require('path'), zlib=require('zlib'), W=128,H=128;
const table=Uint32Array.from({length:256},(_,n)=>{let c=n;for(let i=0;i<8;i++)c=c&1?0xedb88320^(c>>>1):c>>>1;return c>>>0});
const crc=b=>{let c=0xffffffff;for(const x of b)c=table[(c^x)&255]^(c>>>8);return(c^0xffffffff)>>>0};
const chunk=(t,d)=>{const n=Buffer.alloc(4),c=Buffer.alloc(4),type=Buffer.from(t);n.writeUInt32BE(d.length);c.writeUInt32BE(crc(Buffer.concat([type,d])));return Buffer.concat([n,type,d,c])};
function png(name,paint){const px=Buffer.alloc(W*H*4);const set=(x,y,c)=>{if(x<0||y<0||x>=W||y>=H)return;const i=(y*W+x)*4;px[i]=c[0];px[i+1]=c[1];px[i+2]=c[2];px[i+3]=c[3]??255};const rect=(x,y,w,h,c)=>{for(let j=y;j<y+h;j++)for(let i=x;i<x+w;i++)set(i,j,c)};const disk=(x,y,r,c)=>{for(let j=-r;j<=r;j++)for(let i=-r;i<=r;i++)if(i*i+j*j<=r*r)set(x+i,y+j,c)};const poly=(pts,c)=>{let lo=Math.max(0,Math.floor(Math.min(...pts.map(v=>v[1])))),hi=Math.min(H-1,Math.ceil(Math.max(...pts.map(v=>v[1]))));for(let y=lo;y<=hi;y++){let xs=[];for(let i=0;i<pts.length;i++){let a=pts[i],b=pts[(i+1)%pts.length];if((a[1]<=y&&b[1]>y)||(b[1]<=y&&a[1]>y))xs.push(a[0]+(y-a[1])*(b[0]-a[0])/(b[1]-a[1]))}xs.sort((a,b)=>a-b);for(let i=0;i<xs.length;i+=2)for(let x=Math.ceil(xs[i]);x<=Math.floor(xs[i+1]??xs[i]);x++)set(x,y,c)}};paint({set,rect,disk,poly});const raw=Buffer.alloc((W*4+1)*H);for(let y=0;y<H;y++){raw[y*(W*4+1)]=0;px.copy(raw,y*(W*4+1)+1,y*W*4,(y+1)*W*4)}const ih=Buffer.alloc(13);ih.writeUInt32BE(W);ih.writeUInt32BE(H,4);ih[8]=8;ih[9]=6;const data=Buffer.concat([Buffer.from([137,80,78,71,13,10,26,10]),chunk('IHDR',ih),chunk('IDAT',zlib.deflateSync(raw)),chunk('IEND',Buffer.alloc(0))]);fs.writeFileSync(path.join(__dirname,'assets',name),data)}
function draw(kind,state,frame){
  return p=>{
    const O=[20,17,31],skin=[248,198,131],gold=[255,218,111];
    if(kind==='hero'){
      /* Hero animation sheets are intentionally primitive and replaceable.
       * Add detail here, or replace the generated PNGs with hand-painted art. */
      const idleBob=[0,2,2,0,-2,-2][frame%6]||0;
      const moving=state==='run', firing=state==='fire'||state==='action', dashing=state==='dash', hurtState=state==='hurt';
      const bob=moving?idleBob:(dashing?(frame%2?-3:-6):(firing?(frame%2?2:0):idleBob));
      const stride=moving?[-3,0,3,2,0,-2][frame%6]:0;
      const lean=dashing?(frame%2?3:-3):(firing?2:0);
      p.disk(64,67+bob,43,[18,34,57,150]);
      if(dashing){p.rect(22,76+bob,26,5,[102,237,244,110]);p.rect(16,84+bob,20,4,[102,237,244,75])}
      p.rect(37+lean,60+bob,54,48,O);
      p.disk(64+lean,42+bob,26,[82,210,230]);
      p.disk(64+lean,45+bob,17,skin);
      p.rect(38+lean,62+bob,53,43,[203,62,82]);
      p.rect(48+lean,72+bob,32,26,[48,73,115]);
      p.rect(50+lean,40+bob,7,7,O);p.rect(71+lean,40+bob,7,7,O);
      p.rect(48+lean,30+bob,33,7,[125,241,249]);
      /* Separate feet make the stride readable even at the game's small size. */
      p.rect(42+stride,101+bob,14,12,O);p.rect(72-stride,101+bob,14,13,O);
      if(firing){
        p.rect(79+lean,56+bob,35,8,O);p.rect(83+lean,53+bob,34,7,[220,243,249]);p.rect(105+lean,50+bob,11,5,gold);
        if(state==='fire'&&frame%2===1)p.rect(116+lean,49+bob,8,9,[255,232,124]);
      }else if(dashing){
        p.rect(77+lean,53+bob,28,7,O);p.rect(85+lean,50+bob,20,6,[151,246,249]);
      }else{
        p.rect(80+lean,65+bob,24,8,O);p.rect(82+lean,62+bob,19,7,[220,243,249]);
      }
      if(hurtState){p.rect(31,54+bob,66,7,[255,92,104,165]);p.rect(43,112+bob,42,5,[255,92,104,135])}
    }else if(kind==='boss'){
      const bob=frame?2:0,action=state==='action';p.disk(64,69+bob,51,[63,20,48,160]);p.rect(25,60+bob,78,51,O);p.disk(64,39+bob,31,[204,45,76]);p.disk(64,44+bob,18,skin);p.rect(32,66+bob,64,42,[91,24,56]);p.rect(41,17+bob,10,20,O);p.rect(77,17+bob,10,20,O);p.rect(53,42+bob,7,8,O);p.rect(70,42+bob,7,8,O);p.rect(47,29+bob,34,7,[255,103,116]);if(action){p.rect(86,38+bob,9,53,O);p.rect(88,40+bob,5,45,[238,189,92]);p.disk(91,33+bob,15,[244,73,106])}else{p.rect(19,62+bob,19,7,[209,58,90]);p.rect(91,62+bob,19,7,[209,58,90])}
    }else{
      const bob=frame?2:0,action=state==='action';p.disk(64,67+bob,40,[93,58,118,120]);p.disk(64,43+bob,20,skin);p.rect(40,58+bob,48,49,[120,73,150]);p.rect(45,69+bob,38,28,[204,124,192]);p.rect(45,22+bob,8,14,gold);p.rect(60,17+bob,8,19,gold);p.rect(75,22+bob,8,14,gold);p.rect(53,40+bob,6,6,[255,244,214]);p.rect(70,40+bob,6,6,[255,244,214]);if(action){p.rect(83,54+bob,20,8,skin);p.rect(97,48+bob,7,14,skin);p.disk(101,45+bob,5,[255,221,111])}else{p.rect(28,60+bob,14,8,[189,105,179])}
    }
  };
}

// The hero uses the directional sheets below.  Keep hero-idle-1.png as a
// tiny boot fallback for the first canvas paint before the directional art is
// loaded; the older flat hero animation sheets are intentionally not emitted.
png('hero-idle-1.png',draw('hero','idle',0));
for(const kind of ['boss','princess'])for(const state of ['idle','action'])for(let frame=0;frame<2;frame++)png(`${kind}-${state}-${frame+1}.png`,draw(kind,state,frame));

// Eight-direction hero sheets. These are separate PNGs rather than rotating
// one drawing at runtime, so an upward-facing hero actually shows their back.
const heroDirections=['right','down-right','down','down-left','left','up-left','up','up-right'];
const directionAngle={right:0,'down-right':Math.PI/4,down:Math.PI/2,'down-left':Math.PI*3/4,left:Math.PI,'up-left':-Math.PI*3/4,up:-Math.PI/2,'up-right':-Math.PI/4};
function blade(p,x,y,a,len=57,w=10){
  const ux=Math.cos(a),uy=Math.sin(a),nx=-uy,ny=ux,tipX=x+ux*len,tipY=y+uy*len;
  p.poly([[x+nx*w,y+ny*w],[x-nx*w,y-ny*w],[tipX-nx*w*.55,tipY-ny*w*.55],[tipX+nx*w*.55,tipY+ny*w*.55]],[17,20,31]);
  p.poly([[x+nx*(w-3),y+ny*(w-3)],[x-nx*(w-3),y-ny*(w-3)],[tipX-nx*(w*.3),tipY-ny*(w*.3)],[tipX+nx*(w*.3),tipY+ny*(w*.3)],[tipX+ux*4,tipY+uy*4]],[205,218,231]);
  p.poly([[x+nx*2,y+ny*2],[x-nx*2,y-ny*2],[tipX,tipY],[tipX+ux*4,tipY+uy*4]],[245,249,255]);
  p.disk(x,y,12,[73,48,31]);p.disk(x,y,8,[255,210,86]);
}
function gun(p,x,y,a){
  const ux=Math.cos(a),uy=Math.sin(a),nx=-uy,ny=ux,endX=x+ux*38,endY=y+uy*38;
  p.poly([[x+nx*6,y+ny*6],[x-nx*6,y-ny*6],[endX-nx*4,endY-ny*4],[endX+nx*4,endY+ny*4]],[18,23,34]);
  p.poly([[x+nx*2,y+ny*2],[x-nx*2,y-ny*2],[endX-nx*2,endY-ny*2],[endX+nx*2,endY+ny*2]],[199,229,235]);
  p.disk(x,y,7,[255,213,111]);
}
// Keep this in sync with SWORD_FRAME_PATHS in game.js. Frame 1 pulls back,
// frames 2–4 travel through the cut, and frame 5 settles the weapon.
const swordComboPaths=[[-2.62,-2.05,-1.42,-.78,-.16],[2.62,2.05,1.42,.78,.16],[Math.PI,2.65,1.48,.26,.02]];
function swordSprite(dir,state,frame,comboStage=1){return p=>{
  const a=directionAngle[dir],bob=0,handX=64+Math.cos(a)*9,handY=68+bob+Math.sin(a)*7;
  const swing=state==='combo'?a+swordComboPaths[comboStage-1][frame%5]:a-1.18;
  const finisher=state==='combo'&&comboStage===3;
  // The isolated 128px sword canvas is rendered much larger than the hero
  // body at runtime. You can redraw these files without touching hero poses.
  blade(p,handX,handY,swing,finisher?54:50,finisher?17:14);
  p.disk(handX,handY,5,[244,82,63]);
}}
function directionalHero(dir,weapon,state,frame,comboStage=1,includeSword=true){return p=>{
  const a=directionAngle[dir],back=dir==='up'||dir==='up-left'||dir==='up-right',side=dir==='left'||dir==='right',sign=dir.includes('left')?-1:1;
  const bob=state==='run'?[0,-2,0,2][frame%4]:0,stride=state==='run'?[-4,1,4,-1][frame%4]:0;
  const ink=[18,19,31],hood=[55,194,219],hoodHi=[120,239,245],skin=[245,187,122],coat=[191,48,70],coatHi=[239,83,87],blue=[45,72,118],steel=[181,199,210],gold=[255,211,94];
  const handX=64+Math.cos(a)*12,handY=68+bob+Math.sin(a)*8;
  // For back-facing poses the heavy sword sits behind the cloak until its swing.
  p.disk(64,76+bob,37,[14,27,47,115]);
  p.rect(46+stride,94+bob,16,20,ink);p.rect(67-stride,94+bob,16,20,ink);
  p.rect(48+stride,91+bob,13,16,blue);p.rect(68-stride,91+bob,13,16,blue);
  if(back){
    p.disk(64,44+bob,24,hood);p.rect(47,44+bob,34,18,hood);p.rect(50,28+bob,28,7,hoodHi);
    p.poly([[37,60+bob],[91,60+bob],[96,101+bob],[76,108+bob],[64,96+bob],[51,108+bob],[31,101+bob]],ink);
    p.poly([[40,61+bob],[88,61+bob],[89,97+bob],[74,102+bob],[64,90+bob],[53,102+bob],[38,97+bob]],coat);
    p.rect(59,63+bob,10,34,coatHi);p.rect(47,65+bob,5,31,gold);p.rect(76,65+bob,5,31,gold);
  }else if(side){
    p.disk(63+sign*5,44+bob,24,hood);p.disk(64+sign*8,47+bob,15,skin);p.rect(47,29+bob,20,6,hoodHi);
    p.rect(44,61+bob,39,37,ink);p.rect(47,62+bob,34,34,coat);p.rect(56+sign*10,66+bob,15,22,steel);p.rect(48,65+bob,7,30,gold);
    p.rect(62+sign*10,44+bob,5,5,ink);
  }else{
    p.disk(64,43+bob,25,hood);p.disk(64,47+bob,16,skin);p.rect(47,29+bob,34,7,hoodHi);
    p.rect(48,43+bob,6,6,ink);p.rect(73,43+bob,6,6,ink);
    p.rect(37,60+bob,54,40,ink);p.rect(40,62+bob,48,35,coat);p.rect(51,66+bob,26,28,blue);p.rect(41,66+bob,8,23,steel);p.rect(79,66+bob,8,23,steel);p.rect(61,64+bob,6,30,gold);
  }
  if(weapon==='gun')gun(p,handX,handY,a);
  else if(includeSword){
    // Five frames make the first two cuts visibly reverse direction.  The
    // third path pulls the sword back, then drives it straight through.
    const swing=state==='combo'?a+swordComboPaths[comboStage-1][frame%5]:a-1.18;
    const heavy=state==='combo'&&comboStage===3;
    // Keep every blade inside the 128px canvas. The gameplay reach remains
    // independent in SWORD_COMBOS; these values only control sprite framing.
    blade(p,handX,handY,swing,heavy?48:state==='combo'?43:46,heavy?14:state==='combo'?11:10);
  }
}}
for(const weapon of ['gun','sword'])for(const dir of heroDirections){
  png(`hero-${weapon}-${dir}-idle-1.png`,directionalHero(dir,weapon,'idle',0));
  for(let frame=0;frame<4;frame++)png(`hero-${weapon}-${dir}-run-${frame+1}.png`,directionalHero(dir,weapon,'run',frame));
}
// Sword bodies deliberately contain no weapon; see sword-rest / sword-swing
// below for the separately editable oversized weapon layer.
for(const dir of heroDirections){
  png(`hero-sword-${dir}-idle-1.png`,directionalHero(dir,'sword','idle',0,1,false));
  for(let frame=0;frame<4;frame++)png(`hero-sword-${dir}-run-${frame+1}.png`,directionalHero(dir,'sword','run',frame,1,false));
  png(`sword-rest-${dir}.png`,swordSprite(dir,'idle',0));
  for(let combo=1;combo<=3;combo++)for(let frame=0;frame<5;frame++){
    png(`hero-sword-${dir}-combo-${combo}-${frame+1}.png`,directionalHero(dir,'sword','combo',frame,combo,false));
    png(`sword-swing-${dir}-combo-${combo}-${frame+1}.png`,swordSprite(dir,'combo',frame,combo));
  }
}
