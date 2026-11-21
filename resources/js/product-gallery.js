import { enableGallerySwipe } from './gallery-swipe.js';
// With no dialog support every photo remains a normal link to the full image.
for (const gallery of document.querySelectorAll('[data-gallery]')) {
  const photos=[...gallery.querySelectorAll('[data-photo]')], dialog=gallery.querySelector('dialog');
  if(!photos.length || !dialog?.showModal)continue;
  const large=dialog.querySelector('[data-large]');
  let index=0, invoker;
  function show(next, animate=true){
    const previous=index;index=(next+photos.length)%photos.length;
    photos.forEach((photo,i)=>{photo.hidden=i!==index;});
    const image=photos[index].querySelector('img');
    if(dialog.open){large.src=image.src;large.alt=image.alt;}
    for(const count of gallery.querySelectorAll('[data-count]'))count.textContent=`Foto ${index+1} dari ${photos.length}`;
    const target=dialog.open?large:image;
    if(animate && index!==previous && target.animate && !matchMedia('(prefers-reduced-motion: reduce)').matches){
      target.getAnimations().forEach(a=>a.cancel());
      target.animate([{opacity:.55,transform:`translateX(${next>previous?8:-8}px)`},{opacity:1,transform:'none'}],{duration:190,easing:'ease-out'});
    }
  }
  function open(from){invoker=from;dialog.showModal();show(index,false);dialog.querySelector('[data-close]').focus();}
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
  gallery.addEventListener('keydown',e=>{
    if(photos.length<2 || e.altKey||e.ctrlKey||e.metaKey || !['ArrowLeft','ArrowRight'].includes(e.key))return;
    e.preventDefault();const onPhoto=e.target.closest('[data-photo]');show(index+(e.key==='ArrowRight'?1:-1));
    if(onPhoto)photos[index].querySelector('a').focus({preventScroll:true});
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
