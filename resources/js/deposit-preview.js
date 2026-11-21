for (const form of document.querySelectorAll('[data-deposit-preview]')) {
    const field = form.elements.nominal;
    const message = form.querySelector('[data-projection]');
    const rupiah = amount => 'Rp' + amount.toLocaleString('id-ID');
    function update() {
        const used = field.valueAsNumber;
        message.textContent = !field.value ? '' : !field.validity.valid ? 'Periksa nominal pemakaian.' :
            `Setelah disimpan: sisa deposit ${rupiah(Number(form.dataset.deposit) - used)} · saldo pesanan ${rupiah(Number(form.dataset.balance) - used)}.`;
    }
    field.addEventListener('input', update);
    update();
}
