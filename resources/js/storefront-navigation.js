import { navigationSheet } from './navigation-sheet.js';
const header = document.querySelector('#main-header');
const pill = document.querySelector('#bk-expand-pill');
if (pill && header) {
  const full = header.querySelector('.bk-full-nav');
  const palette = document.querySelector('#bk-palette');
  const launcher = header.querySelector('[data-open-palette]');
  const allowTrace=matchMedia('(prefers-reduced-motion: no-preference)');
  let traceVersion=0, traceTween;
  function traceCompact(visible){
    const version=++traceVersion;
    traceTween?.revert();traceTween=null;
    if(!visible || !allowTrace.matches)return;
    import('./seal-motion.js').then(({traceSeal})=>{
      if(version===traceVersion && !pill.hidden && allowTrace.matches)traceTween=traceSeal(pill);
    }).catch(()=>{}); // The static seal and its button never depend on the plugin.
  }
  allowTrace.addEventListener('change',()=>traceCompact(!pill.hidden));
  let beyond = false;
  const overlayOpen = () => document.querySelector('dialog[open],#role-sidebar[aria-modal="true"]') ||
    header.querySelector('#mobile-menu.is-open,[aria-expanded="true"]:not(#bk-expand-pill):not([data-sidebar-toggle])');
  function compact() {
    const focused=document.activeElement;
    const yes=beyond && !overlayOpen() && !full.contains(focused);
    if(yes === header.classList.contains('bk-compact'))return;
    header.classList.toggle('bk-compact',yes);
    full.inert=yes;pill.hidden=!yes;pill.setAttribute('aria-expanded',String(!yes));
    traceCompact(yes);
    if(!yes && focused===pill){header.tabIndex=-1;header.focus({preventScroll:true});}
  }
  // Observe the document or the role's scrolling main. Never hide a focused navigation link.
  const marker=document.querySelector('#bk-scroll-marker'), scrollRoot=marker.closest('main');
  if ('IntersectionObserver' in window) new IntersectionObserver(([entry])=>{
    beyond = !entry.isIntersecting && entry.boundingClientRect.top < (entry.rootBounds?.top ?? 0);
    compact();
  },{root:scrollRoot}).observe(marker);
  // Scroll the same surface the sentinel observes. No navigation, hash change, or reload.
  pill.onclick = () => (scrollRoot ?? window).scrollTo({top:0,behavior:allowTrace.matches?'smooth':'instant'});
  // Wait for the browser to move focus before rechecking (pointerdown runs too early).
  function settle(){requestAnimationFrame(()=>{
    if(full.contains(document.activeElement) || overlayOpen())return;
    compact();
  });}
  header.addEventListener('focusout',settle);
  document.addEventListener('pointerdown',e=>{if(!header.contains(e.target))settle();});
  document.addEventListener('close',settle,true);
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
    },document.querySelectorAll('[data-edit-collection]'));
  }
}
