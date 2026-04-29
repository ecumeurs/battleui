---
id: mech_hologram_shader
human_name: "Holographic Shader Effect"
type: MECHANIC
layer: IMPLEMENTATION
version: 1.1
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
To provide a highly configurable "Neon in the Dust" holographic aesthetic across all board entities (pawns, obstacles) with toggles for flickering, floating, radiation, and scanlines.

## THE RULE / LOGIC
- **Scrolling Scanlines:** A sine wave function applied to the Y-coordinate of the UV mapping. Configurable via density and speed.
- **Periodic Flicker:** A "glitch" window triggered every `uFlickerPeriod` seconds. Intensity oscillates between `uFlickerMin` and `uFlickerMax`.
- **Floating Bob:** A vertical offset applied in the vertex shader: `(sin(time * speed + offset) * 0.5 + 0.5) * height`.
- **Radiation Pulse:** A temporal glow multiplier applied to the Fresnel edge effect to simulate high-energy radiation.
- **Depth Handling:** `depthWrite` is set to `true` (updated) to ensure correct sorting in complex scenes, while remaining `transparent`.

## TECHNICAL INTERFACE (The Bridge)
- **Code Tag:** `@spec-link [[mech_hologram_shader]]`
- **Component:** `HologramMaterial.vue`
- **Props:**
  - `flicker` (bool), `flickerPeriod` (float), `flickerMin/Max` (float)
  - `floating` (bool), `floatSpeed` (float), `floatHeight` (float)
  - `radiation` (bool), `radiationIntensity` (float)
  - `scanlines` (bool), `scanlineDensity` (float), `scanlineSpeed` (float)
- **Key Uniforms:**
  - `uFlicker`, `uFlickerPeriod`, `uFlickerMin`, `uFlickerMax`
  - `uFloating`, `uFloatSpeed`, `uFloatHeight`
  - `uRadiation`, `uRadiationIntensity`
  - `uScanlines`, `uScanlineDensity`, `uScanlineSpeed`

## EXPECTATION (For Testing)
- The object appears semi-transparent with configurable scrolling horizontal lines.
- The object has a periodic "glitch" or flicker effect (if enabled), with configurable timing and intensity.
- The object can "float" or bob vertically with configurable height and speed.
- High-energy "radiation" pulses can be enabled for specific entities (e.g. player pawns).
- Colors appear vibrant and can be fine-tuned via glow intensity and radiation power.
