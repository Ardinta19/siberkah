// Native :active provides immediate pressure. Accent the completed action, not
// pointerdown (which may turn into a scroll, drag, or cancelled touch).
const controls = '#main-header :is(button,a), .koleksi :is(button,a,summary), [data-product-detail] :is(button,a), .kc-dock a';
const reduced = matchMedia('(prefers-reduced-motion: reduce)');
let accent;

document.addEventListener('click', event => {
  const control = event.target.closest?.(controls);
  if (!control || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
  accent?.cancel();
  // Favorite state is updated by the button's own handler before this bubbles.
  const saved = control.matches('[data-favorite][aria-pressed="true"]');
  const ink = saved ? '171,56,95' : '147,102,71';
  const ring = (width, alpha) => `0 0 0 ${width}px rgba(${ink},${alpha})`;
  if (typeof control.animate === 'function') {
    accent = control.animate(reduced.matches
      ? [{boxShadow:ring(2,.5)}, {boxShadow:ring(2,0)}]
      : [{boxShadow:ring(0,.45)}, {boxShadow:ring(saved ? 7 : 4,.18),offset:.45}, {boxShadow:ring(saved ? 10 : 6,0)}],
    {duration:reduced.matches ? 220 : saved ? 490 : 300, easing:'cubic-bezier(.2,.7,.3,1)'});
  }
});
