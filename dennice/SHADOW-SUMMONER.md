# The Umbral Conductor — prepared character

This package is selected from the opening character roster. The Vanguard remains
the default combat path; choosing the Umbral Conductor activates her own
movement, command attacks, and ultimate without replacing the Vanguard assets.

The character-selection screen now sets `window.selectedHero`. Selecting the
Umbral Conductor activates `shadow-playable.js`: click commands the next shadow
familiar, right-click or `R` calls tendrils, and `E` triggers Eclipse Menagerie.
The Vanguard keeps its existing sword, Earthsplitter and Dismantle controls.

## Assets

- `assets/shadow-summoner-movement-atlas.png`: idle, two walk poses, shadow-step;
  four authored viewing angles.
- `assets/shadow-summoner-attack-atlas.png`: cane mark, ground command, aerial
  command, and finisher; four chronological frames per action.
- `assets/shadow-summoner-familiars-atlas.png`: independent beast, wing, tendril,
  and spectral-hand sequences.
- `shadow-summoner.js`: names, row indexes, timings, summon cues, and mirroring
  metadata. Change this file when tuning animation timing later.
- `shadow-playable.js`: playable command controller and Eclipse Menagerie
  sequence. Change `cfg`, the `events` array in `startMenagerie()`, or
  `hitBoss()` when balancing this character.

All three images use transparent backgrounds and 4×4 layouts. The character is
an original remote shadow commander. Her body and summoned shadows are separate
so a summon can move, collide, linger, or disappear independently.

## Final generation prompts

Built-in image generation was used. The supplied portrait was the facial-
likeness reference. The movement prompt requested the same 2.5D painterly RPG
quality as the active hero, four directions, full uncropped bodies, and an
original charcoal-and-violet conductor outfit. The attack prompt preserved that
model and generated four-stage cane, ground, aerial, and finisher commands. The
effects prompt generated original quadruped, winged, tendril, and spectral-hand
shadow sequences as a separate transparent atlas.
