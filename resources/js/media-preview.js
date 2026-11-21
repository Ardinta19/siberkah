// Local inspection is only a preview. The server still decodes and validates the upload.
const fileInput = document.querySelector('[data-photo-file]');
if (fileInput && URL.createObjectURL) {
  const form=fileInput.form, preview=form.querySelector('[data-file-preview]');
  const status=form.querySelector('[data-file-status]'), clear=form.querySelector('[data-clear-file]');
  const formats=['image/jpeg','image/png','image/webp'];
  let url;
  function release(){
    if(url) URL.revokeObjectURL(url);
    url=null;preview.hidden=true;preview.querySelector('img').removeAttribute('src');
  }
  function check(){
    release();fileInput.setCustomValidity('');fileInput.removeAttribute('aria-invalid');
    const file=fileInput.files[0];clear.hidden=!file;
    if(!file){status.textContent='';return;}
    function reject(message){
      release();fileInput.setCustomValidity(message);fileInput.setAttribute('aria-invalid','true');status.textContent=message;
    }
    if(file.size>5*1024*1024)return reject('Ukuran foto melebihi 5 MB. Pilih berkas yang lebih kecil.');
    if(file.type && !formats.includes(file.type))return reject('Pilih foto JPG, PNG, atau WebP.');
    fileInput.setCustomValidity('Tunggu sampai foto selesai diperiksa.');status.textContent='Memeriksa ukuran dan dimensi foto…';
    const selected=URL.createObjectURL(file), probe=new Image();url=selected;
    probe.onload=()=>{
      if(url!==selected)return;
      const w=probe.naturalWidth,h=probe.naturalHeight;
      if(w<800 || w>8000 || h>8000 || w*h>12000000)return reject(`Foto ${w} × ${h} piksel tidak sesuai. Periksa batas dimensi di atas.`);
      fileInput.setCustomValidity('');preview.querySelector('img').src=selected;preview.hidden=false;
      status.textContent=`${w} × ${h} piksel · ${Math.ceil(file.size/1024)} KB. Foto belum diunggah.`;
    };
    probe.onerror=()=>{if(url===selected)reject('Foto tidak dapat dibaca. Pilih berkas gambar lain.');};
    probe.src=selected;
  }
  fileInput.addEventListener('change',check);
  clear.onclick=()=>{fileInput.value='';check();status.textContent='Pilihan foto dibatalkan.';fileInput.focus();};
  window.addEventListener('pagehide',release);
  window.addEventListener('pageshow',check);
}
