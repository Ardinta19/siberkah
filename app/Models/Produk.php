<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produk extends Model
{
    public const CREATED_AT = 'dibuat_pada';
    public const UPDATED_AT = 'diubah_pada';

    protected $table = 'produk';

    protected $fillable = [
        'kategori_id',
        'nama',
        'slug',
        'jenis',
        'kelompok_kapasitas',
        'deskripsi',
        'harga_dasar',
        'lead_custom_hari',
        'izin_hari_sama',
        'foto_utama',
        'aktif',
    ];

    protected $casts = [
        'kategori_id' => 'integer',
        'harga_dasar' => 'integer',
        'aktif' => 'boolean',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class);
    }

    /**
     * Semua varian produk dalam kategori ini.
     */
    public function varianProduk(): HasMany
    {
        return $this->hasMany(VarianProduk::class, 'produk_id');
    }

    /**
     * Semua opsi produk.
     */
    public function opsiProduk(): HasMany
    {
        return $this->hasMany(OpsiProduk::class, 'produk_id');
    }

    /**
     * Semua nilai opsi.
     */
    public function nilaiOpsi(): HasMany
    {
        return $this->hasMany(NilaiOpsi::class, 'produk_id');
    }

    /**
     * Semua gambar produk.
     */
    public function gambarProduk(): HasMany
    {
        return $this->hasMany(GambarProduk::class, 'produk_id');
    }

    /**
     * Semua unit sewa untuk produk ini.
     */
    public function unitSewa(): HasManyThrough
    {
        return $this->hasManyThrough(UnitSewa::class, VarianProduk::class, 'produk_id', 'varian_produk_id');
    }

    /** Date-specific catalog estimate; checkout must still lock and reserve the units. */
    public function scopeDenganHargaTampil(Builder $query, ?string $date = null): Builder
    {
        $variantPrice = \App\Models\VarianProduk::selectRaw('min(harga)')->whereColumn('produk_id','produk.id')->where('aktif',true)
            ->where('harga','>',0)->where(fn ($q) => $q->where('produk.jenis','!=','jual')->orWhere('stok','>',0))
            ->when($date, fn ($q) => $q->tersediaPada($date));
        return $query->select('produk.*')->selectRaw("coalesce(({$variantPrice->toSql()}), case when produk.jenis = 'custom' and not exists (select 1 from varian_produk v where v.produk_id = produk.id) then nullif(produk.harga_dasar,0) end) as harga_tampil", $variantPrice->getBindings());
    }

    public function scopeBisaDisewaPada(Builder $query, string $date): Builder
    {
        return $query->where('jenis', 'sewa')->whereHas('varianProduk', fn ($q) => $q->tersediaPada($date));
    }

    /**
     * Semua item pesanan yang menggunakan produk ini.
     */
    public function itemPesanan(): HasMany
    {
        return $this->hasMany(ItemPesanan::class, 'produk_id');
    }

    /**
     * Produk aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Produk yang sedang dipesan (status Menunggu Pembayaran atau Dikonfirmasi).
     */
    public function scopeSedangDipesan($query)
    {
        return $query->where('aktif', true)->whereNotNull('harga_dasar');
    }

    /**
     * Produk yang sudah berubah/diubah.
     */
    public function scopeDiubah($query)
    {
        return $query->latest('diubah_pana');
    }

    /**
     * Produk custom.
     */
    public function scopeCustom($query)
    {
        return $query->where('jenis', 'custom');
    }

    /**
     * Produk sewa.
     */
    public function scopeSewa($query)
    {
        return $query->where('jenis', 'sewa');
    }

    /**
     * Produk siap jual.
     */
    public function scopeJual($query)
    {
        return $query->where('jenis', 'jual');
    }

    /**
     * Label untuk tampilan.
     */
    public function label(): string
    {
        return match ($this->jenis) {
            'sewa' => 'Disewakan',
            'jual' => 'Dijual',
            'custom' => 'Custom',
            default => $this->nama,
        };
    }

    /**
     * Cek apakah produk ini butuh lead time.
     */
    public function butuhLeadTime(): bool
    {
        return ! $this->jenis === 'jual';
    }

    /**
     * Cek apakah produk ini wajib punya kelompok kapasitas.
     */
    public function butuhKelompokKapasitas(): bool
    {
        return ! empty($this->kelompok_kapasitas);
    }
}
