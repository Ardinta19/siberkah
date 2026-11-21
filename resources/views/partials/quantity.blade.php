<div class="bk-qty-label"><label for="qty-{{ $itemId }}">Jumlah</label>
    <span class="bk-stepper" data-stepper>
        <button hidden type="button" data-step="-1" aria-label="Kurangi jumlah">−</button>
        <input id="qty-{{ $itemId }}" required type="number" name="{{ $quantityName ?? 'jumlah' }}" min="1" max="100" value="{{ $quantity }}">
        <button hidden type="button" data-step="1" aria-label="Tambah jumlah">+</button>
    </span>
</div>
