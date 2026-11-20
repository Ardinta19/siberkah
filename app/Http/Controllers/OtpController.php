<?php

namespace App\Http\Controllers;

use App\Models\KodeOtp;
use App\Models\Pelanggan;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\OtpQuota;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class OtpController extends Controller
{
    public function showForm()
    {
        return view('pelanggan.auth.masuk');
    }

    public function issue(Request $request, OtpQuota $quota)
    {
        $number = $request->validate(['nomor_wa' => ['required', 'string', 'regex:/^(?:0|\+?62)8\d{7,12}$/D']])['nomor_wa'];
        $number = preg_replace('/^(?:0|\+62)/', '62', $number);
        $config = config('services.whatsapp');
        if (! ($config['token'] && $config['phone_id'] && $config['template'] && $config['version'])) {
            return $this->unavailable($request);
        }
        $quota->consume($number, (string) $request->ip());

        $code = (string) random_int(100000, 999999);
        $otp = KodeOtp::create(['nomor_wa' => $number, 'kode_hash' => Hash::make($code), 'alamat_ip' => $request->ip(),
            'kedaluwarsa_pada' => now()->addMinutes(5), 'status_kirim' => 'menunggu']);
        $parameters = [['type' => 'text', 'text' => $code]];
        try {
            $response = Http::withToken($config['token'])->timeout(8)->withoutRedirecting()->post(
                "https://graph.facebook.com/{$config['version']}/{$config['phone_id']}/messages",
                ['messaging_product' => 'whatsapp', 'to' => $number, 'type' => 'template', 'template' => [
                    'name' => $config['template'], 'language' => ['code' => $config['language']], 'components' => [
                        ['type' => 'body', 'parameters' => $parameters],
                        ['type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => $parameters],
                    ],
                ]]);
            $sent = $response->successful() && $response->json('messages.0.id');
        } catch (ConnectionException) {
            $sent = false;
        }
        $otp->update(['status_kirim' => $sent ? 'terkirim' : 'gagal']);
        if (! $sent) {
            return $this->unavailable($request);
        }
        KodeOtp::where('nomor_wa', $number)->where('id', '<', $otp->id)
            ->whereNull('dipakai_pada')->update(['status_kirim' => 'diganti']);
        $request->session()->put('otp.challenge', $otp->id);

        return redirect()->route('otp.form')->with('status', 'Periksa kode pada WhatsApp Anda.');
    }

    private function unavailable(Request $request)
    {
        $message = 'Kode belum dapat dikirim. Coba lagi nanti. Jangan berikan kode OTP kepada siapa pun.';
        $link = \App\Services\WhatsAppLinks::contact('Halo Admin, saya perlu bantuan masuk ke SIBERKAH.');
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'bantuan' => $link], 503)->header('Cache-Control', 'no-store')
            : response()->view('pelanggan.auth.masuk', ['otpError' => $message, 'supportLink' => $link], 503)->header('Cache-Control', 'no-store');
    }

    public function verify(Request $request)
    {
        $code = $request->validate(['kode' => 'required|digits:6'])['kode'];
        $customer = DB::transaction(function () use ($request, $code) {
            $otp = KodeOtp::whereKey($request->session()->get('otp.challenge'))->lockForUpdate()->first();
            if (! $otp || $otp->status_kirim !== 'terkirim' || $otp->dipakai_pada || $otp->kedaluwarsa_pada->isPast() || $otp->percobaan >= 5) {
                return null;
            }
            if (! Hash::check((string) $code, $otp->kode_hash)) {
                $otp->increment('percobaan');
                return null;
            }
            $otp->update(['dipakai_pada' => now()]);
            $customer = Pelanggan::firstOrCreate(['nomor_wa' => $otp->nomor_wa], ['aktif' => true]);
            if (! $customer->isActive()) {
                return null;
            }
            $customer->update(['nomor_terverifikasi_pada' => now(), 'masuk_terakhir_pada' => now()]);
            DB::table('log_aktivitas')->insert(['pelanggan_id' => $customer->id, 'aksi' => 'login_otp', 'dibuat_pada' => now()]);
            return $customer;
        });
        if (! $customer) {
            throw ValidationException::withMessages(['kode' => 'Kode tidak berlaku. Periksa kode atau minta kode baru.']);
        }
        $request->session()->forget('otp.challenge');
        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();
        $request->session()->put('otp.verified', ['id' => $customer->id, 'at' => now()->timestamp]);
        return redirect()->route($customer->isOnboarded() ? 'catalog.index' : 'profile.edit');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
