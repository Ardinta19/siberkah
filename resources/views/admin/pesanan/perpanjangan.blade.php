<section class="bk-task" id="perpanjangan" aria-labelledby="judul-durasi"><h2 id="judul-durasi">Durasi sewa</h2>
<p>Batas kembali: <strong>{{ $extension['end']->format('d/m/Y H:i') }} WIB</strong> · {{ $extension['used'] }} dari 5 jam tambahan terpakai.</p>
@if($extension['reason'])<p>{{ $extension['reason'] }}</p>@else
<details class="bk-confirm"><summary>Perpanjang durasi sewa</summary>
<p>Biaya menambah total dan saldo pesanan, bukan mencatat pembayaran pelanggan. Pilih tambahan dari batas kembali saat ini.</p>
<form class="bk-product-editor grid gap-3 p-4" method="post" action="{{ route('papan.perpanjang',$order->id) }}" data-ledger>@csrf
<input type="hidden" name="kunci" value="{{ \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="jam_sebelum" value="{{ $extension['used'] }}">
<label>Durasi, batas baru dan biaya <select name="jam" required><option value="">Pilih durasi</option>@for($h=1;$h<=5-$extension['used'];$h++)<option value="{{ $h }}">{{ $h }} jam · {{ $extension['end']->copy()->addHours($h)->format('H:i') }} WIB · +Rp{{ number_format($extension['rate']*$h,0,',','.') }}</option>@endfor</select></label>
<label>Alasan perpanjangan <textarea name="alasan" required maxlength="200"></textarea></label><button class="btn-primary">Simpan perpanjangan</button>@include('partials.ledger-feedback')</form></details>@endif
@if($extension['entries']->isNotEmpty())<details class="bk-confirm"><summary>Riwayat perpanjangan · {{ $extension['entries']->count() }} catatan</summary><ol>@foreach($extension['entries'] as $entry)<li><strong>{{ $entry->jam }} jam · Rp{{ number_format($entry->nominal,0,',','.') }}</strong><p>Batas baru {{ $entry->batas_baru }} WIB · {{ $entry->alasan }}</p></li>@endforeach</ol></details>@endif</section>
