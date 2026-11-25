# Photo in Depth

A rotatable 3D depth relief made from the supplied photograph. Open [the viewer](index.html) at `http://localhost/IMS/Load-Plan/3d-photo/` while XAMPP is running, or import `output/photo_3d.glb` into a GLB-compatible 3D application.

## Files

- `output/photo_3d.glb` — textured 3D relief, with a thin backing and rim.
- `output/depth.png` — estimated relative depth.
- `output/photo.jpg` — source photo used by the viewer's original view.
- `create_scene.py` — script to regenerate the model.

## GitHub project used

The script follows the ONNX inference preprocessing from [fabio-sim/Depth-Anything-ONNX](https://github.com/fabio-sim/Depth-Anything-ONNX) and uses that project's [Depth Anything V2 ViT-S ONNX model](https://github.com/fabio-sim/Depth-Anything-ONNX/releases/tag/v2.0.0). The depth network comes from [Depth Anything V2](https://github.com/DepthAnything/Depth-Anything-V2). The viewer bundles [Three.js](https://github.com/mrdoob/three.js) release r181.

## Regenerate

Install Python 3.12 and the packages in `requirements.txt`. Download `depth_anything_v2_vits.onnx` from the ONNX project's v2.0.0 release, then run:

```powershell
python create_scene.py "path\to\photo.png" "path\to\depth_anything_v2_vits.onnx" output
```

The model predicts relative depth from one image. It cannot recover unseen sides or real-world dimensions, so the viewer limits rotation to a front-facing range.
