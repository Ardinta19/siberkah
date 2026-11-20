import React, { useEffect, useState } from 'react';
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
  useEffect(()=>{
    const sync = () => setItems(selection(form));
    form.addEventListener('input',sync); form.addEventListener('change',sync);
    sync();
    return () => {form.removeEventListener('input',sync);form.removeEventListener('change',sync);};
  },[form]);
  function clear(key) {
    const field = form.elements.namedItem(key);
    field.value=''; field.dispatchEvent(new Event('change',{bubbles:true}));
    (key==='tanggal' ? field.closest('[data-rental-date]').querySelector('button') : field).focus();
  }
  return <section className="bk-selection" aria-label="Ringkasan pencarian">
    <p className="kc-kicker">{items.length ? 'Pilihan pencarian' : 'Seluruh koleksi'}</p>
    {items.length ? <ul>{items.map(({key,text})=><li key={key}><button type="button" onClick={()=>clear(key)} aria-label={`Hapus ${labels[key]}: ${text}`}>
      <small>{labels[key]}</small> {text} <span aria-hidden="true">×</span></button></li>)}</ul>
      : <p>Isi nama produk atau pilih filter untuk mempersempit hasil.</p>}
  </section>;
}
export function mount(host) {createRoot(host).render(<SearchSelection form={host.closest('form')}/>);}
