<div class="bk-date-field" data-rental-date>
    <label for="{{ $dateId ?? 'rental-date' }}">Tanggal sewa</label>
    <input id="{{ $dateId ?? 'rental-date' }}" type="date" name="tanggal" value="{{ $day }}" @required($required)>
    <button type="button" hidden class="bk-date-trigger" aria-haspopup="dialog"><span>Pilih tanggal</span><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 10h18"/></svg></button>
    @unless($required)<small class="bk-date-help">Khusus produk sewa.</small>@endunless
</div>
