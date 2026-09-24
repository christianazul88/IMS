/*
 * Boss arena map assets.
 *
 * The old version of this file added rectangular collision cover, projectile
 * blocking, boss beam blocking, and destructible HP bars. That feature has
 * intentionally been removed: scenery is visual only. arena-map.js replaces
 * the Adapted One's fallback image with the live Tiled animation canvas.
 */
(function(){
  const adaptedArenaMap=new Image();
  adaptedArenaMap.src='assets/adapted-cursed-land.png?v=2';
  window.ARENA_MAPS={adapted:adaptedArenaMap};
  // The generated cursed-land PNG is already 1280x720.
  window.ARENA_MAP_CROPS={
    adapted:{x:0,y:0,w:1280,h:720}
  };
  // Compatibility surface for the developer lab and older save/debug code.
  // It deliberately exposes no obstacles and no collision tuning.
  window.PRINCESS_COVER={obstacles:()=>[],reset:()=>{},tuning:{enabled:false}};
})();
