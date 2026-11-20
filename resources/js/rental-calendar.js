// Enhance only rental discovery. Checkout rules and the submitted ISO date stay native.
const fmt = (options) => new Intl.DateTimeFormat('id-ID', options);
const full = fmt({weekday:'long', day:'numeric', month:'long', year:'numeric'});
const month = fmt({month:'long',year:'numeric'});
const short = fmt({day:'numeric',month:'short',year:'numeric'});
const iso = d => `${String(d.getFullYear()).padStart(4,'0')}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
const parse = v => new Date(`${v}T12:00:00`);

for (const field of document.querySelectorAll('[data-rental-date]')) {
  if (!window.HTMLDialogElement?.prototype.showModal) continue;
  const input = field.querySelector('input'), trigger = field.querySelector('button');
  const dialog = document.createElement('dialog');
  dialog.className = 'bk-calendar'; dialog.setAttribute('aria-label','Pilih tanggal sewa');
  dialog.innerHTML = `<p class="kc-kicker">Berkah Kreatif · Kalender sewa</p><div class="bk-cal-head"><button type="button" aria-label="Bulan sebelumnya">‹</button><h2 aria-live="polite"></h2><button type="button" aria-label="Bulan berikutnya">›</button></div><div class="bk-week" aria-hidden="true">${['Sen','Sel','Rab','Kam','Jum','Sab','Min'].map(s=>`<span>${s}</span>`).join('')}</div><div class="bk-days" role="group" aria-label="Tanggal"></div><div class="bk-cal-actions"><button type="button" data-today>Hari ini</button><button type="button" data-clear>Kosongkan</button><button type="button" data-close>Tutup</button></div>`;
  document.body.append(dialog);
  const days = dialog.querySelector('.bk-days');
  let cursor;
  function sync() {
    trigger.disabled = input.disabled;
    trigger.querySelector('span').textContent = input.value ? short.format(parse(input.value)) : 'Pilih tanggal';
    trigger.setAttribute('aria-label',`Tanggal sewa: ${trigger.textContent.trim()}`);
  }
  function render(focus=false) {
    const y = cursor.getFullYear(), m = cursor.getMonth();
    dialog.querySelector('h2').textContent = month.format(cursor);
    days.replaceChildren();
    for(let d=1; d<=new Date(y,m+1,0).getDate(); d++) {
      const date = new Date(y,m,d,12), key = iso(date), btn = document.createElement('button');
      btn.type='button'; btn.textContent=d; btn.dataset.date=key; btn.tabIndex=d===cursor.getDate()?0:-1;
      btn.setAttribute('aria-label', full.format(date)); btn.setAttribute('aria-pressed',String(key===input.value));
      if (key===iso(new Date())) btn.setAttribute('aria-current','date');
      if(d===1) btn.style.gridColumnStart=(date.getDay()+6)%7+1;
      days.append(btn);
    }
    if(focus) days.querySelector('[tabindex="0"]').focus();
  }
  function pick(value) {
    input.value=value; input.dispatchEvent(new Event('input',{bubbles:true}));input.dispatchEvent(new Event('change',{bubbles:true})); dialog.close();
    if (trigger.animate) trigger.animate([{boxShadow:'0 0 0 2px #b6718a'},{boxShadow:'0 0 0 2px transparent'}],{duration:320});
  }
  function open(){if(input.disabled || dialog.open)return;cursor=parse(input.value || iso(new Date()));render();dialog.showModal();days.querySelector('[tabindex="0"]').focus();}
  trigger.id=input.id+'-trigger'; field.querySelector('label').htmlFor=trigger.id;
  input.hidden=true;trigger.hidden=false;sync();
  new MutationObserver(sync).observe(input,{attributes:true,attributeFilter:['disabled']});
  input.addEventListener('change',sync); input.addEventListener('invalid',e=>{e.preventDefault();open();});
  trigger.addEventListener('click',open);dialog.addEventListener('close',()=>trigger.focus());
  dialog.querySelectorAll('.bk-cal-head button').forEach((btn,i)=>btn.onclick=()=>{cursor=new Date(cursor.getFullYear(),cursor.getMonth()+(i?1:-1),1,12);render();
    if(days.animate && !matchMedia('(prefers-reduced-motion: reduce)').matches){
      days.getAnimations().forEach(a=>a.cancel());
      days.animate([{transform:`translateX(${i ? 12 : -12}px)`,opacity:.55},{transform:'none',opacity:1}],{duration:180,easing:'ease-out'});
    }
  });
  days.onclick=e=>{if(e.target.dataset.date)pick(e.target.dataset.date);};
  days.onkeydown=e=>{
    const shifts={ArrowLeft:-1,ArrowRight:1,ArrowUp:-7,ArrowDown:7};
    if(!(e.key in shifts))return;e.preventDefault();cursor=parse(e.target.dataset.date);cursor.setDate(cursor.getDate()+shifts[e.key]);render(true);
  };
  dialog.querySelector('[data-today]').onclick=()=>pick(iso(new Date()));
  const clear=dialog.querySelector('[data-clear]');clear.hidden=input.required;clear.onclick=()=>pick('');
  dialog.querySelector('[data-close]').onclick=()=>dialog.close();
}
