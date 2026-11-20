<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('pelanggan.profil.edit', ['pelanggan' => $request->user('customer')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['nama' => 'required|string|max:100']);
        $onboarding = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $data) {
            $customer = \App\Models\Pelanggan::lockForUpdate()->findOrFail($request->user('customer')->id);
            abort_unless($customer->isActive() && $customer->isVerified(), 403);
            $first = ! $customer->isOnboarded();
            if ($first) {
                $version = config('checkout.privacy_version');
                abort_unless(is_string($version) && filled($version) && mb_strlen($version) <= 40, 503, 'Versi kebijakan privasi belum tersedia.');
                $request->validate(['setuju_privasi' => 'accepted', 'versi_privasi' => ['required', 'string', \Illuminate\Validation\Rule::in([$version])]]);
                $customer->versi_kebijakan = $version;
                $customer->disetujui_pada = now('Asia/Jakarta');
            }
            $customer->fill($data)->save();
            return $first;
        });
        return ($onboarding ? redirect()->route('catalog.index') : back())->with('status', 'Profil berhasil diperbarui.');
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
