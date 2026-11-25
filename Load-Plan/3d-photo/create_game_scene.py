from pathlib import Path
import numpy as np
import trimesh as t
from PIL import Image
S=t.Scene(); rng=np.random.default_rng(42)
colors={'skin':[186,133,99,255],'shirt':[225,232,231,255],'hair':[13,20,27,255],'shorts':[20,27,33,255],'bag':[20,35,48,255]}
def add(m,n,c):
 m.visual.vertex_colors=colors.get(c,c) if isinstance(c,str) else c
 S.add_geometry(m,node_name=n,geom_name=n); return m
def ell(n,p,s,c):
 m=t.creation.icosphere(subdivisions=3); m.vertices*=s; m.apply_translation(p); return add(m,n,c)
def box(n,p,s,c):
 m=t.creation.box(s); m.apply_translation(p); return add(m,n,c)
def limb(n,a,b,r,c):
 a=np.array(a); b=np.array(b); m=t.creation.capsule(radius=r,height=np.linalg.norm(b-a),count=[12,16]); m.apply_transform(t.geometry.align_vectors([0,0,1],b-a)); m.apply_translation((a+b)/2); return add(m,n,c)
# Y up, subject faces +Z. Complete closed meshes, approximate seated anatomy.
ell('hips',(0,.43,0),(.40,.25,.29),'shorts')
ell('shirt_torso',(0,.92,-.04),(.39,.53,.25),'shirt')
limb('neck',(0,1.28,.03),(0,1.47,.15),.115,'skin')
ell('head',(0,1.57,.22),(.22,.27,.23),'skin')
ell('hair_cap',(0,1.70,.17),(.235,.20,.245),'hair')
for i in range(19):
 x=(i-9)*.023; limb('fringe_%02d'%i,(x,1.72,.32),(x,1.55+rng.uniform(-.025,.025),.40),.026,'hair')
ell('nose',(0,1.48,.435),(.045,.065,.055),'skin')
for x in [-.22,.22]: ell('ear_'+str(x),(x,1.56,.20),(.045,.07,.045),'skin')
for side in [-1,1]:
 shoulder=(side*.30,1.17,.02); elbow=(side*.43,.76,.26); wrist=(side*.19,.58,.67)
 limb('upper_sleeve'+str(side),shoulder,elbow,.15,'shirt')
 limb('forearm'+str(side),elbow,wrist,.095,'skin')
 limb('rolled_cuff'+str(side),elbow,(side*.39,.71,.32),.115,'shirt')
 ell('hand'+str(side),(side*.14,.57,.71),(.10,.055,.13),'skin')
 knee=(side*.52,.28,.40)
 limb('short_leg'+str(side),(side*.23,.40,.08),(side*.39,.32,.27),.19,'shorts')
 limb('thigh'+str(side),(side*.34,.32,.25),knee,.16,'skin')
 limb('crossed_shin'+str(side),knee,(-side*.17,.13,.66),.11,'skin')
 ell('foot'+str(side),(-side*.24,.10,.65),(.18,.075,.10),'skin')
 for j in range(4): ell('finger_%s_%s'%(side,j),(side*(.075+j*.025),.55,.79),(.012,.018,.055),'skin')
box('phone',(0,.60,.77),(.19,.022,.12),[45,40,55,255])
box('screen',(0,.613,.77),(.16,.003,.095),[80,100,116,255])
for y in [.71,.88,1.05]: ell('shirt_button'+str(y),(0,y,.213),(.012,.012,.008),[116,111,95,255])
for x in [-.17,.17]: box('shirt_pocket'+str(x),(x,1.02,.20),(.15,.14,.025),'shirt')
ell('travel_bag',(.91,.36,.04),(.38,.36,.34),'bag')
box('bag_pocket',(.91,.34,.355),(.50,.28,.055),'bag')
for x in [.74,1.06]:
 limb('bag_handle_a'+str(x),(x,.55,.14),(x,.83,.10),.025,'bag')
limb('bag_handle_top',(.74,.83,.1),(1.06,.83,.1),.025,'bag')
# Draped patterned cloth on bag, real surface with back faces.
v=[]; f=[]; uv=[]
for j in range(25):
 for i in range(25):
  u=i/24; w=j/24; v.append([.62+u*.57,.69-.44*w+.035*np.sin(u*24),.04+w*.40]); uv.append([u,w])
for j in range(24):
 for i in range(24):
  a=j*25+i;f.extend([[a,a+25,a+1],[a+1,a+25,a+26]])
m=t.Trimesh(v,f,process=False); m.visual.vertex_colors=np.array([[235,235,231,255] if rng.random()>.25 else [30,55,85,255] for _ in v]); S.add_geometry(m,node_name='patterned_cloth')
# White picnic mat with a gently rippled surface.
box('picnic_mat',(.05,.018,.25),(2.5,.018,2.0),[219,227,225,255])
# Ground texture with alpha feathering at the perimeter, exported as BLEND.
size=512; yy,xx=np.mgrid[:size,:size]; r=np.maximum(abs(xx-size/2),abs(yy-size/2))/(size/2)
a=np.clip((1-r)/.23,0,1); a=a*a*(3-2*a)
noise=rng.normal(0,7,(size,size)); tex=np.zeros((size,size,4),dtype=np.uint8)
for k,val in enumerate([166,158,130]):tex[:,:,k]=np.clip(val+noise,0,255)
tex[:,:,3]=(a*255).astype(np.uint8); Image.fromarray(tex).save('3d-photo/output/ground_fade.png')
g=t.Trimesh([[-2.6,0,-2.6],[2.6,0,-2.6],[2.6,0,2.6],[-2.6,0,2.6]],[[0,2,1],[0,3,2]],process=False)
g.visual=t.visual.TextureVisuals(uv=[[0,0],[1,0],[1,1],[0,1]],material=t.visual.material.PBRMaterial(baseColorTexture=Image.fromarray(tex),alphaMode='BLEND',doubleSided=True,roughnessFactor=1))
S.add_geometry(g,node_name='ground_soft_fade')
for x in np.linspace(-1.8,1.8,35):box('corrugated_fence'+str(x),(x,.40,-1.65),(.105,.80,.07),[100+int(rng.integers(0,25)),39,31,255])
for x in [-1.8,0,1.8]:limb('fence_post'+str(x),(x,0,-1.62),(x,.94,-1.62),.045,[104,94,76,255])
for i in range(28):
 p=[rng.uniform(-2.1,-1.4),rng.uniform(.4,1.15),rng.uniform(-1.8,-1.2)]
 ell('foliage'+str(i),p,(.09,.025,.055),[65,int(rng.integers(85,125)),45,255])
S.export('3d-photo/output/game_scene.glb')
print('Exported',len(S.geometry),'objects;',sum(len(m.faces) for m in S.geometry.values()),'triangles')
