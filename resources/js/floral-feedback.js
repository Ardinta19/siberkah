// Decorative overlay outside overflow-clipped cards. Never intercept navigation.
const controls = '#main-header :is(button,a), .koleksi :is(button,a,summary), [data-product-detail] :is(button,a,select), .bk-cart, .kc-dock a';
const reduced = matchMedia('(prefers-reduced-motion: reduce)');
let flower, timer;
function bloom(target, x, y) {
  const box = target.getBoundingClientRect();
  if (!box.width || !box.height) return;
  flower?.remove();
  clearTimeout(timer);
  flower = document.createElement('span');
  flower.className = 'bk-flower-glow';
  flower.setAttribute('aria-hidden', 'true');
  flower.style.left = `${x ?? box.left + box.width / 2}px`;
  flower.style.top = `${y ?? box.top + box.height / 2}px`;
  for (let i = 0; i < 6; i++) {
    const petal = document.createElement('i');
    petal.style.setProperty('--angle', `${i * 60}deg`);
    flower.append(petal);
  }
  document.body.append(flower);
  timer = setTimeout(() => flower?.remove(), 700);
}

document.addEventListener('pointerdown', e => {
  const control = e.target.closest?.(controls);
  if (control && e.isPrimary && e.button === 0) bloom(control, e.clientX, e.clientY);
}, {passive:true});
document.addEventListener('click', e => {
  const control = e.target.closest?.(controls);
  if (control && e.detail === 0) bloom(control); // keyboard or assistive activation
});
const title = document.querySelector('[data-product-detail] h1');
if (title && !reduced.matches) requestAnimationFrame(() => {
  const box = title.getBoundingClientRect();
  if (box.top >= 0 && box.top < innerHeight) bloom(title, box.left + 12, box.top + 5);
});
