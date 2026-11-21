const list=document.querySelector('[data-media-list]');
if(list){
  const initial=[...list.querySelectorAll('[data-media-row]')], save=document.querySelector('[data-save-order]'), note=document.querySelector('[data-order-note]');
  const reset=document.querySelector('[data-reset-order]');
  function sync(){
    const rows=[...list.children];save.disabled=initial.every((el,i)=>el===rows[i]);
    reset.hidden=save.disabled;
    rows.forEach((row,i)=>{row.querySelector('[data-move="-1"]').disabled=i===0;row.querySelector('[data-move="1"]').disabled=i===rows.length-1;});
  }
  if(initial.length>1){
    save.hidden=false;initial.forEach(el=>el.querySelector('[data-move-tools]').hidden=false);sync();
    reset.onclick=()=>{
      list.append(...initial);sync();initial[0].querySelector('[data-move="1"]').focus();
      note.textContent='Perubahan dibatalkan. Urutan tersimpan dipulihkan.';
    };
    list.addEventListener('click',e=>{
      const btn=e.target.closest('[data-move]');if(!btn)return;
      const row=btn.closest('[data-media-row]'), up=btn.dataset.move==='-1', next=up?row.previousElementSibling:row.nextElementSibling;
      if(!next)return;
      list.insertBefore(up?row:next,up?next:row);sync();
      (btn.disabled?row.querySelector('[data-move]:not(:disabled)'):btn).focus();
      note.textContent=`Foto di posisi ${[...list.children].indexOf(row)+1} dari ${initial.length}. ${save.disabled?'Urutan kembali seperti semula.':'Urutan belum disimpan.'}`;
    });
  }
}
