// Generates intentionally low-resolution editable PNG sprites (mostly 128px transparent canvases; the wide Demon Lord body is 256×128).
const fs=require('fs'),path=require('path'),zlib=require('zlib'),W=128,H=128;
const table=Uint32Array.from({length:256},(_,n)=>{let c=n;for(let i=0;i<8;i++)c=c&1?0xedb88320^(c>>>1):c>>>1;return c>>>0});
const crc=b=>{let c=0xffffffff;for(const x of b)c=table[(c^x)&255]^(c>>>8);return(c^0xffffffff)>>>0};
const chunk=(t,d)=>{let n=Buffer.alloc(4),c=Buffer.alloc(4),q=Buffer.from(t);n.writeUInt32BE(d.length);c.writeUInt32BE(crc(Buffer.concat([q,d])));return Buffer.concat([n,q,d,c])};
function png(name,paint){let px=Buffer.alloc(W*H*4),set=(x,y,c)=>{if(x<0||y<0||x>=W||y>=H)return;let i=(y*W+x)*4;px[i]=c[0];px[i+1]=c[1];px[i+2]=c[2];px[i+3]=c[3]??255},rect=(x,y,w,h,c)=>{for(let j=y;j<y+h;j++)for(let i=x;i<x+w;i++)set(i,j,c)},disk=(x,y,r,c)=>{for(let j=-r;j<=r;j++)for(let i=-r;i<=r;i++)if(i*i+j*j<=r*r)set(x+i,y+j,c)},poly=(pts,c)=>{let minY=Math.max(0,Math.floor(Math.min(...pts.map(v=>v[1])))),maxY=Math.min(H-1,Math.ceil(Math.max(...pts.map(v=>v[1]))));for(let y=minY;y<=maxY;y++){let xs=[];for(let i=0;i<pts.length;i++){let a=pts[i],b=pts[(i+1)%pts.length];if((a[1]<=y&&b[1]>y)||(b[1]<=y&&a[1]>y))xs.push(a[0]+(y-a[1])*(b[0]-a[0])/(b[1]-a[1]))}xs.sort((a,b)=>a-b);for(let i=0;i<xs.length;i+=2)for(let x=Math.ceil(xs[i]);x<=Math.floor(xs[i+1]??xs[i]);x++)set(x,y,c)}};paint({set,rect,disk,poly});let raw=Buffer.alloc((W*4+1)*H);for(let y=0;y<H;y++){raw[y*(W*4+1)]=0;px.copy(raw,y*(W*4+1)+1,y*W*4,(y+1)*W*4)}let ih=Buffer.alloc(13);ih.writeUInt32BE(W);ih.writeUInt32BE(H,4);ih[8]=8;ih[9]=6;fs.writeFileSync(path.join(__dirname,'assets',name+'.png'),Buffer.concat([Buffer.from([137,80,78,71,13,10,26,10]),chunk('IHDR',ih),chunk('IDAT',zlib.deflateSync(raw)),chunk('IEND',Buffer.alloc(0))]))}
function pngSized(name,sw,sh,paint){let px=Buffer.alloc(sw*sh*4),set=(x,y,c)=>{if(x<0||y<0||x>=sw||y>=sh)return;let i=(y*sw+x)*4;px[i]=c[0];px[i+1]=c[1];px[i+2]=c[2];px[i+3]=c[3]??255},rect=(x,y,w,h,c)=>{for(let j=y;j<y+h;j++)for(let i=x;i<x+w;i++)set(i,j,c)},disk=(x,y,r,c)=>{for(let j=-r;j<=r;j++)for(let i=-r;i<=r;i++)if(i*i+j*j<=r*r)set(x+i,y+j,c)},poly=(pts,c)=>{let minY=Math.max(0,Math.floor(Math.min(...pts.map(v=>v[1])))),maxY=Math.min(sh-1,Math.ceil(Math.max(...pts.map(v=>v[1]))));for(let y=minY;y<=maxY;y++){let xs=[];for(let i=0;i<pts.length;i++){let a=pts[i],b=pts[(i+1)%pts.length];if((a[1]<=y&&b[1]>y)||(b[1]<=y&&a[1]>y))xs.push(a[0]+(y-a[1])*(b[0]-a[0])/(b[1]-a[1]))}xs.sort((a,b)=>a-b);for(let i=0;i<xs.length;i+=2)for(let x=Math.ceil(xs[i]);x<=Math.floor(xs[i+1]??xs[i]);x++)set(x,y,c)}};paint({set,rect,disk,poly});let raw=Buffer.alloc((sw*4+1)*sh);for(let y=0;y<sh;y++){raw[y*(sw*4+1)]=0;px.copy(raw,y*(sw*4+1)+1,y*sw*4,(y+1)*sw*4)}let ih=Buffer.alloc(13);ih.writeUInt32BE(sw);ih.writeUInt32BE(sh,4);ih[8]=8;ih[9]=6;fs.writeFileSync(path.join(__dirname,'assets',name+'.png'),Buffer.concat([Buffer.from([137,80,78,71,13,10,26,10]),chunk('IHDR',ih),chunk('IDAT',zlib.deflateSync(raw)),chunk('IEND',Buffer.alloc(0))]))}
const dark=[23,25,42],cyan=[89,225,238],gold=[255,210,105],red=[220,64,86],violet=[116,69,153];
function humanoid(c,extra=[]){return p=>{p.disk(64,70,44,[dark[0],dark[1],dark[2],150]);p.rect(35,58,58,51,c);p.disk(64,39,25,gold);p.rect(48,39,7,7,dark);p.rect(72,39,7,7,dark);p.rect(33,65,19,10,dark);p.rect(78,65,28,10,dark);extra.forEach(f=>f(p))}}
png('ally',humanoid([57,124,142],[p=>{p.rect(45,14,38,12,cyan);p.rect(91,53,23,7,gold)}]));
png('machinegun',p=>{p.rect(14,43,99,19,[111,149,162]);p.rect(30,61,25,41,[46,67,77]);p.rect(82,62,13,29,[234,173,67]);p.rect(16,49,22,7,[222,244,242]);p.rect(93,39,20,27,dark)});
png('sword',p=>{p.rect(59,8,10,74,[226,232,240]);p.rect(62,8,4,74,[255,255,255,220]);p.rect(59,72,10,10,[150,160,175]);p.rect(38,80,52,11,[201,169,92]);p.rect(48,80,32,11,[228,198,120]);p.rect(58,91,12,30,[92,61,31]);p.rect(60,91,8,30,[122,84,45]);p.disk(64,121,10,[214,183,104]);p.disk(64,121,5,[168,132,64])});
png('lifesteal',p=>{p.disk(64,50,30,[201,32,64]);p.rect(34,50,60,30,[201,32,64]);p.disk(94,50,30,[201,32,64]);p.rect(44,60,40,44,[70,6,20]);for(let y=64;y<96;y+=4)p.rect(60,y,8,3,[255,120,140,220]);p.rect(58,100,12,20,[255,60,90]);p.disk(64,120,7,[255,90,120])});
// Legacy boss art is archived below and deliberately not generated. Keep the
// block for reference while editing old regression fixtures.
if(false){
// ---- THE DEMON LORD: a single, fused silhouette.  These six-frame sheets replace
// the old disconnected head/body construction.  Every frame is a transparent 128px
// PNG so artists can later paint over the same filenames without changing the game.
function demonFrame(state,frame){return p=>{
  const bob=[0,1,2,1,0,-1][frame], ink=[10,4,12], wine=[67,8,24], crimson=[151,24,43], skin=[216,68,65], bone=[241,189,145], gold=[255,214,86], violet=[109,39,136], hot=[255,101,48];
  // A single 90s arcade-style digitized silhouette: face, torso, wings and limbs overlap.
  p.disk(64,108,57,[4,2,8,155]); p.rect(31,78+bob,23,40,ink); p.rect(74,78+bob,23,40,ink);
  // enormous bat wings framing the face
  p.poly([[51,24+bob],[25,8+bob],[4,17+bob],[19,35+bob],[2,51+bob],[33,48+bob],[49,61+bob]],wine);
  p.poly([[77,24+bob],[103,8+bob],[124,17+bob],[109,35+bob],[126,51+bob],[95,48+bob],[79,61+bob]],wine);
  p.poly([[48,27+bob],[27,16+bob],[15,21+bob],[35,31+bob],[14,42+bob],[42,40+bob]],crimson);
  p.poly([[80,27+bob],[101,16+bob],[113,21+bob],[93,31+bob],[114,42+bob],[86,40+bob]],crimson);
  // spider arms, eight segmented limbs with sharp claw tips
  const arms=[[[39,58],[22,55],[8,65],[3,61]],[[38,65],[22,69],[9,81],[2,78]],[[89,58],[106,55],[120,65],[125,61]],[[90,65],[106,69],[119,81],[126,78]],[[42,74],[27,86],[16,101],[8,103]],[[86,74],[101,86],[112,101],[120,103]],[[47,69],[34,74],[22,88],[15,87]],[[81,69],[94,74],[106,88],[113,87]]];
  for(const a of arms){p.rect(a[0][0],a[0][1]+bob,10,10,ink);p.poly([[a[0][0]+5,a[0][1]+bob+5],[a[1][0]+5,a[1][1]+bob+5],[a[2][0]+5,a[2][1]+bob+5],[a[3][0]+5,a[3][1]+bob+5]],crimson);p.disk(a[3][0]+5,a[3][1]+bob+5,5,gold)}
  // Two signature side limbs: each ends in a broad alien jaw with visible teeth.
  const jawOpen=state==='attack'||state==='laser';
  p.poly([[44,43+bob],[13,36+bob],[0,45+bob],[8,67+bob],[38,75+bob],[49,63+bob]],ink);
  p.poly([[84,43+bob],[115,36+bob],[128,45+bob],[120,67+bob],[90,75+bob],[79,63+bob]],ink);
  p.poly([[4,47+bob],[16,41+bob],[39,49+bob],[27,61+bob],[9,58+bob]],jawOpen?crimson:wine);
  p.poly([[124,47+bob],[112,41+bob],[89,49+bob],[101,61+bob],[119,58+bob]],jawOpen?crimson:wine);
  for(let i=0;i<6;i++){p.poly([[10+i*4,43+bob],[13+i*4,44+bob],[12+i*4,52+bob]],bone);p.poly([[118-i*4,43+bob],[115-i*4,44+bob],[116-i*4,52+bob]],bone)}
  // torso and armored rib cage
  p.poly([[38,51+bob],[90,51+bob],[103,101+bob],[78,119+bob],[50,119+bob],[25,101+bob]],wine); p.rect(41,58+bob,46,49,crimson); p.rect(48,65+bob,32,40,skin);
  for(let i=0;i<3;i++){p.rect(43+i*14,77+bob,10,20,ink);p.rect(45+i*14,77+bob,6,15,bone)}
  p.disk(64,98+bob,10,hot);p.disk(64,98+bob,4,gold);
  // huge woman-like face with crown horns, eyes, lips, and fangs
  p.disk(64,39+bob,32,skin); p.poly([[43,25+bob],[52,8+bob],[62,22+bob],[64,4+bob],[70,22+bob],[78,8+bob],[85,25+bob]],bone);
  p.rect(38,35+bob,10,7,ink);p.rect(80,35+bob,10,7,ink);p.disk(47,38+bob,6,gold);p.disk(81,38+bob,6,gold);p.rect(45,36+bob,4,3,[255,255,230]);p.rect(79,36+bob,4,3,[255,255,230]);
  p.rect(51,51+bob,26,6,[69,7,20]);p.rect(54,52+bob,20,3,[255,91,103]);p.rect(57,55+bob,4,8,bone);p.rect(67,55+bob,4,8,bone);
  // state-specific smear frames
  if(state==='attack'){p.rect(6,52+bob,20,8,hot);p.rect(102,52+bob,20,8,hot);p.disk(64,68+bob,15,violet)}
  if(state==='laser'){p.rect(33,48+bob,62,28,ink);p.rect(38,57+bob,52,13,hot);p.rect(42,59+bob,44,5,[255,255,220]);}
  if(state==='stomp'){p.poly([[15,98+bob],[45,91+bob],[64,111+bob],[83,91+bob],[113,98+bob],[94,114+bob],[34,114+bob]],hot);}
  if(state==='summon'){p.disk(14,72+bob,12,violet);p.disk(114,72+bob,12,violet);p.rect(10,56+bob,8,28,gold);p.rect(110,56+bob,8,28,gold);}
}}
// The old single-body demon sheets are intentionally no longer emitted. The
// current fight uses demon-core, demon-flank, and repeated demon-neck sheets.
// ---- Three-head shrine set. The core and flank heads are separate transparent
// sprites so the renderer can keep their sockets fixed while the flank necks stretch.
function demonCoreFrame(state,frame){return p=>{const bob=[0,1,2,1,0,-1][frame],ink=[14,15,20],bone=[190,194,187],light=[235,236,216],red=[137,20,28],glow=[255,57,45],dark=[37,38,45];
  p.rect(54,92+bob,20,36,ink);p.poly([[26,25+bob],[42,8+bob],[55,20+bob],[64,2+bob],[73,20+bob],[86,8+bob],[102,25+bob],[93,81+bob],[35,81+bob]],bone);
  p.disk(64,53+bob,35,light);p.poly([[25,36+bob],[8,17+bob],[16,8+bob],[35,26+bob]],dark);p.poly([[103,36+bob],[120,17+bob],[112,8+bob],[93,26+bob]],dark);
  p.disk(64,19+bob,10,glow);p.disk(64,19+bob,5,[255,178,62]);p.rect(40,44+bob,18,8,red);p.rect(70,44+bob,18,8,red);p.disk(49,48+bob,5,glow);p.disk(79,48+bob,5,glow);
  p.rect(38,61+bob,52,20,[25,13,18]);p.rect(43,65+bob,42,8,red);for(let i=0;i<6;i++)p.poly([[45+i*7,68+bob],[49+i*7,68+bob],[47+i*7,79+bob]],bone);p.rect(48,81+bob,32,5,dark);
  for(let i=0;i<3;i++){p.rect(31+i*22,28+bob,8,19,red);p.rect(33+i*22,29+bob,4,14,[222,46,49])}
  if(state==='laser'){p.rect(28,57+bob,72,28,ink);p.rect(34,63+bob,60,14,glow);p.rect(39,66+bob,50,6,[255,255,220]);}
  if(state==='attack'){p.rect(31,53+bob,10,25,red);p.rect(87,53+bob,10,25,red);p.disk(64,87+bob,8,glow)}
}}
function demonFlankFrame(state,frame){return p=>{const bob=[0,1,2,1,0,-1][frame],ink=[13,14,19],bone=[190,194,184],light=[224,226,205],red=[126,19,30],glow=[255,57,45];
  p.rect(56,84+bob,16,42,ink);p.poly([[19,31+bob],[36,11+bob],[63,22+bob],[104,31+bob],[113,58+bob],[96,85+bob],[30,85+bob]],bone);p.disk(66,51+bob,32,light);
  p.disk(48,46+bob,6,glow);p.disk(69,40+bob,5,glow);p.disk(85,48+bob,6,glow);p.disk(66,61+bob,7,red);p.disk(66,61+bob,3,[255,198,78]);
  p.rect(34,65+bob,65,18,ink);p.rect(40,70+bob,53,8,red);for(let i=0;i<7;i++)p.poly([[42+i*7,72+bob],[46+i*7,72+bob],[44+i*7,82+bob]],bone);
  p.poly([[20,24+bob],[8,8+bob],[28,15+bob],[36,31+bob]],red);p.poly([[111,24+bob],[124,8+bob],[103,15+bob],[96,31+bob]],red);
  if(state==='attack'){p.rect(22,59+bob,19,29,red);p.rect(88,59+bob,19,29,red);p.rect(47,82+bob,39,8,glow)}
}}
for(const state of ['idle','attack','laser'])for(let f=0;f<6;f++)png(`demon-core-${state}-${f+1}`,demonCoreFrame(state,f));
for(const state of ['idle','attack'])for(let f=0;f<6;f++)png(`demon-flank-${state}-${f+1}`,demonFlankFrame(state,f));
function demonNeckFrame(state,frame){return p=>{const bob=[0,1,2,1,0,-1][frame],ink=[16,17,22],deep=[37,38,45],bone=[156,160,154],hi=[218,218,192],hot=[176,35,40];
  p.rect(42,4+bob,44,120,ink);p.poly([[42,6+bob],[30,22+bob],[42,43+bob],[30,64+bob],[42,86+bob],[30,108+bob],[42,123+bob],[86,123+bob],[98,108+bob],[86,86+bob],[98,64+bob],[86,43+bob],[98,22+bob],[86,6+bob]],deep);
  for(let y=15;y<120;y+=18){p.rect(36,y+bob,56,8,bone);p.rect(43,y+2+bob,42,3,hi);p.rect(43,y+6+bob,42,2,ink)}
  p.rect(54,10+bob,20,108,state==='expand'?hot:bone);p.rect(59,10+bob,10,108,state==='expand'?hi:deep);
}}
for(const state of ['idle','expand'])for(let f=0;f<6;f++)png(`demon-neck-${state}-${f+1}`,demonNeckFrame(state,f));
// The two red bars below (x=18..60 and x=196..238, y=91..99) are the
// intentional neck-source marks. DEMON_NECK_TUNING in game.js maps them to
// fixed world sockets; keep these marks and the tuning block in sync if the
// body artwork is redrawn later.
pngSized('demon-body-sockets',256,128,p=>{const ink=[13,14,19],deep=[35,36,43],wing=[26,25,34],bone=[126,130,128],hi=[199,201,188],red=[139,24,33],hot=[194,32,42];p.poly([[8,18],[36,5],[75,14],[104,2],[128,18],[152,2],[181,14],[220,5],[248,18],[238,58],[250,100],[218,119],[178,126],[128,118],[78,126],[38,119],[6,100],[18,58]],deep);p.poly([[10,22],[40,10],[72,20],[100,34],[82,47],[50,42],[22,58],[5,48]],wing);p.poly([[246,22],[216,10],[184,20],[156,34],[174,47],[206,42],[234,58],[251,48]],wing);p.rect(64,42,128,80,ink);p.poly([[74,34],[112,20],[128,30],[144,20],[182,34],[174,112],[128,124],[82,112]],deep);for(let y=48;y<116;y+=14){p.rect(70,y,116,7,bone);p.rect(80,y+2,96,3,hi);p.rect(78,y+7,100,3,ink)}p.rect(120,34,16,88,[82,12,23]);p.rect(124,38,8,80,red);p.disk(100,86,22,ink);p.disk(156,86,22,ink);p.disk(100,86,16,red);p.disk(156,86,16,red);p.disk(100,86,7,hi);p.disk(156,86,7,hi);p.rect(18,91,42,8,hot);p.rect(196,91,42,8,hot);p.poly([[40,104],[68,93],[86,112],[56,120]],wing);p.poly([[216,104],[188,93],[170,112],[200,120]],wing)});
// No Mercy hero shield (rotates around the hero in-game) and the rocket-pod
// rapid-fire buff icon (dropped occasionally once the Siege Pod is picked up).
png('shield',p=>{const rim=[214,224,255],glass=[123,227,255],dark=[27,42,66],core=[255,255,255];p.disk(64,64,58,dark);p.disk(64,64,52,rim);p.disk(64,64,44,glass);p.rect(60,14,8,100,[255,255,255,90]);p.rect(14,60,100,8,[255,255,255,60]);p.disk(64,64,14,core);p.disk(64,64,8,[126,220,255])});
png('rocket-buff',p=>{const body=[94,104,100],hi=[204,216,193],flame=[255,157,64],flame2=[255,214,120];p.rect(28,50,72,28,body);p.rect(40,54,52,20,hi);p.disk(96,64,15,[247,98,48]);p.poly([[8,54],[30,60],[30,68],[8,74]],flame);p.poly([[4,58],[20,64],[20,70],[4,76]],flame2)});
png('rocket-launcher',p=>{p.rect(12,43,94,24,[42,55,61]);p.rect(18,47,73,8,[196,213,202]);p.rect(88,37,28,35,[24,31,37]);p.rect(42,65,23,39,[103,57,41]);p.rect(15,49,18,11,[255,123,55]);p.disk(106,55,10,[244,218,133])});
png('rocket',p=>{p.rect(15,51,86,26,[94,104,100]);p.rect(25,55,70,18,[204,216,193]);p.disk(102,64,15,[247,98,48]);p.rect(8,52,18,10,[255,187,64]);p.rect(8,66,18,10,[255,187,64]);p.rect(93,57,22,14,[255,230,135])});
function beamTexture(seed){return p=>{for(let y=20;y<108;y++){let dy=Math.abs(y-64)/26;if(dy>1.5)continue;let core=Math.max(0,1-dy*dy);for(let x=0;x<128;x++){let flicker=.7+.3*Math.sin(x*.22+seed+y*.06);let v=core*flicker;if(v<=.03)continue;p.set(x,y,[255,Math.round(90+140*v),Math.round(40+90*v*v),Math.round(255*Math.min(1,v*1.15))])}}for(let x=0;x<128;x++){let a=Math.round(210*(.75+.25*Math.sin(x*.3+seed)));p.set(x,63,[255,255,240,a]);p.set(x,64,[255,255,240,a])}}}
png('beam-1',beamTexture(0));
png('beam-2',beamTexture(3.14));
}
console.log('Created editable PNGs: ally, weapons, pickups, and support effects. Enemy and legacy boss art is intentionally not generated.');
