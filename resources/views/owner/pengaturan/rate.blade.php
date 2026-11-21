@php($anchor = $field.'-'.$row->id)
<article id="{{ $anchor }}" class="bk-task"><h3>{{ $row->nama }}</h3><p>{{ $detail }} · Rp{{ number_format($row->$field,0,',','.') }}</p>
@if(session('rate_saved')===$anchor)<p role="status">Tarif tersimpan.</p>@endif
@if($warning=session('rate_warning.'.$anchor))<p role="alert">{{ $warning }}</p>@endif
@if(config('checkout.staging') && app()->environment('local','staging','testing'))
<details class="bk-confirm" @if($warning) open @endif><summary>Edit {{ $field }} {{ $row->nama }}</summary>
<form method="post" data-pending="Menyimpan tarif…" class="bk-product-editor grid gap-3 p-4" action="{{ route($endpoint,$row->id) }}">@csrf @method('patch')
<input type="hidden" name="asal" value="pengaturan"><input type="hidden" name="sebelum" value="{{ $row->$field }}">
<label>{{ ucfirst($field) }} {{ $row->nama }} (Rp) <input type="number" required min="0" max="10000000" name="{{ $field }}" value="{{ $row->$field }}"></label>
<button class="btn-primary">Simpan tarif</button></form></details>@endif</article>
