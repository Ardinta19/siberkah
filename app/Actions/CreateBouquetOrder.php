<?php

namespace App\Actions;

use App\Models\Pelanggan;
use App\Support\BusinessSettings;
use App\Models\Pesanan;
use App\Models\Pengguna;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** One order/date, one delivery charge, one invoice for all non-rental lines. */
final class CreateBouquetOrder
{
    public function create(Pelanggan $actor, array $input, ?Pengguna $staff = null): Pesanan
    {
        abort_if($staff && (! $staff->aktif || ! $staff->canVerifyPayment()), 403);
        $d = Validator::make($input, [
            'kunci' => 'required|uuid', 'tanggal' => 'required|date_format:Y-m-d', 'pembayaran' => 'required|in:penuh,dp',
            'cara_serah' => 'required|in:ambil,antar', 'zona_antar_id' => 'required_if:cara_serah,antar|prohibited_if:cara_serah,ambil|integer|min:1',
            'alamat_antar' => 'required_if:cara_serah,antar|prohibited_if:cara_serah,ambil|string|max:500',
            'versi_privasi' => 'required|string|max:40', 'setuju_privasi' => 'accepted', 'items' => 'required|array|list|min:1|max:25',
            'items.*' => 'array:produk_id,varian_id,jumlah,opsi', 'items.*.produk_id' => 'required|integer|min:1', 'items.*.varian_id' => 'sometimes|integer|min:1',
            'items.*.jumlah' => 'required|integer|between:1,100', 'items.*.opsi' => 'sometimes|array|list|max:20',
            'items.*.opsi.*' => 'array:id,nilai_id,teks,satuan', 'items.*.opsi.*.id' => 'required|integer|min:1',
            'items.*.opsi.*.nilai_id' => 'sometimes|integer|min:1', 'items.*.opsi.*.teks' => 'sometimes|required|string|max:300',
            'items.*.opsi.*.satuan' => 'sometimes|integer|between:1,100',
        ])->validate();
        $d['kunci'] = strtolower($d['kunci']);
        if ($staff) {
            $d['operator_id'] = $staff->id;
        }
        $hash = hash('sha256', json_encode($d, JSON_THROW_ON_ERROR));
        return DB::transaction(function () use ($actor, $d, $hash, $staff) {
            $customer = Pelanggan::lockForUpdate()->findOrFail($actor->id);
            abort_unless($customer->isActive() && $customer->isVerified() && filled($customer->nama), 403);
            // Take the month lock before the first consistent read. A checkout that waited
            // for another writer must see its committed reservations and invoice amounts.
            $period = now('Asia/Jakarta')->format('ym');
            app(OrderNumber::class)->lockPeriod($period);
            $prior = DB::table('checkout_buket')->where(['pelanggan_id' => $customer->id, 'kunci' => $d['kunci']])->first();
            if ($prior) {
                abort_unless($prior->sidik === $hash, 409, 'Isi keranjang berbeda untuk kunci yang sama.');
                return Pesanan::findOrFail($prior->pesanan_id);
            }
            abort_unless($staff || $customer->isOnboarded(), 403, 'Lengkapi nama dan persetujuan privasi pada profil.');
            abort_unless($d['versi_privasi'] === config('checkout.privacy_version'), 409);
            $now = now('Asia/Jakarta');
            $date = Carbon::parse($d['tanggal'], 'Asia/Jakarta')->startOfDay();
            abort_if($date->lt($now->copy()->startOfDay()), 422);
            $number = app(OrderNumber::class)->next($period);
            $zone = null;
            if ($d['cara_serah'] === 'antar') {
                $zone = DB::table('zona_antar')->where('id', $d['zona_antar_id'])->where('aktif', true)->where('jarak_maks_km', '<=', 15)->sharedLock()->first();
                abort_unless($zone, 422, 'Zona tidak dilayani.');
            }
            $order = Pesanan::create(['nomor' => $number, 'asal' => $staff ? 'admin' : 'situs', 'pengguna_id' => $staff?->id, 'pelanggan_id' => $customer->id, 'nama_pemesan' => $customer->nama,
                'nomor_wa_pemesan' => $customer->nomor_wa, 'status' => 'menunggu_pembayaran', 'cara_serah' => $d['cara_serah'], 'tanggal_serah' => $date,
                'zona_antar_id' => $zone?->id, 'alamat_antar' => $d['alamat_antar'] ?? null]);
            $custom = $subtotal = 0;
            foreach ($d['items'] as $line) {
                $p = DB::table('produk')->where('id', $line['produk_id'])->where('aktif', true)->whereIn('jenis', ['jual','custom'])->lockForUpdate()->first();
                abort_unless($p && DB::table('kategori')->where('id', $p->kategori_id)->where('aktif', true)->exists(), 409, 'Produk tidak tersedia.');
                abort_if(DB::table('blokir_kalender')->where('tanggal', $d['tanggal'])->where(fn ($q) => $q->whereNull('produk_id')->orWhere('produk_id', $p->id))->exists(), 409, 'Tanggal diblokir.');
                $variant = null;
                if (isset($line['varian_id'])) {
                    $variant = DB::table('varian_produk')->where('produk_id', $p->id)->where('id', $line['varian_id'])->where('aktif', true)->lockForUpdate()->first();
                    abort_unless($variant, 422, 'Varian tidak tersedia.');
                }
                abort_if(! $variant && DB::table('varian_produk')->where('produk_id', $p->id)->exists(), 422, 'Pilih varian aktif.');
                $qty = (int) $line['jumlah'];
                if ($p->jenis === 'jual') {
                    abort_if($date->isSameDay($now) && (! $p->izin_hari_sama || $now->gt($now->copy()->setTime(15, 0))), 409, 'Produk atau jam pemesanan tidak mengizinkan hari yang sama.');
                    abort_unless($variant && $variant->stok >= $qty, 409, 'Stok siap jual tidak mencukupi.');
                    DB::table('varian_produk')->where('id', $variant->id)->decrement('stok', $qty);
                } else {
                    abort_if($date->lt($now->copy()->startOfDay()->addDays($p->lead_custom_hari + ($now->gt($now->copy()->setTime(15, 0)) ? 1 : 0))), 409, 'Waktu produksi tidak cukup.');
                    $custom += $qty;
                }
                $options = $this->options($p->id, $line['opsi'] ?? []);
                $price = ($variant?->harga ?? $p->harga_dasar) + array_sum(array_column($options, 'harga_tambahan'));
                $subtotal += $qty * $price;
                abort_unless($price > 0 && $subtotal <= 2147482648, 422, 'Harga atau jumlah di luar batas.');
                $item = DB::table('item_pesanan')->insertGetId(['pesanan_id' => $order->id, 'produk_id' => $p->id, 'varian_produk_id' => $variant?->id,
                    'nama_produk' => $p->nama, 'nama_varian' => $variant?->nama, 'harga_satuan' => $price, 'jumlah' => $qty, 'subtotal' => $price * $qty,
                    'dibuat_pada' => $now, 'diubah_pada' => $now]);
                foreach ($options as $option) {
                    DB::table('opsi_item_pesanan')->insert($option + ['item_pesanan_id' => $item]);
                }
                if ($p->jenis === 'jual') {
                    DB::table('stok_ditahan')->insert(['item_pesanan_id' => $item, 'varian_produk_id' => $variant->id, 'jumlah' => $qty]);
                }
            }
            $capacity = null;
            if ($custom) {
                $key = ['tanggal' => $d['tanggal'], 'kelompok_kapasitas' => 'buket_custom'];
                DB::table('kapasitas_produksi_harian')->insertOrIgnore($key + ['batas' => 15]);
                $capacity = DB::table('kapasitas_produksi_harian')->where($key)->lockForUpdate()->first();
                abort_if($capacity->terpakai + $custom > min(15, $capacity->batas), 409, 'Kapasitas buket custom tidak mencukupi.');
                DB::table('kapasitas_produksi_harian')->where('id', $capacity->id)->increment('terpakai', $custom);
            }
            $fee = $zone?->ongkir ?? 0;
            $total = intdiv($subtotal + $fee + 999, 1000) * 1000;
            $dp = $d['pembayaran'] === 'dp';
            $due = $date->copy()->subDay()->setTime(18, 0);
            abort_if($total > 2147482648 || ($dp && ($total < BusinessSettings::value('ambang_dp_rupiah') || ! $due->gt($now))), 422, 'Total atau pilihan pembayaran tidak valid.');
            $peak = DB::table('tanggal_puncak')->whereBetween('tanggal', [$now->toDateString(), $now->copy()->addDays(2)->toDateString()])->exists();
            $order->update(['subtotal' => $subtotal, 'ongkir' => $fee, 'total' => $total, 'saldo' => $total, 'wajib_dp' => $dp,
                'tahan_hingga_pada' => $now->copy()->addMinutes(BusinessSettings::value($peak ? 'tahan_puncak_menit' : 'tahan_normal_menit')), 'batas_pelunasan_pada' => $dp ? $due : null]);
            app(IssueRentalInvoice::class)->issue($order->id, $d['pembayaran'], $dp ? intdiv($total, 2) : $total, $now);
            DB::table('riwayat_status_pesanan')->insert(['pesanan_id' => $order->id, 'status_baru' => $order->status, 'kode_transisi' => 'T01', 'pelaku' => $staff ? 'staf' : 'pelanggan', 'pengguna_id' => $staff?->id, 'dibuat_pada' => $now]);
            DB::table('checkout_buket')->insert(['pesanan_id' => $order->id, 'pelanggan_id' => $customer->id, 'kunci' => $d['kunci'], 'sidik' => $hash,
                'versi_privasi' => $d['versi_privasi'], 'disetujui_pada' => $now, 'kapasitas_id' => $capacity?->id, 'jumlah_custom' => $custom]);
            return $order;
        }, 3);
    }

    private function options(int $product, array $choices): array
    {
        $given = collect($choices)->keyBy('id');
        abort_if($given->count() !== count($choices), 422, 'Opsi ganda.');
        $rows = [];
        foreach (DB::table('opsi_produk')->where('produk_id', $product)->orderBy('id')->get() as $option) {
            $choice = $given->pull($option->id);
            if (! $choice) {
                abort_if($option->wajib, 422, 'Opsi wajib belum diisi.');
                continue;
            }
            $row = ['opsi_produk_id' => $option->id, 'nama_opsi' => $option->nama];
            if ($option->tipe === 'teks') {
                abort_unless(isset($choice['teks']) && !isset($choice['nilai_id']) && !isset($choice['satuan']), 422);
                $rows[] = $row + ['label_nilai' => 'Teks', 'teks_bebas' => $choice['teks'], 'harga_tambahan' => 0];
            } else {
                abort_unless(in_array($option->tipe, ['pilihan','satuan'], true) && !isset($choice['teks']) && isset($choice['nilai_id']) && ($option->tipe === 'satuan') === isset($choice['satuan']), 422);
                $value = DB::table('nilai_opsi')->where('opsi_produk_id', $option->id)->where('id', $choice['nilai_id'])->where('aktif', true)->first();
                abort_unless($value, 422, 'Nilai bukan milik opsi ini.');
                $rows[] = $row + ['nilai_opsi_id' => $value->id, 'label_nilai' => $value->label, 'harga_tambahan' => $value->harga_tambahan * ($choice['satuan'] ?? 1),
                    'teks_bebas' => isset($choice['satuan']) ? (string) $choice['satuan'] : null];
            }
        }
        abort_unless($given->isEmpty(), 422, 'Opsi bukan milik produk.');
        return $rows;
    }
}
