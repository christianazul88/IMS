// Creates the replaceable 4096×720 starter level map at assets/map.png.
const fs = require('fs'), zlib = require('zlib'), path = require('path');
const W = 4096, H = 720, raw = Buffer.alloc((W * 4 + 1) * H);
for (let y = 0; y < H; y++) {
  raw[y * (W * 4 + 1)] = 0;
  for (let x = 0; x < W; x++) {
    const i = y * (W * 4 + 1) + 1 + x * 4, arena = x >= 2680, road = y > 100 && y < 620;
    let r = 18, g = 18, b = 35;
    if (road && !arena) { r = 57 + ((x >> 6 ^ y >> 6) & 1) * 7; g = 37; b = 57; }
    if (arena && road) { r = 42 + ((x >> 6 ^ y >> 6) & 1) * 7; g = 23; b = 55; }
    if (x > 2660 && x < 2708) { r = 33; g = 18; b = 39; }
    if (road && x % 180 < 10) { r = x % 180 < 8 ? 105 : 216; g = x % 180 < 8 ? 64 : 77; b = x % 180 < 8 ? 80 : 91; }
    if (arena && road && x % 110 < 10) { r = 77; g = 41; b = 83; }
    raw[i] = r; raw[i + 1] = g; raw[i + 2] = b; raw[i + 3] = 255;
  }
}
const crcTable = Uint32Array.from({length:256}, (_, n) => { let c=n; for(let k=0;k<8;k++) c=(c&1)?0xedb88320^(c>>>1):c>>>1; return c>>>0; });
const crc = b => { let c=0xffffffff; for(const v of b)c=crcTable[(c^v)&255]^(c>>>8); return (c^0xffffffff)>>>0; };
const chunk = (type, data) => { const t=Buffer.from(type), len=Buffer.alloc(4), sum=Buffer.alloc(4); len.writeUInt32BE(data.length); sum.writeUInt32BE(crc(Buffer.concat([t,data]))); return Buffer.concat([len,t,data,sum]); };
const ihdr = Buffer.alloc(13); ihdr.writeUInt32BE(W); ihdr.writeUInt32BE(H,4); ihdr[8]=8; ihdr[9]=6;
fs.writeFileSync(path.join(__dirname, 'assets', 'map.png'), Buffer.concat([Buffer.from([137,80,78,71,13,10,26,10]),chunk('IHDR',ihdr),chunk('IDAT',zlib.deflateSync(raw,{level:9})),chunk('IEND',Buffer.alloc(0))]));
