import React, { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';

const labels = {q:'Nama', kategori:'Kategori', jenis:'Jenis', tanggal:'Sewa', urut:'Urutan'};
const dateFormat = new Intl.DateTimeFormat('id-ID',{day:'numeric',month:'short',year:'numeric'});
function selection(form) {
  return Object.keys(labels).flatMap(key => {
    const field = form.elements.namedItem(key);
    if (!field || field.disabled || !field.value.trim()) return [];
    const text = field.selectedOptions?.[0]?.text ?? (key === 'tanggal'
      ? dateFormat.format(new Date(field.value+'T12:00:00')) : field.value.trim());
    return [{key,text}];
  });
}
function SearchSelection({form}) {
  const [items, setItems] = useState(()=>selection(form));
  const root=useRef(null), current=useRef(items), motion=useRef(null), pending=useRef(null), stop=useRef(null);
  useEffect(()=>{
    const media=matchMedia('(prefers-reduced-motion: no-preference)');
    let alive=true;
    const cancel=()=>{pending.current=null;stop.current?.();stop.current=null;};
    async function prepare(){
      cancel();motion.current=null;
      if(!media.matches)return;
      try{const module=await import('./search-chip-motion.js');if(alive && media.matches)motion.current=module;}catch{/* Native controls still work. */}
    }
    function sync(){
      const next=selection(form), old=current.current;
      if(next.length===old.length && next.every((item,i)=>item.key===old[i].key && item.text===old[i].text))return;
      if(next.map(x=>x.key).join()!==old.map(x=>x.key).join()){
        cancel();
        if(motion.current && root.current?.getClientRects().length){
          const nodes=[...root.current.querySelectorAll('[data-chip]')];
          pending.current={state:motion.current.before(nodes),oldKeys:new Set(nodes.map(el=>el.dataset.chip))};
        }
      }
      current.current=next;setItems(next);
    }
    prepare();media.addEventListener('change',prepare);
    form.addEventListener('input',sync);form.addEventListener('change',sync);sync();
    return ()=>{alive=false;cancel();media.removeEventListener('change',prepare);form.removeEventListener('input',sync);form.removeEventListener('change',sync);};
  },[form]);
  useLayoutEffect(()=>{
    const snapshot=pending.current;pending.current=null;
    if(!snapshot || !motion.current)return;
    const nodes=[...root.current.querySelectorAll('[data-chip]')];
    stop.current=motion.current.after(snapshot.state,nodes.filter(el=>snapshot.oldKeys.has(el.dataset.chip)),nodes.filter(el=>!snapshot.oldKeys.has(el.dataset.chip)));
  },[items]);
  function clear(key) {
    const field = form.elements.namedItem(key);
    field.value=''; field.dispatchEvent(new Event('change',{bubbles:true}));
    (key==='tanggal' ? field.closest('[data-rental-date]').querySelector('button') : field).focus();
  }
  return <section ref={root} className="bk-selection" aria-label="Ringkasan pencarian">
    <p className="kc-kicker">{items.length ? 'Pilihan pencarian' : 'Seluruh koleksi'}</p>
    {items.length ? <ul>{items.map(({key,text})=><li key={key} data-chip={key}><button type="button" onClick={()=>clear(key)} aria-label={`Hapus ${labels[key]}: ${text}`}>
      <small>{labels[key]}</small> {text} <span aria-hidden="true">×</span></button></li>)}</ul>
      : <p>Isi nama produk atau pilih filter untuk mempersempit hasil.</p>}
  </section>;
}
export function mount(host) {createRoot(host).render(<SearchSelection form={host.closest('form')}/>);}
