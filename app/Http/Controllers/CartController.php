<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;

final class CartController extends Controller
{
    private function available()
    {
        return Produk::where('aktif', true)->whereHas('kategori', fn ($q) => $q->where('aktif', true));
    }

    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);
        $products = $this->available()->denganHargaTampil()->whereIn('id', array_keys($cart))->get();
        $cart = array_intersect_key($cart, array_flip($products->modelKeys()));
        $request->session()->put('cart', $cart);
        return response()->view('publik.keranjang', compact('products', 'cart'))->header('Cache-Control', 'private, no-store');
    }

    public function save(Request $request, int $id)
    {
        $product = $this->available()->findOrFail($id);
        $qty = $request->validate(['jumlah' => 'required|integer|between:1,100'])['jumlah'];
        if (! $request->session()->has("cart.$id") && count($request->session()->get('cart', [])) >= 30) {
            return back()->withErrors(['cart' => 'Keranjang penuh. Hapus satu produk sebelum menambahkan produk lain.']);
        }
        $request->session()->put("cart.$id", (int) $qty);
        if ($request->boolean('lanjut') && config('checkout.staging') && app()->environment('local', 'testing', 'staging')) {
            return redirect()->route('checkout.product', ['slug' => $product->slug, 'dari_keranjang' => 1]);
        }
        return redirect()->route('cart.index')->with('cart_notice', $product->nama.' · '.$qty.' item tersimpan di keranjang.');
    }

    public function remove(Request $request, int $id)
    {
        $request->session()->forget("cart.$id");
        return redirect()->route('cart.index')->with('cart_notice', 'Produk dihapus dari keranjang.');
    }
}
