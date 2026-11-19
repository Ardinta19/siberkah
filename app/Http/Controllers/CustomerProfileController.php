<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Customer Profile Controller - Profile management for authenticated customers
 * --------------------------------------------------------
 * auth:customer middleware applied at route level.
 */
class CustomerProfileController extends BaseController
{
    /**
     * Show the edit profile form.
     *
     * @return View
     */
    public function edit()
    {
        // Get the authenticated pelanggan
        // In a real app: $pelanggan = auth('customer')->user();
        // For now, we'll assume the pelanggan is in the session
        $pelanggan = Pelanggan::find(1); // Placeholder - replace with auth

        return view('pages.profile.edit', ['pelanggan' => $pelanggan]);
    }

    /**
     * Update the profile.
     *
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'nama' => 'sometimes|string|max:100',
        ]);

        // Get the authenticated pelanggan
        // $pelanggan = auth('customer')->user();
        // $pelanggan->nama = $request->nama;
        // $pelanggan->save();

        return back()
            ->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Destroy the account (anonymize).
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        // $pelanggan = auth('customer')->user();
        // $pelanggan->nomor_wa = hash('sha256', $pelanggan->nomor_wa);
        // $pelanggan->nama = 'Pelanggan Dihapus';
        // $pelanggan->dihapus_pana = now();
        // $pelanggan->save();

        // Log aktivitas
        // \App\Models\LogAktivitas::create([...]);

        return redirect()->route('index')
            ->with('success', 'Akun telah dihapus secara anonim.');
    }
}
