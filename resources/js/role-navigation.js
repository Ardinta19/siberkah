import { disclosure } from './disclosure-motion.js';
const account = document.querySelector('[data-account]');
if (account) {
  const toggle = account.querySelector('[data-account-toggle]'), panel = account.querySelector('#account-links');
  const reveal = disclosure(panel);
  const set = open => {
    if ((toggle.getAttribute('aria-expanded') === 'true') === open) return;
    toggle.setAttribute('aria-expanded', String(open)); reveal(open);
  };
  toggle.onclick = () => set(toggle.getAttribute('aria-expanded') !== 'true');
  document.addEventListener('click', e => { if (!account.contains(e.target)) set(false); });
  account.addEventListener('focusout', e => { if (!account.contains(e.relatedTarget)) set(false); });
  account.addEventListener('keydown', e => { if (e.key === 'Escape') { set(false); toggle.focus(); } });
}
const sidebar = document.querySelector('#role-sidebar');
if (sidebar) {
  const large = matchMedia('(min-width:64rem)'), toggle = document.querySelector('[data-sidebar-toggle]');
  const shade = document.querySelector('[data-shade]'), close = sidebar.querySelector('[data-sidebar-close]');
  const content = sidebar.nextElementSibling;
  const set = open => {
    const modal = open && !large.matches;
    sidebar.hidden = !open && !large.matches; shade.hidden = !modal; content.inert = modal;
    toggle.setAttribute('aria-expanded', String(!sidebar.hidden));
    if(modal) { sidebar.setAttribute('role','dialog');sidebar.setAttribute('aria-modal','true');close.focus(); }
    else { sidebar.removeAttribute('role'); sidebar.removeAttribute('aria-modal'); }
  };
  const dismiss = () => { set(false); toggle.focus(); };
  toggle.onclick = () => set(true); close.onclick = shade.onclick = dismiss;
  sidebar.onkeydown = e => {
    if (large.matches) return;
    if(e.key === 'Escape') dismiss();
    if(e.key === 'Tab') {
      const nodes = [...sidebar.querySelectorAll('a[href],button:not(:disabled)')].filter(el=>el.getClientRects().length);
      const first=nodes[0], last=nodes.at(-1);
      if(e.shiftKey && document.activeElement===first) { e.preventDefault(); last.focus(); }
      else if(!e.shiftKey && document.activeElement===last) { e.preventDefault(); first.focus(); }
    }
  };
  large.addEventListener('change',()=>set(false)); set(false);
}
