<?php

namespace App\Console\Commands;

use App\Models\Pesanan;
use App\Support\BusinessSettings;
use App\Support\InspectionPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ReviewPrivateEvidence extends Command
{
    protected $signature = 'privasi:bukti {nomor} {--hapus : Hapus hanya bukti privat yang lolos pemeriksaan retensi}';
    protected $description = 'Tinjau bukti satu pesanan tertutup; tanpa opsi --hapus tidak ada perubahan';

    public function handle(): int
    {
        return DB::transaction(function () {
            $order = Pesanan::where('nomor', $this->argument('nomor'))->lockForUpdate()->first();
            $closed = match ($order?->status) { 'selesai' => $order->selesai_pada, 'dibatalkan' => $order->dibatalkan_pada, 'kedaluwarsa' => $order->tahan_hingga_pada, default => null };
            $cutoff = now('Asia/Jakarta')->startOfDay()->subYearsNoOverflow(BusinessSettings::value('retensi_transaksi_tahun'));
            if (! $closed || ! $closed->lt($cutoff)) { $this->error('Bukan pesanan tertutup di luar retensi.'); return 1; }
            $bills = DB::table('pembayaran')->where('pesanan_id',$order->id);
            $unsettled = $order->cara_serah === 'antar' && (clone $bills)->where('tunai_saat_serah',true)->where('status','terverifikasi')
                ->whereRaw('nominal_diterima <> COALESCE((SELECT s.sesudah FROM setoran_kas_antar s WHERE s.pembayaran_id=pembayaran.id ORDER BY s.id DESC LIMIT 1),0)')->exists();
            if ($unsettled || ($order->status === 'selesai' && $order->saldo != 0) || (clone $bills)->where('status','menunggu')->exists()
                || DB::table('mutasi_masuk')->whereIn('pembayaran_id',(clone $bills)->select('id'))->where('hasil','perlu_rekonsiliasi')->exists()
                || DB::table('permohonan_batal')->where(['pesanan_id'=>$order->id,'status'=>'menunggu'])->exists()) {
                $this->error('Kewajiban atau rekonsiliasi masih terbuka.'); return 1;
            }
            $proofs = DB::table('bukti_transfer')->whereIn('pembayaran_id',(clone $bills)->select('id'));
            $inspections = DB::table('inspeksi_sewa')->whereIn('item_pesanan_id',DB::table('item_pesanan')->where('pesanan_id',$order->id)->select('id'));
            $photos = DB::table('foto_inspeksi')->whereIn('inspeksi_sewa_id',(clone $inspections)->select('id'));
            $files = (clone $photos)->get();
            $this->line('Bukti transfer: '.$proofs->count().'; foto inspeksi: '.$files->count().'.');
            if (! $this->option('hapus')) { return 0; }
            foreach ($files as $f) {
                if (! InspectionPath::valid($f->inspeksi_sewa_id, $f->jalur_berkas)) { $this->error('Metadata foto perlu ditinjau.'); return 1; }
            }
            foreach ($files as $f) {
                $disk = Storage::disk('local');
                if ($disk->exists($f->jalur_berkas) && ! $disk->delete($f->jalur_berkas)) {
                    throw new \RuntimeException('Penghapusan berkas belum selesai. Periksa disk sebelum mengulang.');
                }
            }
            $counts = ['bukti_transfer'=>$proofs->delete(),'foto_inspeksi'=>$photos->delete(),'catatan_inspeksi'=>(clone $inspections)->whereNotNull('catatan')->update(['catatan'=>null])];
            if (array_sum($counts)) {
                DB::table('log_aktivitas')->insert(['aksi'=>'retensi_bukti_cli','subjek_tabel'=>'pesanan','subjek_id'=>$order->id,'sebelum'=>json_encode($counts),'sesudah'=>json_encode(array_fill_keys(array_keys($counts),0)),'dibuat_pada'=>now()]);
            }
            return 0;
        });
    }
}
