(() => {
  const uuid = () => '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, c => (Number(c) ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> Number(c) / 4).toString(16));
  const read = (key, fallback) => { try { return localStorage.getItem(key) || fallback; } catch { return fallback; } };
  const save = (key, value) => { try { localStorage.setItem(key, String(value)); } catch { /* The invite still works without storage. */ } };
  const visitor = read('invite.visitor', uuid());
  save('invite.visitor', visitor);
  const visit = uuid();
  const started = performance.now();
  let typedName = read('invite.name', '');
  let visits = Number(read('invite.visits', '0')) + 1;
  save('invite.visits', visits);
  let queue = Promise.resolve();
  // Walking should be audible on a first visit; an explicit "off" choice still wins.
  let sound = read('invite.sound', 'on') !== 'off';
  let context;
  let music;
  let walkSound;
  let walkTimer;
  let walking = false;
  let primingWalk = false;
  let celebrationMusicTimer;
  let celebrationMusicActive = false;
  const celebrationOscillators = new Set();
  const celebrationNotes = [523.25, 659.25, 783.99, 659.25, 587.33, 698.46, 880, 698.46];
  let sideTimer;
  let hideTimer;
  function track(type, target = '') {
    const event = { visitor, visit, type, target, name: typedName, elapsedMs: Math.round(performance.now() - started) };
    queue = queue.then(async () => {
      try {
        const response = await fetch('activity.php', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(event), keepalive: true});
        if (!response.ok) throw new Error('Activity unavailable');
        const result = await response.json();
        if (type === 'visit') { visits = result.visits; save('invite.visits', visits); greet(); }
      } catch {
        document.querySelector('#recordingNotice').textContent = 'Hindi naka-save ang activity ngayon.';
      }
    });
    return queue;
  }
  function greet() {
    const heading = document.querySelector('#welcomeTitle');
    const copy = document.querySelector('#welcomeCopy');
    if (visits > 1 && !document.querySelector('#nameScreen').hidden) {
      heading.textContent = visits === 2 ? 'Oh, andito ka ulit.' : visits === 3 ? 'Ikaw ulit. Hello.' : 'Uy, welcome back.';
      copy.textContent = visits === 2 ? 'Same tanong pa rin. Baka iba na sagot mo.' : visits === 3 ? 'Hindi ko pa rin naliligpit yung mga button.' : 'Alam mo na kung nasaan yung buttons.';
    }
  }
  function tone(kind = 'tap') {
    if (!sound) return;
    try {
      context ??= new (window.AudioContext || window.webkitAudioContext)();
      context.resume().catch(() => {});
      const notes = kind === 'success' ? [523, 659, 784] : kind === 'whisper' ? [440, 360] : [580];
      notes.forEach((frequency, index) => {
        const oscillator = context.createOscillator();
        const gain = context.createGain();
        const at = context.currentTime + index * .1;
        oscillator.type = 'sine'; oscillator.frequency.value = frequency;
        gain.gain.setValueAtTime(0, at);
        gain.gain.linearRampToValueAtTime(.045, at + .012);
        gain.gain.exponentialRampToValueAtTime(.001, at + .16);
        oscillator.connect(gain); gain.connect(context.destination);
        oscillator.start(at); oscillator.stop(at + .18);
      });
    } catch { /* Audio is optional. */ }
  }
  function stopWalk() {
    walking = false;
    primingWalk = false;
    clearTimeout(walkTimer);
    walkTimer = undefined;
    if (!walkSound) return;
    walkSound.pause();
    try { walkSound.currentTime = 0; } catch { /* The sound may not be ready yet. */ }
  }
  function startWalkAudio() {
    if (!sound) return;
    try {
      walkSound ??= new Audio('assets/squidward-walk.mp3');
      walkSound.preload = 'auto';
      walkSound.loop = true;
      walkSound.volume = .42;
      primingWalk = false;
      if (walkSound.paused) {
        walkSound.currentTime = 0;
        walkSound.play().catch(() => {});
      }
    } catch { /* Audio is optional. */ }
  }
  function primeWalkSound() {
    if (!sound) return;
    try {
      walkSound ??= new Audio('assets/squidward-walk.mp3');
      walkSound.preload = 'auto';
      walkSound.loop = true;
      walkSound.volume = 0;
      primingWalk = true;
      const started = walkSound.play();
      started?.then(() => {
        if (!primingWalk) return;
        primingWalk = false;
        walkSound.pause();
        try { walkSound.currentTime = 0; } catch { /* The sound may not be ready yet. */ }
        walkSound.volume = .42;
      }).catch(() => { primingWalk = false; });
    } catch { /* Audio is optional. */ }
  }
  function primeCelebrationMusic() {
    if (!sound) return;
    try {
      context ??= new (window.AudioContext || window.webkitAudioContext)();
      context.resume().catch(() => {});
    } catch { /* Audio is optional. */ }
  }
  function stopCelebrationMusic() {
    celebrationMusicActive = false;
    clearTimeout(celebrationMusicTimer);
    celebrationMusicTimer = undefined;
    celebrationOscillators.forEach((oscillator) => {
      try { oscillator.stop(); } catch { /* It may already be finished. */ }
    });
    celebrationOscillators.clear();
  }
  function scheduleCelebrationMusic() {
    if (!celebrationMusicActive || !context) return;
    const beat = .22;
    const startAt = context.currentTime + .03;
    celebrationNotes.forEach((frequency, index) => {
      const oscillator = context.createOscillator();
      const gain = context.createGain();
      const at = startAt + index * beat;
      oscillator.type = index % 2 ? 'triangle' : 'sine';
      oscillator.frequency.setValueAtTime(frequency, at);
      gain.gain.setValueAtTime(.0001, at);
      gain.gain.exponentialRampToValueAtTime(.09, at + .018);
      gain.gain.exponentialRampToValueAtTime(.0001, at + .19);
      oscillator.connect(gain); gain.connect(context.destination);
      celebrationOscillators.add(oscillator);
      oscillator.addEventListener('ended', () => celebrationOscillators.delete(oscillator), { once: true });
      oscillator.start(at); oscillator.stop(at + .21);
    });
    celebrationMusicTimer = setTimeout(scheduleCelebrationMusic, celebrationNotes.length * beat * 1000 - 20);
  }
  function startCelebrationMusic() {
    if (!sound) return;
    try {
      context ??= new (window.AudioContext || window.webkitAudioContext)();
      stopCelebrationMusic();
      celebrationMusicActive = true;
      const begin = () => { if (celebrationMusicActive) scheduleCelebrationMusic(); };
      if (context.state === 'suspended') context.resume().then(begin).catch(() => {});
      else begin();
    } catch { /* Audio is optional. */ }
  }
  function walk(duration = 0) {
    walking = true;
    clearTimeout(walkTimer);
    if (duration > 0) walkTimer = setTimeout(stopWalk, duration + 120);
    startWalkAudio();
  }
  function whisper(text, delay = 0) {
    clearTimeout(sideTimer); clearTimeout(hideTimer);
    sideTimer = setTimeout(() => {
      const bubble = document.querySelector('#sidekickLine');
      bubble.textContent = text;
      document.querySelector('#sidekick').classList.add('is-speaking');
      tone('whisper');
      hideTimer = setTimeout(() => document.querySelector('#sidekick').classList.remove('is-speaking'), 4200);
    }, delay);
  }
  function hush() {
    clearTimeout(sideTimer); clearTimeout(hideTimer);
    document.querySelector('#sidekick').classList.remove('is-speaking');
  }
  const catMusicTrigger = document.querySelector('#catMusicTrigger');
  catMusicTrigger?.addEventListener('click', startCelebrationMusic);
  window.Invite = {
    track, setName: (name) => { typedName = String(name || '').slice(0, 80); save('invite.name', typedName); }, tone, whisper, hush, walk, stopWalk, primeWalkSound,
    primeCelebrationMusic, startCelebrationMusic, stopCelebrationMusic
  };
  greet();
  track('visit');
  const soundButton = document.querySelector('#soundButton');
  const renderSound = () => { soundButton.textContent = `Sound ${sound ? 'on' : 'off'}`; soundButton.setAttribute('aria-pressed', String(sound)); };
  renderSound();
  soundButton.addEventListener('click', () => {
    sound = !sound;
    save('invite.sound', sound ? 'on' : 'off');
    renderSound();
    if (sound) {
      primeWalkSound();
      primeCelebrationMusic();
      if (!document.querySelector('#yayScreen')?.hidden) catMusicTrigger?.click();
    }
    if (sound && walking) startWalkAudio();
    if (!sound) stopWalk();
    if (!sound) stopCelebrationMusic();
  });
  const musicButton = document.querySelector('#musicButton');
  fetch('music.php').then(response => response.json()).then(result => {
    if (result.available) {
      music = new Audio('assets/music.mp3'); music.loop = true; music.volume = .16;
      musicButton.hidden = false;
      music.addEventListener('error', () => { musicButton.textContent = 'Music unavailable'; musicButton.disabled = true; });
    }
  }).catch(() => {});
  musicButton.addEventListener('click', async () => {
    if (!music) return;
    if (music.paused) {
      try { await music.play(); musicButton.textContent = 'Music on'; musicButton.setAttribute('aria-pressed', 'true'); }
      catch { musicButton.textContent = 'Try music again'; }
    } else { music.pause(); musicButton.textContent = 'Music off'; musicButton.setAttribute('aria-pressed', 'false'); }
  });
  document.addEventListener('click', event => {
    const target = event.target.closest('button, a');
    if (!target) return;
    track('click', target.dataset.food || target.id || target.getAttribute('aria-label') || target.textContent.trim().replace(/\s+/g, ' ').slice(0, 100));
    tone();
  });
  let asideIndex = 0;
  document.querySelector('#sidekickButton').addEventListener('click', () => {
    const lines = ['Psst. Kanina pa niya pinapractice yan.', 'Wag ka maniwala. Kinakabahan yan.', 'Ako na bahala. Ikaw pumili.', 'Sabi niya simple lang. Tignan mo naman.'];
    whisper(lines[asideIndex++ % lines.length]);
  });
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) return;
    if (music) { music.pause(); musicButton.textContent = 'Music off'; musicButton.setAttribute('aria-pressed', 'false'); }
    stopCelebrationMusic();
  });
  window.addEventListener('pagehide', () => { stopWalk(); stopCelebrationMusic(); track('leave'); });
})();
