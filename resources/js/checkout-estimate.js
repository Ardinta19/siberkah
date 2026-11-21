// Preview only. The server still validates stock, delivery, deadlines and every amount.
const form=document.querySelector('#checkout');
if(form){
  const field=name=>form.elements.namedItem(name), box=form.querySelector('#estimate');
  const price=select=>Number(select?.selectedOptions[0]?.dataset.price || 0);
  const money=new Intl.NumberFormat('id-ID'), rp=value=>'Rp'+money.format(value);
  const started=performance.now(), dp=field('pembayaran').querySelector('[value="dp"]');
  let timer;
  const write=(selector,value)=>{const el=box.querySelector(selector);if(el.textContent!==value)el.textContent=value;};
  function update(){
    clearTimeout(timer);
    const deliver=field('cara_serah').value==='antar', destination=deliver?'saat diantar':'saat ambil di toko';
    const delivery=form.querySelector('#delivery');delivery.disabled=!deliver;delivery.hidden=!deliver;
    field('alamat_antar').required=deliver;
    const date=field('tanggal').value;
    if(field('jam_serah')){
      const today=date===form.dataset.today;
      field('jam_serah').disabled=!today;field('jam_serah').required=today;form.querySelector('#slot').hidden=!today;
    }
    const base=field('varian_id')?price(field('varian_id')):Number(form.dataset.price);
    let extras=0, selectedExtra=false;
    let valid=field('jumlah').validity.valid;
    for(const opt of form.querySelectorAll('[data-extra]')){
      const units=field(`opsi[${opt.dataset.extra}][satuan]`);
      if(units){
        units.disabled=!opt.value;units.required=Boolean(opt.value);
        units.closest('label').hidden=!opt.value;
      }
      if(!opt.value){if(opt.required)valid=false;continue;}
      selectedExtra=true;
      if(units && !units.validity.valid)valid=false;
      extras+=price(opt)*Number(units?.value ?? 1);

    }
    const unit=base+extras;
    const subtotal=unit*field('jumlah').valueAsNumber, fee=deliver?price(field('zona_antar_id')):0;
    const total=Math.ceil((subtotal+fee)/1000)*1000, rounding=total-subtotal-fee;
    valid=valid && unit>0 && Number.isSafeInteger(total) && total<=2147482648;
    const now=Number(form.dataset.renderedAt)*1000+performance.now()-started;
    const deadline=Date.parse(`${date}T18:00:00+07:00`)-86400000;
    const timeLeft=deadline-now;
    if(dp){
      dp.disabled=!valid || total<Number(form.dataset.threshold) || !(timeLeft>0);
      if(dp.disabled && field('pembayaran').value==='dp')field('pembayaran').value='penuh';
      if(timeLeft>0 && timeLeft<2147483000)timer=setTimeout(update,timeLeft+50);
    }
    box.hidden=false;
    box.querySelector('[data-base-row]').hidden=!selectedExtra;
    box.querySelector('[data-extra-row]').hidden=!selectedExtra;
    write('[data-base]',rp(base));write('[data-extra-cost]',valid?rp(extras):'—');
    write('[data-subtotal]',valid?rp(subtotal):'—');write('[data-fee]',deliver?rp(fee):'Ambil di toko · Rp0');
    box.querySelector('[data-rounding-row]').hidden=!valid || rounding===0;
    write('[data-rounding]',valid?rp(rounding):'—');write('[data-total]',valid?rp(total):'—');
    const cash=field('metode_pelunasan').value==='tunai_saat_serah';
    const note=!valid?'Lengkapi harga dan jumlah yang valid untuk melihat perkiraan.' : field('pembayaran').value==='dp'
      ? `DP ${rp(total/2)} melalui bank; sisa ${rp(total/2)} ${cash?'tunai '+destination:'melalui bank'}. Kode unik mengikuti tagihan, bukan bagian dari pokok DP.`
      : cash?`Bayar penuh ${rp(total)} tunai ${destination}.`:`Bayar penuh ${rp(total)} melalui bank sesuai tagihan.`;
    write('[data-payment-note]',note);
  }
  form.addEventListener('input',update);window.addEventListener('pageshow',update);update();
}
