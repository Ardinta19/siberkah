<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Immutable account details: add a replacement and retire the previous destination. */
final class PaymentDestinationController extends Controller
{
    public function index()
    {
        return response()->json(DB::table('rekening_pembayaran')->orderBy('urutan')->orderBy('id')->paginate(10))
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request)
    {
        $d = $request->validate(['jenis' => 'required|in:bank,qris', 'atas_nama' => 'required|string|max:100',
            'nama_bank' => 'required_if:jenis,bank|prohibited_if:jenis,qris|in:BCA,BRI,Mandiri,Bank Jambi',
            'nomor_rekening' => ['required_if:jenis,bank','prohibited_if:jenis,qris','string','regex:/^[0-9]{5,40}$/D'],
            'qris' => 'required_if:jenis,qris|prohibited_if:jenis,bank|file|mimes:png|max:512|dimensions:max_width=1500,max_height=1500']);
        $png = null;
        if ($d['jenis'] === 'qris') {
            $image = @imagecreatefrompng($d['qris']->getRealPath());
            abort_unless($image, 422, 'Gambar PNG tidak dapat dibaca.');
            ob_start();
            imagepng($image);
            $bytes = ob_get_clean();
            abort_unless($bytes && strlen($bytes) <= 524288, 422, 'Gambar QRIS terlalu besar.');
            $png = base64_encode($bytes);
        }
        $id = DB::transaction(function () use ($request, $d, $png) {
            $id = DB::table('rekening_pembayaran')->insertGetId(['jenis' => $d['jenis'], 'atas_nama' => $d['atas_nama'],
                'nama_bank' => $d['nama_bank'] ?? 'QRIS', 'nomor_rekening' => $d['nomor_rekening'] ?? '', 'qris_png' => $png,
                'aktif' => false, 'dibuat_pada' => now(), 'diubah_pada' => now()]);
            $this->audit($request, $id, 'buat_tujuan_bayar', false);
            return $id;
        });
        return response()->json(['id' => $id, 'aktif' => false], 201);
    }

    public function status(Request $request, int $id)
    {
        $request->validate(['aktif' => 'required|boolean']);
        DB::transaction(function () use ($request, $id) {
            $row = DB::table('rekening_pembayaran')->where('id', $id)->lockForUpdate()->first();
            abort_unless($row, 404);
            $active = $request->boolean('aktif');
            if ((bool) $row->aktif !== $active) {
                DB::table('rekening_pembayaran')->where('id', $id)->update(['aktif' => $active, 'diubah_pada' => now()]);
                $this->audit($request, $id, 'status_tujuan_bayar', $active);
            }
        });
        return response()->noContent();
    }

    private function audit(Request $request, int $id, string $action, bool $active): void
    {
        \App\Support\StaffAudit::record($action,'rekening_pembayaran',$id,$action === 'buat_tujuan_bayar' ? null : ['aktif'=>! $active],['aktif'=>$active]);
    }
}
