// Preview only. The server checks quantities and tariffs again before issuing a bill.
for (const form of document.querySelectorAll('form[data-assess-return]')) {
    const estimate = form.querySelector('[data-estimate]');
    if (!estimate) continue;
    const quantities = [...form.querySelectorAll('[data-rate]')];
    const format = new Intl.NumberFormat('id-ID');
    const update = () => {
        const valid = quantities.every(field => field.validity.valid);
        const total = quantities.reduce((sum, field) => sum + Number(field.value) * Number(field.dataset.rate), 0);
        const confirmation = form.elements.biaya_kerusakan;
        const mismatch = valid && confirmation.value && confirmation.valueAsNumber !== total;
        confirmation.setCustomValidity(mismatch ? 'Cocokkan nominal dengan estimasi biaya kerusakan.' : '');
        estimate.textContent = valid
            ? 'Estimasi biaya kerusakan: Rp' + format.format(total) + (mismatch ? ' · Nominal konfirmasi belum cocok.' : '')
            : 'Periksa jumlah kerusakan sebelum mengonfirmasi biaya.';
    };
    form.addEventListener('input', update);
    update();
}
