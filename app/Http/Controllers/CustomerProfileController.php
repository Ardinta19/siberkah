<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('pelanggan.profil', ['pelanggan' => $request->user('customer')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['nama' => 'required|string|max:100']);
        \App\Models\Pelanggan::whereKey($request->user('customer')->id)->where('aktif', true)->whereNull('dihapus_pada')->update($data);
        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function destroy(Request $request, \App\Actions\AnonymizeCustomer $action)
    {
        $request->validate(['konfirmasi' => 'required|in:HAPUS AKUN SAYA']);
        $id = $request->user('customer')->id;
        $proof = $request->session()->get('otp.verified');
        abort_unless(is_array($proof) && ($proof['id'] ?? null) === $id && ($proof['at'] ?? 0) >= now()->subMinutes(5)->timestamp,
            403, 'Masuk kembali dengan OTP sebelum menghapus akun.');
        $action->handle($id);
        \Illuminate\Support\Facades\Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->noContent();
    }
}
