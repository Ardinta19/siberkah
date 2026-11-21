@extends('layouts.admin')
@section('title','Galeri produk · SIBERKAH')
@section('content')
<section class="bk-owner-list bk-media-manager">
    <x-back-link href="{{ route('admin.katalog.index',$context) }}#kelola-{{ $product->id }}">Kembali ke kelola katalog</x-back-link>
    <header><p class="kc-kicker">Owner · Galeri</p><h1>Foto {{ $product->nama }}</h1><p>{{ $photos->count() }} dari 50 foto galeri. Mengelola foto tidak mengubah status publikasi produk.</p></header>
    @if(session('success'))<p class="bk-notice" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<ul class="bk-notice" role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    @if($photos->count()<50)
        <form class="bk-task grid gap-3" data-pending="Mengunggah foto…" method="post" enctype="multipart/form-data" action="{{ route('admin.katalog.manage-images',['id'=>$product->id]+$context) }}">@csrf
            <input type="hidden" name="asal" value="galeri"><h2>Tambah foto</h2>
            <label>Berkas foto <input required type="file" name="foto" accept="image/jpeg,image/png,image/webp" aria-describedby="photo-help photo-inspection" data-photo-file></label>
            <small id="photo-help">JPG, PNG, atau WebP, maksimal 5 MB. Lebar minimal 800 piksel, maksimal 8000 piksel per sisi dan 12 megapiksel.</small>
            <figure hidden class="bk-media-proof" data-file-preview><img alt="Pratinjau foto pilihan sebelum diunggah"></figure>
            <p id="photo-inspection" role="status" data-file-status></p>
            <button hidden type="button" class="bk-secondary" data-clear-file>Batalkan pilihan foto</button>
            <label>Deskripsi foto <input required maxlength="255" name="teks_alternatif" value="{{ old('teks_alternatif') }}" placeholder="Contoh: Buket mawar putih dengan pita merah"></label>
            <label>Varian <select name="varian_produk_id"><option value="">Foto umum produk</option>@foreach($product->varianProduk as $v)<option value="{{ $v->id }}" @selected(old('varian_produk_id')==$v->id)>{{ $v->nama }}</option>@endforeach</select></label>
            <button class="btn-primary">Unggah foto</button>
        </form>
    @else<p>Galeri penuh. Hapus foto yang tidak diperlukan sebelum mengunggah foto baru.</p>@endif
    <h2>Urutan galeri</h2><p>Setelah urutan disimpan, foto pertama menjadi foto utama.</p>
    <form id="media-order" method="post" data-pending="Menyimpan urutan…" action="{{ route('admin.katalog.images.order',['id'=>$product->id]+$context) }}">@csrf @method('put')<input type="hidden" name="asal" value="galeri"></form>
    <ol data-media-list>
        @forelse($photos as $photo)<li class="bk-task" data-media-row>
            <input form="media-order" type="hidden" name="urutan[]" value="{{ $photo->id }}">
            <img loading="lazy" width="80" height="80" src="{{ asset('storage/'.$photo->jalur_berkas) }}" alt="{{ $photo->teks_alternatif ?: $product->nama }}">
            <strong>{{ $photo->teks_alternatif ?: $product->nama }}</strong>
            @if($product->foto_utama===$photo->jalur_berkas)<small>Foto utama tersimpan</small>@endif
            <div hidden data-move-tools><button type="button" data-move="-1">Naik</button> <button type="button" data-move="1">Turun</button></div>
            <details class="bk-confirm"><summary>Hapus foto</summary><p>Hapus foto “{{ $photo->teks_alternatif ?: $product->nama }}” dari produk ini?</p><form data-pending="Menghapus foto…" method="post" action="{{ route('admin.katalog.images.delete',['id'=>$product->id,'foto'=>$photo->id]+$context) }}">@csrf @method('delete')<input type="hidden" name="asal" value="galeri"><button>Ya, hapus foto ini</button></form></details>
        </li>@empty<li>Belum ada foto galeri.</li>@endforelse
    </ol>
    <p role="status" data-order-note></p><button hidden type="button" data-reset-order class="bk-secondary">Batalkan perubahan urutan</button> <button hidden form="media-order" data-save-order class="btn-primary">Simpan urutan</button>
</section>
@endsection
