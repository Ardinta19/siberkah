@if($canApply && auth('staff')->user()->isOwner())
<details class="bk-confirm"><summary>Gunakan deposit untuk kerusakan</summary>
<form method="post" action="{{ route('retur.potong',$order->id) }}" class="bk-product-editor grid gap-3 p-4" data-ledger data-deposit-preview data-deposit="{{ $sisa }}" data-balance="{{ $order->saldo }}">@csrf
<p>Keputusan sekali dan final untuk {{ $order->nomor }}. Maksimum {{ $rupiah($cap) }}, yaitu nilai terkecil antara biaya kerusakan, deposit dan saldo.</p>
<label>Nominal deposit yang dipakai (Rp)<input type="number" min="1" max="{{ $cap }}" step="1" required name="nominal"></label>
<p role="status" data-projection></p><label>Alasan penggunaan<textarea name="alasan" maxlength="300" required></textarea></label>
<p>Tagihan lama akan diganti. Jika masih ada saldo, sistem menerbitkan tagihan sisa. Ini bukan mutasi bank atau transfer pengembalian.</p>
<label><input required type="checkbox"> Saya sudah memeriksa nominal dan memahami bahwa keputusan ini final.</label>
<button type="submit" class="btn-primary">Simpan pemakaian deposit</button>@include('partials.ledger-feedback')</form></details>
@elseif($canApply)<p>Hanya Owner yang dapat memutuskan penggunaan deposit untuk biaya kerusakan.</p>
@elseif($cap>0)<p>Pemakaian deposit tidak tersedia. Periksa status tagihan sebelum melanjutkan.</p>@endif
