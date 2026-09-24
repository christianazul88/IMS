/* Shadow Summoner — prepared character package (not active in the duel yet).
 * Each atlas is a 4x4 grid. Keeping body and familiar effects separate lets
 * gameplay tune summons, hitboxes and timing without regenerating her model.
 */
window.SHADOW_SUMMONER = Object.freeze({
  id: 'shadow-summoner',
  displayName: 'The Umbral Conductor',
  bodyHeight: 66,
  atlases: Object.freeze({
    movement: 'assets/shadow-summoner-movement-atlas.png',
    attacks: 'assets/shadow-summoner-attack-atlas.png',
    familiars: 'assets/shadow-summoner-familiars-atlas.png'
  }),
  grid: Object.freeze({ columns: 4, rows: 4 }),
  // Movement rows; columns are front, right, back and rear-right views.
  movementRows: Object.freeze({ idle: 0, walkA: 1, walkB: 2, shadowStep: 3 }),
  movementColumns: Object.freeze({ front: 0, right: 1, back: 2, rearRight: 3 }),
  // Attack rows; columns are anticipation, cast, impact and recovery.
  attacks: Object.freeze({
    caneMark: Object.freeze({ row: 0, frameMs: 80, hitFrame: 2 }),
    groundCommand: Object.freeze({ row: 1, frameMs: 105, summon: 'beast', summonFrame: 1 }),
    aerialCommand: Object.freeze({ row: 2, frameMs: 115, summon: 'wing', summonFrame: 1 }),
    shadowFinisher: Object.freeze({ row: 3, frameMs: 145, summon: 'hand', summonFrame: 1 })
  }),
  familiarRows: Object.freeze({ beast: 0, wing: 1, tendrils: 2, hand: 3 }),
  // Recommended direction handling for the future playable implementation.
  direction: Object.freeze({ mirrorLeftFromRight: true, rearLeftFromRearRight: true })
});
