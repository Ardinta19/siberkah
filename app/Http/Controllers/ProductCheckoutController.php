<?php

namespace App\Http\Controllers;

use App\Actions\CreateBouquetOrder;
use App\Actions\CreateRentalOrder;
use App\Models\Produk;
use App\Support\BusinessSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Browser adapter for one catalog product. Pricing and inventory remain in the checkout actions. */
final class ProductCheckoutController extends Controller
{
    private function product(string $slug): Produk
    {
        return Produk::where('slug', $slug)->where('aktif', true)->whereHas('kategori', fn ($q) => $q->where('aktif', true))->firstOrFail();
    }

    public function show(Request $request, string $slug)
    {
        if (! $request->user('customer')->canPlaceOrders()) {
            return redirect()->route('profile.edit');
        }
        $product = $this->product($slug);
        $day = $request->validate(['tanggal' => 'nullable|date_format:Y-m-d'])['tanggal'] ?? null;
        return response()->view('pelanggan.checkout.produk', [
            'product' => $product, 'variants' => $product->varianProduk()->where('aktif', true)->orderBy('id')->get(),
            'needsVariant' => $product->jenis !== 'custom' || $product->varianProduk()->exists(),
            'options' => $product->opsiProduk()->with(['nilaiOpsi' => fn ($q) => $q->where('aktif', true)])->orderBy('urutan')->get(),
            'zones' => DB::table('zona_antar')->where('aktif', true)->where('jarak_maks_km', '<=', 15)->orderBy('ongkir')->get(),
            'bank' => DB::table('rekening_pembayaran')->where('aktif', true)->exists(), 'threshold' => BusinessSettings::value('ambang_dp_rupiah'),
            'day' => $day, 'key' => Str::uuid(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, string $slug)
    {
        $product = $this->product($slug);
        $customer = $request->user('customer');
        $d = $request->only(['kunci','tanggal','pembayaran','metode_pelunasan','cara_serah','versi_privasi','setuju_privasi']);
        if ($request->input('cara_serah') === 'antar') {
            $d += $request->only(['alamat_antar','zona_antar_id']);
        }
        $line = ['produk_id' => $product->id] + $request->only(['jumlah','varian_id']);
        try {
            if ($product->jenis === 'sewa') {
                $d += $request->only(['metode_teks']);
                if ($request->filled('jam_serah')) { $d['jam_serah'] = $request->input('jam_serah'); }
                $order = app(CreateRentalOrder::class)->create($customer, $d + $line);
            } else {
                $choices = $request->validate(['opsi' => 'sometimes|array|max:20', 'opsi.*' => 'array:teks,nilai_id,satuan'])['opsi'] ?? [];
                foreach ($choices as $id => $choice) {
                    if (filled($choice['teks'] ?? null) || filled($choice['nilai_id'] ?? null)) {
                        $line['opsi'][] = ['id' => $id] + $choice;
                    }
                }
                $order = app(CreateBouquetOrder::class)->create($customer, $d + ['items' => [$line]]);
            }
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['checkout' => $e->getMessage()]);
        } catch (HttpExceptionInterface $e) {
            if (! in_array($e->getStatusCode(), [409,422], true)) { throw $e; }
            return back()->withInput()->withErrors(['checkout' => $e->getMessage() ?: 'Periksa pilihan, tanggal, dan ketersediaan produk.']);
        }
        return redirect()->route('pesanan.show', $order->nomor);
    }
}
