<form class="bk-product-editor grid gap-3 p-4" method="get" action="{{ route('laporan.penerimaan') }}"><h2>Penerimaan terverifikasi</h2><p>Pilih tanggal awal dan akhir (inklusif), maksimal satu tahun.</p>
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
<label>Dari tanggal <input name="mulai" type="date" required value="{{ old('mulai',$awal) }}"></label><label>Sampai tanggal <input name="akhir" required type="date" value="{{ old('akhir',$akhir) }}"></label><button class="btn-primary">Lihat penerimaan</button></form>
