const petals=document.getElementById("petals");

const images=[
"assets/petals/petal1.png",
"assets/petals/petal2.png",
"assets/petals/petal3.png"
];

function petal(){

const img=document.createElement("img");

img.src=images[Math.floor(Math.random()*images.length)];

img.className="petal";

const size=20+Math.random()*35;

const duration=8+Math.random()*8;

const left=Math.random()*window.innerWidth;

img.style.left=left+"px";

img.style.top="-100px";

img.style.width=size+"px";

petals.appendChild(img);

let x=left;
let y=-80;

const rotate=Math.random()*360;

const drift=(Math.random()*2)-1;

const speed=1+Math.random()*2;

let angle=rotate;

function animate(){

y+=speed;

x+=drift;

angle+=1;

img.style.transform=
`translate(${x-left}px,${y}px)
rotate(${angle}deg)`;

if(y<window.innerHeight+100){

requestAnimationFrame(animate);

}else{

img.remove();

}

}

animate();

}

setInterval(petal,250);

/* Stars */

const stars=document.getElementById("stars");

for(let i=0;i<80;i++){

const s=document.createElement("div");

s.className="star";

const size=Math.random()*3;

s.style.width=size+"px";
s.style.height=size+"px";

s.style.left=Math.random()*100+"vw";
s.style.top=Math.random()*100+"vh";

s.style.animationDelay=Math.random()*5+"s";

stars.appendChild(s);

}