<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerProfileController extends Controller
{
    private function pendingCheckout(Request $request): ?array
    {
        $pending = $request->session()->get('checkout.onboarding');
        return $pending && $pending['customer'] === $request->user('customer')->id && $pending['expires'] > now()->timestamp
            && $pending['token'] === $request->input('lanjut') ? $pending : null;
    }

    public function edit(Request $request)
    {
        return response()->view('pelanggan.profil.edit', ['pelanggan' => $request->user('customer'), 'pending'=>$this->pendingCheckout($request)])->header('Cache-Control', 'private, no-store');
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
        if ($pending = $this->pendingCheckout($request)) {
            $request->session()->forget('checkout.onboarding');
            $product = \App\Models\Produk::whereKey($pending['product'])->where('aktif',true)
                ->whereHas('kategori',fn ($q) => $q->where('aktif',true))->first();
            if ($product) {
                return redirect()->route('checkout.product',['slug'=>$product->slug]+$pending['params']);
            }
            return redirect()->route('catalog.index')->with('status','Profil tersimpan. Produk tidak lagi tersedia.');
        }
        return ($onboarding ? redirect()->route('catalog.index') : back())->with('status', 'Profil berhasil diperbarui.');
    }

    public function destroy(Request $request, \App\Actions\AnonymizeCustomer $action)
    {
        $request->validate(['konfirmasi' => 'required|in:HAPUS AKUN SAYA']);
        $id = $request->user('customer')->id;
        $proof = $request->session()->get('otp.verified');
        if (!is_array($proof) || ($proof['id'] ?? null) !== $id || ($proof['at'] ?? 0) < now()->subMinutes(5)->timestamp) {
            $message = 'Masuk kembali dengan OTP sebelum menghapus akun.';
            abort_if($request->expectsJson(),403,$message);
            return back()->withErrors(['konfirmasi'=>$message]);
        }
        try {
            $action->handle($id);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            if ($e->getStatusCode() !== 409 || $request->expectsJson()) { throw $e; }
            return back()->withErrors(['konfirmasi'=>$e->getMessage()]);
        }
        \Illuminate\Support\Facades\Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return $request->expectsJson() ? response()->noContent() : redirect()->route('catalog.index')->with('status','Akun pelanggan telah dianonimkan.');
    }
}
