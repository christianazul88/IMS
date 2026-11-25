from pathlib import Path
import trimesh as t
p=Path('3d-photo/index.html');s=p.read_text(encoding='utf8')
s=s.replace('output/photo_3d.glb','output/game_scene.glb').replace('photo_3d.glb','game_scene.glb').replace('A moment in depth','Recreated game scene').replace('Single image · 3D relief','Complete 3D geometry').replace('The model uses estimated depth from the original photograph.','A stylized reconstruction of the photographed scene.').replace('Depth is inferred from a single view. Hidden sides and exact distances cannot be recovered from this photo, so the model is best viewed near the front.','Full rotation is enabled. Hidden surfaces are artistically inferred. The ground uses alpha blending to fade its edges.').replace('controls.minAzimuthAngle = -0.28;','controls.minAzimuthAngle = -Infinity;').replace('controls.maxAzimuthAngle = 0.28;','controls.maxAzimuthAngle = Infinity;').replace('controls.minPolarAngle = Math.PI / 2 - 0.18;','controls.minPolarAngle = 0.1;').replace('controls.maxPolarAngle = Math.PI / 2 + 0.18;','controls.maxPolarAngle = Math.PI - 0.1;').replace('controls.target.set(0, 0, 0.20);','controls.target.set(0, 0.65, 0);').replace('camera.position.set(0, 0, Math.min(10.5, distance));','camera.position.set(3.4, 2.8, 4.5);')
Path('3d-photo/game.html').write_text(s,encoding='utf8')
a=t.load('3d-photo/output/game_scene.glb');print('Loaded',len(a.geometry),'meshes')
for m in a.geometry.values():
 assert m.vertices.shape[0]>0
 if hasattr(m.visual,'material') and getattr(m.visual.material,'alphaMode',None)=='BLEND':print('Verified alpha-blended ground material')
