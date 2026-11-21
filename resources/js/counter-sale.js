for (const form of document.querySelectorAll('[data-counter-sale]')) {
    const {varian_id:variant, jumlah:qty, nominal:cash} = form.elements;
    function update() {
        const option = variant.selectedOptions[0];
        qty.max = Math.min(100, Number(option?.dataset.stock || 100));
        const total = option?.value && qty.value && qty.validity.valid ? Math.ceil(Number(option.dataset.price)*qty.valueAsNumber/1000)*1000 : 0;
        form.querySelector('[data-counter-total]').textContent = total ? `Total tunai: Rp${total.toLocaleString('id-ID')}` : 'Pilih varian dan jumlah sesuai stok.';
        cash.setCustomValidity(total && cash.value && cash.valueAsNumber !== total ? 'Nominal harus sama dengan total tunai.' : '');
    }
    form.addEventListener('input', update);
    update();
}
