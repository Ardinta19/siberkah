// Helpers only: manual slug and server validation remain authoritative.
const editor=document.querySelector('[data-product-editor]');
if(editor){
  const {nama,slug,jenis}=editor.elements;
  function syncKind(){
    for(const group of editor.querySelectorAll('[data-kind-setting]')){
      group.hidden=!group.dataset.kindSetting.split(' ').includes(jenis.value);
      group.querySelectorAll('input,select').forEach(field=>field.disabled=group.hidden);
    }
  }
  jenis.addEventListener('change',syncKind);syncKind();
  const suggest=editor.querySelector('[data-suggest-slug]');
  if(suggest){
    const derived=()=>nama.value.normalize('NFKD').replace(/\p{M}/gu,'').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
    let manual=Boolean(slug.value);
    const sync=()=>{const value=derived();if(!manual)slug.value=value;suggest.disabled=!value||value===slug.value;};
    nama.addEventListener('input',sync);
    slug.addEventListener('input',()=>{manual=true;sync();});
    suggest.hidden=false;suggest.onclick=()=>{manual=false;sync();slug.focus();};sync();
  }
}
