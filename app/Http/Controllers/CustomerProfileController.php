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
        $request->user('customer')->update($request->validate(['nama' => 'required|string|max:100']));
        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function destroy()
    {
        abort(503, 'Penghapusan akun belum tersedia.');
    }
}
