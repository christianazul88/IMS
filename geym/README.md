# Princess Protocol

Open `index.html` in a modern browser (or serve this folder with any static web server).

## Controls

- **Move:** WASD / arrow keys
- **Aim and fire:** mouse / touchpad click
- **Double dash:** Space — two charges; each charge regenerates independently.
- **Switch weapons:** 1 / 2 / 3 (after collecting them)
- **Pause:** P, Esc, or the ⅠⅠ button
- **Mute:** the 🔊 button (top right)
- **Mobile:** left thumb pad to move; hold and drag the FIRE control to aim in any direction while shooting; DASH is on the right
- **Fullscreen:** ⛶ FULL button

## Adding your own background music

In `game.js`, find the clearly marked `CUSTOM MP3 FILES` section and set a filename for each story state:

```js
const MUSIC_TRACKS = {
  journey: 'ashen-road.mp3',
  boss: 'citadel-battle.mp3',
  rescued: 'color-returns.mp3'
};
```

Put those MP3 files under `assets/`. Leave any value blank to use that state’s built-in adaptive Web Audio score. Tracks begin after **Begin Rescue**, satisfying mobile-browser audio rules.

## Rescue structure and editable new art

The campaign now has four acts, each ending in its own boss: Gate Knight, Thorn Beast, Glass Oracle, and the Witch of Eclipse. The first three fights lead into the final citadel, so there are two full new levels before the old finale. The Moon Knight roves inside the visible play area and attacks independently instead of trailing the hero.

### Map themes

`MAP_THEMES` near the top of `game.js` is the map routing table for `act1`, `act2`, `act3`, `act4`, and `demon`. Every entry currently points to `map.png`; add a new PNG name to `assets/` and change only the corresponding entry when you are ready to give an act or the final boss its own backdrop.

### Difficulty

The start panel offers **Super Easy**, **Easy**, **Hard**, and **No Mercy**. The editable `DIFFICULTIES` table in `game.js` controls the rules: Super Easy makes normal enemies and the four Act bosses one-hit while leaving the Demon Lord intact, Easy uses the original balance, Hard uses 3× durability, and No Mercy uses exactly 100× durability for enemies, flank heads, and bosses.

Run `node make-expanded-sprites.js` to regenerate the deliberately low-resolution transparent PNGs for the new minions, Moon Knight, machine gun, sword (used by the Glass Oracle's orbiting/thrown blades), and three new bosses. The generated files are all 128×128 and are intended to be edited directly.

## The Demon Lord — secret giant final boss

### Remastered final encounter

The Demon Lord is now a fixed three-head shrine centered at the top of the arena: one central core head and two independently targetable flank heads. Each flank head has a fixed neck socket, and the neck is assembled from repeated transparent PNG segments (`demon-neck-idle-1..6.png` and `demon-neck-expand-1..6.png`). During a charge, the endpoint retargets the hero’s exact current position, capped at a readable maximum reach so the lunge remains dodgeable; the socket never moves. The hero must destroy both flank heads before the center head can take damage. Flanks alternate between jaw lunges, curved bullet spits, and sweeping bullet fans; the center rotates through bullet rings, a huge telegraphed sweeping laser, rockets, summons, stomp shockwaves, and meteors. Destroying either flank drops an `OVERDRIVE` attack-speed pickup, and the center becomes exposed only after both drops have been earned. The center face tracks the hero with moving pupils but never expands. `make-expanded-sprites.js` generates six PNG frames each for the center idle/attack/laser states, flank idle/attack states, and neck idle/expand states. The rendering uses a 1990s arcade fighting-game treatment: hard pixel clusters, limited crimson/violet/gold palette, bright hit highlights, and strongly readable pose silhouettes.

New final-boss patterns are an infernal rocket salvo (large visible rocket PNGs with a short wind-up) and a close-range blade rush, alongside the existing barrage, sweep beam, summon, stomp, and meteor patterns. The post-Witch interlude now explains that the Witch was the lock and the Demon Lord was what it imprisoned.

The Siegebreaker is no longer a hero weapon. If Moon Knight was rescued, he receives the launcher when the Demon Lord appears and fires large rocket PNGs on a readable cooldown. This keeps the hero's weapon controls focused while making the ally a meaningful part of the final strategy.

After the Witch of Eclipse falls, the citadel doesn't go quiet — a giant demon awakens and blocks the way to the Princess. This is the true final fight, and it only appears once Act IV's boss is defeated (the Princess and her cage stay hidden until then).

The Demon Lord:
- Is built from a fixed **wide body/socket PNG** (`demon-body-sockets.png`, generated at 256×128 and rendered across the top of the arena), a center head frame set, two flank-head frame sets, and repeated neck-segment PNGs. The body is rendered first; every neck starts at its matching socket, follows the socket-to-head vector, repeats with overlap, and ends underneath the attached head sprite, so no attack pose can leave a floating neck gap.
- Keeps the center head and both neck sources at the top of the arena. The center head tracks the hero with pupils and fires the laser without expanding; each side head may charge toward the hero's exact current position, but its neck length is capped so the lunge stays readable and dodgeable.
- The socket-to-head layout is intentionally editable in the `DEMON_NECK_TUNING` block in `game.js`. `socketOffsetX`/`socketY` target the circled red source bars in the body PNG, `homeOffsetX`/`homeY` set the resting head position, and `headAttachInset` controls how far the repeated neck tucks underneath each head. The neck curve is rebuilt every frame from the fixed socket to the moving head, so animation never disconnects the segments.
- Fires a laser beam from its mouth using its own animated texture (`beam-1.png`, `beam-2.png` — a stretched, flickering energy-beam graphic, not a plain drawn line). It works in two phases: a short **aim** telegraph (a dashed warning line tracks you so you can see exactly where it's about to fire — "THE MAW TAKES AIM"), then it **commits and fires** — the beam locks in and slowly sweeps rather than re-tracking your position, so once it's live you dodge by reading its sweep instead of trying to outrun a beam that's homing directly onto you.
- Attacks in a rotating ring of bullets with gaps you can dodge through ("DEMONIC BARRAGE").
- Charges across the arena and slams down in a shockwave burst ("THE GROUND TREMBLES") — this is also what makes him actually move around the arena instead of sitting in one spot.
- Rains down telegraphed fire bursts at marked ground spots near you ("HELLFIRE FALLS") — move off the warning rings before they erupt.
- Summons demonic imps (`imp.png`) around the arena while simultaneously healing a little ("RISE, MY CHILDREN").
- Tactically channels a bigger emergency heal once, the first time its HP drops below 35% ("THE DEMON LORD DEVOURS THE DARK TO MEND") — after that it's spent and won't heal that way again.
- Gets faster/denser attacks the lower its HP goes, and its head gets a pulsing red enrage glow once it passes those thresholds.
- Drops one **OVERDRIVE attack-speed pickup** when each flank head is destroyed. The core remains sealed until both flank heads are gone, forcing the player to choose safe attack windows instead of burning the center's health early.
- Flank necks now rotate through five attacks: a three-second tracking hunt, curved spit, lane sweep, rake crossfire, and a radial bloom. Add a new name to `DEMON_NECK_ATTACKS` and a matching branch in `demonPartsUpdate()` to extend the set.
- The whole fight has a subtle pulsing red vignette around the screen edges for atmosphere.

Its HP lives in the same editable `BOSS_HP` block at the top of `game.js` as the other bosses (`BOSS_HP.demon`).

## Tactical heal drops

Every boss (all four Act bosses and the Demon Lord) now drops a healing pickup at three points in the fight — when it crosses 70%, 40%, and 15% of its own HP. That's it: three per fight, tied to boss HP milestones rather than random drops, so healing shows up right when a fight is getting serious instead of being spammed in.

If you die during this fight, your checkpoint sends you straight back to the Demon Lord — not all the way back to the Witch of Eclipse.

## Lifesteal buff

A one-time **Lifedrinker** pickup (`lifesteal.png`) appears in Act II, ahead of the harder bosses. Once collected, a percentage of the damage your own shots deal (not your ally's) is returned to you as HP for the rest of the run. The percentage is set by `LIFESTEAL_PCT` near the top of `game.js`.

## Editing the ending credits


After the reunion, the game shows a sunset beach scene and rolls its credits. In `game.js`, edit the `END_CREDITS` array. Each quoted string is one line; use an empty string for vertical space. The scene and credits are canvas-rendered so they stay sharp in Chrome, Edge, Safari, desktop, and mobile browsers.

## Map and mobile layout

The game loads `assets/map.png` when it is present, then falls back to its built-in drawn background. `make-map.js` creates the included starter map; replace `assets/map.png` with your own 4096×720 PNG whenever you are ready to draw the final level.

On touch devices, tap **⚙ UI** to edit your control layout. While that panel is open you can **drag the move pad, fire stick, or dash button anywhere on the screen** to place them directly — the sliders in the panel (size, opacity, position, aim sensitivity) stay in sync with whatever you drag to, so you can fine-tune after placing. Dash now has its own independent position instead of being stuck next to Fire. There's also a **SWAP SIDES** button for left-handed play (mirrors the move pad and fire/dash cluster), and a control-opacity slider if you want the buttons less visually intrusive. Everything is saved per device and restored automatically.

Pinch-zoom and double-tap-zoom are disabled at the document level (not just the viewport meta tag, which Safari doesn't always fully respect), so mid-fight taps — like mashing the dash button — shouldn't ever trigger the browser's zoom gesture. On iPhone Safari, use Share → **Add to Home Screen** and launch the installed web app for the reliable app-style fullscreen experience.

## Animated character sprites

`make-animated-sprites.js` creates low-resolution starter PNGs for two animation sets per character, each with two frames:

- `hero-idle-1/2.png` and `hero-action-1/2.png`
- `boss-idle-1/2.png` and `boss-action-1/2.png`
- `princess-idle-1/2.png` and `princess-action-1/2.png`

The Demon Lord's head/body/beam sprites are generated separately by `make-expanded-sprites.js` (see the "Demon Lord" section above) since it needs three head states instead of two.

The renderer alternates the frames automatically. Keep these filenames if you replace the artwork with your own sprites.

## Recent changes

- The boss arena is much wider now — the fixed camera and movement bounds were mismatched before, so roughly half the "usable" arena was actually off-screen. It's now sized to match the visible canvas.
- Boss attacks got a pass for fairness: the bullet barrage has a short telegraphed windup before it starts firing, fires slower, and the shots move slower; the eclipse nova fires fewer/slower bullets; the charge attack is a touch gentler. Dash cooldown is shorter to give you more outs.
- Small "juice" additions: procedural sound effects (fire/hit/dash/pickup/warnings/victory, generated with the Web Audio API — no external audio files needed), screen shake on impacts, a kill-combo callout, a road progress bar, and a saved best-run kill count (`localStorage`).
- The audio pass now includes an adaptive procedural score: an exploratory motif on the Ashen Road that switches to a faster, darker combat motif in the boss arena. It starts after **Begin Rescue** (to respect browser audio policies) and is controlled by the existing sound button.
- Quality-of-life polish: the dash now follows your latest movement direction, its cooldown/readiness is visible in the HUD, a cyan ring confirms dash invulnerability, and the game auto-pauses if the browser loses focus so movement/fire cannot get stuck.
- Performance and mobile pass: shots and particles have safe visual caps, off-screen shots are culled early, collision checks are cheaper during dense boss patterns, and the costly full-canvas grayscale filter was removed. Touch controls and an in-game fullscreen toggle are available on touch devices.
- The rescue finale now has a short reunion animation: the hero and Princess meet, dance together, and celebrate beneath a floating heart before the end screen.
- Mobile control pass: the FIRE button is now a rotatable aim stick with an on-screen reticle. The game surface prevents double-tap/pinch zoom gestures (including Safari gesture events), while desktop has a matching mouse-aim reticle and the game auto-pauses when the page becomes hidden.
- Encounter pass: the Moon Guardian ally sigil appears ahead of the gate, then an Eclipse Warden sub-boss blocks the approach to the Witch. The ally follows and fires at the nearest threat; only one may be active.

## Replacing artwork

All art lives in `assets/` and is loaded by filename in `game.js`. Each is a standalone PNG with a transparent background, so you can redraw and overwrite it while keeping its filename:

| File | Suggested canvas |
|---|---:|
| `hero.png`, `minion.png`, `boss.png`, `princess.png`, `imp.png`, `demon-core-*.png`, `demon-flank-*.png`, `demon-neck-*.png` | 128×128 |
| `demon-body-sockets.png` | 256×128 |
| `gun.png`, `shotgun.png`, `buff.png`, `sword.png`, `lifesteal.png`, `rocket-launcher.png`, `rocket.png` | 96×96 |
| `bullet.png`, `boss-bullet.png` | 32×32 |

The Demon Lord's center/flank/neck animation frames and its laser beam (`beam-1/2.png`) are also 128×128 — see the "Demon Lord" section above for how the pieces fit together.

The starter sprite pack is intentionally simple and stylized; it is only there to make the game playable immediately. The game retains the sprite's aspect ratio and includes drawn fallbacks if an asset is briefly missing.
