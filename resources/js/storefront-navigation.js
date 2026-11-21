import { navigationSheet } from './navigation-sheet.js';
const header = document.querySelector('#main-header');
const pill = document.querySelector('#bk-expand-pill');
if (pill && header) {
  const full = header.querySelector('.bk-full-nav');
  const palette = document.querySelector('#bk-palette');
  const launcher = header.querySelector('[data-open-palette]');
  let beyond = false, pinned = false;
  const overlayOpen = () => document.querySelector('dialog[open],#role-sidebar[aria-modal="true"]') ||
    header.querySelector('#mobile-menu.is-open,[aria-expanded="true"]:not(#bk-expand-pill):not([data-sidebar-toggle])');
  const firstControl = () => [...full.querySelectorAll('a[href],button:not(:disabled)')].find(el=>el.getClientRects().length);
  function compact({returnFocus=false}={}) {
    const focused=document.activeElement;
    const yes=beyond && !pinned && !overlayOpen() && (returnFocus || !full.contains(focused));
    if(yes === header.classList.contains('bk-compact'))return;
    header.classList.toggle('bk-compact',yes);
    full.inert=yes;pill.hidden=!yes;pill.setAttribute('aria-expanded',String(!yes));
    if(returnFocus && yes)pill.focus({preventScroll:true});
    else if(!yes && focused===pill)firstControl()?.focus({preventScroll:true});
  }
  // Observe the document or the role's scrolling main. Never hide a focused navigation link.
  const marker=document.querySelector('#bk-scroll-marker'), scrollRoot=marker.closest('main');
  if ('IntersectionObserver' in window) new IntersectionObserver(([entry])=>{
    beyond = !entry.isIntersecting && entry.boundingClientRect.top < (entry.rootBounds?.top ?? 0);
    if (!beyond) pinned = false;
    compact();
  },{root:scrollRoot}).observe(marker);
  pill.onclick = () => { pinned=true;compact();firstControl()?.focus({preventScroll:true}); };
  // Wait for the browser to move focus before rechecking (pointerdown runs too early).
  function settle(){requestAnimationFrame(()=>{
    if(full.contains(document.activeElement) || overlayOpen())return;
    pinned=false;compact();
  });}
  header.addEventListener('focusout',settle);
  document.addEventListener('pointerdown',e=>{if(!header.contains(e.target))settle();});
  document.addEventListener('close',settle,true);
  header.addEventListener('keydown',e=>{
    if(e.key!=='Escape' || !pinned || !beyond || overlayOpen() || e.defaultPrevented)return;
    e.preventDefault();pinned=false;compact({returnFocus:true});
  });
  if (palette?.showModal) {
    const input=palette.querySelector('#palette-query');
    const form = input.form, kind = form.elements.jenis, date = form.elements.tanggal;
    const syncDate = () => { date.disabled = ['jual','custom'].includes(kind.value); };
    kind.addEventListener('change',syncDate);syncDate();
    const clearQuery=palette.querySelector('[data-clear-query]'), resetFilters=palette.querySelector('[data-reset-filters]');
    const facets=[form.elements.kategori,kind,date,form.elements.urut];
    function syncActions(){clearQuery.disabled=!input.value;resetFilters.disabled=!facets.some(el=>!el.disabled && el.value);}
    form.addEventListener('input',syncActions);form.addEventListener('change',syncActions);syncActions();
    clearQuery.onclick=()=>{input.value='';input.dispatchEvent(new Event('input',{bubbles:true}));input.focus();};
    resetFilters.onclick=()=>{
      for(const el of facets) el.value='';
      syncDate(); date.dispatchEvent(new Event('change',{bubbles:true}));input.focus();
    };
    let selectionModule;
    function enhanceSelection() {
      selectionModule ??= import('./search-selection.jsx').then(({mount})=>mount(form.querySelector('[data-search-selection]')))
        .catch(()=>{selectionModule=null;}); // The native search remains usable offline.
    }
    navigationSheet(palette,launcher,()=>{
      form.reset();syncDate();date.dispatchEvent(new Event('change',{bubbles:true}));
      input.focus();enhanceSelection();
    });
  }
}
