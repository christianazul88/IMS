/* Character selection owns presentation only. Combat implementations read
 * window.selectedHero, so adding a hero never needs to rewrite this screen.
 */
(() => {
  const panel=document.querySelector('#characterPanel');
  const start=document.querySelector('#startPanel');
  const cards=[...document.querySelectorAll('.hero-card')];
  const confirm=document.querySelector('#confirmHeroButton');
  const change=document.querySelector('#changeHeroButton');
  const status=document.querySelector('#heroSelectionStatus');
  const names={vanguard:'THE VANGUARD', 'shadow-summoner':'THE UMBRAL CONDUCTOR'};
  window.selectedHero=null;
  function select(id){
    window.selectedHero=id;document.documentElement.dataset.hero=id;
    for(const card of cards){const active=card.dataset.hero===id;card.classList.toggle('selected',active);card.setAttribute('aria-checked',String(active));}
    confirm.disabled=false;status.textContent=names[id]+' SELECTED';
  }
  for(const card of cards)card.addEventListener('click',()=>select(card.dataset.hero));
  confirm.addEventListener('click',()=>{
    if(!window.selectedHero)return;
    panel.hidden=true;start.hidden=false;start.style.display='flex';
    const chosen=names[window.selectedHero];
    start.querySelector('p').textContent=chosen+' will enter the ruined arena. Choose the law of this hunt, then face The Adapted One.';
    start.querySelector('small').textContent=window.selectedHero==='shadow-summoner'
      ?'WASD move · 1–4 / Q: wing, gorilla, guard, titan · Click: command · Space: evade · R / right click: beast special · E / F: Sovereign Eclipse'
      :'WASD move · Click: attack · Space: evade · R: Earthsplitter · E: Dismantle · F: Heavenbreaker · Q: switch weapon';
  });
  change.addEventListener('click',()=>{start.style.display='none';start.hidden=true;panel.hidden=false;});
  window.CHARACTER_SELECT={select,get selected(){return window.selectedHero;},show(){change.click();}};
})();
