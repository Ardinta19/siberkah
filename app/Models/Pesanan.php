<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pesanan - model utama pesanan pembayaran
 */
class Pesanan extends Model
{
    public const CREATED_AT = 'dibuat_pada';
    public const UPDATED_AT = 'diubah_pada';

    protected $table = 'pesanan';

    protected $fillable = [
        'nomor',
        'pelanggan_id',
        'pengguna_id',
        'asal',
        'status',
        'nama_pemesan',
        'nomor_wa_pemesan',
        'cara_serah',
        'alamat_antar',
        'zona_antar_id',
        'tanggal_serah',
        'jam_serah_mulai',
        'jam_serah_selesai',
        'catatan_pelanggan',
        'subtotal',
        'ongkir',
        'penyesuaian',
        'total',
        'jumlah_dibayar',
        'saldo',
        'wajib_dp',
        'tahan_hingga_pada',
        'batas_pelunasan_pada',
        'jenis_jaminan',
        'nominal_jaminan',
        'jaminan_kembali_pada',
        'persen_refund',
        'nominal_refund',
        'alasan_batal',
        'dikonfirmasi_pada',
        'diserahkan_pada',
        'dikembalikan_pada',
        'selesai_pada',
        'dibatalkan_pada',
    ];

    protected $casts = [
        'tanggal_serah' => 'date',
        'tunai_saat_serah' => 'boolean',
        'jam_serah_mulai' => 'datetime:H:i',
        'jam_serah_selesai' => 'datetime:H:i',
        'tahan_hingga_pada' => 'datetime',
        'batas_pelunasan_pada' => 'datetime',
        'jaminan_kembali_pada' => 'datetime',
        'dikonfirmasi_pada' => 'datetime',
        'diserahkan_pada' => 'datetime',
        'dikembalikan_pada' => 'datetime',
        'selesai_pada' => 'datetime',
        'dibatalkan_pada' => 'datetime',
        'subtotal' => 'integer',
        'ongkir' => 'integer',
        'penyesuaian' => 'integer',
        'total' => 'integer',
        'jumlah_dibayar' => 'integer',
        'saldo' => 'integer',
        'nominal_jaminan' => 'integer',
        'nominal_refund' => 'integer',
        'wajib_dp' => 'boolean',
    ];

    /**
     * Get the pelanggan that the pesanan belongs to.
     */
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    /**
     * Get the admin user who created this order (if manual).
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }

    /**
     * Get the payment for the pesanan.
     */
    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'pesanan_id');
    }

    /**
     * Get the items for the pesanan.
     */
    public function snapshotOpsiPerItem(): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('opsi_item_pesanan')
            ->whereIn('item_pesanan_id', $this->items->modelKeys())->orderBy('id')->get()
            ->groupBy('item_pesanan_id');
    }

    /** Customer-safe breakdown of recorded adjustments; no staff notes or identities. */
    public function rincianPenyesuaian(): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('penyesuaian_pesanan')
            ->where('pesanan_id', $this->id)->groupBy('jenis')->orderBy('jenis')
            ->selectRaw('jenis, SUM(nominal) AS jumlah')->get();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemPesanan::class, 'pesanan_id');
    }

    /**
     * Get the history status for the pesanan.
     */
    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusPesanan::class, 'pesanan_id');
    }

    /**
     * Get the zona antar.
     */
    public function zonaAntar(): BelongsTo
    {
        return $this->belongsTo(ZonaAntar::class, 'zona_antar_id');
    }
}
