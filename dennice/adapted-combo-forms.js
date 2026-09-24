/* Shorter warnings, varied strings. Random selection never removes the
 * committed tell: each beat locks its aim, and every 4th/5th beat has a
 * deliberate pause. Fifteen-hit strings therefore have escape opportunities.
 * Lengths, damage and windups are intentionally independent of difficulty. */
(() => {
  const lengths=[3,5,10,15];
  for(const n of lengths){const name='flurry'+n;ADAPTED_MOVES[name]={...ADAPTED_MOVES.combo,label:n+'-HIT FORM — WATCH THE HANDS'};for(const pool of Object.values(ADAPTED_ATTACK_POOL))if(!pool.includes(name))pool.push(name)}
  const lab=document.querySelector('#labMove');if(lab)for(const n of lengths){const o=document.createElement('option');o.value=o.textContent='flurry'+n;lab.appendChild(o)}
  const choose=adaptedChooseAttack;adaptedChooseAttack=function(b){if(dist(b,adaptedTarget(b))<235&&Math.random()<.78){const options=lengths.filter(n=>'flurry'+n!==b.lastAttacks.at(-1)&&!(b.cooldowns['flurry'+n]>0));if(options.length)return 'flurry'+options[Math.floor(Math.random()*options.length)]}return choose(b)};
  const start=adaptedStartAttack;adaptedStartAttack=function(b,name){start(b,name);if(!name.startsWith('flurry')){if(b.move&&b.stage==='windup'&&!['shockwave','judgment'].includes(name)){b.move.wind=Math.max(.22,b.move.wind*.82);b.timer=b.move.wind}return}
    const n=Number(name.slice(6));b.sequence=Array.from({length:n},(_,i)=>{const heavy=i===n-1,pause=i>0&&i%4===0,side=Math.random()<.45;return{...ADAPTED_MOVES[heavy?'heavyPunch':side?'backhand':'quickPunch'],wind:heavy?.44:i===0?.3:pause?.34:.17+Math.random()*.06,active:.12,recover:heavy?1.1:pause?.2:.1,step:heavy?48:28+Math.random()*14,damage:heavy?18:n>=10?5:8,arc:side?1.1:.72,label:heavy?'FINISHER — EVADE':'FOLLOW-UP'}});b.sequenceIndex=0;b.cooldowns[name]=5+n*.15;adaptedBeginBeat(b);
  };
})();
