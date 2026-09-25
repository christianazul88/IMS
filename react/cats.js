// Her cat, animated as a real ten-frame celebration finale.
(() => {
  const stage = document.querySelector('#catParty');
  if (!stage) return;

  const frameCount = 10;
  const frames = Array.from({ length: frameCount }, (_, index) => {
    const frame = document.createElement('img');
    frame.className = `cat-frame${index === 0 ? ' is-active' : ''}`;
    frame.src = `assets/cat-frames/dennice-cat-frame-${String(index + 1).padStart(2, '0')}.png`;
    frame.alt = index === 0 ? 'Her fluffy brown-and-white cat celebrating' : '';
    frame.setAttribute('aria-hidden', index === 0 ? 'false' : 'true');
    frame.decoding = 'async';
    frame.draggable = false;
    stage.append(frame);
    return frame;
  });
  stage.dataset.frameCount = String(frameCount);

  const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion) return;

  let activeIndex = 0;
  let startedAt;
  let frameRequest;
  const frameDuration = 150;
  const render = (now) => {
    startedAt ??= now;
    const nextIndex = Math.floor((now - startedAt) / frameDuration) % frameCount;
    if (nextIndex !== activeIndex) {
      frames[activeIndex].classList.remove('is-active');
      frames[activeIndex].setAttribute('aria-hidden', 'true');
      frames[nextIndex].classList.add('is-active');
      frames[nextIndex].setAttribute('aria-hidden', 'false');
      activeIndex = nextIndex;
    }
    frameRequest = window.requestAnimationFrame(render);
  };
  frameRequest = window.requestAnimationFrame(render);
  window.addEventListener('pagehide', () => window.cancelAnimationFrame(frameRequest), { once: true });
})();
