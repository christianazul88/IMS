# Princess Protocol

**Current version:** Play goes straight to The Adapted One. See
[DUEL-REWORK.md](DUEL-REWORK.md) for the revised hero, live shockwave,
controls, art prompt and current tests. The campaign notes below are historical.

Open `index.html` in a modern browser (or serve this folder with any static web server).

> **Current campaign scope:** The playable campaign is now a four-act, enemy-free
> journey that culminates in **The Adapted One**. All other bosses, enemy waves,
> and their PNG art have been removed from the shipped route and `assets/`.
> The `?dev=1` lab retains isolated legacy diagnostics only, so the regression
> harness can still exercise combat math without making those encounters part
> of the game.

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

The campaign now has four acts of exploration. There are no regular enemy waves
or interim bosses; each act is a quiet traversal that leads to The Adapted One.
The Moon Knight roves inside the visible play area and attacks independently
instead of trailing the hero.

## Opening boss — The Adapted One

The normal route travels through Act I–IV first, then starts the dedicated Adapted
One encounter. The `?dev=1` lab can still open the boss directly for testing.
Its visible standing body is twice the hero's body height (94 vs 47 game pixels);
the halo sits above that measurement.

The boss learns weapon categories at attack emission, remembers the last eight dodge directions, and chooses attacks appropriate to the hero's range. Unused resistance fades. Observe → Adapt → Hunt → Perfect Adaptation phases occur at 75%, 40%, and 15% HP, after the current committed sequence finishes. Its jab/cross combo adds a delayed heavy third hit in Hunt. Other moves are a targeted dash punch, backhand, grab, slam with six parryable bolts, five-shot halo fan, judgment beam, and frontal adaptive guard. Every move has warning, active, and recovery stages. Orange areas show danger, FOLLOW-UP warns of another combo beat, and OPEN marks the final punish window (22% bonus damage).

`game.js` contains `ADAPTED_ONE_CONFIG` (body size ratio, intro, dash speed/warning), `BOSS_HP.adapted` (durability), and `ADAPTED_ATTACK_POOL` (phase unlocks). The readable boss implementation is in `adapted-one.js`: edit `ADAPTED_MOVES` for each move's `wind`, `active`, `recover`, `reach`, `step`, `damage`, and `arc`. Damage uses the hero's actual position; prediction only chooses the telegraphed aim. Guard protects the front, so flanking or weapon switching remains useful. Sword finishers interrupt briefly; automatic fire cannot permanently freeze the boss.

`ADAPTED_ONE_CONFIG.dashWindup` is 0.62 seconds and `dashSpeed` is 1050. The dash locks its destination at wind-up, then commits along the orange lane and recovers for 0.82 seconds. Sidestepping escapes the lane; dash invulnerability prevents both damage and knockback. A swept collision test prevents the fast fist from skipping over the hero at lower frame rates. Boss movement is bounded to keep the whole body inside the playable area.

The existing `assets/adapted-one-combat.png` has 32 poses but **unequal row heights**. `ADAPTED_ATLAS.rows` in `adapted-one.js` specifies measured [top, bottom] source bounds; `feet` supplies the common floor anchor. Never replace these with image height / 8 or a blanket top inset: that was the head/foot clipping bug. `adaptedSeam()` separates overlapping dash trails/halos between rows, and `drawAdaptedFrame()` is shared by the game and inspector. The source PNG is preserved. Animation now follows the actual warning/strike/recovery state, rather than showing a punch throughout its wind-up.

Dismantle remains a high-impact crowd-control ultimate, while The Adapted One
limits each lane to 1.2% of its base HP before resistance, responds to the hits,
and is slowed to 70% speed. Tune that cap at `ultimateDismantleHit()` in
`game.js` and the boss's poise/slow in `adaptedBossUpdate()`.

Open `?dev=1` to use the **Adapted One test lab**. It can replay every move in any phase, run at quarter speed, inspect all 32 poses, equip any weapon, and run all ten hero attack/support cases using real combat code. The arsenal test restores hero HP for observation and reports actual damage; it does not alter boss durability. Reset normal duel returns to manual play. The headless `node smoke.js` suite additionally verifies all moves/phases, 30/60/120 Hz dash collisions, successful evades, facing, memory decay, guard flanking, Dismantle reactions, death handoff, and frame source bounds. Visual checks still require the browser inspector.

Design references: [Cuphead's animation and boss presentation](https://www.cupheadgame.com/), [Hollow Knight's challenging 2D combat](https://www.hollowknight.com/), and [Furi creative director's warning / reaction / punish approach](https://blog.playstation.com/2016/03/09/furi-on-ps4-a-gauntlet-of-brutal-boss-battles/). The new timing and patterns are original adaptations for this game's free movement arena.

### Map themes

`MAP_THEMES` near the top of `game.js` is the map routing table for `act1`,
`act2`, `act3`, `act4`, and `adapted`. Every entry currently points to `map.png`;
add a new PNG name to `assets/` and change only the corresponding entry when
you are ready to give an act or The Adapted One its own backdrop.

The Adapted One arena uses the shared `map.png` route backdrop. `arena-map.js`
adds animated water glints, drifting spores, plant sway, and depth-sorted
foreground trees; scenery is visual only, so the earlier rectangular collision
cover system is disabled.

### Difficulty

The start panel offers **Super Easy**, **Easy**, **Hard**, and **No Mercy**. The
editable `DIFFICULTIES` table in `game.js` controls The Adapted One's durability
and attack tuning. The journey itself has no enemy waves, so difficulty changes
are felt in the final hunt rather than in disposable filler encounters.

Run `node make-expanded-sprites.js` to regenerate the deliberately low-resolution
transparent PNGs for the Moon Knight, weapons, pickups, and support effects.
Legacy enemy and boss generation is disabled in that script.

## Archived developer diagnostics (not part of the shipped campaign)

The historical Demon Lord notes below are retained only as a record for the
headless regression harness. The Demon Lord, its flank heads, necks, summons,
and arena art are not loaded by the normal game and their PNG files have been
removed. The only boss players can encounter is The Adapted One.

### Historical Demon Lord notes

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

The Adapted One drops healing at three HP milestones — 70%, 40%, and 15% —
instead of flooding the quiet traversal acts with random pickups. If you die
during the hunt, your checkpoint sends you straight back to The Adapted One.

## Lifesteal buff

A one-time **Lifedrinker** pickup (`lifesteal.png`) appears in Act II, ahead of the harder bosses. Once collected, a percentage of the damage your own shots deal (not your ally's) is returned to you as HP for the rest of the run. The percentage is set by `LIFESTEAL_PCT` near the top of `game.js`.

## Editing the ending credits


After the reunion, the game shows a sunset beach scene and rolls its credits. In `game.js`, edit the `END_CREDITS` array. Each quoted string is one line; use an empty string for vertical space. The scene and credits are canvas-rendered so they stay sharp in Chrome, Edge, Safari, desktop, and mobile browsers.

## Map and mobile layout

The game loads `assets/map.png` when it is present, then falls back to its built-in drawn background. `make-map.js` creates the included starter map; replace `assets/map.png` with your own 4096×720 PNG whenever you are ready to draw the final level.

On touch devices, tap **⚙ UI** to edit your control layout. While that panel is open you can **drag the move pad, fire stick, or dash button anywhere on the screen** to place them directly — the sliders in the panel (size, opacity, position, aim sensitivity) stay in sync with whatever you drag to, so you can fine-tune after placing. Dash now has its own independent position instead of being stuck next to Fire. There's also a **SWAP SIDES** button for left-handed play (mirrors the move pad and fire/dash cluster), and a control-opacity slider if you want the buttons less visually intrusive. Everything is saved per device and restored automatically.

Pinch-zoom and double-tap-zoom are disabled at the document level (not just the viewport meta tag, which Safari doesn't always fully respect), so mid-fight taps — like mashing the dash button — shouldn't ever trigger the browser's zoom gesture. On iPhone Safari, use Share → **Add to Home Screen** and launch the installed web app for the reliable app-style fullscreen experience.

## Animated character sprites

`make-animated-sprites.js` creates low-resolution starter PNGs. The hero now has state-specific sheets:

- `hero-<weapon>-<direction>-idle-1.png` — directional rest pose
- `hero-<weapon>-<direction>-run-1..4.png` — directional stride loop
- `boss-idle-1/2.png` and `boss-action-1/2.png`
- `princess-idle-1/2.png` and `princess-action-1/2.png`

The Demon Lord's head/body/beam sprites are generated separately by `make-expanded-sprites.js` (see the "Demon Lord" section above) since it needs three head states instead of two.

The renderer selects the hero state automatically. The canvas adds a projected contact shadow, a small lift above that shadow, squash/stretch, a cyan dash rim, and translucent dash afterimages to give the side-on game a 2.5D read. Keep the filenames if you replace the artwork with your own sprites. To regenerate the starter sheets after editing the generator, run `node make-animated-sprites.js` from the project folder.

The 2.5D hero now also uses real eight-direction art instead of rotating the same image: `up` shows the hero's back, `down` shows the front, and the six other directions use distinct side or three-quarter poses. Directional sheets follow these filename patterns:

- `hero-gun-<direction>-idle-1.png` and `hero-gun-<direction>-run-1..4.png`
- `hero-sword-<direction>-idle-1.png` and `hero-sword-<direction>-run-1..4.png`
- `hero-sword-<direction>-combo-<stage>-1..5.png` — five frames for each of the three directional combo stages

Directions are `right`, `down-right`, `down`, `down-left`, `left`, `up-left`, `up`, and `up-right`.

## Heavy sword

The hero begins with the **Heavy Sword** available alongside guns. Press **4** to select it directly or **Q** to cycle weapons; mobile players use the **SWAP** button beside FIRE. One click makes one quick cut. Click again during that cut to queue the reverse slash; a third click queues the straight forward dash-pierce finisher. Holding FIRE for 120ms also chains the combo. This is a compact, DMC-inspired rhythm: quick cut → reverse cut → committed stinger dash, with no automatic return swing after a single tap. Each stage has five directional animation frames. `SWORD_COMBOS` near the end of `game.js` controls each hit's damage, range, arc width, timing, cooldown, and final-pierce speed.

Every sword stage has a generated Web Audio hit: the first two cuts use lighter metallic swishes, while the dash has a low impact and bright transient. During an active swing the blade also has a narrow front-facing parry cone. Ordinary enemy bullets that enter that cone are reflected back at 1.8× damage; rockets, rear shots, and bullets outside the 104px / 0.95-radian guard window still hurt. Tune `SWORD_GUARD_RADIUS` and `SWORD_GUARD_ARC` at the end of `game.js` when balancing defense.

The Act II route drops a **Cleave Sigil**. It sends three delayed, slower ghost-sword echoes through targets beyond the initial sword hit, dealing secondary damage and slowing non-boss enemies to 35% movement speed for 0.9 seconds. The echoes are drawn in code, not from a slash PNG, so they stay aligned with every attack angle. Enemy and boss damage now scatters droplets and leaves persistent floor blood; lethal hits use a larger burst. Dash remains an evade: while its invulnerability window is active, bullets are consumed and `hurt()` exits before HP or shield damage is applied.

Against a boss core, the Heavy Sword is a **Siegebreaker**: every direct sword hit deals 2.5× damage, and the dash-pierce finisher staggers the boss for 0.55 seconds. The dash leaves short-lived afterimages so its travel and impact are readable. This makes guns the safer ranged option, while a completed sword combo is the high-risk burst option. Tune `SWORD_BOSS_DAMAGE_MULTIPLIER` and `SWORD_FINISHER_STAGGER` in `game.js` to change that tradeoff.

## Weapon ultimates

Press **E** or the mobile **ULT** button. The equipped weapon determines the attack, with an eight-second cooldown: the sword fires **Dismantle Storm** for ten seconds, laying down 260 long slash textures across the whole playable arena in every direction. Each lane reaches full length at 10,000x its normal travel speed, so every direction reads on its first rendered frame while the storm keeps filling the arena. Every cut now applies a readable micro hit-stop, recoil pulse, flash, and blood burst to minions; bosses and demon heads receive a stronger stagger and screen shake. The pistol fires a precision fan, the shotgun fires a wide scatter burst, and the machine gun fires a rocket-heavy **Gatling Rocket Bloom**. The developer preview is enabled with `?dev=1` or F9; F6 forces the sword ultimate, F7 the shotgun, and F8 the machine-gun version. Ultimate cooldown and the developer hint are shown in the HUD.

The Dismantle brush is the transparent `assets/sword-dismantle.png` sprite. Replace that file to change the slash silhouette; the arena lanes, ten-second duration, 260-count budget, 10,000x travel multiplier, million-power damage scale, hit-stop/recoil timings, crowd-control slow, and fade timing are controlled by the Dismantle constants and `ultimateDismantleUpdate()` near the end of `game.js`. The million-power setting is applied per legal target rather than creating a million render objects, so the attack stays instant without freezing mobile browsers.

Normal Heavy Sword hits now apply directional knockback too. Tune `SWORD_MINION_KNOCKBACK`, `SWORD_BOSS_KNOCKBACK`, and `SWORD_KNOCKBACK_TIME` beside the combo settings when you want lighter or heavier launches.

Act I also runs a **300-minion horde budget**. The game recycles the field at a 36-enemy active cap so the browser stays responsive, but every spawn is counted toward the full 300; the HUD shows `ACT I HORDE spawned/300` while travelling through the act. `ACT1_MINION_TOTAL` and `ACT1_ACTIVE_CAP` near the top of `game.js` are the tuning points.

## Replacing the huge sword art

The sword is no longer baked into the hero body. Draw or replace these transparent 128×128 PNGs in `assets/` while preserving their filenames:

- `sword-rest-<direction>.png` — eight ready poses.
- `sword-swing-<direction>-combo-<stage>-<frame>.png` — 120 attack frames.

The game renders those weapon images at a larger scale than the hero. The attachment point is the image center; keep the handle near `(64, 68)` so your weapon stays in the hero’s hand. `swordSize` in `drawHero()` controls its on-screen size.

Hero timing and depth tuning is grouped near the top of `game.js`:

```js
const HERO_ANIM={state:'idle',t:0,moving:false,fire:0,hurt:0,direction:'right',faceAngle:0};
const HERO_FRAME_RATE={idle:7,run:12,fire:16,dash:18,hurt:9,sword:12};
```

The `drawHero()` function near the end of `game.js` controls the 2.5D presentation. Adjust `lift`, `scaleX`, `scaleY`, or the shadow ellipse there without changing collision or movement logic.

## Recent changes

- The boss arena is much wider now — the fixed camera and movement bounds were mismatched before, so roughly half the "usable" arena was actually off-screen. It's now sized to match the visible canvas.
- Boss attacks got a pass for fairness: the bullet barrage has a short telegraphed windup before it starts firing, fires slower, and the shots move slower; the eclipse nova fires fewer/slower bullets; the charge attack is a touch gentler. Dash cooldown is shorter to give you more outs.
- Small "juice" additions: procedural sound effects (fire/hit/dash/pickup/warnings/victory plus sword swishes, finisher impact, and bullet parry, generated with the Web Audio API — no external audio files needed), screen shake on impacts, a kill-combo callout, a road progress bar, and a saved best-run kill count (`localStorage`).
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
| `hero.png`, `princess.png`, `ally.png`, `adapted-one*.png` | 128×128 (source art varies) |
| `shotgun.png`, `buff.png`, `sword.png`, `lifesteal.png`, `rocket-launcher.png`, `rocket.png` | 96×96 |
| `bullet.png`, `adapted-bullet.png` | 32×32 |

The removed legacy boss/enemy sheets are intentionally not generated or shipped.

The starter sprite pack is intentionally simple and stylized; it is only there to make the game playable immediately. The game retains the sprite's aspect ratio and includes drawn fallbacks if an asset is briefly missing.

## No Mercy shield

Choosing **No Mercy** now also equips the hero with a rotating deflector shield (`shield.png`), drawn spinning around the hero whenever it's charged. It absorbs incoming damage — from enemy contact, bullets, boss/Demon Lord attacks, everything — up to `SHIELD_MAX` total, then goes fully offline for `SHIELD_COOLDOWN` seconds before snapping back to full charge. Both constants live at the top of the new shield block in `game.js`:

```js
const SHIELD_MAX=60,SHIELD_COOLDOWN=8;
```

- Damage beyond what's left in the shield spills over onto the hero's HP in the same hit (e.g. a 55-damage hit against a 40-charge shield does 40 absorbed + 15 real damage).
- While it's down, a dashed gray ring around the hero and a HUD readout (top right, "SHIELD DOWN Xs") make it obvious you're unprotected; while it's up, a filled cyan ring shows the remaining charge.
- The shield only exists on No Mercy — other difficulties are unaffected — and it fully resets (charged, no cooldown) whenever you start a new run or respawn at a checkpoint.
- Dash invulnerability still takes priority: dashing through an attack never touches the shield's charge either way.

## Auto Rocket Pod (buff weapon)

A new pickup, the **Siege Pod** (`rocketpack`, reusing the `rocket-launcher.png` art),
gives the hero an automatic homing rocket launcher. It fires independently of
the held gun and locks onto The Adapted One during the final hunt. A second
pickup, **ROCKET OVERDRIVE** (`rocketrapid`, using `rocket-buff.png`), temporarily
shortens its firing cooldown. Tuning constants live together in `game.js`:

```js
const ROCKET_BASE_CD=1.5,ROCKET_RAPID_CD=.6,ROCKET_RAPID_DURATION=8,ROCKET_DAMAGE=7,ROCKET_SPEED=460,ROCKET_TURN_RATE=3.4,ROCKET_RANGE=980;
```

`ROCKET_TURN_RATE` is the max radians/second the rocket can turn — raise it for tighter homing, lower it to make dodging the rockets' own limitations (irrelevant since these are yours, but it also governs how "smart" it looks) more visible. Once acquired, "• SIEGE POD" appears in the weapon HUD line.

## Never-zoom hardening (mobile)

On top of the existing Safari gesture-event blocking and double-tap-zoom guard, `html`/`body`/`.shell` now use `touch-action:none` (previously `manipulation`, which technically still permits pinch-zoom per spec) plus `overflow:hidden`, and a raw multi-touch `touchstart`/`touchmove` listener now calls `preventDefault()` on any 2+ finger touch anywhere in the document — a backstop for Android/Chrome builds that have been known to ignore both the `user-scalable=no` viewport meta tag and `touch-action` CSS for pinch gestures specifically. Single-finger touches (move pad, fire stick, dash button) are completely unaffected since the guard only triggers on `e.touches.length>1`.

## Cursed Land map animation

The Adapted One arena uses the supplied Cursed Land TMX's original PNG atlases
and tile animation definitions, not a single still image with simulated glints.
All 32 authored layers retain their order. The map has 284 animated placements
using 179 distinct definitions; all 487 definitions from the pack are imported.
Unused tile variants are available in the data but are not placed over the map.
Water, shoreline and surface-detail frames use their original 150ms/1500ms
durations. Plants, rocks and trees have no frame animations in the supplied TMX.
The Adapted One now uses `arena-terrain.js` for a closer following camera,
water movement boundaries and real scenery depth sorting. Existing PNG objects
are separated from the ground and sorted with characters by their foot/base Y.
Destructible cover and projectile-blocking walls remain disabled.

### Arena size, water and foreground tuning

Edit the commented `tuning` object at the top of `arena-terrain.js`, then reload:

- `mapScale: 3`: native-to-screen zoom (previously approximately 2).
- `followRate`: camera follow smoothing; the full 608×400 native map is an
  explorable 1824×1200 world at the unchanged 3× zoom.
- `characterScale: .72`: hero/boss artwork scale; boss remains roughly twice
  hero body height. Attack warning areas retain their actual gameplay reach.
- `heroFootRadius/bossFootRadius`: shore clearance at the ground-contact point.
- `propBaseFraction: .86`: sorting anchor within a scenery sprite's height.

Water is identified from the supplied water palette and terrain layers; bridge
art remains walkable. Swept movement stops walking, dashing and knockback from
crossing ponds. The boss routes around ponds during pursuit. Characters spawned
in water are moved to nearby dry ground. Other acts are unaffected.

With `?dev=1`, the lab includes **Test whole-map water**, **Tour map corners**,
and **Frame timing**. Corner previews freeze the game; press P to resume.
The water test exercises both footprints and long dash/knockback paths.
Routing uses a precomputed 3,800-cell navigation grid and a cached distance
field refreshed at most every .35 seconds when the hero changes cells.
Offscreen scenery is culled. The first PNG/mask preparation runs from the menu;
frame timing reports JavaScript update/draw costs, not GPU timing or device FPS.

### Adapted One: anti-kiting attacks

`adapted-hunt.js` contains the commented tuning values and three new attacks:

- **Rupture:** expanding shockwave with a visible safe gap; dash through the
  ring or move through the gap. Later phases send two separated rings.
- **Fault Line:** three delayed eruptions along a predicted escape lane.
  Circles lock at cast time; moving out of them avoids the damage.
- **Double Hunt:** two separately warned lunges. Dodging the first is not
  enough; the follow-up gets its own warning and committed target.

Repeated long-range play builds pressure and biases these attacks. Ordinary
pursuit is faster, with an extra catch-up sprint at long range. Recovery windows,
dash invulnerability and the boss's approximately 2× hero scale remain intact.
Replay all three attacks from the existing Boss move selector in the test lab.

- `arena-map.js`: change `tuning.animationSpeed` (1 = original, 2 = twice as fast).
- `make-cursed-map.js`: source folder and visible map crop; run `node make-cursed-map.js`
  after editing the source TMX/TSX. An alternate asset-pack folder can be passed
  as the first argument. It copies atlases and regenerates `assets/cursed-land/map-data.js`.
- `test-map.html`: browser animation preview and pixel/timing/loop/cache checks.
  It runs locally with the same renderer as the game.

Static layers are cached; the native-resolution map only recomposites when a
tile's frame changes. Animation follows game time and stops when paused.
The existing static arena PNG remains a loading fallback. No asset generation
or external network requests are needed to load this map.

## Testing

`test/smoke.js` is a headless regression test: it loads the real `index.html`/`game.js` in `jsdom` with a stubbed canvas context and drives simulated frames through travel, an Act boss fight, the Demon Lord fight (including flank destruction → core exposure), and the new shield/rocket-pod systems (including a combined No-Mercy + Demon-Lord + rocket-pod stress pass), asserting on real internal game state rather than just "did it throw." Run it after any change to `game.js`:

```
npm install jsdom
node test/smoke.js
```

It can't check pixels/visuals, so still eyeball a real playthrough in a browser afterward, but it will catch most crashes and logic regressions immediately.
