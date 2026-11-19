<?php

namespace App\Http\Controllers;

use App\Models\KodeOtp;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * OTP Controller - Authentication flow for customers
 * --------------------------------------------------------
 * Guest:customer middleware applied at route level.
 * Handles OTP issuance, verification, and resend.
 */
class OtpController extends BaseController
{
    /**
     * Show the OTP form.
     * K-03: Masuk OTP 2 langkah - nomor WA → kode 6 kotak, hitung mundur 60s
     *
     * @return View
     */
    public function showForm()
    {
        return view('pelanggan.otp-form');
    }

    /**
     * Issue/ send OTP to the provided phone number.
     *
     * @return JsonResponse
     */
    public function issue(Request $request)
    {
        $request->validate([
            'nomor_wa' => 'required|regex:/^(628|08)[0-9]{6,12}$|^\+?62?8[0-9]{6,12}$/',
        ]);

        // Normalize the phone number
        $nomorWa = $request->nomor_wa;
        if (str_starts_with($nomorWa, '08')) {
            $nomorWa = '+62'.substr($nomorWa, 2);
        } elseif (! str_starts_with($nomorWa, '+')) {
            $nomorWa = '+'.$nomorWa;
        }

        // Check if there's an existing OTP that's still valid (within 5 minutes)
        $now = now();
        $existingOtp = KodeOtp::where('nomor_wa', $nomorWa)
            ->where('status_kirim', 'menunggu')
            ->where('kedaluwarsa_pana', '>', $now)
            ->first();

        if ($existingOtp) {
            // Reuse existing OTP if still valid
            return response()->json([
                'success' => true,
                'message' => 'Kode OTP sudah dikirim. Cek WhatsApp Anda.',
                'otp_id' => $existingOtp->id,
                'waktu_sisa' => $existingOtp->kedaluwarsa_pana->diffForHumans(),
            ]);
        }

        // Generate a simple 6-digit OTP code
        $otpCode = rand(100000, 999999);

        // Hash the OTP code for security
        $kodeHash = bcrypt($otpCode);

        // Set expiry to 5 minutes from now
        $kedaluwarsa = now()->addMinutes(5);

        // Save or update the OTP record
        $otp = KodeOtp::updateOrCreate(
            ['nomor_wa' => $nomorWa],
            [
                'kode_hash' => $kodeHash,
                'alamat_ip' => $request->ip(),
                'kedaluwarsa_pana' => $kedaluwarsa,
                'status_kirim' => 'menunggu',
                'dibuat_pana' => $now,
            ]
        );

        // TODO: In production, send actual SMS via provider
        // For now, log the OTP (development only)
        \Log::info("OTP generated for {$nomorWa}: {$otpCode}", [
            'kode_hash' => $kodeHash,
            'kedaluwarsa' => $kedaluwarsa,
        ]);

        // Store the plain OTP in session for verification later
        // Actually, we'll use the database - pass the code via a secure flow
        // For now, return success and let the frontend handle it
        return response()->json([
            'success' => true,
            'message' => 'Kode OTP telah dikirim ke nomor WhatsApp Anda.',
            'otp_id' => $otp->id,
        ]);
    }

    /**
     * Verify the OTP code.
     *
     * @return JsonResponse
     */
    public function verify(Request $request)
    {
        $request->validate([
            'nomor_wa' => 'required|regex:/^(628|08)[0-9]{6,12}$|^\+?62?8[0-9]{6,12}$/',
            'kode' => 'required|string|size:6',
        ]);

        // Normalize the phone number
        $nomorWa = $request->nomor_wa;
        if (str_starts_with($nomorWa, '08')) {
            $nomorWa = '+62'.substr($nomorWa, 2);
        } elseif (! str_starts_with($nomorWa, '+')) {
            $nomorWa = '+'.$nomorWa;
        }

        // Find the OTP record
        $otp = KodeOtp::where('nomor_wa', $nomorWa)
            ->where('status_kirim', 'menunggu')
            ->where('kedaluwarsa_pana', '>', now())
            ->first();

        if (! $otp) {
            return back()
                ->with('error', 'Kode OTP tidak valid atau sudah kadaluwarsa. Silakan minta kode baru.');
        }

        // Verify the hash
        if (! password_verify($request->kode, $otp->kode_hash)) {
            // Increment attempt count
            $otp->percobaan = ($otp->percobaan ?? 0) + 1;

            // If 5 failed attempts, mark as expired/hungus
            if ($otp->percobaan >= 5) {
                $otp->status_kirim = 'gagal';
                $otp->save();

                return back()
                    ->with('error', 'Kode Salah. Coba 5 kali. Kode telah dibutuhkan.');
            }

            $otp->save();

            return back()
                ->with('error', 'Kode OTP salah. Sisa coba: '.(5 - $otp->percobaan).' kali.');
        }

        // OTP verified - find or create the pelanggan
        $pelanggan = Pelanggan::where('nomor_wa', $nomorWa)->first();

        if (! $pelanggan) {
            // Create new pelanggan
            $pelanggan = Pelanggan::create([
                'nomor_wa' => $nomorWa,
                'nama' => null, // Will be completed on first onboarding
                'aktif' => true,
                'nomor_terverifikasi_pana' => now(),
            ]);
        } else {
            // Update last login
            $pelanggan->masuk_terakhir_pana = now();
            $pelanggan->save();
        }

        // Mark OTP as used
        $otp->status_kirim = 'digunakan';
        $otp->dipakai_pana = now();
        $otp->save();

        // Log aktivitas
        \App\Models\LogAktivitas::create([
            'pengguna_id' => $pelanggan->id,
            'aksi' => 'login_otp',
            'subjek_tabel' => 'pelanggan',
            'subjek_id' => $pelanggan->id,
        ]);

        // Login the customer (using session guard)
        // Note: In a real app, you'd use Auth::login() or set the session
        // For now, just mark as verified and redirect
        return redirect()->route('pelanggan.dashboard')
            ->with('success', 'Selamat datang, Kak! Verifikasi OTP berhasil.');
    }

    /**
     * Resend OTP.
     *
     * @return JsonResponse
     */
    public function resend(Request $request)
    {
        $request->validate([
            'nomor_wa' => 'required|regex:/^(628|08)[0-9]{6,12}$|^\+?62?8[0-9]{6,12}$/',
        ]);

        // Normalize
        $nomorWa = $request->nomor_wa;
        if (str_starts_with($nomorWa, '08')) {
            $nomorWa = '+62'.substr($nomorWa, 2);
        } elseif (! str_starts_with($nomorWa, '+')) {
            $nomorWa = '+'.$nomorWa;
        }

        // Find and invalidate old OTP, create new one
        $otp = KodeOtp::where('nomor_wa', $nomorWa)
            ->where('status_kirim', 'menunggu')
            ->first();

        if ($otp) {
            $otp->status_kirim = 'dibatalkan';
            $otp->save();
        }

        // Generate new OTP
        $otpCode = rand(100000, 999999);
        $kodeHash = bcrypt($otpCode);
        $kedaluwarsa = now()->addMinutes(5);

        KodeOtp::updateOrCreate(
            ['nomor_wa' => $nomorWa],
            [
                'kode_hash' => $kodeHash,
                'alamat_ip' => $request->ip(),
                'kedaluwarsa_pana' => $kedaluwarsa,
                'status_kirim' => 'menunggu',
                'dibuat_pana' => now(),
                'percobaan' => 0,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP baru telah dikirim. Cek WhatsApp Anda.',
        ]);
    }
}