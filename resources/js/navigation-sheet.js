// Keep native modal focus containment, Escape and inert background. Only the exit is delayed.
export function navigationSheet(dialog, trigger, prepare) {
  let origin, exit;
  const close = () => {
    if (!dialog.open || exit) return;
    if (!dialog.animate || matchMedia('(prefers-reduced-motion: reduce)').matches) return dialog.close();
    const y = matchMedia('(min-width:48rem)').matches ? '-12px' : '40px';
    exit = dialog.animate([{opacity:1,transform:'none'},{opacity:0,transform:`translateY(${y})`}], {duration:180,easing:'ease-in'});
    exit.onfinish = () => dialog.close();
  };
  const open = () => {
    if (document.querySelector('dialog[open],#role-sidebar[aria-modal=true]')) return;
    origin = document.activeElement;
    dialog.showModal(); prepare();
  };
  trigger.hidden=false;
  trigger.querySelector('kbd').textContent = /Mac|iPhone|iPad/.test(navigator.platform) ? '⌘ K' : 'Ctrl K';
  trigger.onclick=open;
  dialog.querySelector('[data-dismiss]').onclick=close;
  dialog.addEventListener('keydown',e=>{
    if(e.key==='Escape' && !e.target.closest('select')){e.preventDefault();e.stopPropagation();close();}
  });
  dialog.addEventListener('cancel',e=>{e.preventDefault();close();});
  dialog.addEventListener('close',()=>{exit?.cancel();exit=null;if(origin?.isConnected)origin.focus({preventScroll:true});});
  dialog.addEventListener('click',e=>{
    if(e.target!==dialog)return;
    const r=dialog.getBoundingClientRect();
    if(e.clientX<r.left || e.clientX>r.right || e.clientY<r.top || e.clientY>r.bottom)close();
  });
  document.addEventListener('keydown',e=>{
    if(!(e.ctrlKey||e.metaKey)||e.key.toLowerCase()!=='k'||e.altKey||e.isComposing)return;
    e.preventDefault();if(e.repeat || document.querySelector('dialog[open]:not(#'+dialog.id+')'))return;
    dialog.open?close():open();
  });
}
