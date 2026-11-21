import { navigationSheet } from './navigation-sheet.js';
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
  let panelMotion, shadeMotion;
  const set = (open, animate=true) => {
    const modal = open && !large.matches, visible = open || large.matches;
    const previousClip = sidebar.hidden ? 'circle(0% at 1.5rem 1.5rem)' : getComputedStyle(sidebar).clipPath;
    const previousOpacity = shade.hidden ? 0 : Number(getComputedStyle(shade).opacity);
    panelMotion?.cancel(); shadeMotion?.cancel();
    sidebar.inert = !visible; content.inert = modal;
    toggle.setAttribute('aria-expanded', String(visible));
    if(modal) { sidebar.setAttribute('role','dialog');sidebar.setAttribute('aria-modal','true'); }
    else { sidebar.removeAttribute('role'); sidebar.removeAttribute('aria-modal'); }
    const moving = animate && !large.matches && sidebar.animate && !matchMedia('(prefers-reduced-motion: reduce)').matches;
    sidebar.hidden = !visible && !moving; shade.hidden = !modal && !moving;
    shade.style.pointerEvents = modal ? '' : 'none';
    if(moving){
      const expanded='circle(155% at 1.5rem 1.5rem)', collapsed='circle(0% at 1.5rem 1.5rem)';
      const timing={duration:modal?380:235,easing:'cubic-bezier(.22,.82,.25,1)'};
      const current=sidebar.animate([{clipPath:previousClip==='none'?expanded:previousClip},{clipPath:modal?expanded:collapsed}],timing);
      panelMotion=current;
      shadeMotion=shade.animate([{opacity:previousOpacity},{opacity:Number(modal)}],timing);
      current.onfinish=()=>{if(panelMotion===current){sidebar.hidden=!visible;shade.hidden=!modal;}};
    }
    if(modal)close.focus({preventScroll:true});
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
  large.addEventListener('change',()=>set(false,false)); set(false,false);
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
  input.oninput=filter;
  command.addEventListener('keydown',e=>{
    if(e.isComposing || e.ctrlKey || e.metaKey || e.altKey)return;
    const choices=[...list.children].filter(a=>!a.hidden);
    if(!choices.length)return;
    const index=choices.indexOf(document.activeElement);
    if(e.target!==input && index<0)return;
    if(e.key==='Enter' && e.target===input){e.preventDefault();choices[0].click();return;}
    if(!['ArrowDown','ArrowUp'].includes(e.key))return;
    e.preventDefault();
    const next=index<0 ? (e.key==='ArrowDown'?0:choices.length-1) : (index+(e.key==='ArrowDown'?1:-1)+choices.length)%choices.length;
    choices[next].focus();
  });
  navigationSheet(command,trigger,()=>{input.value='';filter();input.focus();});
}
