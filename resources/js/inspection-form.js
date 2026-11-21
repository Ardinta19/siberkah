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
    form.addEventListener('change', refresh);
    refresh();
}
