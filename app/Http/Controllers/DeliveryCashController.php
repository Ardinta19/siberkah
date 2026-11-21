<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Custody of cash after delivery, NOT a new customer payment or bank mutation. */
final class DeliveryCashController extends Controller
{
    private function receipts()
    {
        return DB::table('pembayaran as b')->join('pesanan as p', 'p.id', '=', 'b.pesanan_id')
            ->where('b.tunai_saat_serah', true)->where('b.status', 'terverifikasi')->whereIn('b.jenis', ['penuh','pelunasan'])
            ->where('p.cara_serah', 'antar')->whereNotNull('b.pengguna_id')->where('b.nominal_diterima', '>', 0);
    }

    public function index(Request $request)
    {
        $filters = $request->validate(['tanggal' => 'required|date_format:Y-m-d', 'petugas_id' => 'sometimes|integer|min:1']);
        $start = \Carbon\CarbonImmutable::parse($filters['tanggal'], 'Asia/Jakarta')->startOfDay();
        $latest = DB::table('setoran_kas_antar')->groupBy('pembayaran_id')->selectRaw('pembayaran_id, MAX(id) AS terakhir');
        $base = $this->receipts()->leftJoinSub($latest, 'l', 'l.pembayaran_id', '=', 'b.id')->leftJoin('setoran_kas_antar as s', 's.id', '=', 'l.terakhir')
            ->where('b.diterima_pada', '>=', $start)->where('b.diterima_pada', '<', $start->addDay())
            ->when(isset($filters['petugas_id']), fn ($q) => $q->where('b.pengguna_id',  $filters['petugas_id']));
        $summary = (clone $base)->groupBy('b.pengguna_id')->orderBy('b.pengguna_id')->selectRaw('b.pengguna_id AS petugas_id, COUNT(*) AS pembayaran, SUM(b.nominal_diterima) AS tagihan_setor, SUM(COALESCE(s.sesudah,0)) AS disetor, SUM(GREATEST(CAST(b.nominal_diterima AS SIGNED)-CAST(COALESCE(s.sesudah,0) AS SIGNED),0)) AS kurang, SUM(GREATEST(CAST(COALESCE(s.sesudah,0) AS SIGNED)-CAST(b.nominal_diterima AS SIGNED),0)) AS lebih')->get();
        $rows = $base->orderBy('b.id')->paginate(50, ['b.id as pembayaran_id','p.nomor','b.pengguna_id as petugas_id','b.diterima_pada','b.nominal_diterima as tagihan_setor',
                DB::raw('COALESCE(s.sesudah,0) AS sudah_disetor'), DB::raw("CASE WHEN s.id IS NULL THEN 'belum_disetor' WHEN s.sesudah = b.nominal_diterima THEN 'cocok' WHEN s.sesudah < b.nominal_diterima THEN 'kurang' ELSE 'lebih' END AS status_setoran")])->withQueryString();
        $data = ['ringkasan' => $summary, 'rincian' => $rows];
        if ($request->expectsJson()) {
            return response()->json($data)->header('Cache-Control', 'private, no-store');
        }
        $queue = DB::table('pembayaran as b')->join('pesanan as p', 'p.id', '=', 'b.pesanan_id')
            ->leftJoin('bukti_transfer as bt','bt.pembayaran_id','=','b.id')
            ->join('rekening_pembayaran as r', 'r.id', '=', 'b.rekening_pembayaran_id')
            ->where('b.status', 'menunggu')->whereNotNull('b.dikonfirmasi_pelanggan_pada')->where('b.tunai_saat_serah', false)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('mutasi_masuk as m')->whereColumn('m.pembayaran_id', 'b.id')->where('m.hasil', 'perlu_rekonsiliasi'))
            ->orderBy('b.dikonfirmasi_pelanggan_pada')->orderBy('b.id')->paginate(25, ['bt.pembayaran_id as bukti_id','p.nomor','b.id','b.pesanan_id','b.jenis','b.nominal_diharapkan','r.nama_bank','r.nomor_rekening','r.atas_nama'], 'antrean')->withQueryString();
        return response()->view('admin.keuangan.kas-antar', $data + ['tanggal' => $filters['tanggal'], 'queue' => $queue])->header('Cache-Control', 'private, no-store');
    }

    public function proof(int $id, int $bill)
    {
        $encrypted = DB::table('bukti_transfer as bt')->join('pembayaran as b','b.id','=','bt.pembayaran_id')
            ->where(['b.id' => $bill, 'b.pesanan_id' => $id])->value('bt.gambar_terenkripsi');
        abort_unless($encrypted, 404);
        $bytes = base64_decode(\Illuminate\Support\Facades\Crypt::decryptString($encrypted), true);
        abort_if($bytes === false, 500);
        return response($bytes)->withHeaders(['Content-Type'=>'image/webp','Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }

    public function history(Request $request, int $id)
    {
        $receipt = $this->receipts()->where('b.id', $id)->first(['p.nomor', 'b.pengguna_id', 'b.diterima_pada', 'b.nominal_diterima']);
        abort_unless($receipt, 404);
        $ledger = DB::table('setoran_kas_antar')->where('pembayaran_id', $id);
        if ($request->expectsJson() || $request->acceptsAnyContentType() || $request->getPreferredFormat() !== 'html') {
            return response()->json($ledger->orderBy('id')->paginate(50))->header('Cache-Control', 'private, no-store');
        }
        $context = $request->validate(['petugas_id'=>'sometimes|integer|min:1','halaman_setoran'=>'sometimes|integer|min:1','antrean'=>'sometimes|integer|min:1']);
        $entries = $ledger->orderByDesc('id')->paginate(50, ['kasir_id','sebelum','sesudah','dicatat_pada','catatan'])->appends($context);
        $back = ['tanggal'=>substr($receipt->diterima_pada,0,10),'page'=>$context['halaman_setoran'] ?? 1] + array_intersect_key($context,array_flip(['antrean','petugas_id']));
        return response()->view('admin.keuangan.riwayat-setoran', compact('receipt','entries','back','id'))->header('Cache-Control', 'private, no-store');
    }

    public function record(Request $request, int $id)
    {
        $d = $request->validate(['kunci' => 'required|uuid', 'sebelum' => 'required|integer|between:0,4294967295',
            'sesudah' => 'required|integer|between:0,4294967295', 'catatan' => 'nullable|string|max:200', 'diperiksa_fisik' => 'accepted']);
        $key = strtolower($d['kunci']);
        $cashier = $request->user('staff')->id;
        return DB::transaction(function () use ($d, $id, $key, $cashier) {
            $receipt = $this->receipts()->where('b.id', $id)->lockForUpdate()->first(['b.pengguna_id','b.nominal_diterima']);
            abort_unless($receipt, 404);
            abort_if((int) $receipt->pengguna_id === $cashier, 403, 'Setoran harus diperiksa petugas lain.');
            $ledger = DB::table('setoran_kas_antar')->where('pembayaran_id', $id);
            $before = (int) $d['sebelum'];
            $after = (int) $d['sesudah'];
            $note = $d['catatan'] ?? null;
            $old = (clone $ledger)->where('kunci', $key)->first();
            if ($old) {
                abort_unless((int) $old->kasir_id === $cashier && (int) $old->sebelum === $before && (int) $old->sesudah === $after && $old->catatan === $note, 409);
                return response()->json(['id' => $old->id, 'sesudah' => $after]);
            }
            $last = (clone $ledger)->latest('id')->first();
            abort_unless((int) ($last->sesudah ?? 0) === $before, 409, 'Saldo setoran berubah. Muat ulang sebelum mencatat.');
            abort_if($last && $before === $after, 409, 'Tidak ada perubahan setoran.');
            abort_if(($after !== (int) $receipt->nominal_diterima || $last) && blank($note), 422, 'Jelaskan selisih atau koreksi.');
            $entry = DB::table('setoran_kas_antar')->insertGetId(['pembayaran_id' => $id, 'kunci' => $key, 'kasir_id' => $cashier,
                'sebelum' => $before, 'sesudah' => $after, 'catatan' => $note, 'dicatat_pada' => now('Asia/Jakarta')]);
            return response()->json(['id' => $entry, 'sesudah' => $after], 201);
        }, 3);
    }
}
