"""Turn one photograph into a textured depth-relief GLB.

Depth inference follows the preprocessing and ONNX Runtime approach in
https://github.com/fabio-sim/Depth-Anything-ONNX (Apache-2.0), using its
Depth Anything V2 ViT-S release model. Distances are relative, not metric.
"""

from __future__ import annotations

import argparse
from pathlib import Path

import numpy as np
import onnxruntime as ort
from PIL import Image, ImageFilter
import trimesh


def estimate_depth(photo: Image.Image, model_path: Path) -> np.ndarray:
    image = np.asarray(photo.resize((518, 518), Image.Resampling.BICUBIC), dtype=np.float32) / 255.0
    image = (image - np.array([0.485, 0.456, 0.406], np.float32)) / np.array(
        [0.229, 0.224, 0.225], np.float32
    )
    image = np.transpose(image, (2, 0, 1))[None]

    options = ort.SessionOptions()
    options.intra_op_num_threads = 4
    session = ort.InferenceSession(str(model_path), sess_options=options, providers=["CPUExecutionProvider"])
    depth = session.run(None, {session.get_inputs()[0].name: image})[0].squeeze()
    if depth.ndim != 2 or not np.isfinite(depth).all():
        raise ValueError(f"Unexpected depth model output: {depth.shape}")
    print(f"Depth output: {depth.shape}, min={depth.min():.3f}, max={depth.max():.3f}")
    return np.asarray(
        Image.fromarray(depth.astype(np.float32), "F").resize(photo.size, Image.Resampling.BICUBIC),
        dtype=np.float32,
    )


def make_mesh(photo: Image.Image, depth: np.ndarray, columns: int, relief: float) -> trimesh.Scene:
    width, height = photo.size
    rows = round(columns * height / width)
    mesh_size = (columns, rows)

    lo, hi = np.percentile(depth, [2, 98])
    normalized = np.clip((depth - lo) / (hi - lo), 0, 1)
    # Gently smooth stair steps while retaining the estimated shape.
    depth_image = Image.fromarray(np.uint8(normalized * 255), "L")
    depth_image = depth_image.resize(mesh_size, Image.Resampling.BICUBIC).filter(ImageFilter.GaussianBlur(2.0))
    z_values = np.asarray(depth_image, dtype=np.float32) / 255.0 * relief

    image_width = 2.25
    image_height = image_width * height / width
    x = np.linspace(-image_width / 2, image_width / 2, columns, dtype=np.float32)
    y = np.linspace(image_height / 2, -image_height / 2, rows, dtype=np.float32)
    xx, yy = np.meshgrid(x, y)
    vertices = np.stack((xx, yy, z_values), axis=-1).reshape((-1, 3))
    u, v = np.meshgrid(np.linspace(0, 1, columns), np.linspace(1, 0, rows))
    uv = np.stack((u, v), axis=-1).reshape((-1, 2))

    faces = []
    for row in range(rows - 1):
        for col in range(columns - 1):
            a = row * columns + col
            b = a + 1
            c = a + columns
            d = c + 1
            faces.extend(((a, c, b), (b, c, d)))
    front = trimesh.Trimesh(vertices=vertices, faces=np.asarray(faces), process=False)
    front.visual = trimesh.visual.TextureVisuals(
        uv=uv,
        material=trimesh.visual.material.PBRMaterial(
            baseColorTexture=photo,
            metallicFactor=0.0,
            roughnessFactor=1.0,
            doubleSided=True,
        ),
    )

    # A thin backing gives the relief a visible edge when rotated.
    back_z = -0.045
    corners = np.array(
        [
            [-image_width / 2, image_height / 2, back_z],
            [image_width / 2, image_height / 2, back_z],
            [image_width / 2, -image_height / 2, back_z],
            [-image_width / 2, -image_height / 2, back_z],
        ]
    )
    back = trimesh.Trimesh(vertices=corners, faces=[[0, 1, 2], [0, 2, 3]], process=False)
    back.visual.vertex_colors = [28, 30, 34, 255]

    rim = []
    rim_faces = []
    edge_indices = (
        list(range(columns))
        + [r * columns + columns - 1 for r in range(1, rows)]
        + list(range((rows - 1) * columns + columns - 2, (rows - 1) * columns - 1, -1))
        + [r * columns for r in range(rows - 2, 0, -1)]
    )
    for idx in edge_indices:
        rim.append(vertices[idx])
        rim.append([vertices[idx, 0], vertices[idx, 1], back_z])
    for i in range(len(edge_indices)):
        a = i * 2
        b = ((i + 1) % len(edge_indices)) * 2
        rim_faces.extend(((a, b, a + 1), (b, b + 1, a + 1)))
    sides = trimesh.Trimesh(vertices=np.asarray(rim), faces=rim_faces, process=False)
    sides.visual.vertex_colors = [48, 50, 53, 255]

    scene = trimesh.Scene()
    scene.add_geometry(front, node_name="photo_relief")
    scene.add_geometry(back, node_name="back")
    scene.add_geometry(sides, node_name="sides")
    print(f"Mesh: {len(vertices):,} front vertices, {len(faces):,} front triangles")
    return scene


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("photo", type=Path)
    parser.add_argument("model", type=Path)
    parser.add_argument("output", type=Path)
    parser.add_argument("--columns", type=int, default=256)
    parser.add_argument("--relief", type=float, default=0.50)
    args = parser.parse_args()

    args.output.mkdir(parents=True, exist_ok=True)
    photo = Image.open(args.photo).convert("RGB")
    depth = estimate_depth(photo, args.model)
    photo.save(args.output / "photo.jpg", quality=92)

    lo, hi = np.percentile(depth, [2, 98])
    preview = np.uint8(np.clip((depth - lo) / (hi - lo), 0, 1) * 255)
    Image.fromarray(preview, "L").save(args.output / "depth.png")
    scene = make_mesh(photo, depth, args.columns, args.relief)
    scene.export(args.output / "photo_3d.glb")
    print(f"Saved to {args.output}")


if __name__ == "__main__":
    main()
