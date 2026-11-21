@if($order->status !== 'sudah_dikembalikan' || !$complete)<p>Penilaian tersedia setelah semua unit kembali dan setiap item memiliki inspeksi kembali.</p>
@elseif($damaged && !auth('staff')->user()->isOwner())<p>Ada kondisi selain baik. Hanya Owner yang dapat menetapkan penilaian final, termasuk keputusan tanpa biaya.</p>
@else<details class="bk-confirm"><summary>Nilai hasil pengembalian</summary>
<form data-ledger data-assess-return class="bk-product-editor grid gap-3 p-4" method="post" action="{{ route('retur.nilai',$order->id) }}">@csrf
<input type="hidden" name="asal" value="bukti-retur">
@if($damaged)<fieldset><legend>Jumlah kerusakan menurut tarif</legend><p>Isi jumlah yang didukung bukti. Kosong atau nol berarti tidak ditagihkan.</p>
@foreach($rates as $rate)<label>{{ $rate->nama }} · {{ $rupiah($rate->tarif) }}/{{ $rate->satuan }}<input type="number" min="0" max="100" step="1" data-rate="{{ $rate->tarif }}" name="jumlah[{{ $rate->kode }}]"></label>@endforeach</fieldset>
<p role="status" data-estimate>Estimasi biaya kerusakan: Rp0</p><label>Konfirmasi total biaya kerusakan (Rp)<input required type="number" name="biaya_kerusakan" min="0" max="2147483647" step="1"></label>
@else<p>Seluruh unit kembali dalam kondisi baik. Biaya kerusakan Rp0.</p><input type="hidden" name="biaya_kerusakan" value="0">@endif
<label>Alasan penilaian<textarea name="alasan" maxlength="200" required></textarea></label>
<p>Keputusan bersifat final. Jika ada saldo, sistem menerbitkan tagihan pelunasan. Deposit tidak otomatis dipotong.</p>
<label><input type="checkbox" name="diperiksa" value="1" required> Saya sudah mencocokkan bukti, jumlah dan biaya.</label>
<button class="btn-primary">Simpan penilaian final</button>@include('partials.ledger-feedback')</form></details>@endif
