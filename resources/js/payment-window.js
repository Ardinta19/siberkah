// Display only server-issued amounts. Copying must never submit the confirmation form.
for(const panel of document.querySelectorAll('[data-payment-window]')){
  const elapsedAtLoad=performance.now();
  const duration=Date.parse(panel.dataset.end)-Date.parse(panel.dataset.server);
  const clock=panel.querySelector('[data-clock]'), copy=panel.querySelector('[data-copy-amount]');
  const feedback=panel.querySelector('[data-copy-status]');
  let timer;
  function refresh(){
    clearTimeout(timer);
    if(!panel.dataset.end)return true; // A post-return closing invoice may have no deadline.
    const ms=duration-(performance.now()-elapsedAtLoad);
    if(!Number.isFinite(ms)||ms<=0){
      const restore=panel.contains(document.activeElement), warning=panel.nextElementSibling;
      panel.hidden=true;warning.hidden=false;
      if(restore)warning.focus({preventScroll:true});
      return false;
    }
    const seconds=Math.ceil(ms/1000);
    clock.textContent=`Sisa ${Math.floor(seconds/60)} menit ${String(seconds%60).padStart(2,'0')} detik.`;
    timer=setTimeout(refresh,ms%1000 || 1000);
    return true;
  }
  if(navigator.clipboard?.writeText){
    copy.hidden=false;
    copy.onclick=async()=>{
      if(!refresh())return;
      try{
        await navigator.clipboard.writeText(copy.dataset.copyAmount);
        feedback.textContent='Nominal tersalin tanpa Rp atau pemisah ribuan.';
      }catch{feedback.textContent='Tidak dapat menyalin. Gunakan angka pada baris Bayar persis.';}
    };
  }
  panel.querySelector('form').addEventListener('submit',e=>{if(!refresh())e.preventDefault();},true);
  window.addEventListener('pageshow',refresh);
  document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh();});
  refresh();
}
