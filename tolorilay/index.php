<?php
/**
 * ============================================================
 *  Goodnight, Lorilay — a small scrapbook website
 * ============================================================
 *  Everything lives in this one file on purpose, so it's easy
 *  to host anywhere that runs PHP. Two things you'll want to
 *  edit before sharing the link:
 *
 *  1. $PASSWORD below — the front-door password.
 *  2. The PHOTOS / SCREENSHOTS arrays further down in the
 *     <script> section — drop your own images into an
 *     /images folder next to this file and point to them.
 * ============================================================
 */

$PASSWORD = "wabisabi"; // <-- change this to whatever she'll actually know

// Load memories for the random memory generator (falls back to an
// empty list if the file is missing, so the page never hard-errors).
$memoriesFile = __DIR__ . '/memories.json';
$memories = file_exists($memoriesFile) ? file_get_contents($memoriesFile) : '[]';

// Load existing guestbook entries so the page can render instantly
// without waiting on a fetch.
$guestbookFile = __DIR__ . '/guestbook.json';
$guestbook = file_exists($guestbookFile) ? file_get_contents($guestbookFile) : '[]';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Goodnight, Lorilay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500;1,600&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ============================================================
   TOKENS
============================================================ */
:root{
  --night-900:#080c16;
  --night-700:#121a2c;
  --night-500:#1c2740;
  --espresso-700:#3a2417;
  --espresso-500:#5c3823;
  --espresso-300:#8a5c3a;
  --cream:#f3e8d6;
  --cream-dim:#cdbfa4;
  --petal-1:#f0b1c0;
  --petal-2:#e08ea3;
  --petal-3:#f7d3da;
  --gold:#d3a75c;
  --gold-dim:#a8824a;
  --shadow: 0 18px 40px rgba(0,0,0,.45);
  --serif: 'Cormorant Garamond', serif;
  --sans: 'Quicksand', sans-serif;
}

*{box-sizing:border-box;}
html,body{height:100%;}
body{
  margin:0;
  min-height:100vh;
  background:
    radial-gradient(ellipse 80% 50% at 50% -10%, #1a2440 0%, transparent 60%),
    linear-gradient(180deg, var(--night-900) 0%, var(--night-700) 55%, var(--espresso-700) 130%);
  color:var(--cream);
  font-family:var(--sans);
  overflow-x:hidden;
  position:relative;
}

h1,h2,h3,.display{
  font-family:var(--serif);
  font-weight:500;
  letter-spacing:.01em;
  margin:0;
}

button{
  font-family:var(--sans);
  cursor:pointer;
}

.hidden{ display:none !important; }

a{ color:inherit; }

/* ============================================================
   AMBIENT LAYERS — stars, moon, petals
============================================================ */
#stars{
  position:fixed; inset:0; z-index:0; pointer-events:none;
}
.star{
  position:absolute; background:#fff; border-radius:50%;
  opacity:.6;
  animation: twinkle 3.5s ease-in-out infinite;
}
@keyframes twinkle{
  0%,100%{ opacity:.15; }
  50%{ opacity:.85; }
}

#moon{
  position:fixed;
  top:6%; right:8%;
  width:74px; height:74px;
  border-radius:50%;
  background:
    radial-gradient(circle at 35% 30%, #fff8e6, var(--gold) 55%, var(--gold-dim) 100%);
  box-shadow:
    0 0 50px 6px rgba(211,167,92,.35),
    inset -14px -10px 0 0 rgba(0,0,0,.18);
  z-index:0; pointer-events:none;
  opacity:.9;
}

#petals{
  position:fixed; inset:0; z-index:5; pointer-events:none;
  overflow:hidden;
}
.petal{
  position:absolute;
  top:-5%;
  background:linear-gradient(135deg, var(--petal-3), var(--petal-1) 55%, var(--petal-2));
  border-radius:0% 100% 0% 100%;
  box-shadow: 0 0 6px rgba(224,142,163,.35);
  opacity:.85;
  animation-name: fall, sway;
  animation-timing-function: linear, ease-in-out;
  animation-iteration-count: 1, infinite;
}
@keyframes fall{
  0%{ transform: translateY(-10vh) rotate(0deg); }
  100%{ transform: translateY(110vh) rotate(380deg); }
}
@keyframes sway{
  0%,100%{ margin-left:0px; }
  50%{ margin-left:40px; }
}

/* ============================================================
   SCENE FRAMEWORK
============================================================ */
.scene{
  position:relative;
  z-index:2;
  min-height:100vh;
  width:100%;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:8vh 6vw;
  opacity:0;
  animation: sceneIn .9s ease forwards;
}
@keyframes sceneIn{
  from{ opacity:0; transform:translateY(14px); }
  to{ opacity:1; transform:translateY(0); }
}
.scene-inner{
  max-width:720px;
  width:100%;
  text-align:center;
}

.eyebrow{
  font-family:var(--sans);
  text-transform:uppercase;
  letter-spacing:.32em;
  font-size:.68rem;
  color:var(--gold);
  margin-bottom:1.4rem;
  opacity:.85;
}

.rule{
  width:60px; height:1px;
  background:linear-gradient(90deg, transparent, var(--gold), transparent);
  margin:1.6rem auto;
  opacity:.6;
}

.lead{
  font-family:var(--serif);
  font-style:italic;
  font-size:clamp(1.6rem, 3.4vw, 2.3rem);
  line-height:1.5;
  color:var(--cream);
  margin:0 0 1rem;
}

.body-text{
  font-size:1.02rem;
  line-height:1.9;
  color:var(--cream-dim);
  max-width:520px;
  margin:0 auto 1.6rem;
}

.btn{
  font-family:var(--sans);
  font-weight:600;
  font-size:.92rem;
  letter-spacing:.03em;
  padding:.85rem 1.9rem;
  border-radius:999px;
  border:1px solid rgba(211,167,92,.55);
  background:linear-gradient(180deg, rgba(211,167,92,.16), rgba(211,167,92,.04));
  color:var(--cream);
  transition:.25s ease;
  margin:.3rem;
}
.btn:hover{
  background:linear-gradient(180deg, rgba(211,167,92,.32), rgba(211,167,92,.1));
  transform:translateY(-2px);
  box-shadow:0 10px 24px rgba(211,167,92,.18);
}
.btn:focus-visible{ outline:2px solid var(--gold); outline-offset:3px; }
.btn.ghost{
  border-color:rgba(243,232,214,.3);
  background:transparent;
}
.btn.small{ font-size:.78rem; padding:.6rem 1.3rem; }

.back-link{
  position:fixed;
  top:22px; left:26px;
  z-index:20;
  font-size:.78rem;
  letter-spacing:.06em;
  text-transform:uppercase;
  color:var(--cream-dim);
  background:rgba(8,12,22,.4);
  border:1px solid rgba(243,232,214,.15);
  padding:.55rem 1rem;
  border-radius:999px;
  text-decoration:none;
  backdrop-filter:blur(6px);
}
.back-link:hover{ color:var(--gold); border-color:rgba(211,167,92,.4); }

/* ============================================================
   PASSWORD SCENE
============================================================ */
#scene-password{
  background:
    radial-gradient(ellipse 60% 40% at 50% 20%, rgba(211,167,92,.08), transparent 60%);
}
.pw-title{
  font-size:clamp(1.9rem, 5vw, 2.8rem);
  font-style:italic;
  margin-bottom:.4rem;
}
.pw-hint{
  font-size:.85rem;
  color:var(--cream-dim);
  opacity:.75;
  margin:1.4rem 0 1.8rem;
  font-style:italic;
}
.pw-form{
  display:flex; flex-wrap:wrap; gap:.6rem; justify-content:center;
}
#pw-input{
  font-family:var(--sans);
  background:rgba(243,232,214,.06);
  border:1px solid rgba(243,232,214,.25);
  color:var(--cream);
  padding:.85rem 1.1rem;
  border-radius:999px;
  min-width:220px;
  font-size:.95rem;
}
#pw-input:focus-visible{ outline:2px solid var(--gold); }
#pw-error{
  color:var(--petal-2);
  font-size:.82rem;
  margin-top:1rem;
  min-height:1.2em;
}
#pw-loading{
  font-size:.85rem;
  color:var(--cream-dim);
  line-height:2.1;
}
#pw-loading span{ display:block; opacity:0; animation: fadeStep .6s ease forwards; }
#pw-loading span:nth-child(1){ animation-delay:.1s; }
#pw-loading span:nth-child(2){ animation-delay:1s; }
#pw-loading span:nth-child(3){ animation-delay:1.9s; }
@keyframes fadeStep{ to{ opacity:1; } }

/* ============================================================
   TYPING TEXT (intro)
============================================================ */
.typing-block p{
  font-family:var(--serif);
  font-style:italic;
  font-size:clamp(1.3rem, 2.6vw, 1.7rem);
  line-height:1.7;
  color:var(--cream);
  margin:0 0 .9rem;
  opacity:0;
  animation: fadeStep .9s ease forwards;
}

/* ============================================================
   DESK SCENE
============================================================ */
.desk-grid{
  display:grid;
  grid-template-columns:repeat(3, 1fr);
  gap:1.4rem;
  margin-top:2.2rem;
}
@media(max-width:640px){ .desk-grid{ grid-template-columns:repeat(2,1fr); } }

.desk-item{
  background:linear-gradient(180deg, rgba(58,36,23,.55), rgba(28,39,64,.5));
  border:1px solid rgba(211,167,92,.22);
  border-radius:18px;
  padding:1.6rem 1rem;
  display:flex; flex-direction:column; align-items:center; gap:.6rem;
  transition:.25s ease;
  color:var(--cream);
}
.desk-item:hover{
  transform:translateY(-4px);
  border-color:rgba(211,167,92,.55);
  box-shadow:var(--shadow);
}
.desk-icon{ font-size:1.9rem; }
.desk-label{
  font-family:var(--serif);
  font-style:italic;
  font-size:1.05rem;
}

/* coffee stain easter egg trigger */
#coffee-stain{
  position:fixed;
  bottom:22px; right:26px;
  width:34px; height:34px;
  border-radius:50%;
  background:radial-gradient(circle at 40% 40%, rgba(120,74,45,.55), rgba(90,55,33,.35) 60%, transparent 75%);
  border:1px solid rgba(211,167,92,.25);
  z-index:15;
  cursor:pointer;
}
#coffee-stain:hover{ box-shadow:0 0 14px rgba(211,167,92,.35); }

/* ============================================================
   POLAROIDS / SCREENSHOT PAPERS
============================================================ */
.gallery-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill, minmax(150px,1fr));
  gap:1.4rem;
  margin-top:2rem;
}
.polaroid{
  background:#f3ede1;
  padding:10px 10px 34px;
  border-radius:4px;
  box-shadow:0 12px 24px rgba(0,0,0,.35);
  cursor:pointer;
  transform:rotate(var(--tilt,0deg));
  transition:.2s ease;
}
.polaroid:hover{ transform:rotate(0deg) translateY(-4px) scale(1.03); }
.polaroid .frame{
  width:100%; aspect-ratio:1/1;
  border-radius:2px;
}
.polaroid .cap{
  display:block;
  text-align:center;
  font-family:var(--serif);
  font-style:italic;
  color:#3a2417;
  font-size:.8rem;
  margin-top:.5rem;
}

.lightbox{
  position:fixed; inset:0; z-index:50;
  background:rgba(4,6,12,.86);
  display:flex; align-items:center; justify-content:center;
  padding:6vh 6vw;
}
.lightbox-card{
  background:#f3ede1; color:#3a2417;
  padding:16px 16px 26px; border-radius:6px;
  max-width:420px; width:100%;
  box-shadow:var(--shadow);
  text-align:center;
}
.lightbox-card .frame{ width:100%; aspect-ratio:4/3; border-radius:3px; margin-bottom:.8rem; }
.lightbox-card p{ font-family:var(--serif); font-style:italic; font-size:1.05rem; margin:0 0 1rem; }

/* ============================================================
   FOLDERS (Screenshot Archive)
============================================================ */
.folder-grid{
  display:grid; grid-template-columns:repeat(2,1fr); gap:1rem;
  margin-top:2rem;
}
@media(max-width:520px){ .folder-grid{ grid-template-columns:1fr; } }
.folder{
  background:linear-gradient(180deg, rgba(92,56,35,.4), rgba(58,36,23,.5));
  border:1px solid rgba(211,167,92,.25);
  border-radius:14px;
  padding:1.2rem;
  text-align:left;
  color:var(--cream);
}
.folder-title{
  font-family:var(--serif); font-style:italic; font-size:1.15rem; margin-bottom:.6rem;
}
.folder-open-btn{
  background:none; border:none; color:var(--gold); font-size:.8rem;
  letter-spacing:.05em; text-transform:uppercase; padding:0;
}
.paper-list{ margin-top:.9rem; display:flex; flex-direction:column; gap:.6rem; }
.paper{
  background:rgba(243,232,214,.06);
  border:1px dashed rgba(243,232,214,.25);
  border-radius:8px;
  padding:.7rem .9rem;
  font-size:.86rem;
  color:var(--cream-dim);
}
.sticky{
  background:#f4dd7c; color:#4a3a12;
  border-radius:4px; padding:.5rem .7rem;
  font-family:var(--serif); font-style:italic; font-size:.82rem;
  transform:rotate(-1.5deg);
  box-shadow:0 6px 12px rgba(0,0,0,.25);
}

/* ============================================================
   MEMORY GENERATOR
============================================================ */
.cup{
  font-size:3.6rem;
  margin-bottom:.6rem;
  position:relative;
  display:inline-block;
}
.steam{
  position:absolute; top:-18px; left:50%;
  width:3px; height:20px;
  background:rgba(243,232,214,.5);
  border-radius:3px;
  animation: steamRise 2.4s ease-in-out infinite;
}
.steam:nth-child(2){ left:42%; animation-delay:.5s; }
.steam:nth-child(3){ left:58%; animation-delay:1s; }
@keyframes steamRise{
  0%{ opacity:0; transform:translateY(0) scaleY(.6); }
  40%{ opacity:.7; }
  100%{ opacity:0; transform:translateY(-22px) scaleY(1.3); }
}
.memory-card{
  margin:1.6rem auto 0;
  max-width:440px;
  background:rgba(243,232,214,.06);
  border:1px solid rgba(211,167,92,.3);
  border-radius:14px;
  padding:1.5rem;
  font-family:var(--serif); font-style:italic;
  font-size:1.15rem; line-height:1.7;
  min-height:3.4em;
  display:flex; align-items:center; justify-content:center;
  opacity:0;
  transition:opacity .4s ease;
}
.memory-card.show{ opacity:1; }

/* ============================================================
   FOUND CARDS
============================================================ */
.found-grid{
  display:grid; grid-template-columns:repeat(2,1fr); gap:1rem;
  margin-top:2rem; text-align:left;
}
@media(max-width:560px){ .found-grid{ grid-template-columns:1fr; } }
.found-card{
  background:rgba(243,232,214,.05);
  border:1px solid rgba(211,167,92,.22);
  border-radius:12px;
  padding:1.1rem 1.2rem;
  font-size:.92rem;
  color:var(--cream-dim);
  line-height:1.6;
}

/* ============================================================
   INVESTIGATION / APOLOGY
============================================================ */
.case-file{
  text-align:left;
  background:rgba(243,232,214,.04);
  border:1px solid rgba(211,167,92,.25);
  border-radius:14px;
  padding:1.6rem 1.8rem;
  margin:1.6rem 0;
}
.charges{ list-style:none; padding:0; margin:.8rem 0 0; }
.charges li{ margin:.4rem 0; color:var(--cream-dim); }
.stamp{
  font-family:var(--serif);
  font-weight:600;
  font-size:clamp(2.4rem,7vw,3.6rem);
  color:#c15a63;
  border:6px double #c15a63;
  display:inline-block;
  padding:.2rem 1rem;
  transform:rotate(-6deg);
  margin:1.4rem 0;
  letter-spacing:.06em;
  opacity:.9;
}
.apology-box{
  text-align:left;
  background:rgba(243,232,214,.05);
  border-left:3px solid var(--gold);
  padding:1.2rem 1.4rem;
  border-radius:6px;
  font-size:1rem;
  line-height:1.85;
  color:var(--cream);
  margin:1.6rem 0;
}

/* ============================================================
   GUESTBOOK
============================================================ */
.gb-form{
  display:flex; flex-direction:column; gap:.7rem;
  max-width:420px; margin:1.8rem auto 0;
  text-align:left;
}
.gb-form label{
  font-size:.75rem; text-transform:uppercase; letter-spacing:.08em;
  color:var(--gold); margin-bottom:.2rem; display:block;
}
.gb-form input, .gb-form textarea{
  width:100%;
  font-family:var(--sans);
  background:rgba(243,232,214,.06);
  border:1px solid rgba(243,232,214,.25);
  color:var(--cream);
  padding:.7rem .9rem;
  border-radius:10px;
  font-size:.92rem;
  resize:vertical;
}
.gb-entries{
  margin-top:2.2rem; display:flex; flex-direction:column; gap:.8rem;
  max-height:260px; overflow-y:auto; text-align:left;
}
.gb-entry{
  background:rgba(243,232,214,.05);
  border:1px solid rgba(211,167,92,.2);
  border-radius:10px;
  padding:.8rem 1rem;
  font-size:.88rem;
}
.gb-entry .who{ color:var(--gold); font-weight:600; margin-right:.4rem; }
.gb-note{ font-size:.82rem; color:var(--cream-dim); margin-top:1rem; }

/* ============================================================
   FINAL / ENDING
============================================================ */
.shelf-row{
  display:flex; gap:.8rem; justify-content:center; flex-wrap:wrap; margin-top:1.8rem;
}
.shelf-item{
  width:64px; height:64px; border-radius:10px;
  background:linear-gradient(135deg, rgba(211,167,92,.25), rgba(224,142,163,.2));
  border:1px solid rgba(211,167,92,.3);
}
.fade-line{
  opacity:0; animation:fadeStep 1s ease forwards;
}
.fade-line.d1{ animation-delay:.2s; }
.fade-line.d2{ animation-delay:1.6s; }
.fade-line.d3{ animation-delay:3s; }
.fade-line.d4{ animation-delay:4.4s; }

.achievement{
  border:1px solid rgba(211,167,92,.4);
  border-radius:14px;
  padding:1.6rem;
  background:rgba(211,167,92,.08);
  margin:1.6rem 0;
}
.achievement .a-title{ font-size:.75rem; letter-spacing:.12em; text-transform:uppercase; color:var(--gold); }
.achievement .a-name{ font-family:var(--serif); font-style:italic; font-size:1.5rem; margin:.4rem 0; }
.achievement .a-reward{ color:var(--petal-1); font-size:.9rem; margin-top:.5rem; }

/* Reduced motion */
@media (prefers-reduced-motion: reduce){
  .petal, .star, .steam, *{ animation-duration:.001ms !important; animation-iteration-count:1 !important; }
}
</style>
</head>
<body>

<div id="stars"></div>
<div id="moon"></div>
<div id="petals"></div>

<!-- ============================================================
     1. PASSWORD SCENE
============================================================ -->
<section class="scene" id="scene-password">
  <div class="scene-inner">
    <div class="eyebrow">Coffee Shop &middot; Night</div>
    <h1 class="pw-title">Goodnight, Lorilay</h1>
    <div class="rule"></div>
    <p class="body-text">Before entering: what is the password?</p>
    <p class="pw-hint">(Hint: you once trusted me with it.)</p>

    <form class="pw-form" id="pw-form">
      <input type="password" id="pw-input" placeholder="Enter password" autocomplete="off" />
      <button type="submit" class="btn">Enter</button>
    </form>
    <div id="pw-error"></div>
  </div>
</section>

<!-- password success loading state -->
<section class="scene hidden" id="scene-loading">
  <div class="scene-inner">
    <p class="lead">Password Accepted.</p>
    <div id="pw-loading">
      <span>Brewing coffee...</span>
      <span>Collecting memories...</span>
      <span>Loading courage...</span>
    </div>
  </div>
</section>

<!-- ============================================================
     2. INTRO SCENE
============================================================ -->
<section class="scene hidden" id="scene-intro">
  <div class="scene-inner typing-block" id="intro-text">
    <p style="animation-delay:.2s">Hi, Lorilay.</p>
    <p style="animation-delay:1.4s">This website is not trying to change anything.</p>
    <p style="animation-delay:2.6s">It's not asking for anything.</p>
    <p style="animation-delay:3.8s">I just wanted to leave a few things somewhere.</p>
    <p style="animation-delay:5s">So... welcome.</p>
    <div style="margin-top:1.6rem; opacity:0; animation:fadeStep 1s ease forwards; animation-delay:6.2s;">
      <button class="btn" onclick="goTo('scene-desk')">Open Scrapbook</button>
    </div>
  </div>
</section>

<!-- ============================================================
     3. SCRAPBOOK DESK (HUB)
============================================================ -->
<section class="scene hidden" id="scene-desk">
  <div class="scene-inner">
    <div class="eyebrow">The Desk</div>
    <h2 class="lead">Where should we start?</h2>
    <div class="desk-grid">
      <button class="desk-item" onclick="goTo('scene-photos')">
        <span class="desk-icon">📷</span>
        <span class="desk-label">Photos</span>
      </button>
      <button class="desk-item" onclick="goTo('scene-screenshots')">
        <span class="desk-icon">✉️</span>
        <span class="desk-label">Screenshots</span>
      </button>
      <button class="desk-item" onclick="goTo('scene-memory')">
        <span class="desk-icon">☕</span>
        <span class="desk-label">Random Memories</span>
      </button>
      <button class="desk-item" onclick="goTo('scene-goodnight')">
        <span class="desk-icon">🌙</span>
        <span class="desk-label">Goodnight Collection</span>
      </button>
      <button class="desk-item" onclick="goTo('scene-found')">
        <span class="desk-icon">🍃</span>
        <span class="desk-label">Found Between The Pages</span>
      </button>
      <button class="desk-item" onclick="goTo('scene-drawer')">
        <span class="desk-icon">🔒</span>
        <span class="desk-label">???</span>
      </button>
    </div>
    <p class="gb-note" style="margin-top:2.2rem;">Whenever you're ready to leave, there's a <a href="#" onclick="goTo('scene-guestbook');return false;" style="color:var(--gold); text-decoration:underline;">guestbook</a> by the door.</p>
  </div>
</section>
<div id="coffee-stain" onclick="stainClick()" title=""></div>

<!-- ============================================================
     4. GOODNIGHT COLLECTION
============================================================ -->
<section class="scene hidden" id="scene-goodnight">
  <a href="#" class="back-link" onclick="goTo('scene-desk');return false;">&larr; Back to desk</a>
  <div class="scene-inner">
    <div class="eyebrow">Page One</div>
    <h2 class="lead">A collection of goodnights.</h2>
    <p class="body-text">I don't know how many there were. A lot.</p>
    <div class="folder-grid" style="max-width:520px;margin-left:auto;margin-right:auto;">
      <div class="paper">"goodnight lorilay"</div>
      <div class="paper">"goodnight my wabi-sabi"</div>
      <div class="paper">"makatulog ka sana ng mahimbing, aking sinta"</div>
      <div class="paper">and every other one I never deleted.</div>
    </div>
  </div>
</section>

<!-- ============================================================
     5. PHOTO GALLERY
============================================================ -->
<section class="scene hidden" id="scene-photos">
  <a href="#" class="back-link" onclick="goTo('scene-desk');return false;">&larr; Back to desk</a>
  <div class="scene-inner" style="max-width:800px;">
    <div class="eyebrow">Corkboard</div>
    <h2 class="lead">A photo gallery.</h2>
    <p class="body-text">Fewer than twenty, on purpose. It makes each one feel intentional. Tap one.</p>
    <div class="gallery-grid" id="photo-grid"></div>
  </div>
</section>

<!-- ============================================================
     6. SCREENSHOT ARCHIVE
============================================================ -->
<section class="scene hidden" id="scene-screenshots">
  <a href="#" class="back-link" onclick="goTo('scene-desk');return false;">&larr; Back to desk</a>
  <div class="scene-inner" style="max-width:640px;">
    <div class="eyebrow">Wooden Drawer</div>
    <h2 class="lead">Conversation Archive</h2>
    <div class="folder-grid" id="folder-grid"></div>
  </div>
</section>

<!-- ============================================================
     7. RANDOM MEMORY GENERATOR
============================================================ -->
<section class="scene hidden" id="scene-memory">
  <a href="#" class="back-link" onclick="goTo('scene-desk');return false;">&larr; Back to desk</a>
  <div class="scene-inner">
    <div class="cup">☕<span class="steam"></span><span class="steam"></span><span class="steam"></span></div>
    <h2 class="lead">Brew a memory.</h2>
    <button class="btn" onclick="brewMemory()">Brew Memory</button>
    <div class="memory-card" id="memory-card">Click the button — let's see what comes up.</div>
  </div>
</section>

<!-- ============================================================
     8. FOUND BETWEEN THE PAGES
============================================================ -->
<section class="scene hidden" id="scene-found">
  <a href="#" class="back-link" onclick="goTo('scene-desk');return false;">&larr; Back to desk</a>
  <div class="scene-inner" style="max-width:640px;">
    <div class="eyebrow">Things You Probably Never Knew</div>
    <h2 class="lead">Found Between The Pages</h2>
    <div class="found-grid">
      <div class="found-card">I saved more screenshots than I probably should have.</div>
      <div class="found-card">There were moments I wanted to tell you something, but didn't.</div>
      <div class="found-card">I remembered tiny things for absolutely no reason.</div>
      <div class="found-card">You became part of my routine before I realized it.</div>
    </div>
  </div>
</section>

<!-- ============================================================
     9. SECRET DRAWER (LOCK)
============================================================ -->
<section class="scene hidden" id="scene-drawer">
  <a href="#" class="back-link" onclick="goTo('scene-desk');return false;">&larr; Back to desk</a>
  <div class="scene-inner">
    <div class="eyebrow">Secret Drawer</div>
    <h2 class="lead" id="drawer-status">🔒 Locked</h2>
    <button class="btn" onclick="unlockDrawer()" id="drawer-btn">Try the drawer</button>
    <div id="drawer-checks" class="hidden" style="margin-top:1.4rem;">
      <div id="drawer-loading">
        <span>Checking nostalgia level...</span><br>
        <span>Checking coffee level...</span><br>
        <span>Checking courage level...</span><br>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     10. DEPARTMENT OF REGRETTABLE DECISIONS
============================================================ -->
<section class="scene hidden" id="scene-investigation">
  <div class="scene-inner">
    <div class="eyebrow">Official Investigation Report</div>
    <h2 class="lead">Case #050444</h2>
    <div class="case-file">
      <p style="margin:0;"><strong>Subject:</strong> Christian</p>
      <ul class="charges">
        <li>✓ Poor timing</li>
        <li>✓ Overthinking</li>
        <li>✓ Occasional stupidity</li>
        <li>✓ Whatever happened there</li>
      </ul>
    </div>
    <button class="btn" onclick="continueInvestigation()">Continue Investigation</button>
  </div>
</section>

<section class="scene hidden" id="scene-verdict">
  <div class="scene-inner">
    <p class="body-text">After reviewing all available evidence... the committee has reached a unanimous verdict.</p>
    <div class="stamp">GUILTY</div>
    <div>
      <button class="btn" onclick="goTo('scene-apology')">Okay. No jokes for a minute.</button>
    </div>
  </div>
</section>

<section class="scene hidden" id="scene-apology">
  <div class="scene-inner">
    <div class="apology-box">
      <p>If I hurt you, disappointed you, overwhelmed you, or became part of a reason you wanted distance — I'm sorry.</p>
      <p>I don't expect this to change anything. I don't expect a reply.</p>
      <p>I just think some things should be acknowledged instead of quietly ignored. So I'm acknowledging it.</p>
    </div>
    <p class="body-text" style="font-style:italic;">End of Report.</p>
    <button class="btn" onclick="goTo('scene-guestbook')">Continue</button>
  </div>
</section>

<!-- ============================================================
     11. GUESTBOOK
============================================================ -->
<section class="scene hidden" id="scene-guestbook">
  <a href="#" class="back-link" onclick="goTo('scene-desk');return false;">&larr; Back to desk</a>
  <div class="scene-inner">
    <div class="eyebrow">Coffee Shop Guestbook</div>
    <h2 class="lead">Before you leave</h2>
    <p class="body-text">If you want to leave a note, you can. If not, that's okay too.</p>

    <form class="gb-form" id="gb-form">
      <div>
        <label for="gb-name">Name</label>
        <input type="text" id="gb-name" placeholder="Lorilay" />
      </div>
      <div>
        <label for="gb-message">Message</label>
        <textarea id="gb-message" rows="3" placeholder="Leave a note..."></textarea>
      </div>
      <button type="submit" class="btn small" style="align-self:flex-start;">Leave A Note</button>
      <div id="gb-error" style="color:var(--petal-2); font-size:.8rem;"></div>
    </form>

    <div class="gb-entries" id="gb-entries"></div>

    <div style="margin-top:2rem;">
      <button class="btn ghost" onclick="goTo('scene-final')">Continue &rarr;</button>
    </div>
  </div>
</section>

<!-- ============================================================
     12. FINAL ROOM
============================================================ -->
<section class="scene hidden" id="scene-final">
  <div class="scene-inner">
    <div class="eyebrow">One Last Shelf</div>
    <p class="body-text">Final photos. Final screenshots. Final notes.</p>
    <p class="body-text">No dramatic revelations. Just appreciation.</p>
    <div class="shelf-row">
      <div class="shelf-item"></div>
      <div class="shelf-item"></div>
      <div class="shelf-item"></div>
      <div class="shelf-item"></div>
    </div>
    <div style="margin-top:2rem;">
      <button class="btn" onclick="goTo('scene-ending')">Continue &rarr;</button>
    </div>
  </div>
</section>

<!-- ============================================================
     13. THE ENDING
============================================================ -->
<section class="scene hidden" id="scene-ending">
  <div class="scene-inner">
    <p class="lead fade-line d1">Goodnight, Lorilay.</p>
    <p class="body-text fade-line d2">There wasn't really a grand point to any of this. I just didn't want these moments to exist only in my memory.</p>
    <p class="body-text fade-line d3">Thank you for being part of them.</p>
    <p class="body-text fade-line d4">And thank you for making it this far. 🤍🌙</p>
    <div class="fade-line d4" style="margin-top:1rem;">
      <button class="btn ghost" onclick="goTo('scene-easteregg')">One Last Thing</button>
    </div>
  </div>
</section>

<!-- ============================================================
     14. FINAL EASTER EGG
============================================================ -->
<section class="scene hidden" id="scene-easteregg">
  <div class="scene-inner">
    <div class="achievement">
      <div class="a-title">Achievement Unlocked</div>
      <div class="a-name">Made A Website Instead Of Sending A 47-Page Letter</div>
      <div class="a-reward">Reward: +100 Closure</div>
    </div>
    <p class="lead">Makatulog ka sana ng mahimbing, aking sinta.</p>
    <p class="body-text">Goodnight, Lorilay.</p>
  </div>
</section>

<!-- ============================================================
     HIDDEN: THE THINGS I NEVER SENT
============================================================ -->
<section class="scene hidden" id="scene-neversent">
  <a href="#" class="back-link" onclick="goTo('scene-desk');return false;">&larr; Back to desk</a>
  <div class="scene-inner" style="max-width:560px;">
    <div class="eyebrow">A Coffee Stain Led You Here</div>
    <h2 class="lead">The Things I Never Sent</h2>
    <div class="found-grid">
      <div class="found-card">This reminded me of you.</div>
      <div class="found-card">I almost sent this.</div>
      <div class="found-card">Never mind.</div>
      <div class="found-card">Saved anyway.</div>
    </div>
  </div>
</section>

<!-- lightbox for photo gallery -->
<div class="lightbox hidden" id="lightbox" onclick="closeLightbox(event)">
  <div class="lightbox-card" onclick="event.stopPropagation()">
    <div class="frame" id="lightbox-frame"></div>
    <p id="lightbox-caption"></p>
    <button class="btn small" onclick="closeLightbox()">Close</button>
  </div>
</div>

<script>
/* ============================================================
   DATA — server-provided password / memories / guestbook
============================================================ */
const SITE_PASSWORD = <?php echo json_encode($PASSWORD); ?>;
const MEMORIES = <?php echo $memories; ?>;
let GUESTBOOK = <?php echo $guestbook; ?>;

/* ------------------------------------------------------------
   EDIT ME: point these at your own images under /images.
   Each entry just needs a caption; "img" is optional — leave
   it out (or leave it wrong) and a soft placeholder color
   shows instead, so nothing breaks before you add real photos.
------------------------------------------------------------ */
const PHOTOS = [
  { caption: "This was a good day." },
  { caption: "I still remember where we were." },
  { caption: "This picture survived three phones." },
  { caption: "You weren't even trying to smile here." },
  { caption: "I don't think you know I kept this one." },
  { caption: "A completely unremarkable Tuesday, somehow." },
];

const FOLDERS = [
  {
    title: "Folder 01 — Goodnights",
    papers: ["goodnight lorilay", "sleep well, okay?", "goodnight my wabi-sabi"],
    sticky: "I don't think you even remember sending this."
  },
  {
    title: "Folder 02 — Random Laughs",
    papers: ["that one message that made no sense", "the typo that became a whole bit"],
    sticky: "I laughed for five minutes after reading this."
  },
  {
    title: "Folder 03 — Things I Saved",
    papers: ["a screenshot I never explained", "something small you said once"],
    sticky: "Saved without a reason. Kept anyway."
  },
  {
    title: "Folder 04 — The Unhinged Era",
    papers: ["3am conversation about nothing", "a debate that went nowhere, happily"],
    sticky: "We were something, that's for sure."
  },
];

/* ============================================================
   AMBIENT: STARS + PETALS
============================================================ */
function buildStars(){
  const wrap = document.getElementById('stars');
  const count = window.innerWidth < 600 ? 45 : 90;
  for(let i=0;i<count;i++){
    const s = document.createElement('div');
    s.className = 'star';
    const size = Math.random()*2 + 1;
    s.style.width = size+'px';
    s.style.height = size+'px';
    s.style.left = Math.random()*100+'vw';
    s.style.top = Math.random()*100+'vh';
    s.style.animationDelay = (Math.random()*3.5)+'s';
    wrap.appendChild(s);
  }
}

function spawnPetal(){
  const wrap = document.getElementById('petals');
  const p = document.createElement('div');
  p.className = 'petal';
  const size = Math.random()*10 + 8;
  const duration = Math.random()*6 + 9;
  const swayDur = Math.random()*2 + 2.5;
  p.style.width = size+'px';
  p.style.height = size*0.85+'px';
  p.style.left = Math.random()*100+'vw';
  p.style.animationDuration = duration+'s, '+swayDur+'s';
  p.style.opacity = (Math.random()*.4 + .5).toFixed(2);
  wrap.appendChild(p);
  setTimeout(()=> p.remove(), duration*1000 + 200);
}
function startPetals(){
  spawnPetal();
  setInterval(spawnPetal, 550);
}

/* ============================================================
   SCENE NAVIGATION
============================================================ */
function goTo(id){
  document.querySelectorAll('.scene').forEach(s => s.classList.add('hidden'));
  const target = document.getElementById(id);
  target.classList.remove('hidden');
  // restart the scene-in animation
  target.style.animation = 'none';
  void target.offsetWidth;
  target.style.animation = null;
  window.scrollTo({top:0, behavior:'instant'});
}

/* ============================================================
   PASSWORD GATE
============================================================ */
document.getElementById('pw-form').addEventListener('submit', function(e){
  e.preventDefault();
  const val = document.getElementById('pw-input').value.trim();
  const err = document.getElementById('pw-error');
  if(val.toLowerCase() === SITE_PASSWORD.toLowerCase()){
    err.textContent = '';
    goTo('scene-loading');
    setTimeout(()=> goTo('scene-intro'), 2600);
  } else {
    err.textContent = "That's not quite it. Try again?";
  }
});

/* ============================================================
   PHOTO GALLERY
============================================================ */
function buildPhotoGrid(){
  const grid = document.getElementById('photo-grid');
  const hues = ['#e0aeb8','#d9c2a0','#b7c4d6','#e3b98f','#c9aed6','#a9c9b7'];
  PHOTOS.forEach((photo, i) => {
    const card = document.createElement('div');
    card.className = 'polaroid';
    card.style.setProperty('--tilt', (Math.random()*8-4)+'deg');
    const hue = hues[i % hues.length];
    card.innerHTML = `<div class="frame" style="background:linear-gradient(135deg, ${hue}, #fff2 80%);"></div><span class="cap">Photo ${i+1}</span>`;
    card.addEventListener('click', ()=> openLightbox(hue, photo.caption));
    grid.appendChild(card);
  });
}
function openLightbox(hue, caption){
  document.getElementById('lightbox-frame').style.background = `linear-gradient(135deg, ${hue}, #fff2 80%)`;
  document.getElementById('lightbox-caption').textContent = caption;
  document.getElementById('lightbox').classList.remove('hidden');
}
function closeLightbox(e){
  document.getElementById('lightbox').classList.add('hidden');
}

/* ============================================================
   SCREENSHOT FOLDERS
============================================================ */
function buildFolders(){
  const grid = document.getElementById('folder-grid');
  FOLDERS.forEach((folder, i) => {
    const el = document.createElement('div');
    el.className = 'folder';
    const listId = 'papers-'+i;
    el.innerHTML = `
      <div class="folder-title">${folder.title}</div>
      <button class="folder-open-btn" onclick="toggleFolder('${listId}', this)">Open Folder</button>
      <div class="paper-list hidden" id="${listId}">
        ${folder.papers.map(p => `<div class="paper">${p}</div>`).join('')}
        <div class="sticky">${folder.sticky}</div>
      </div>
    `;
    grid.appendChild(el);
  });
}
function toggleFolder(id, btn){
  const el = document.getElementById(id);
  const nowHidden = el.classList.toggle('hidden');
  btn.textContent = nowHidden ? 'Open Folder' : 'Close Folder';
}

/* ============================================================
   RANDOM MEMORY GENERATOR
============================================================ */
function brewMemory(){
  if(!MEMORIES.length) return;
  const card = document.getElementById('memory-card');
  card.classList.remove('show');
  const pick = MEMORIES[Math.floor(Math.random()*MEMORIES.length)];
  setTimeout(()=>{
    card.textContent = pick.memory;
    card.classList.add('show');
  }, 220);
}

/* ============================================================
   SECRET DRAWER -> INVESTIGATION -> APOLOGY
============================================================ */
function unlockDrawer(){
  document.getElementById('drawer-btn').classList.add('hidden');
  document.getElementById('drawer-checks').classList.remove('hidden');
  const spans = document.querySelectorAll('#drawer-loading span');
  spans.forEach((s,i)=>{
    s.style.opacity = 0;
    s.style.animation = `fadeStep .6s ease forwards ${i*0.8}s`;
  });
  setTimeout(()=>{
    document.getElementById('drawer-status').textContent = 'Access granted.';
    setTimeout(()=> goTo('scene-investigation'), 900);
  }, spans.length*800 + 500);
}
function continueInvestigation(){
  goTo('scene-verdict');
}

/* ============================================================
   GUESTBOOK
============================================================ */
function renderGuestbook(){
  const wrap = document.getElementById('gb-entries');
  wrap.innerHTML = '';
  GUESTBOOK.slice().reverse().forEach(entry => {
    const el = document.createElement('div');
    el.className = 'gb-entry';
    el.innerHTML = `⭐ <span class="who">${escapeHtml(entry.name)}</span>${escapeHtml(entry.message)}`;
    wrap.appendChild(el);
  });
}
function escapeHtml(str){
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

document.getElementById('gb-form').addEventListener('submit', function(e){
  e.preventDefault();
  const name = document.getElementById('gb-name').value.trim();
  const message = document.getElementById('gb-message').value.trim();
  const err = document.getElementById('gb-error');
  if(!message){
    err.textContent = 'Write a little something first.';
    return;
  }
  err.textContent = '';
  fetch('save_guestbook.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({name, message})
  })
  .then(r => r.json())
  .then(data => {
    if(data.ok){
      GUESTBOOK = data.entries;
      renderGuestbook();
      document.getElementById('gb-name').value = '';
      document.getElementById('gb-message').value = '';
    } else {
      err.textContent = data.error || 'Something went wrong — try again?';
    }
  })
  .catch(()=>{
    err.textContent = "Couldn't save that just now — try again?";
  });
});

/* ============================================================
   COFFEE STAIN EASTER EGG
============================================================ */
let stainClicks = 0;
function stainClick(){
  stainClicks++;
  if(stainClicks >= 5){
    stainClicks = 0;
    goTo('scene-neversent');
  }
}

/* ============================================================
   INIT
============================================================ */
buildStars();
startPetals();
buildPhotoGrid();
buildFolders();
renderGuestbook();
</script>
</body>
</html>
