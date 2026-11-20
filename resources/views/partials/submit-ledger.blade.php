<script>
for (const form of document.querySelectorAll('form[data-ledger]')) {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button'), output = form.querySelector('output');
        if (button.disabled) return;
        button.disabled = true;
        const label = button.textContent;
        button.textContent = 'Mencatat…';
        form.setAttribute('aria-busy','true');
        try {
            const res = await fetch(form.action, {method:'POST', body:new FormData(form), headers:{Accept:'application/json'}});
            const result = await res.json();
            if (!res.ok) throw new Error(result.message || 'Pencatatan ditolak. Muat ulang untuk memeriksa status.');
            if (res.status === 202) { output.textContent = 'Mutasi perlu rekonsiliasi. Jangan catat ulang.'; return; }
            location.reload();
        } catch (err) {
            output.textContent = `${err.message} Periksa status sebelum mengulang.`;
            button.disabled = false;
        } finally {
            form.removeAttribute('aria-busy'); button.textContent = label;
        }
    });
}
</script>
