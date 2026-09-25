const fs = require('node:fs');
const assert = require('node:assert/strict');
const { JSDOM } = require('jsdom');
const html = fs.readFileSync(`${__dirname}/index.html`, 'utf8');
const catsCss = fs.readFileSync(`${__dirname}/cats.css`, 'utf8');
const catsJs = fs.readFileSync(`${__dirname}/cats.js`, 'utf8');
const experienceJs = fs.readFileSync(`${__dirname}/experience.js`, 'utf8');
const walkSoundPath = `${__dirname}/assets/squidward-walk.mp3`;
assert.equal(fs.existsSync(walkSoundPath), true, 'missing walking sound effect');
assert.match(experienceJs, /assets\/squidward-walk\.mp3/);
assert.match(experienceJs, /function walk\(duration = 0\)/);
assert.match(experienceJs, /read\('invite\.sound', 'on'\) !== 'off'/);
assert.match(experienceJs, /function primeWalkSound\(\)/);
assert.match(experienceJs, /function startCelebrationMusic\(\)/);
assert.match(experienceJs, /catMusicTrigger/);
assert.match(html, /id="catMusicTrigger"/);
assert.ok((html.match(/Invite\.walk\?\./g) || []).length >= 5, 'walking and pulling scenes should trigger the walk sound');
const catFramesDir = `${__dirname}/assets/cat-frames`;
const catFramePaths = Array.from({length: 10}, (_, index) =>
  `${catFramesDir}/dennice-cat-frame-${String(index + 1).padStart(2, '0')}.png`
);
for (const framePath of catFramePaths) {
  assert.equal(fs.existsSync(framePath), true, `missing cat frame: ${framePath}`);
  const png = fs.readFileSync(framePath);
  assert.equal(png.subarray(0, 8).toString('hex'), '89504e470d0a1a0a', `not a PNG: ${framePath}`);
  assert.equal(png.readUInt32BE(16), 512, `unexpected cat-frame width: ${framePath}`);
  assert.equal(png.readUInt32BE(20), 512, `unexpected cat-frame height: ${framePath}`);
}
const script = [...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)].map(m => m[1]).join('\n');
function setup() {
  const dom = new JSDOM(html, {runScripts: 'outside-only', url: 'http://localhost/'});
  const w = dom.window, events = [], timers = new Map();
  let time = 0, id = 0;
  Object.defineProperty(w.performance, 'now', {value: () => time, configurable: true});
  w.Invite = {
    track: (...args) => events.push(args),
    hush() {}, whisper() {}, tone() {},
    walk: duration => events.push(['walk_sound', duration]),
    stopWalk: () => events.push(['walk_sound_stop']),
    primeWalkSound: () => events.push(['walk_sound_prime']),
    primeCelebrationMusic: () => events.push(['celebration_music_prime']),
    stopCelebrationMusic: () => events.push(['celebration_music_stop'])
  };
  w.scrollTo = () => {};
  w.matchMedia = () => ({matches: true});
  w.requestAnimationFrame = () => 0;
  w.cancelAnimationFrame = () => {};
  w.setTimeout = (fn, delay = 0) => { timers.set(++id, {at: time + delay, fn}); return id; };
  w.clearTimeout = id => timers.delete(id);
  function advance(ms) {
    const until = time + ms;
    for (;;) {
      const next = [...timers].filter(([, v]) => v.at <= until).sort((a, b) => a[1].at - b[1].at)[0];
      if (!next) break;
      time = next[1].at; timers.delete(next[0]); next[1].fn();
    }
    time = until;
  }
  w.eval(fs.readFileSync(`${__dirname}/cats.js`, 'utf8'));
  w.eval(script);
  const el = selector => w.document.querySelector(selector);
  const down = selector => el(selector).dispatchEvent(new w.Event('pointerdown', {bubbles: true, cancelable: true}));
  const up = selector => el(selector).dispatchEvent(new w.Event('pointerup', {bubbles: true}));
  el('#nameInput').value = 'Dennice';
  el('#nameForm').dispatchEvent(new w.Event('submit', {bubbles: true, cancelable: true}));
  advance(4601);
  assert.equal(el('#askScreen').hidden, false);
  return {dom, el, down, up, advance, events};
}

let t = setup();
t.down('.yes-hold'); t.advance(4681);
assert.equal(t.el('#yayScreen').hidden, false);
assert.equal(t.el('#introScreen'), null);
assert.equal(t.el('#pickScreen'), null);
assert.equal(t.el('#freeMascot').hidden, true);
assert.equal(t.el('#sidekick').hidden, true);
assert.equal(t.el('#catParty').querySelectorAll('.cat-frame').length, 10);
assert.equal(t.el('#catParty').dataset.frameCount, '10');
assert.match(t.el('#catParty').querySelector('.cat-frame').getAttribute('src'), /dennice-cat-frame-01\.png$/);
assert.match(catsJs, /const frameCount = 10/);
assert.match(catsCss, /Ten actual transparent PNG frames/);
assert.match(t.el('#yayScreen').textContent, /Did I make you laugh/);
assert.match(t.el('#yayScreen').textContent, /seryoso naman ako/);
assert.ok(t.events.some(e => e[0] === 'confirmed' && e[1] === 'G'));
t.dom.window.close();

t = setup();
t.down('.yes-hold');
t.advance(1200);
t.up('.yes-hold');
assert.equal(t.el('.yes-hold').classList.contains('is-paused'), true);
assert.equal(t.el('#yayScreen').hidden, true);
t.advance(5000);
assert.equal(t.el('.yes-hold').classList.contains('is-paused'), true);
t.down('.yes-hold');
assert.equal(t.el('.yes-hold').classList.contains('is-paused'), false);
t.advance(3480);
assert.equal(t.el('#yayScreen').hidden, false);
assert.ok(t.events.some(e => e[0] === 'hold_pause' && e[1].startsWith('yes:')));
assert.ok(t.events.some(e => e[0] === 'hold_resume' && e[1] === 'yes'));
t.dom.window.close();

t = setup();
t.down('.yes-hold');
t.advance(1200);
t.up('.yes-hold');
assert.equal(t.el('.yes-hold').classList.contains('is-paused'), true);
t.down('.no-hold');
assert.equal(t.el('.yes-hold').classList.contains('is-paused'), false);
assert.equal(t.el('.no-hold').classList.contains('is-holding'), true);
assert.ok(t.events.some(e => e[0] === 'hold_switch' && e[1] === 'yes->no'));
t.advance(1800);
assert.equal(t.el('#freeMascot').classList.contains('is-walking'), true);
t.dom.window.close();

t = setup();
t.down('.no-hold');
t.advance(8500);
assert.equal(t.el('.no-hold').classList.contains('is-dragging'), true);
assert.ok(t.events.some(e => e[0] === 'walk_sound' && e[1] === 4600));
assert.ok(t.events.some(e => e[0] === 'walk_sound' && e[1] === 2140));
t.up('.no-hold');
assert.equal(t.el('.no-hold').classList.contains('is-paused'), true);
t.down('.yes-hold');
assert.equal(t.el('.no-hold').classList.contains('is-paused'), false);
assert.equal(t.el('.no-hold').classList.contains('is-returning'), true);
assert.equal(t.el('.yes-hold').classList.contains('is-holding'), true);
assert.ok(t.events.some(e => e[0] === 'hold_switch' && e[1] === 'no->yes'));
t.advance(661);
assert.equal(t.el('.no-hold').classList.contains('is-dragging'), false);
t.dom.window.close();

t = setup(); t.down('.no-hold'); t.advance(45000);
assert.equal(t.el('.no-hold').classList.contains('is-dragging'), false);
t.down('.no-hold'); t.advance(1500); t.up('.no-hold'); t.advance(3500);
assert.equal(t.el('#noThanksScreen').hidden, true);
t.down('.no-hold'); t.advance(3001);
assert.equal(t.el('#noThanksScreen').hidden, false);
assert.ok(t.events.some(e => e[0] === 'confirmed' && e[1] === 'Pass (red)'));
assert.match(t.el('#noThanksScreen').textContent, /Kung pass muna, okay lang/);
assert.equal(t.el('#freeMascot').hidden, false);
t.dom.window.close();

t = setup();
t.down('.no-hold');
t.advance(2500);
t.up('.no-hold');
assert.equal(t.el('.no-hold').classList.contains('is-paused'), true);
assert.equal(t.el('.no-hold').classList.contains('is-dragging'), false);
t.advance(5000);
assert.equal(t.el('.no-hold').classList.contains('is-paused'), true);
t.down('.no-hold');
assert.equal(t.el('.no-hold').classList.contains('is-paused'), false);
t.advance(18400);
assert.equal(t.el('.no-hold').classList.contains('is-dragging'), true);
 t.advance(25000);
assert.equal(t.el('.no-hold').classList.contains('is-dragging'), false);
assert.ok(t.events.some(e => e[0] === 'hold_pause' && e[1].startsWith('no:')));
assert.ok(t.events.some(e => e[0] === 'hold_resume' && e[1] === 'no'));
t.dom.window.close();

t = setup();
t.down('.no-hold');
t.advance(18500);
t.up('.no-hold');
assert.equal(t.el('.no-hold').classList.contains('is-paused'), true);
const exitEvent = new t.dom.window.Event('transitionend', {bubbles: true});
Object.defineProperty(exitEvent, 'propertyName', {value: 'transform'});
t.el('.no-hold').dispatchEvent(exitEvent);
t.advance(451);
assert.equal(t.el('.no-hold').classList.contains('is-paused'), false);
assert.equal(t.el('#freeMascot').classList.contains('is-walking'), true);
t.dom.window.close();

t = setup(); t.el('#declineButton').click();
assert.equal(t.el('#noThanksScreen').hidden, false);
assert.ok(t.events.some(e => e[1] === 'Pass (ibang araw)'));
t.dom.window.close();
console.log('PASS: finale flow, touch/pointer pause-resume, and green/red choice-switch checks. No live tracking data written.');
