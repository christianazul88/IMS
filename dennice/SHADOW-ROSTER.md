# Four shadow contracts

Select the Umbral Conductor, then use **1–4**, **Q**, or the on-screen contract buttons.

1. **Winged Shade** — autonomous ranged shots; a 4.5-second dive special with brief electrical stagger pulses.
2. **Echo Gorilla** — 1.5× hero body height; autonomous melee with delayed echoes hitting nearby secondary enemies. Four-second accelerated rampage.
3. **Mirror Warden** — no offensive attacks. Absorbs incoming damage and reflects a capped portion. A limited guard pool and recharge prevent invulnerability. Four-second stronger guard special.
4. **Abyss Titan** — 4× hero body height; autonomous heavy melee. Five-second special with charge-up and continuous lasers. Beam damage is shared across all living targets, focused on one when alone.

The Titan's laser breath uses `assets/abyss-titan-laser-breath-6f-v2.png`, a six-cell transparent muzzle-emitter sheet anchored at the mouth: charge, ignition, release, sustained plasma, peak breath, and cooling. One shared core rotates toward the nearest target while the actual beam exits its far edge, so it reads as breath rather than a second monster. The Conductor and every summon are placed in the terrain depth queue and receive a shared contact shadow, allowing trees and ruins to occlude them naturally.

**R/right-click** activates the selected beast's special. Specials share a 14-second cooldown, and switching is locked during a special. Guard capacity persists across switching.

Each summon costs 20% of the Conductor's maximum health. A defeated shadow stays dispersed for **15 seconds** (`cfg.summonCooldown`) and can then be deliberately summoned again for the same sacrifice; it never silently reforms. While the Conductor is genuinely idle (not moving, firing, dashing, or casting), she regenerates 4 health per second (`cfg.idleRegen`).

When the Umbral Conductor has a living summon nearby, enemies can switch aggro to the nearest shadow (within `shadowAggroRadius`) instead of the player. Melee enemies chase and strike that shadow; wisps aim their next projectile at it. The Adapted One uses the same priority and must break the living court before targeting the Conductor. If no living shadow remains, enemies and bosses fall back to the player. The Vanguard never changes this default aggro behavior.

**F** (or the Ultimate button) activates a separate six-second cinematic power: **Sovereign Eclipse** summons all four contracts for the Conductor; **Heavenbreaker** gives the Vanguard a gold storm and final judgment. Cooldown is 28 seconds. The Conductor can also use E. Vanguard retains E for Dismantle and R for Earthsplitter.

Tune summon cost/cooldown/regen (`summonCostRatio`, `summonCooldown`, `idleRegen`), sizes, damage, durations, guard capacity, cooldowns, and the invisible melee capsules (`meleeReach` / `meleeLineWidth`) in `shadow-roster.js` → `cfg`. Gorilla and Titan can only begin a windup when the target intersects their forward range line; the line is never drawn. Their idle/locomotion loop is restricted to atlas cells 0–1, so attack cells 2–7 cannot appear until a real action starts. All bodies render with global opacity 1.0. The atlas uses real PNG alpha, eight columns and four rows. Rows: wing, gorilla, guardian, titan; columns are authored idle, windup, contact, follow-through, special and recovery poses. Runtime blends only a small amount of the adjacent pose to keep motion fluid without a translucent trail. Hero movement/command artwork remains in its existing atlases.

Boss strings and warning timings are in `adapted-combo-forms.js`. Lengths are 3, 5, 10 and 15; long strings use lower damage per hit, staggered rhythm, locked attack directions and final recovery. The test lab lists all four strings.

## Asset provenance

Generated using the built-in image-generation tool. Runtime asset: `assets/shadow-roster-atlas-8f.png` (32 poses). The source preview showed a dark backdrop, but alpha sampling confirmed empty regions have alpha 0–1; do not add clipping that crops extremities.

Final generation prompt:

Use case: stylized-concept. Create a production game sprite atlas on a genuinely transparent background, landscape, EXACTLY four equal rows and eight equal columns (32 cells), no labels, no grid lines. Dark fantasy 2.5D painted sprites, crisp opaque bodies, pale violet edge lighting and charcoal armor, bright eyes, readable at small size. Each row features ONE consistent creature facing three-quarter right across eight distinct animation poses with in-between motion. Row 1 WINGED SHADE: folded idle, crouch, wing lift, wing spread, launch, winged dive, electricity impact, brake recovery. Row 2 ECHO GORILLA: knuckle idle, left step, shoulder windup, left punch in, left punch follow-through, right uppercut, two-fist slam, recovery with echo rings. Row 3 MIRROR WARDEN: idle guard, walk, shield raise, shield lock, impact absorption, reflected beam release, mirror shards outward, shield-lowered recovery. Row 4 ABYSS TITAN: idle, heavy step, claw windup, claw swing, claw follow-through, mouth charge, sustained multi-target laser channel, laser recovery. Entire body including feet, wings, horns and effects fits within each cell with 10% transparent padding, consistent foot baseline and creature scale within each row. Bodies fully opaque and strongly visible. Empty space truly transparent. No scenery, colored background, text, watermark, checkerboard or ground plane.

## Verification

Run `npm test`. The suite exercises the shipped scripts, four contracts, autonomous damage, guardian reflection and retained capacity, secondary cleave, single/multiple laser targeting and bounded total damage, both cinematic timelines, and all boss attack/render paths.
