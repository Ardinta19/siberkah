<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\RekeningPembayaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Customer Payment Controller - Payment handling for authenticated customers
 * --------------------------------------------------------
 * auth:customer middleware applied at route level.
 * K-05: Pembayaran - hitung mundur 60/30 menit, nominal + kode unik
 */
class CustomerPaymentController extends BaseController
{
    /**
     * Show the payment page.
     * K-05: Pembayaran - hitung mundur 60/30 menit, nominal + kode unik,
     *         rekening+QRIS, "Salin nominal", "Kirim konfirmasi WA"
     *
     * @return View
     */
    public function show(string $nomor)
    {
        // $pesanan = Pesanan::where('nomor', $nomor)->firstOrFail();

        // Placeholder data
        $pesanan = null; // Pesanan::where('nomor', $nomor)->firstOrFail();

        // Get available payment methods
        $rekening = RekeningPembayaran::where('aktif', true)->orderBy('urutan')->get();

        // Calculate total and generate unique code
        $total = 0; // $pesanan->total ?? 0;
        $kodeUnik = rand(1, 999);

        // Check if there's already a pending payment with this nominal_tertunda
        // This ensures unique kode unik per order/status

        return view('pages.customer.payment.show', [
            'nomor' => $nomor,
            'pesanan' => $pesanan,
            'rekening' => $rekening,
            'total' => $total,
            'kodeUnik' => $kodeUnik,
            'waktu_sisa' => '02:30', // Countdown timer placeholder
        ]);
    }

    /**
     * Process payment confirmation (WA link).
     *
     * @param  string  $id
     * @return RedirectResponse
     */
    public function confirm(Request $request, $id)
    {
        $request->validate([
            'nominal_diterima' => 'required|integer|min:1',
            'bukti_transfer' => 'sometimes|image|max:5120', // 5MB max
        ]);

        // $pembayaran = Pembayaran::findOrFail($id);
        // $pesanan = $pembayaran->pesanan;

        // Verify nominal matches exactly
        // if ($request->nominal_diterima != $pesanan->total) {
        //     return back()->with('error', 'Nominal pembayaran tidak sesuai dengan total.');
        // }

        // Update payment status
        // $pembayaran->status = 'terverifikasi';
        // $pembayaran->nominal_diterima = $request->nominal_diterima;
        // $pembayaran->diterima_pana = now();
        // $pembayaran->verifikasi_pana = auth('customer')->id();
        // $pembayaran->save();

        // Update order status
        // $pesanan->status = 'dikonfirmasi';
        // $pesanan->dikonfirmasi_pana = now();
        // $pesanan->save();

        // Log aktivitas
        // \App\Models\LogAktivitas::create([...]);

        return redirect()->route('pesanan.show', ['nomor' => $pesanan->nomor])
            ->with('success', 'Pembayaran berhasil diverifikasi. Terima kasih, Kak!');
    }
}
