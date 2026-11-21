<?php

namespace App\Http\Controllers;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Records observed bank activity. Never initiates a bank transfer. */
final class TransferReconciliationController extends Controller
{
    private function requireStaging(): void
    {
        abort_unless(app()->environment('local', 'testing', 'staging') && config('checkout.staging'), 503, 'Rekonsiliasi belum dibuka.');
    }

    public function index(Request $request)
    {
        $this->requireStaging();
        $browser = !$request->expectsJson() && !$request->acceptsAnyContentType() && $request->getPreferredFormat()==='html';
        $history = $browser && $request->query('kelompok') === 'dikembalikan';
        $query = DB::table('mutasi_masuk as m')->join('pembayaran as p','p.id','=','m.pembayaran_id')
            ->join('checkout_bank as c','c.pesanan_id','=','p.pesanan_id');
        $columns = ['m.id','p.pesanan_id','m.pembayaran_id','m.referensi','m.nominal','m.alasan','m.dicatat_pada'];
        if ($browser) {
            $query->join('pesanan as o','o.id','=','p.pesanan_id');
            $columns[] = 'o.nomor';
        }
        if ($history) {
            $query->join('pengembalian_mutasi as k','k.mutasi_masuk_id','=','m.id')->where('m.hasil','dikembalikan')
                ->orderByDesc('k.dicatat_pada')->orderByDesc('m.id');
            array_push($columns,'k.referensi_keluar','k.alasan as alasan_keluar','k.dicatat_pada as waktu_keluar');
        } else {
            $query->where('m.hasil','perlu_rekonsiliasi')->orderBy('m.id');
        }
        $rows = $query->paginate(25,$columns)->appends($history ? ['kelompok'=>'dikembalikan'] : []);
        return ($browser ? response()->view('admin.keuangan.rekonsiliasi',compact('rows','history')) : response()->json($rows))->header('Cache-Control','private, no-store');
    }

    public function recordReturn(Request $request, int $mutasi)
    {
        $this->requireStaging();
        $data = $request->validate([
            'referensi_keluar' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:\/-]*$/D'],
            'nominal' => 'required|integer|between:1,4294967295', 'alasan' => 'required|string|max:300',
        ]);
        $ref = strtoupper($data['referensi_keluar']);
        $amount = (int) $data['nominal'];
        $created = DB::transaction(function () use ($mutasi, $request, $data, $ref, $amount) {
            $receipt = DB::table('mutasi_masuk')->where('id', $mutasi)->lockForUpdate()->first();
            abort_unless($receipt, 404);
            abort_unless(DB::table('pembayaran as p')->join('checkout_bank as c', 'c.pesanan_id', '=', 'p.pesanan_id')
                ->where('p.id', $receipt->pembayaran_id)->exists(), 404);
            $old = DB::table('pengembalian_mutasi')->where('mutasi_masuk_id', $mutasi)->first();
            if ($old) {
                abort_unless($old->referensi_keluar === $ref && (int) $old->nominal === $amount && $old->alasan === $data['alasan'],
                    409, 'Pengembalian ini sudah tercatat dengan rincian berbeda.');
                return false;
            }
            abort_unless($receipt->hasil === 'perlu_rekonsiliasi' && (int) $receipt->nominal === $amount, 409,
                'Hanya mutasi yang belum dikreditkan yang dapat dikembalikan, sebesar nominal masuk penuh.');
            try {
                DB::table('referensi_mutasi_keluar')->insert(['referensi_keluar' => $ref]);
                DB::table('pengembalian_mutasi')->insert(['mutasi_masuk_id' => $mutasi, 'pengguna_id' => $request->user('staff')->id,
                    'referensi_keluar' => $ref, 'nominal' => $amount, 'alasan' => $data['alasan'], 'dicatat_pada' => now()]);
            } catch (UniqueConstraintViolationException) {
                abort(409, 'Referensi keluar sudah digunakan.');
            }
            DB::table('mutasi_masuk')->where('id', $mutasi)->update(['hasil' => 'dikembalikan']);
            DB::table('log_aktivitas')->insert(['pengguna_id' => $request->user('staff')->id, 'aksi' => 'pengembalian_mutasi',
                'subjek_tabel' => 'mutasi_masuk', 'subjek_id' => $mutasi, 'dibuat_pada' => now()]);
            return true;
        }, 3);
        if (!$request->expectsJson() && $request->input('asal')==='rekonsiliasi') {
            return redirect()->route('admin.rekonsiliasi.index',['kelompok'=>'dikembalikan'])->with('success','Pengembalian mutasi tercatat.');
        }
        return response()->json(['mutasi_masuk_id' => $mutasi, 'hasil' => 'dikembalikan'], $created ? 201 : 200);
    }
}
