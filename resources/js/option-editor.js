const editor=document.querySelector('[data-option-editor]');
if(editor){
  const extra=editor.querySelector('[data-new-choice]'), type=editor.elements.tipe;
  if(extra){
    function sync(){extra.disabled=extra.hidden=type.value==='teks';}
    type.addEventListener('change',sync);sync();
  }
}
