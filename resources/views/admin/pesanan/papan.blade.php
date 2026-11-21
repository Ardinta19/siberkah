<section class="bk-task" id="teks-papan" aria-labelledby="judul-papan"><h2 id="judul-papan">Teks papan pesanan</h2>
<p>Metode: {{ ucfirst($board->metode) }} @if($board->jadwal_serah) · Jadwal serah: {{ $board->jadwal_serah }} WIB @endif</p>
@php($fields=['judul'=>25,'penerima'=>45,'pengirim'=>40])
<dl>@foreach($fields as $field=>$limit)<div><dt>{{ ucfirst($field) }}</dt><dd>{{ filled($board->$field) ? $board->$field : 'Belum diisi' }}</dd></div>@endforeach</dl>
@if(session('status_papan'))<p role="status">{{ session('status_papan') }}</p>@endif
@if(!in_array($order->status,['menunggu_pembayaran','dikonfirmasi'],true))<p>Teks terkunci pada tahap pesanan ini.</p>
@elseif(config('checkout.staging') && app()->environment('local','testing','staging'))
<details class="bk-confirm" @if($errors->any()) open @endif><summary>Sunting teks papan</summary>
<form method="post" action="{{ route('papan.edit',$order->id) }}" class="bk-product-editor grid gap-3 p-4" data-pending="Menyimpan teks…">@csrf @method('put')
<input type="hidden" name="asal" value="rincian-admin"><input type="hidden" name="versi" value="{{ old('versi',\App\Support\BoardTextVersion::of($board)) }}">
@foreach($fields as $field=>$limit)<label>{{ ucfirst($field) }} (maks. {{ $limit }} karakter)<input name="{{ $field }}" required maxlength="{{ $limit }}" value="{{ old($field,$board->$field) }}"></label>@error($field)<p role="alert">{{ $message }}</p>@enderror @endforeach
<p>Periksa ejaan ketiga baris. Setelah produksi dimulai, teks tidak dapat disunting.</p><button class="btn-primary">Simpan teks papan</button></form></details>@endif</section>
