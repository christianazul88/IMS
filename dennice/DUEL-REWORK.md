# The Last Hunt

Play now enters The Adapted One directly. Defeating it goes to the rescue
ending. No travel act is required. All weapons are available at the start.

Controls: click for the three-hit sword chain, Space to evade/cancel,
R or right click (SLAM on touch) for Earthsplitter, E for the unchanged
ten-second Dismantle, Q or 1–4 for weapon selection.

`duel-rework.js` owns startup, hero drawing and the new combat timings.
`cfg.strikeAt` controls the moment of impact within the sword animation.
`cfg.slamCooldown/range/damage` control Earthsplitter. The sword remains a
separate continuously animated layer, so it stays visible through every pose.
The photo-inspired hero atlas has 16 full-body poses (4 views × 4 actions);
mirroring supplies the left views, with procedural recoil, evade ghosts,
breathing, strike rotation and an independently aimed sword.

`shockwave-live.js` draws live pressure contours, sparks and dust without
enlarging a PNG. Its gap and radius use `PRINCESS_HUNT.tuning` in
`adapted-hunt.js`, shared with damage logic. Two pulses retain the supplied
sound and delayed aftershock. Teleport punch locks its warning direction,
then allows 0.16 seconds after arrival before striking. Foreground objects
fade when they cover the hero, while retaining depth order and water rules.

Run `node test-duel.js` for the current duel regression suite. The old
`smoke.js` suite describes retired campaign behavior and is historical.
Run `node serve-local.js` and open http://127.0.0.1:8080/index.html?dev=1
for the existing browser lab and move/arsenal tests.

## Art generation

Built-in image generation was used for `assets/hero-vanguard-atlas.png`.
Prompt: transparent regular 4×4 sprite atlas, using the supplied face photo
as likeness reference (tousled black hair, straight eyebrows, warm skin,
clean-shaven oval face). Original dark fantasy greatsword warrior in blackened
silver armor, ivory shirt, burgundy split coat and armored boots. Painterly
isometric action RPG art; adult proportions, full bodies inside each cell.
Columns: front, right profile, back, rear-right. Rows: ready, left stride,
right stride, two-hand attack follow-through. No weapon, text, borders or
floor, consistent character size and foot anchors, transparent background.

Combat reference: Blizzard's Diablo III Barbarian Furious Charge and
Seismic Slam guides inspired a movement/commitment/recovery rhythm and the
directional Earthsplitter. No Diablo artwork was used.

### Invisible Dismantle storm

`dismantle-storm.js` keeps the existing ten-second ultimate and damage rules.
Travelling cuts are invisible; confirmed hits create short animated cuts on
the victim. Knockback originates at the hero, with terrain-safe displacement.
The `tuning` object at the top controls impact lifetime, gentle push speed and
duration, wind-wisp count, and vignette opacity. The caster halo identifies
the source without drawing beams across the arena. Effects clear on restart.
