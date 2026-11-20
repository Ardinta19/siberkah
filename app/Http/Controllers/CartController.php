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
        $this->available()->findOrFail($id);
        $qty = $request->validate(['jumlah' => 'required|integer|between:1,100'])['jumlah'];
        abort_if(! $request->session()->has("cart.$id") && count($request->session()->get('cart', [])) >= 30, 422, 'Keranjang penuh.');
        $request->session()->put("cart.$id", (int) $qty);
        return redirect()->route('cart.index');
    }

    public function remove(Request $request, int $id)
    {
        $request->session()->forget("cart.$id");
        return redirect()->route('cart.index');
    }
}
