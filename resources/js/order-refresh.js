// Retain the one-minute refresh only while the detail is idle: never erase a draft,
// dismiss a sheet, or take focus away from a customer using the navigation.
const detail=document.querySelector('[data-order-refresh]');
if(detail){
  const reason=detail.querySelector('[name=alasan]');
  let timer;
  function schedule(){
    clearTimeout(timer);
    if(document.hidden)return;
    timer=setTimeout(()=>{
      const busy=document.querySelector('dialog[open],details[open],[aria-busy="true"],#mobile-menu.is-open,#account-links:not([hidden])');
      if(!document.hidden && !busy && !reason?.value && document.activeElement===document.body)location.reload();
      else schedule();
    },60000);
  }
  document.addEventListener('visibilitychange',schedule);
  window.addEventListener('pageshow',schedule);
  window.addEventListener('pagehide',()=>clearTimeout(timer));
  schedule();
}
