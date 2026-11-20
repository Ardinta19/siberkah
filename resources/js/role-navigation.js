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

// Only use links actually rendered by Blade for this role (never POST actions).
const command = document.querySelector('#workspace-command');
if (command && sidebar && command.showModal) {
  const trigger=document.querySelector('[data-role-command]'), input=command.querySelector('input');
  const links=[...sidebar.querySelectorAll('nav a[href]')];
  const list=command.querySelector('nav'), empty=command.querySelector('[data-empty]');
  const dock=document.createElement('nav');dock.className='kc-dock bk-work-dock';dock.setAttribute('aria-label','Navigasi cepat ruang kerja');
  links.forEach((source,index)=>{
    const a=document.createElement('a');a.href=source.href;a.textContent=source.textContent.trim();
    if(a.pathname===location.pathname)a.setAttribute('aria-current','page');list.append(a);
    if(index<3){const tab=a.cloneNode(true), icon=source.querySelector('svg')?.cloneNode(true);if(icon){icon.setAttribute('aria-hidden','true');tab.prepend(icon);}dock.append(tab);}
  });
  // Keep tabs inside the inert workspace when the mobile sidebar opens.
  sidebar.nextElementSibling.append(dock);
  function filter(){let count=0;for(const a of list.children){a.hidden=!a.textContent.toLocaleLowerCase('id').includes(input.value.trim().toLocaleLowerCase('id'));if(!a.hidden)count++;}empty.hidden=!!count;}
  let before;
  function open(){if(document.querySelector('dialog[open],#role-sidebar[aria-modal=true]'))return;before=document.activeElement;input.value='';filter();command.showModal();input.focus();}
  command.addEventListener('keydown',e=>{if(e.key==='Escape'){e.preventDefault();command.close();}});
  trigger.hidden=false;trigger.onclick=open;input.oninput=filter;
  command.querySelector('[data-dismiss]').onclick=()=>command.close();command.onclose=()=>before?.focus();
  document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();command.open?command.close():open();}});
}
