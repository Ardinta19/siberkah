<?php

namespace App\Http\Controllers;

use App\Models\Pengguna;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Staff Auth Controller - Staff authentication
 * --------------------------------------------------------
 * guest middleware applied at route level.
 */
class StaffAuthController extends BaseController
{
    /**
     * Show the staff login form.
     *
     * @return View
     */
    public function showLogin()
    {
        return view('pages.auth.staff-login');
    }

    /**
     * Handle staff login.
     *
     * @return RedirectResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // TODO: Implement staff authentication
        // Using staff guard with Pengguna model

        return back()
            ->with('success', 'Login berhasil.');
    }

    /**
     * Handle staff password change.
     *
     * @param  int  $user_id
     * @return RedirectResponse
     */
    public function passwordChangeFirstLogin($user_id)
    {
        return view('pages.auth.password-change', ['user_id' => $user_id]);
    }

    /**
     * Process password change.
     *
     * @return RedirectResponse
     */
    public function doPasswordChangeFirstLogin(Request $request, $user_id)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|confirmed|min:8',
        ]);

        // TODO: Implement password change
        // Using Pengguna model

        return back()
            ->with('success', 'Kata sandi berhasil diubah.');
    }
}