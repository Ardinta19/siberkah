// The baseline is the server record, not a repopulated value after validation failed.
const editor=document.querySelector('[data-profile-editor]');
if(editor){
  const name=editor.elements.nama, consent=editor.elements.setuju_privasi;
  const undo=editor.querySelector('[data-undo-profile]'), save=editor.querySelector('[data-save-profile]');
  const unavailable=save.disabled, state=editor.querySelector('[data-profile-state]');
  function sync(){
    const dirty=name.value!==editor.dataset.savedName || Boolean(consent?.checked);
    undo.hidden=!dirty;
    // Onboarding still needs an explicit opt-in even when the name is unchanged.
    save.disabled=unavailable || (!consent && !dirty);
    const message=dirty?'Perubahan belum disimpan.':'';
    if(state.textContent!==message)state.textContent=message;
  }
  undo.onclick=()=>{
    name.value=editor.dataset.savedName;if(consent)consent.checked=false;
    sync();name.focus();state.textContent='Perubahan dibatalkan.';
  };
  editor.addEventListener('input',sync);window.addEventListener('pageshow',sync);sync();
}
