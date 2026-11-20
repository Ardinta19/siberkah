<?php

namespace App\Actions;

use App\Models\Pelanggan;
use App\Support\BusinessSettings;
use App\Models\Pesanan;
use App\Models\Pengguna;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** Checkout satu varian sewa standar, satu tanggal kalender, diantar. */
final class CreateRentalOrder
{
    public function create(Pelanggan $actor, array $input, ?Pengguna $operator = null): Pesanan
    {
        abort_if($operator && (! $operator->aktif || ! $operator->canVerifyPayment()), 403);
        $d = Validator::make($input, [
            'kunci' => 'required|uuid', 'produk_id' => 'required|integer|min:1', 'varian_id' => 'required|integer|min:1',
            'jumlah' => 'required|integer|between:1,100', 'tanggal' => 'required|date_format:Y-m-d',
            'alamat_antar' => 'required|string|max:500', 'zona_antar_id' => 'required|integer|min:1',
            'metode_teks' => 'sometimes|required|in:spidol,templat,cutting', 'jam_serah' => 'sometimes|required|date_format:H:i',
            'cara_serah' => 'sometimes|in:antar', 'opsi' => 'prohibited', 'tanggal_selesai' => 'prohibited',
            'pembayaran' => 'required|in:penuh,dp', 'metode_pelunasan' => 'sometimes|required|in:bank,tunai_saat_serah', 'setuju_privasi' => 'accepted', 'versi_privasi' => 'required|string|max:40',
        ])->validate();
        foreach (['produk_id', 'varian_id', 'jumlah', 'zona_antar_id'] as $key) {
            $d[$key] = (int) $d[$key];
        }
        $d['kunci'] = strtolower($d['kunci']);
        $d['cara_serah'] = 'antar';
        $d['setuju_privasi'] = true;
        if ($operator) {
            $d['operator_id'] = $operator->id;
        }
        ksort($d);
        $fingerprint = hash('sha256', json_encode($d, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $d, $fingerprint, $operator) {
            $customer = Pelanggan::lockForUpdate()->findOrFail($actor->id);
            abort_unless($customer->isActive() && $customer->isVerified(), 403);
            // Take the month lock before the first consistent read. A checkout that waited
            // for another writer must see its committed reservations and invoice amounts.
            $period = now('Asia/Jakarta')->format('ym');
            app(OrderNumber::class)->lockPeriod($period);
            $receipt = DB::table('checkout_sewa')->where('pelanggan_id', $customer->id)->where('kunci', $d['kunci'])->first();
            if ($receipt) {
                $this->check($receipt->sidik === $fingerprint, 'Kunci checkout sudah digunakan dengan isi berbeda.');
                return Pesanan::findOrFail($receipt->pesanan_id);
            }
            abort_unless($operator || $customer->isOnboarded(), 403, 'Lengkapi nama dan persetujuan privasi pada profil.');
            $this->check(filled($customer->nama), 'Lengkapi nama pemesan.');
            $this->check($d['versi_privasi'] === config('checkout.privacy_version'), 'Periksa versi kebijakan privasi.');
            $now = CarbonImmutable::now('Asia/Jakarta');
            $date = CarbonImmutable::parse($d['tanggal'], 'Asia/Jakarta')->startOfDay();
            $this->check(\App\Support\RentalSchedule::permits($d['tanggal'], $d['metode_teks'] ?? null, $d['jam_serah'] ?? null, $now), 'Tanggal sewa terlalu dekat atau jadwal serah tidak sesuai.');
            $number = app(OrderNumber::class)->next($period);
            $product = DB::table('produk')->where('id', $d['produk_id'])->where('aktif', true)->where('jenis', 'sewa')->lockForUpdate()->first();
            $this->check($product && DB::table('kategori')->where('id', $product->kategori_id)->where('aktif', true)->exists(), 'Produk sewa tidak tersedia.');
            $this->check(! $date->isSameDay($now) || $product->izin_hari_sama, 'Produk ini tidak melayani hari yang sama.');
            $this->check(! DB::table('opsi_produk')->where('produk_id', $product->id)->exists(), 'Produk dengan opsi memerlukan checkout khusus.');
            $variant = DB::table('varian_produk')->where('id', $d['varian_id'])->where('produk_id', $product->id)->where('aktif', true)->lockForUpdate()->first();
            $this->check($variant && $variant->harga > 0, 'Varian atau tarif tidak tersedia.');
            $zone = DB::table('zona_antar')->where('id', $d['zona_antar_id'])->where('aktif', true)->where('jarak_maks_km', '<=', 15)->sharedLock()->first();
            $this->check((bool) $zone, 'Zona antar tidak dilayani.');
            $blocked = DB::table('blokir_kalender')->where('tanggal', $d['tanggal'])->where(fn ($q) => $q->whereNull('produk_id')->orWhere('produk_id', $product->id))->exists();
            $this->check(! $blocked, 'Tanggal ini diblokir.');
            $units = DB::table('unit_sewa')->where('varian_produk_id', $variant->id)->where('status', 'aktif')
                ->whereNotIn('id', DB::table('blokir_unit_harian')->select('unit_sewa_id')->where('tanggal', $d['tanggal']))
                ->when($date->isSameDay($now), fn ($q) => $q->whereNotIn('id', DB::table('penguasaan_unit')->select('unit_sewa_id')))
                ->whereNotIn('id', DB::table('hari_reservasi')->select('unit_sewa_id')->where('tanggal', $d['tanggal']))
                ->orderBy('id')->limit($d['jumlah'])->lockForUpdate()->pluck('id');
            $this->check($units->count() === $d['jumlah'], 'Unit pada tanggal ini tidak mencukupi.');

            $subtotal = $variant->harga * $d['jumlah'];
            $total = intdiv($subtotal + $zone->ongkir + 999, 1000) * 1000;
            $this->check($total <= 2147482648, 'Total melampaui batas transaksi.');
            $dp = $d['pembayaran'] === 'dp';
            $due = $date->subDay()->setTime(18, 0);
            $this->check(! $dp || ($total >= BusinessSettings::value('ambang_dp_rupiah') && $due->gt($now)), 'Pilihan DP tidak tersedia.');
            $peak = DB::table('tanggal_puncak')->whereBetween('tanggal', [$now->toDateString(), $now->addDays(2)->toDateString()])->exists();
            $holdUntil = $now->addMinutes(BusinessSettings::value($peak ? 'tahan_puncak_menit' : 'tahan_normal_menit'));
            if (isset($d['jam_serah'])) {
                $holdUntil = $holdUntil->min($date->setTimeFromTimeString($d['jam_serah'])->subHours(2));
            }
            $order = Pesanan::create(['nomor' => $number, 'status' => 'menunggu_pembayaran', 'pelanggan_id' => $customer->id,
                'asal' => $operator ? 'admin' : 'situs', 'pengguna_id' => $operator?->id, 'nama_pemesan' => $customer->nama, 'nomor_wa_pemesan' => $customer->nomor_wa,
                'cara_serah' => 'antar', 'alamat_antar' => $d['alamat_antar'], 'zona_antar_id' => $zone->id, 'tanggal_serah' => $d['tanggal'],
                'subtotal' => $subtotal, 'ongkir' => $zone->ongkir, 'total' => $total, 'saldo' => $total, 'wajib_dp' => $dp,
                'tahan_hingga_pada' => $holdUntil, 'batas_pelunasan_pada' => $dp ? $due : null]);
            if (isset($d['metode_teks'])) {
                DB::table('desain_papan')->insert(['pesanan_id' => $order->id, 'metode' => $d['metode_teks'],
                    'jadwal_serah' => isset($d['jam_serah']) ? $date->setTimeFromTimeString($d['jam_serah']) : null]);
            }
            foreach ($units as $unit) {
                $item = DB::table('item_pesanan')->insertGetId(['pesanan_id' => $order->id, 'produk_id' => $product->id, 'varian_produk_id' => $variant->id,
                    'unit_sewa_id' => $unit, 'nama_produk' => $product->nama, 'nama_varian' => $variant->nama, 'harga_satuan' => $variant->harga,
                    'jumlah' => 1, 'subtotal' => $variant->harga, 'tanggal_mulai' => $d['tanggal'], 'tanggal_selesai' => $d['tanggal'], 'dibuat_pada' => $now, 'diubah_pada' => $now]);
                DB::table('hari_reservasi')->insert(['pesanan_id' => $order->id, 'item_pesanan_id' => $item, 'unit_sewa_id' => $unit, 'tanggal' => $d['tanggal'], 'dibuat_pada' => $now]);
            }
            $cash = ($d['metode_pelunasan'] ?? 'bank') === 'tunai_saat_serah';
            if (! $cash || $dp) {
                app(IssueRentalInvoice::class)->issue($order->id, $d['pembayaran'], $dp ? intdiv($total, 2) : $total, $now);
            }
            DB::table('riwayat_status_pesanan')->insert(['pesanan_id' => $order->id, 'status_baru' => $order->status, 'kode_transisi' => 'T01', 'pelaku' => $operator ? 'staf' : 'pelanggan', 'pengguna_id' => $operator?->id, 'dibuat_pada' => $now]);
            if ($cash) {
                app(HandoverCash::class)->arrange($order, $dp);
            }
            DB::table('checkout_sewa')->insert(['denda_per_jam' => BusinessSettings::value('denda_sewa_per_jam'), 'pesanan_id' => $order->id, 'pelanggan_id' => $customer->id, 'kunci' => $d['kunci'],
                'sidik' => $fingerprint, 'versi_privasi' => $d['versi_privasi'], 'disetujui_pada' => $now]);
            return $order;
        }, 3);
    }

    private function check(bool $ok, string $message): void
    {
        if (! $ok) {
            throw new DomainException($message);
        }
    }
}
