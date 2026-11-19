<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StaffAuthController extends Controller
{
    public function showLogin()
    {
        return view('staf.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $guard = Auth::guard('staff');
        if (! $guard->attempt(['surel' => $data['email'], 'password' => $data['password'], 'aktif' => true,
            'peran' => array_column(UserRole::cases(), 'value')])) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak sesuai.']);
        }

        $request->session()->regenerate();
        $staff = $guard->user();
        $staff->update(['masuk_terakhir_pada' => now()]);

        $request->session()->put('password_hash_staff', $guard->hashPasswordForCookie($staff->getAuthPassword()));
        if ($staff->wajib_ganti_sandi) {
            return redirect()->route('staff.password.form');
        }

        return redirect()->route(match (UserRole::from($staff->peran)) {
            UserRole::STAF_PRODUKSI => 'staf.produksi.index',
            UserRole::STAF_KURIR => 'staf.kurir.index',
            default => 'dashboard.hari-ini',
        });
    }

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password:staff',
            'new_password' => 'required|string|min:12|confirmed|different:current_password',
        ]);
        $request->user('staff')->forceFill(['kata_sandi' => Hash::make($data['new_password']), 'wajib_ganti_sandi' => false])->save();

        return $this->logout($request);
    }

    public function logout(Request $request)
    {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }
}
