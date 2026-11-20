import "./role-navigation.js";
import "./rental-calendar.js";
import "./floral-feedback.js";
/* eslint-disable */
// Load the editorial timeline only on the catalog, and only when motion is welcome.
const motion = matchMedia('(prefers-reduced-motion: no-preference)');
if (document.querySelector('.kc-hero-reveal')) {
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
menuToggle?.addEventListener('click', () => {
  const menu = document.getElementById('mobile-menu');
  menu.hidden = !menu.hidden;
  menuToggle.setAttribute('aria-expanded', String(!menu.hidden));
});

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

// Progressive disclosure: without JS the catalog keeps all filters visible.
const filters = document.querySelector('#kc-extra');
if (filters) {
  const toggle = document.querySelector('.kc-filter-toggle');
  const mobile = matchMedia('(max-width:47.99rem)');
  let open = false;
  const sync = () => {
    toggle.hidden = !mobile.matches;
    filters.hidden = mobile.matches && !open;
    toggle.setAttribute('aria-expanded', String(!filters.hidden));
  };
  toggle.addEventListener('click', () => { open = !open; sync(); });
  mobile.addEventListener('change', sync);
  sync();
  const kind = filters.querySelector('[name=jenis]');
  const day = filters.querySelector('[name=tanggal]');
  const syncDate = () => { day.disabled = ['jual','custom'].includes(kind.value); };
  kind.addEventListener('change', syncDate);
  syncDate();
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
    submit.textContent = form.dataset.pending;
  });
  window.addEventListener('pageshow', () => { form.removeAttribute('aria-busy'); if(submit) submit.textContent = label; });
}
document.addEventListener('keydown', event => {
  if (event.key !== 'Escape') return;
  const menu = document.querySelector('#mobile-menu');
  if (menu && !menu.hidden) { menuToggle.click(); menuToggle.focus(); }
  else if (filters && !filters.hidden && matchMedia('(max-width:47.99rem)').matches && filters.parentElement.contains(document.activeElement)) {
    const toggle = document.querySelector('.kc-filter-toggle'); toggle.click(); toggle.focus();
  }
});
