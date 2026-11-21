<section><h4>Teks papan · {{ ucfirst($p->papan->metode) }}</h4><dl>@foreach(['judul','penerima','pengirim'] as $field)<div><dt>{{ ucfirst($field) }}</dt><dd>{{ $p->papan->$field ?? 'Belum diisi' }}</dd></div>@endforeach</dl>
@if($p->status==='dikonfirmasi' && config('checkout.staging') && app()->environment('testing','staging','local'))
@php($retry=old('asal')==='antrean-produksi' && old('baris')==$p->id)
<details class="bk-confirm" @if($retry && $errors->any()) open @endif><summary>Sunting teks papan {{ $p->nomor }}</summary>
<form data-pending="Menyimpan teks…" method="post" action="{{ route('papan.edit',$p->id) }}" class="bk-product-editor grid gap-3 p-4">@csrf @method('put')
<input type="hidden" name="asal" value="antrean-produksi"><input type="hidden" name="baris" value="{{ $p->id }}"><input type="hidden" name="versi" value="{{ $retry ? old('versi') : \App\Support\BoardTextVersion::of($p->papan) }}">
@foreach(['judul'=>25,'penerima'=>45,'pengirim'=>40] as $field=>$limit)<label>{{ ucfirst($field) }} (maks. {{ $limit }} karakter)<input name="{{ $field }}" maxlength="{{ $limit }}" required value="{{ $retry ? old($field) : $p->papan->$field }}"></label>@endforeach
<p>Periksa ejaan sebelum mulai produksi. Setelah produksi dimulai, teks terkunci.</p><button class="btn-primary">Simpan teks papan</button></form></details>
@elseif($p->status==='sedang_diproduksi')<p>Teks terkunci sejak produksi dimulai.</p>@endif</section>
