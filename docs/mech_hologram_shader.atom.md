---
id: mech_hologram_shader
human_name: "Holographic Shader Effect"
type: MECHANIC
layer: IMPLEMENTATION
version: 1.0
status: STABLE
priority: 3
tags: [shader, glsl, holographic, effects]
parents:
  - [[ui_character_pawn]]
  - [[ui_holo_obstacle]]
dependents: []
---

# Holographic Shader Effect

## INTENT
To provide a consistent "Neon in the Dust" holographic aesthetic across all board entities (pawns, obstacles) using a shared GLSL shader.

## THE RULE / LOGIC
- **Scrolling Scanlines:** A sine wave function applied to the Y-coordinate of the UV mapping: `sin(uv.y * 80.0 + time * 2.0) * 0.04 + 0.96`.
- **Intermittent Flicker:** A high-frequency sine wave applied to opacity to simulate signal instability: `sin(time * 20.0) * 0.01 + 0.99`.
- **Additive Blending:** The material uses `THREE.AdditiveBlending` to ensure transparency and "glow" against dark backgrounds.
- **Depth Handling:** `depthWrite` is set to `false` to avoid transparent sorting artifacts.

## TECHNICAL INTERFACE (The Bridge)
- **Code Tag:** `@spec-link [[mech_hologram_shader]]`
- **Component:** `HologramMaterial.vue`
- **Uniforms:**
  - `uTime`: Elapsed time in seconds.
  - `uColor`: Base vec3 color.
  - `uOpacity`: Master float opacity.

## EXPECTATION (For Testing)
- The object appears semi-transparent with visible scrolling horizontal lines.
- The object has a subtle "glimmer" or flicker effect over time.
- Colors appear vibrant and additive (overlapping objects increase brightness).
