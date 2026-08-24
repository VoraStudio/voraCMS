# Proposal: Global UI/UX Theme Refinement (Dark Warmth & Light Contrast)

## 1. Problem Statement
- **Mode Fosc**: Actualment és excessivament negre (`#08080a`), amb fons molt pla i una barra lateral sense prou diferenciació ni profunditat visual.
- **Mode Clar**: El fons és un to crema massa clar/descolorit (`#f5f2eb`), i el logotip de Vora a la barra lateral és blanc, fent-se invisible.

## 2. Proposed Solution
1. **Mode Fosc (Dark Warm)**:
   - Fons principal càlid/grafit (`#131217` / `#18151c`).
   - Sidebar definit amb fons més fosc (`#100e14`) i vora de separació nítida (`border-right: 1px solid rgba(255, 255, 255, 0.08)`).
   - Panells de contingut amb fons amb textura/elevació suau.
2. **Mode Clar (Light Clean)**:
   - Resolució del logotip: inversió del color del logo per ser perfectament visible en color fosc (`filter: brightness(0) opacity(0.85);`).
   - Fons net (`#f8fafc`), amb targetes blanques definides i ombres suaus.
   - Contrast millorat en textos i enllaços del menú lateral.

## 3. Impact
- Millora notable del confort visual i de l'elegància de la interfície tant en entorns de baixa lluminositat com en entorns d'oficina.
