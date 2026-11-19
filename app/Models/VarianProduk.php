<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\UnitSewa;

class VarianProduk extends Model
{
    protected $table = 'varian_produk';

    protected $fillable = [
        'produk_id',
        'nama',
        'awalan_kode',
        'harga_tambahan',
        'aktif',
    ];

    protected $casts = [
        'produk_id' => 'integer',
        'harga_tambahan' => 'integer',
        'aktif' => 'boolean',
    ];

    /** Catalog estimate only; checkout owns the final reservation. */
    public function scopeTersediaPada(\Illuminate\Database\Eloquent\Builder $query, string $date): \Illuminate\Database\Eloquent\Builder
    {
        if (! \App\Support\RentalSchedule::searchable($date)) { return $query->whereRaw('1 = 0'); }
        return $query->where('varian_produk.aktif', true)
            ->when($date === today()->toDateString(), fn ($q) => $q->whereHas('produk', fn ($p) => $p->where('izin_hari_sama', true)))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('blokir_kalender')->where('tanggal', $date)
                ->where(fn ($b) => $b->whereNull('produk_id')->orWhereColumn('produk_id', 'varian_produk.produk_id')))
            ->whereHas('unitSewa', fn ($q) => $q->where('status', 'aktif')->whereNotIn('unit_sewa.id', \Illuminate\Support\Facades\DB::table('blokir_unit_harian')->select('unit_sewa_id')->where('tanggal', $date))
                ->when($date === today()->toDateString(), fn ($u) => $u->whereNotIn('unit_sewa.id', \Illuminate\Support\Facades\DB::table('penguasaan_unit')->select('unit_sewa_id')))->whereNotExists(fn ($r) => $r->selectRaw('1')->from('hari_reservasi')
                ->whereColumn('hari_reservasi.unit_sewa_id', 'unit_sewa.id')->where('tanggal', $date)));
    }

    /**
     * Produk induk.
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    /**
     * Semua unit sewa untuk varian ini.
     */
    public function unitSewa(): HasMany
    {
        return $this->hasMany(UnitSewa::class, 'varian_produk_id');
    }

    /**
     * Semua item pesanan untuk varian ini.
     */
    public function itemPesanan(): HasMany
    {
        return $this->hasMany(ItemPesanan::class, 'varian_produk_id');
    }

    /**
     * Semua opsi untuk varian ini.
     */
    public function opsiProduk(): HasMany
    {
        return $this->hasMany(OpsiProduk::class, 'produk_id')
            ->where('produk_id', $this->produk->id);
    }

    /**
     * Varian aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Varian yang digunakan.
     */
    public function scopeDigunakan($query)
    {
        return $query->whereHas('itemPesanan');
    }

    /**
     * Label untuk tampilan.
     */
    public function label(): string
    {
        return match ($this->awalan_kode) {
            null => $this->nama,
            default => $this->nama.' ['.$this->awalan_kode.']',
        };
    }
}
