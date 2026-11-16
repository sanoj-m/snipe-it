# Theme v2 rule

All styling/theming changes go to the **killa-v2 theme layer**:
`public/css/killa-v2*.css` (zero-build overlays loaded after `all.css`).

- Never edit upstream/compiled CSS (`public/css/dist/**`, `public/css/build/**`)
  or inline styles in Blade views to change appearance — override in the
  killa-v2 overlay files instead.
- Use the existing `--k-*` CSS custom properties (tokens); add new tokens only
  in `killa-v2.css` (light) / `killa-v2-dark.css` (dark counterparts).
- Keep overrides scoped with `body`/`html[data-theme='dark']` specificity as in
  the surrounding code, so they survive upstream merges (killa-v2 files are
  Killa-owned and never touched by upstream).
- Also read `skills/killa-design-manual/SKILL.md` before any UI work.
