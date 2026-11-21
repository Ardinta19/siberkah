@if($r->alasan==='nominal_berbeda')
@php($within = $r->nominal >= $r->tagihan && $r->nominal < $r->nominal_diharapkan && $r->status_tagihan==='menunggu')
@if(!$within)<p>Penerimaan selisih tidak tersedia: pokok harus tertutup, nominal tidak boleh berlebih, dan tagihan harus menunggu.</p>@endif
@foreach(($within ? ['terima'=>'Terima selisih kode unik','tolak'=>'Tolak selisih'] : ['tolak'=>'Tolak selisih']) as $action=>$label)
<details class="bk-confirm"><summary>{{ $label }} · {{ $r->referensi }}</summary>
<form method="post" action="{{ route('admin.pesanan.reject-payment',$r->pesanan_id) }}" class="bk-product-editor grid gap-3 p-4" data-ledger>@csrf
<input type="hidden" name="mutasi_id" value="{{ $r->id }}"><input type="hidden" name="action" value="{{ $action }}">
<p>{{ $action==='terima' ? 'Jika status dan tenggat masih sesuai, pokok dikreditkan ke pesanan. Keputusan ini final.' : 'Mutasi tidak dikreditkan. Dana belum dikembalikan sampai mutasi keluar aktual dicatat.' }}</p>
<label>Catatan keputusan<textarea required name="catatan" maxlength="300"></textarea></label><button class="btn-primary">{{ $label }} untuk {{ $r->nomor }}</button>@include('partials.ledger-feedback')</form></details>
@endforeach
@elseif($r->alasan==='lewat_batas' && $r->nominal==$r->nominal_diharapkan && $r->status_tagihan==='kedaluwarsa' && $r->status_pesanan==='kedaluwarsa' && in_array($r->jenis,['dp','penuh'],true))
<details class="bk-confirm"><summary>Periksa reaktivasi pembayaran terlambat</summary>
<form method="post" action="{{ route('admin.pesanan.reactivate-expired',$r->pesanan_id) }}" class="bk-product-editor grid gap-3 p-4" data-ledger>@csrf
<input type="hidden" name="mutasi_id" value="{{ $r->id }}"><p>Ini bukan simulasi. Jika pesanan dapat dipenuhi, sistem langsung memesan ulang dan mengkreditkan pembayaran. Jika tidak, dana harus dikembalikan terpisah.</p>
<label><input required type="checkbox"> Saya sudah memeriksa pesanan {{ $r->nomor }} dan referensi {{ $r->referensi }}.</label><button class="btn-primary">Proses reaktivasi</button>@include('partials.ledger-feedback')</form></details>
@elseif(in_array($r->alasan,['unit_tidak_tersedia','buket_tidak_tersedia','selisih_ditolak'],true))<p>Dana belum dikreditkan. Periksa pengembalian penuh sebelum mencatat mutasi keluar.</p>@endif
