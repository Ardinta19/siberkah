import { navigationSheet } from './navigation-sheet.js';
const header = document.querySelector('#main-header');
const pill = document.querySelector('#bk-expand-pill');
if (pill && header) {
  const full = header.querySelector('.bk-full-nav');
  const palette = document.querySelector('#bk-palette');
  const launcher = header.querySelector('[data-open-palette]');
  let beyond = false, pinned = false;
  const compact = requested => {
    const yes = requested && !pinned && !header.contains(document.activeElement) && !document.querySelector('dialog[open]') && !header.querySelector('#mobile-menu.is-open,[aria-expanded="true"]:not([data-sidebar-toggle])');
    header.classList.toggle('bk-compact',yes); full.inert = yes; pill.hidden = !yes;
  };
  // One boundary observation, no per-frame scroll reads or layout writes.
  const marker=document.querySelector('#bk-scroll-marker'), scrollRoot=marker.closest('main');
  if ('IntersectionObserver' in window) new IntersectionObserver(([entry])=>{
    beyond = !entry.isIntersecting && entry.boundingClientRect.top < (entry.rootBounds?.top ?? 0);
    if (!beyond) pinned = false;
    compact(beyond);
  },{root:scrollRoot}).observe(marker);
  pill.onclick = () => { pinned=true;compact(false);[...full.querySelectorAll('a[href],button:not(:disabled)')].find(el=>el.getClientRects().length)?.focus({preventScroll:true}); };
  document.addEventListener('pointerdown', e=>{if(!header.contains(e.target) && !document.querySelector('dialog[open]') && pinned){pinned=false;compact(beyond);}});
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
