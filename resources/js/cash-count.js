// Advisory only: do not prefill the observed cash, check the attestation, or write a ledger entry.
const rupiah=new Intl.NumberFormat('id-ID');
for(const form of document.querySelectorAll('[data-cash-count]')){
  const {sesudah:amount,sebelum:before,catatan:note}=form.elements;
  const target=Number(form.dataset.expected), recorded=form.dataset.recorded==='1';
  const preview=form.querySelector('[data-count-preview]');
  function update(){
    amount.setCustomValidity('');
    const valid=amount.value!=='' && amount.validity.valid;
    const diff=amount.valueAsNumber-target;
    note.required=recorded || (valid && diff!==0);
    let message='';
    if(valid){
      message=diff===0?'Hasil hitung cocok dengan tagihan setor.':`Hasil hitung ${diff<0?'kurang':'lebih'} Rp${rupiah.format(Math.abs(diff))} dari tagihan setor.`;
      if(recorded && amount.valueAsNumber===Number(before.value)){
        message='Jumlah sama dengan setoran terakhir. Tidak ada koreksi untuk dicatat.';
        amount.setCustomValidity(message);
      }
    }
    preview.hidden=!message;
    if(preview.textContent!==message)preview.textContent=message;
  }
  amount.addEventListener('input',update);window.addEventListener('pageshow',update);update();
}
