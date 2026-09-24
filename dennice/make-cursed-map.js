// Export ALL authored Tiled animations: node make-cursed-map.js [asset-pack-folder]
const fs=require('fs'),path=require('path'),zlib=require('zlib'),{JSDOM}=require('jsdom');
const root=path.join(process.argv[2]||'C:/Users/CHRISTIAN/Downloads/Free-Cursed-Land-Top-Down-Pixel-Art-Tileset','Tiled_files');
const output=path.join(__dirname,'assets','cursed-land');
const xml=file=>new JSDOM(fs.readFileSync(file,'utf8'),{contentType:'text/xml'}).window.document.documentElement;
const num=(el,key)=>Number(el.getAttribute(key));
const map=xml(path.join(root,'Cursed_land.tmx'));
// Visible composition inside the source infinite map. Edit crop after extending the TMX.
const crop={x:-608,y:224,w:608,h:400};
fs.mkdirSync(output,{recursive:true});
const tilesets=[...map.querySelectorAll(':scope > tileset')].map(entry=>{
  const def=entry.hasAttribute('source')?xml(path.join(root,entry.getAttribute('source'))):entry;
  const source=def.querySelector('image').getAttribute('source'),name=path.basename(source);
  // Tiled_files sheets have a different layout from the PNG preview sheets.
  fs.copyFileSync(path.join(root,source),path.join(output,name));
  const animations={};
  for(const tile of def.querySelectorAll('tile')){
    const frames=[...tile.querySelectorAll('animation > frame')];
    if(frames.length)animations[num(tile,'id')]=frames.map(f=>[num(f,'tileid'),num(f,'duration')]);
  }
  return {name:def.getAttribute('name'),first:num(entry,'firstgid'),count:num(def,'tilecount'),columns:num(def,'columns'),w:num(def,'tilewidth'),h:num(def,'tileheight'),image:'assets/cursed-land/'+name,animations};
});
const layers=[...map.querySelectorAll(':scope > layer')].map(layer=>{
  const tiles=[];
  for(const chunk of layer.querySelectorAll('chunk')){
    const raw=zlib.inflateSync(Buffer.from(chunk.textContent.trim(),'base64'));
    if(raw.length!==num(chunk,'width')*num(chunk,'height')*4)throw Error('Invalid chunk length');
    for(let i=0;i<raw.length/4;i++){
      const encoded=raw.readUInt32LE(i*4),gid=encoded&0x0fffffff;if(!gid)continue;
      const x=(num(chunk,'x')+i%num(chunk,'width'))*16-crop.x;
      const y=(num(chunk,'y')+Math.floor(i/num(chunk,'width')))*16-crop.y;
      if(x< -15||y< -15||x>=crop.w||y>=crop.h)continue;
      const ts=tilesets.findLastIndex(s=>gid>=s.first);
      if(ts<0||gid-tilesets[ts].first>=tilesets[ts].count)throw Error('Invalid tile '+gid);
      tiles.push([x,y,ts,gid-tilesets[ts].first,encoded>>>28]);
    }
  }
  return {name:layer.getAttribute('name'),visible:layer.getAttribute('visible')!=='0',opacity:layer.hasAttribute('opacity')?num(layer,'opacity'):1,tiles};
});
fs.writeFileSync(path.join(output,'map-data.js'),'// Generated from Cursed_land.tmx; rebuild with make-cursed-map.js.\nwindow.CURSED_LAND_DATA='+JSON.stringify({crop,tilesets,layers})+';\n');
const used=new Set();let placements=0;
for(const l of layers)for(const t of l.tiles)if(tilesets[t[2]].animations[t[3]]){placements++;used.add(t[2]+':'+t[3]);}
console.log(JSON.stringify({layers:layers.length,animationDefinitions:tilesets.reduce((n,s)=>n+Object.keys(s.animations).length,0),usedAnimations:used.size,animatedPlacements:placements},null,2));
