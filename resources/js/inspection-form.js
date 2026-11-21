for (const form of document.querySelectorAll('[data-inspection]')) {
    const units = [...form.querySelectorAll('[data-inspection-unit]')];
    function refresh() {
        const count = units.reduce((sum, unit) => sum + unit.querySelector('[data-photos]').files.length, 0);
        form.querySelector('[data-photo-count]').textContent = `${count} dari maksimal 6 foto barang dipilih.`;
        for (const [index, unit] of units.entries()) {
            const condition = unit.querySelector('[data-condition]');
            const damaged = ['cacat_ringan','rusak'].includes(condition.value);
            condition.setCustomValidity(condition.value === 'baik' && [...unit.querySelectorAll('[data-part]')].some(p => p.value === 'cacat') ? 'Kondisi tidak dapat baik bila ada komponen cacat.' : '');
            unit.querySelector('[data-note]').required = damaged;
            const photos = unit.querySelector('[data-photos]');
            photos.required = damaged || (index === 0 && count === 0);
            photos.setCustomValidity(count > 6 ? 'Maksimal enam foto per tahap.' : [...photos.files].some(f => f.size > 3*1024*1024) ? 'Ukuran foto melebihi 3 MB.' : '');
        }
        const deposit = form.querySelector('[data-deposit-field]');
        if (deposit) {
            const active = form.elements.jenis_jaminan.value === 'deposit';
            deposit.hidden = !active;
            form.elements.nominal_jaminan.disabled = !active;
            form.elements.nominal_jaminan.required = active;
        }
    }
    form.addEventListener('formdata', ({ formData }) => {
        for (const unit of units) {
            const input = unit.querySelector('[data-photos]');
            if (!input.files.length) formData.delete(input.name);
        }
    });
    form.addEventListener('change', refresh);
    refresh();
}

// The named file input is the only upload source. Camera picks append to it,
// not to browser storage; no upload occurs until the courier submits the stage.
for (const unit of document.querySelectorAll('[data-inspection-unit]')) {
    const picker = unit.querySelector('[data-photos]');
    const tools = unit.querySelector('[data-camera-tools]');
    if (!tools || typeof DataTransfer !== 'function') continue;
    const camera = tools.querySelector('[data-camera]');
    const list = tools.querySelector('[data-photo-list]');
    const status = tools.querySelector('[data-camera-status]');
    function assign(files) {
        const transfer = new DataTransfer();
        files.forEach(file => transfer.items.add(file));
        picker.files = transfer.files;
        picker.dispatchEvent(new Event('change', { bubbles: true }));
    }
    function render() {
        list.replaceChildren();
        [...picker.files].forEach((file, index) => {
            const row = document.createElement('li');
            row.append(`${index+1}. ${file.name} · ${Math.ceil(file.size/1024)} KB `);
            const remove = document.createElement('button');
            remove.type = 'button'; remove.textContent = `Hapus foto ${index+1}`;
            remove.className = 'bk-secondary';
            remove.onclick = () => {
                assign([...picker.files].filter((_, i) => i !== index));
                (list.querySelectorAll('button')[index] || picker).focus();
                status.textContent = 'Foto dihapus dari pilihan. Belum ada berkas yang dikirim.';
            };
            row.append(remove); list.append(row);
        });
    }
    picker.addEventListener('change', () => { status.textContent = ''; render(); });
    camera.addEventListener('change', () => {
        const file = camera.files[0];
        if (!file) return;
        const total = [...unit.closest('form').querySelectorAll('[data-photos]')].reduce((n,input)=>n+input.files.length,0);
        if (total >= 6 || file.size > 3*1024*1024 || !['image/jpeg','image/png','image/webp'].includes(file.type)) {
            status.textContent = 'Foto tidak ditambahkan. Periksa format, batas 3 MB dan maksimal 6 foto per tahap.';
        } else {
            assign([...picker.files,file]);
            status.textContent = 'Foto ditambahkan ke pilihan unit ini.';
        }
        camera.value = '';
    });
    tools.hidden = false;
    render();
}
