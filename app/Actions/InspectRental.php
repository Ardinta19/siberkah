<?php

namespace App\Actions;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class InspectRental
{
    public function handle(Request $request, bool $return): array
    {
        $rules = ['pesanan_id' => 'required|integer|min:1', 'inspeksi' => 'required|array|list|min:1|max:100',
            'inspeksi.*' => 'array:item_id,kondisi,catatan,cek,foto', 'inspeksi.*.item_id' => 'required|integer|distinct',
            'inspeksi.*.kondisi' => $return ? 'required|in:baik,cacat_ringan,rusak' : 'required|in:baik,cacat_ringan',
            'inspeksi.*.catatan' => 'nullable|string|max:300', 'inspeksi.*.cek' => 'required|array:permukaan,easel,bunga,led|required_array_keys:permukaan,easel,bunga,led',
            'inspeksi.*.cek.*' => 'required|in:baik,cacat,tidak_ada', 'inspeksi.*.foto' => 'sometimes|array|list|min:1|max:6',
            'inspeksi.*.foto.*' => 'required|image|mimes:jpeg,png,webp|max:3072'];
        if (! $return) {
            $rules += ['jenis_jaminan' => 'required|in:ktp,ktm,deposit', 'nominal_jaminan' => 'required_if:jenis_jaminan,deposit|prohibited_unless:jenis_jaminan,deposit|integer|between:100000,150000'];
        }
        $data = $request->validate($rules);
        $photos = array_sum(array_map(fn ($row) => count($row['foto'] ?? []), $data['inspeksi']));
        abort_if($photos > 6 || $photos < 1, 422, 'Sertakan 1–6 foto kondisi barang per tahap, bukan foto identitas.');
        $written = [];
        try {
            // No automatic DB retry around filesystem side effects.
            return DB::transaction(function () use ($request, $return, $data, &$written) {
                $order = Pesanan::lockForUpdate()->findOrFail($data['pesanan_id']);
                abort_unless(DB::table('checkout_sewa')->where('pesanan_id', $order->id)->exists(), 404);
                $from = $return ? 'sudah_diserahkan' : 'siap';
                abort_unless($order->status === $from, 409, 'Tahap inspeksi tidak sesuai status pesanan.');
                $now = now('Asia/Jakarta');
                if (! $return) {
                    abort_if(DB::table('permohonan_batal')->where('pesanan_id', $order->id)->where('status', 'menunggu')->exists(), 409, 'Putuskan permohonan pembatalan sebelum serah.');
                    abort_unless($order->saldo === 0 && $order->jumlah_dibayar === $order->total && $order->tanggal_serah->isSameDay($now), 409, 'Serah hanya pada hari acara setelah lunas.');
                }
                $items = $order->items()->orderBy('unit_sewa_id')->get();
                $rows = collect($data['inspeksi'])->keyBy('item_id');
                abort_unless($items->count() === $rows->count() && $items->every(fn ($i) => $i->unit_sewa_id && $rows->has($i->id)), 422, 'Periksa seluruh unit milik pesanan ini.');
                $late = 0;
                foreach ($items as $item) {
                    $row = $rows[$item->id];
                    abort_if($row['kondisi'] !== 'baik' && (blank($row['catatan'] ?? null) || empty($row['foto'])), 422, 'Kondisi cacat/rusak memerlukan foto dan catatan.');
                    abort_if(in_array('cacat', $row['cek'], true) && $row['kondisi'] === 'baik', 422, 'Kondisi harus sesuai daftar periksa.');
                    $unit = DB::table('unit_sewa')->where('id', $item->unit_sewa_id)->lockForUpdate()->first();
                    $custody = DB::table('penguasaan_unit')->where('unit_sewa_id', $unit->id);
                    if ($return) {
                        abort_unless($custody->where('pesanan_id', $order->id)->delete() === 1, 409, 'Unit bukan dalam penguasaan pesanan ini.');
                        if ($row['kondisi'] !== 'baik') {
                            DB::table('unit_sewa')->where('id', $unit->id)->update(['status' => $row['kondisi'] === 'rusak' ? 'rusak' : 'perawatan', 'diubah_pada' => $now]);
                        }
                        $end = $order->tanggal_serah->copy()->setTime(18, 0);
                        $seconds = max(0, $now->getTimestamp() - $end->getTimestamp());
                        $late += min($item->harga_satuan, intdiv($seconds + 3599, 3600) * 25000);
                    } else {
                        abort_unless($unit->status === 'aktif' && ! $custody->exists() && DB::table('hari_reservasi')->where(['pesanan_id' => $order->id, 'item_pesanan_id' => $item->id, 'unit_sewa_id' => $unit->id, 'tanggal' => $now->toDateString()])->exists(), 409, 'Unit tidak dapat diserahkan.');
                        DB::table('penguasaan_unit')->insert(['unit_sewa_id' => $unit->id, 'pesanan_id' => $order->id]);
                    }
                    $inspection = DB::table('inspeksi_sewa')->insertGetId(['item_pesanan_id' => $item->id, 'tahap' => $return ? 'kembali' : 'serah',
                        'pengguna_id' => $request->user('staff')->id, 'dilakukan_pada' => $now, 'dibuat_pada' => $now, 'kondisi' => $row['kondisi'],
                        'catatan' => $row['catatan'] ?? null, 'daftar_periksa' => json_encode($row['cek'], JSON_THROW_ON_ERROR)]);
                    foreach ($row['foto'] ?? [] as $n => $file) {
                        $path = $file->store('inspeksi/'.$inspection, 'local');
                        abort_unless($path, 503, 'Penyimpanan bukti gagal.');
                        $written[] = $path;
                        DB::table('foto_inspeksi')->insert(['inspeksi_sewa_id' => $inspection, 'urutan' => $n + 1, 'jalur_berkas' => $path, 'dibuat_pada' => $now]);
                    }
                }
                if ($late) {
                    DB::table('penyesuaian_pesanan')->insert(['pesanan_id' => $order->id, 'jenis' => 'denda_telat', 'nominal' => $late,
                        'keterangan' => 'Terlambat setelah pukul 18.00 WIB, dibatasi tarif harian tiap unit.', 'pengguna_id' => $request->user('staff')->id, 'dibuat_pada' => $now]);
                }
                $to = $return ? 'sudah_dikembalikan' : 'sudah_diserahkan';
                $changes = $return ? ['dikembalikan_pada' => $now, 'penyesuaian' => $order->penyesuaian + $late, 'saldo' => $order->saldo + $late, 'total' => $order->total + $late]
                    : ['diserahkan_pada' => $now, 'jenis_jaminan' => $data['jenis_jaminan'], 'nominal_jaminan' => $data['nominal_jaminan'] ?? null];
                $order->update(['status' => $to] + $changes);
                DB::table('riwayat_status_pesanan')->insert(['pesanan_id' => $order->id, 'status_lama' => $from, 'status_baru' => $to,
                    'kode_transisi' => $return ? 'T09' : 'T08', 'pelaku' => 'staf', 'pengguna_id' => $request->user('staff')->id, 'dibuat_pada' => $now]);
                return ['status' => $to, 'saldo' => $order->saldo];
            });
        } catch (Throwable $error) {
            Storage::disk('local')->delete($written);
            throw $error;
        }
    }
}
