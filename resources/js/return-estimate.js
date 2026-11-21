// Preview only. The server checks quantities and tariffs again before issuing a bill.
for (const form of document.querySelectorAll('form[data-assess-return]')) {
    const estimate = form.querySelector('[data-estimate]');
    if (!estimate) continue;
    const quantities = [...form.querySelectorAll('[data-rate]')];
    const format = new Intl.NumberFormat('id-ID');
    const update = () => {
        estimate.textContent = quantities.every(field => field.validity.valid)
            ? 'Estimasi biaya kerusakan: Rp' + format.format(quantities.reduce((sum, field) => sum + Number(field.value) * Number(field.dataset.rate), 0))
            : 'Periksa jumlah kerusakan sebelum mengonfirmasi biaya.';
    };
    form.addEventListener('input', update);
    update();
}
