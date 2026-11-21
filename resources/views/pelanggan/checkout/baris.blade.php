        @if($needsVariant)<label>Varian <select data-line-variant name="{{ $name('varian_id') }}" required>@foreach($variants as $v)<option value="{{ $v->id }}" data-price="{{ $v->harga }}" @selected(old($oldPrefix.'varian_id') == $v->id)>{{ $v->nama }} · Rp{{ number_format($v->harga,0,',','.') }}</option>@endforeach</select></label>@endif
        @include('partials.quantity',['itemId'=>'checkout-'.$product->id,'quantityName'=>$name('jumlah'),'quantity'=>old($oldPrefix.'jumlah',!$manualCustomer && request()->boolean('dari_keranjang') ? session('cart.'.$product->id,1) : 1)])
        @foreach($options as $o)
            <label>{{ $o->nama }} @if($o->wajib)(wajib)@endif
            @if($o->tipe === 'teks')<input maxlength="300" name="{{ $name('opsi') }}[{{ $o->id }}][teks]" value="{{ old($oldPrefix.'opsi.'.$o->id.'.teks') }}" @required($o->wajib)>
            @else
                <select data-extra="{{ $o->id }}" name="{{ $name('opsi') }}[{{ $o->id }}][nilai_id]" @required($o->wajib)><option value="">{{ $o->wajib ? 'Pilih opsi' : 'Tanpa tambahan' }}</option>@foreach($o->nilaiOpsi as $v)<option value="{{ $v->id }}" data-price="{{ $v->harga_tambahan }}" @selected(old($oldPrefix.'opsi.'.$o->id.'.nilai_id') == $v->id)>{{ $v->label }} (+Rp{{ number_format($v->harga_tambahan,0,',','.') }})</option>@endforeach</select>
            @endif</label>
            @if($o->tipe === 'satuan')<label>Jumlah {{ $o->nama }} per produk <input type="number" name="{{ $name('opsi') }}[{{ $o->id }}][satuan]" min="1" max="100" value="{{ old($oldPrefix.'opsi.'.$o->id.'.satuan',1) }}"></label>@endif
        @endforeach
