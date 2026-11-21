import "./option-editor.js";
import "./product-editor.js";
import "./media-preview.js";
import "./media-order.js";
import "./ledger-submit.js";
import "./cash-count.js";
import "./profile-editor.js";
import "./order-refresh.js";
import "./payment-window.js";
import "./checkout-estimate.js";
import "./product-gallery.js";
import "./storefront-navigation.js";
import "./role-navigation.js";
import "./rental-calendar.js";
import "./floral-feedback.js";
/* eslint-disable */
// Load the editorial timeline only on the catalog, and only when motion is welcome.
const motion = matchMedia('(prefers-reduced-motion: no-preference)');
if (document.querySelector('.kc-hero-reveal, .bk-checkout-page #checkout')) {
  const loadMotion = () => { if (motion.matches) import('./catalog-motion.js'); };
  motion.addEventListener('change', loadMotion);
  loadMotion();
}

// Utility functions for the project
window.utils = {
  formatRupiah: (nominal) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(nominal);
  },

  formatDate: (tanggal) => {
    return new Date(tanggal).toLocaleDateString('id-ID', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  },

  formatDateTime: (tanggal) => {
    return new Date(tanggal).toLocaleDateString('id-ID', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
};
const menuToggle = document.getElementById('guest-menu-toggle');
if (menuToggle) {
  const menu = document.getElementById('mobile-menu');
  const desktop = matchMedia('(min-width:48rem)');
  const set = (open, restore = false) => {
    if (restore && !desktop.matches) menuToggle.focus();
    menu.inert = !open;
    menu.classList.toggle('is-open', open);
    menuToggle.setAttribute('aria-expanded', String(open));
    menuToggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
  };
  menuToggle.addEventListener('click', e => {
    const open = menu.inert;
    set(open);
    if (open && e.detail === 0) menu.querySelector('a')?.focus();
  });
  const outside = e => !menu.contains(e.target) && !menuToggle.contains(e.target);
  document.addEventListener('pointerdown', e => { if (!menu.inert && outside(e)) set(false); });
  document.addEventListener('focusin', e => { if (!menu.inert && outside(e)) set(false); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !menu.inert) set(false,true); });
  desktop.addEventListener('change', () => set(false));
  window.addEventListener('pageshow', () => set(false));
}

for (const button of document.querySelectorAll('[data-favorite]')) {
  const key = `siberkah:favorite:${button.dataset.favorite}`;
  const render = saved => {
    button.setAttribute('aria-pressed', String(saved));
    button.textContent = saved ? '♥' : '♡';
  };
  try { render(localStorage.getItem(key) === '1'); } catch { /* Storage may be disabled. */ }
  button.addEventListener('click', () => {
    const saved = button.getAttribute('aria-pressed') !== 'true';
    render(saved);
    try { saved ? localStorage.setItem(key, '1') : localStorage.removeItem(key); } catch { /* Session only. */ }
  });
}

// Keep the native number field usable with JS disabled, keyboard, or touch.
for (const stepper of document.querySelectorAll('[data-stepper]')) {
  const field = stepper.querySelector('input');
  const buttons = [...stepper.querySelectorAll('[data-step]')];
  const sync = () => buttons.forEach(button => {
    button.hidden = false;
    button.disabled = Number(button.dataset.step) < 0 ? field.valueAsNumber <= +field.min : field.valueAsNumber >= +field.max;
  });
  for (const button of buttons) button.addEventListener('click', () => {
    if (!Number.isFinite(field.valueAsNumber)) field.value = field.min;
    else if (+button.dataset.step > 0) field.stepUp(); else field.stepDown();
    field.dispatchEvent(new Event('input', { bubbles:true }));
  });
  field.addEventListener('input', sync);
  sync();
}

// Submit is emitted only after native validation. Do not signal success on click.
for (const form of document.querySelectorAll('form[data-pending]')) {
  let submit, label;
  form.addEventListener('submit', event => {
    if (form.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
    submit = event.submitter || form.querySelector('button:not([type=button])');
    if (!submit || event.defaultPrevented) return;
    label = submit.textContent;
    form.setAttribute('aria-busy','true');
    submit.textContent = submit.dataset.pendingLabel || form.dataset.pending;
  });
  window.addEventListener('pageshow', () => { form.removeAttribute('aria-busy'); if(submit) submit.textContent = label; });
}

// Progressive enhancement: jump forms still work as native <details> without JS.
const jumps = [...document.querySelectorAll('.bk-jump')];
for (const jump of jumps) {
  jump.addEventListener('toggle',()=>{
    if (!jump.open) return;
    for (const other of jumps) if (other!==jump) other.open=false;
    const field=jump.querySelector('input[type=number]');field.focus({preventScroll:true});field.select();
  });
  jump.addEventListener('keydown',e=>{
    if(e.key==='Escape' && jump.open){e.preventDefault();jump.open=false;jump.querySelector('summary').focus();}
  });
}
if(jumps.length) document.addEventListener('pointerdown',e=>{
  for(const jump of jumps) if(jump.open && !jump.contains(e.target))jump.open=false;
});

// Keep the saved count distinct from the draft; checkout submits the visible field even without JS.
for(const form of document.querySelectorAll('[data-bag-form]')) {
  const field=form.elements.jumlah, save=form.querySelector('[data-save-quantity]'), note=form.querySelector('[data-quantity-state]');
  function sync(){
    const dirty=field.valueAsNumber!==Number(field.defaultValue);
    save.disabled=!dirty;
    const text=!field.validity.valid ? 'Masukkan jumlah bulat antara 1 dan 100.' : dirty ? 'Jumlah berubah. Perbarui untuk menyimpan.' : '';
    if(note.textContent!==text)note.textContent=text;
  }
  field.addEventListener('input',sync);window.addEventListener('pageshow',sync);sync();
}
