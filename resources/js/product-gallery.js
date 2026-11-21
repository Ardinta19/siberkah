import { enableGallerySwipe } from './gallery-swipe.js';
// With no dialog support every photo remains a normal link to the full image.
for (const gallery of document.querySelectorAll('[data-gallery]')) {
  const photos=[...gallery.querySelectorAll('[data-photo]')], dialog=gallery.querySelector('dialog');
  if(!photos.length || !dialog?.showModal)continue;
  const large=dialog.querySelector('[data-large]');
  const strip=gallery.querySelector('[data-filmstrip]');
  const thumbs=[...gallery.querySelectorAll('[data-photo-index]')];
  // The visible rail owns the marker; it never intercepts taps or enters the tab order.
  const motion=matchMedia('(prefers-reduced-motion: no-preference)');
  let marker, flip, flight, marked;
  if(strip){
    marker=document.createElement('span');marker.className='bk-filmstrip-marker';
    marker.setAttribute('aria-hidden','true');strip.append(marker);
    const prepare=()=>{
      if(!motion.matches){flight?.progress(1);return;}
      import('./flip-runtime.js').then(module=>{flip=module.Flip;}).catch(()=>{});
    };
    motion.addEventListener('change',prepare);
    // Static placement works immediately; load motion only when a visitor uses the rail/gallery.
    for(const event of ['pointerdown','focusin'])gallery.addEventListener(event,prepare,{once:true});
  }
  function markThumb(){
    if(!strip)return;
    thumbs.forEach((button,i)=>{
      button.tabIndex=i===index?0:-1;
      if(i===index)button.setAttribute('aria-current','true');else button.removeAttribute('aria-current');
    });
    // Scroll only the thumbnail rail, never the product page beneath the modal.
    const item=thumbs[index].getBoundingClientRect(), rail=strip.getBoundingClientRect();
    const delta=item.left<rail.left ? item.left-rail.left-6 : item.right>rail.right ? item.right-rail.right+6 : 0;
    if(marked!==index){
      flight?.progress(1);
      const state=marked!==undefined && motion.matches && flip && !dialog.open && !delta ? flip.getState(marker) : null;
      marker.style.left=thumbs[index].offsetLeft+'px';marker.style.width=thumbs[index].offsetWidth+'px';
      marked=index;
      if(state)flight=flip.from(state,{duration:.26,ease:'power2.out',prune:true});
    }
    if(delta)strip.scrollLeft+=delta;
  }
  let index=0, invoker;
  function show(next, animate=true){
    const previous=index;index=(next+photos.length)%photos.length;
    photos.forEach((photo,i)=>{photo.hidden=i!==index;});
    const image=photos[index].querySelector('img');
    if(dialog.open){
      large.src=image.src;large.alt=image.alt;
      const caption=dialog.querySelector('[data-large-variant]'), variant=photos[index].dataset.variantLabel;
      caption.hidden=!variant;caption.textContent=variant?`Varian: ${variant}`:'';
    }
    for(const count of gallery.querySelectorAll('[data-count]'))count.textContent=`Foto ${index+1} dari ${photos.length}`;
    markThumb();
    const target=dialog.open?large:image;
    if(animate && index!==previous && target.animate && !matchMedia('(prefers-reduced-motion: reduce)').matches){
      target.getAnimations().forEach(a=>a.cancel());
      target.animate([{opacity:.55,transform:`translateX(${next>previous?8:-8}px)`},{opacity:1,transform:'none'}],{duration:190,easing:'ease-out'});
    }
  }
  function open(from){invoker=from;dialog.showModal();show(index,false);dialog.querySelector('[data-close]').focus();}
  for(const link of gallery.closest('[data-product-detail]').querySelectorAll('[data-variant-photo]')){
    const position=photos.findIndex(photo=>photo.dataset.mediaId===link.dataset.variantPhoto);
    if(position<0)continue;
    link.setAttribute('aria-haspopup','dialog');
    link.onclick=e=>{
      if(e.ctrlKey||e.metaKey||e.shiftKey||e.altKey)return;
      e.preventDefault();show(position,false);open(link);
    };
  }
  photos.forEach(photo=>{const link=photo.querySelector('a');link.onclick=e=>{
    if(e.ctrlKey||e.metaKey||e.shiftKey||e.altKey)return;
    e.preventDefault();open(link);
  };});
  gallery.querySelector('[data-enlarge]').onclick=e=>open(e.currentTarget);
  dialog.querySelector('[data-close]').onclick=()=>dialog.close();
  dialog.addEventListener('close',()=>{
    (invoker?.closest('[data-photo]') ? photos[index].querySelector('a') : invoker)?.focus({preventScroll:true});
  });
  for(const button of gallery.querySelectorAll('[data-prev],[data-next]'))button.onclick=()=>show(index+(button.hasAttribute('data-next')?1:-1));
  if(strip){
    strip.hidden=false;
    thumbs.forEach((button,i)=>button.onclick=()=>show(i));
  }
  gallery.addEventListener('keydown',e=>{
    if(photos.length<2 || e.altKey||e.ctrlKey||e.metaKey)return;
    const thumb=e.target.closest('[data-photo-index]');
    if(!['ArrowLeft','ArrowRight'].includes(e.key) && !(thumb && ['Home','End'].includes(e.key)))return;
    e.preventDefault();const onPhoto=e.target.closest('[data-photo]');
    const next=e.key==='Home'?0:e.key==='End'?photos.length-1:index+(e.key==='ArrowRight'?1:-1);
    show(next);
    if(thumb)thumbs[index].focus({preventScroll:true});
    else if(onPhoto)photos[index].querySelector('a').focus({preventScroll:true});
  });
  if(photos.length>1){
    gallery.classList.add('bk-swipe-ready');
    for(const surface of [...photos,large])enableGallerySwipe(surface,delta=>show(index+delta));
  }
  dialog.addEventListener('click',e=>{
    if(e.target!==dialog)return;
    const r=dialog.getBoundingClientRect();
    if(e.clientX<r.left || e.clientX>r.right || e.clientY<r.top || e.clientY>r.bottom)dialog.close();
  });
  gallery.querySelector('[data-gallery-bar]').hidden=false;show(0,false);
}
