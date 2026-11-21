// Losing the response does not mean the transaction was rolled back.
for(const form of document.querySelectorAll('form[data-ledger]')){
  const button=form.querySelector('button[type="submit"],button:not([type])');
  const output=form.querySelector('output');
  const review=form.querySelector('[data-review-ledger]');
  const label=button.textContent;
  let locked=false;
  function requireReview(message){
    output.textContent=message;review.hidden=false;review.focus({preventScroll:true});
  }
  form.addEventListener('submit',async event=>{
    event.preventDefault();if(locked)return;
    locked=true;button.disabled=true;button.textContent='Mencatat…';
    output.textContent='';form.setAttribute('aria-busy','true');
    try{
      const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json'}});
      const json=response.headers.get('content-type')?.includes('application/json');
      const data=json?await response.json():null;
      if(response.status===422 && data){
        output.textContent=Object.values(data.errors||{}).flat().join(' ') || data.message || 'Periksa isian sebelum mengirim.';
        locked=false;button.disabled=false;
        const invalid=form.elements.namedItem(Object.keys(data.errors||{})[0]);
        (invalid?.focus ? invalid : button).focus();
      }else if(response.status===413 && form.hasAttribute('data-inspection')){
        output.textContent='Unggahan terlalu besar. Kurangi ukuran atau jumlah foto, lalu periksa kembali.';
        locked=false;button.disabled=false;form.querySelector('[data-photos]')?.focus();
      }else if(response.status===202 && data){
        requireReview('Mutasi perlu rekonsiliasi. Jangan catat ulang.');
      }else if(response.ok && data && !response.redirected){
        location.reload();
      }else if(response.status===409){
        requireReview('Pencatatan ditolak karena konflik. Periksa status terbaru sebelum melanjutkan.');
      }else if([401,403,419].includes(response.status)||response.redirected){
        requireReview('Periksa kembali sesi dan hak akses Anda. Jangan kirim ulang dari formulir ini.');
      }else{
        requireReview('Hasil pencatatan belum dapat dipastikan. Jangan catat ulang sebelum memeriksa status terbaru.');
      }
    }catch{
      requireReview('Hasil pencatatan belum dapat dipastikan. Jangan catat ulang sebelum memeriksa status terbaru.');
    }finally{
      form.removeAttribute('aria-busy');button.textContent=label;
    }
  });
}
