<?php

namespace App\Http\Controllers;

use App\Actions\CancelPaidRental;
use App\Models\Pesanan;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaidRentalCancellationController extends Controller
{
    private function requireStaging(): void
    {
        abort_unless(app()->environment('local', 'testing', 'staging') && config('checkout.staging'), 503, 'Refund belum dibuka.');
    }

    public function cancel(Request $request, int $id, CancelPaidRental $action)
    {
        return $this->decision($request, $id, $action, false);
    }

    public function approve(Request $request, int $id, CancelPaidRental $action)
    {
        return $this->decision($request, $id, $action, true);
    }

    private function decision(Request $request, int $id, CancelPaidRental $action, bool $fromRequest)
    {
        $this->requireStaging();
        $data = $request->validate(['alasan' => 'required|string|max:300', 'nominal_override' => 'sometimes|required|integer|min:0',
            'alasan_override' => 'required_with:nominal_override|string|max:300']);
        try {
            $amount = $action->decide($request->user('staff'), $id, $data['alasan'], isset($data['nominal_override']) ? (int) $data['nominal_override'] : null, $data['alasan_override'] ?? null, $fromRequest);
        } catch (\DomainException $e) {
            abort(409, $e->getMessage());
        }
        if ($fromRequest && $request->input('asal')==='antrean' && !$request->expectsJson()) {
            return redirect()->route('admin.permohonan-batal.index')->with('success','Pesanan dibatalkan. Nominal refund: Rp'.number_format($amount,0,',','.').'. Persetujuan bukan bukti transfer keluar.');
        }
        return response()->json(['status' => 'dibatalkan', 'nominal_refund' => $amount]);
    }

    public function record(Request $request, int $id)
    {
        $this->requireStaging();
        $data = $request->validate(['referensi_keluar' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9._:\/-]*$/D'], 'nominal' => 'required|integer|min:1']);
        $ref = strtoupper($data['referensi_keluar']);
        $created = DB::transaction(function () use ($id, $request, $data, $ref) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            $decision = DB::table('keputusan_refund')->where('pesanan_id', $id)->first();
            abort_unless($order->status === 'dibatalkan' && $decision && $decision->pembayaran_refund_id && (int) $decision->nominal_final === (int) $data['nominal'], 409, 'Tidak ada kewajiban refund yang cocok.');
            $bill = DB::table('pembayaran')->where('id', $decision->pembayaran_refund_id)->where('pesanan_id', $id)->where('jenis', 'refund')->lockForUpdate()->first();
            abort_unless($bill && (int) $bill->tagihan === (int) $data['nominal'], 409);
            if ($bill->status === 'terverifikasi') {
                abort_unless(DB::table('referensi_mutasi_keluar')->where('referensi_keluar', $ref)->where('pembayaran_refund_id', $bill->id)->exists(), 409, 'Refund sudah tercatat dengan referensi berbeda.');
                return false;
            }
            abort_unless($bill->status === 'menunggu', 409);
            try {
                DB::table('referensi_mutasi_keluar')->insert(['referensi_keluar' => $ref, 'pembayaran_refund_id' => $bill->id]);
            } catch (UniqueConstraintViolationException) {
                abort(409, 'Referensi transfer keluar sudah digunakan.');
            }
            $now = now();
            DB::table('pembayaran')->where('id', $bill->id)->update(['status' => 'terverifikasi', 'nominal_diterima' => $data['nominal'],
                'diterima_pada' => $now, 'diverifikasi_pada' => $now, 'diubah_pada' => $now, 'pengguna_id' => $request->user('staff')->id]);
            DB::table('log_aktivitas')->insert(['aksi' => 'refund_pembatalan_dicatat', 'subjek_tabel' => 'pembayaran', 'subjek_id' => $bill->id,
                'pengguna_id' => $request->user('staff')->id, 'dibuat_pada' => $now]);
            return true;
        }, 3);
        return response()->json(['hasil' => 'refund_tercatat'], $created ? 201 : 200);
    }
}
