<?php

namespace App\Http\Controllers;

use App\Actions\IssueRentalInvoice;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RentalCloseoutController extends Controller
{
    public function evidence(Request $request, int $id)
    {
        abort_unless(DB::table('checkout_sewa')->where('pesanan_id', $id)->exists(), 404);
        $query = DB::table('inspeksi_sewa as i')->join('item_pesanan as item', 'item.id', '=', 'i.item_pesanan_id')
            ->leftJoin('foto_inspeksi as f', 'f.inspeksi_sewa_id', '=', 'i.id')->where('item.pesanan_id', $id)
            ->orderBy('i.id')->orderBy('f.urutan');
        if (!$request->expectsJson() && !$request->acceptsAnyContentType() && $request->getPreferredFormat() === 'html') {
            $order = Pesanan::findOrFail($id);
            $items = $order->items()->leftJoin('unit_sewa as u','u.id','=','item_pesanan.unit_sewa_id')
                ->orderBy('item_pesanan.id')->get(['item_pesanan.id','nama_produk','nama_varian','u.kode']);
            $inspections = $query->get(['item.id as item_id','i.tahap','i.kondisi','i.daftar_periksa','i.catatan','i.dilakukan_pada','i.pengguna_id','f.id as foto_id','f.urutan'])->groupBy(['item_id','tahap']);
            $lateFee = (int) DB::table('penyesuaian_pesanan')->where(['pesanan_id'=>$id,'jenis'=>'denda_telat'])->sum('nominal');
            $review = DB::table('penutupan_sewa')->where('pesanan_id',$id)->first();
            $returns = $inspections->map(fn ($stages) => $stages->get('kembali')?->first())->filter();
            $complete = $items->isNotEmpty() && $returns->count() === $items->count();
            $damaged = $returns->contains(fn ($row) => $row->kondisi !== 'baik');
            $rates = !$review && $complete && $damaged && $request->user('staff')->isOwner()
                ? DB::table('tarif_kerusakan')->orderBy('nama')->get(['kode','nama','satuan','tarif']) : collect();
            $deposit = $review ? DB::table('pemakaian_deposit')->where('pesanan_id',$id)->first(['nominal','alasan','dicatat_pada','pengguna_id']) : null;
            $cap = $review && !$deposit && $order->jenis_jaminan === 'deposit' && $review->setoran_pada
                && $order->status === 'sudah_dikembalikan' ? min($review->biaya_kerusakan,$order->nominal_jaminan,$order->saldo) : 0;
            $canApply = $cap > 0 && $order->jumlah_dibayar + $order->saldo === $order->total
                && \App\Actions\ApplyDamageDeposit::eligibleBill($order,$review->tagihan_id)->exists();
            $closure = $order->status === 'selesai' ? DB::table('riwayat_status_pesanan')->where(['pesanan_id'=>$id,'kode_transisi'=>'T11'])->latest('id')->first(['pengguna_id','dibuat_pada']) : null;
            $context = $request->only(['q','status','tanggal','pelanggan','page','asal']);
            return response()->view('admin.pesanan.inspeksi',compact('order','items','inspections','context','closure','lateFee','review','deposit','complete','damaged','rates','cap','canApply'))->header('Cache-Control','private, no-store');
        }
        $rows = $query->get(['item.unit_sewa_id','i.tahap','i.kondisi','i.daftar_periksa','i.catatan','f.id as foto_id']);
        return response()->json($rows)->header('Cache-Control', 'private, no-store');
    }

    public function photo(int $id, int $foto)
    {
        $path = DB::table('foto_inspeksi as f')->join('inspeksi_sewa as i', 'i.id', '=', 'f.inspeksi_sewa_id')
            ->join('item_pesanan as item', 'item.id', '=', 'i.item_pesanan_id')->join('checkout_sewa as c', 'c.pesanan_id', '=', 'item.pesanan_id')
            ->where('item.pesanan_id', $id)->where('f.id', $foto)->first(['f.jalur_berkas','i.id as inspection']);
        abort_unless($path && \App\Support\InspectionPath::valid($path->inspection, $path->jalur_berkas), 404);
        $path = $path->jalur_berkas;
        abort_unless(Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function assess(Request $request, int $id, IssueRentalInvoice $invoices)
    {
        if ($request->input('asal') === 'bukti-retur') {
            $form = $request->validate(['jumlah'=>'sometimes|array','jumlah.*'=>'nullable|integer|between:0,100',
                'biaya_kerusakan'=>'required|integer|min:0','diperiksa'=>'accepted']);
            $lines = [];
            foreach ($form['jumlah'] ?? [] as $code => $qty) {
                if ((int)$qty > 0) { $lines[] = ['kode'=>(string)$code,'jumlah'=>(int)$qty]; }
            }
            $request->merge(['rincian'=>$lines]);
        }
        $data = $request->validate(['biaya_kerusakan' => 'sometimes|integer|between:0,2147483647', 'rincian' => 'sometimes|array|list|max:30',
            'rincian.*'=>'array:kode,jumlah','rincian.*.kode'=>'required|string|max:30|distinct','rincian.*.jumlah'=>'required|integer|between:1,100', 'alasan' => 'required|string|max:200']);
        $staff = $request->user('staff');
        $quantities = collect($data['rincian'] ?? [])->mapWithKeys(fn ($line) => [$line['kode'] => (int) $line['jumlah']])->sortKeys()->all();
        return DB::transaction(function () use ($id, $quantities, $data, $staff, $invoices) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            abort_unless(DB::table('checkout_sewa')->where('pesanan_id', $id)->exists(), 404);
            $existing = DB::table('penutupan_sewa')->where('pesanan_id', $id)->first();
            if ($existing) {
                $saved = collect(json_decode($existing->rincian_tarif ?? '[]', true))->pluck('jumlah','kode')->sortKeys()->all();
                abort_unless($saved === $quantities && (! isset($data['biaya_kerusakan']) || (int) $data['biaya_kerusakan'] === (int) $existing->biaya_kerusakan) && $data['alasan'] === $existing->alasan, 409, 'Penilaian sudah final.');
                return response()->json(['tagihan_id' => $existing->tagihan_id]);
            }
            abort_unless($order->status === 'sudah_dikembalikan' && $order->total === $order->saldo + $order->jumlah_dibayar, 409);
            $returned = DB::table('inspeksi_sewa as i')->join('item_pesanan as item', 'item.id', '=', 'i.item_pesanan_id')
                ->where('item.pesanan_id', $id)->where('i.tahap', 'kembali')->pluck('i.kondisi');
            abort_unless($returned->isNotEmpty() && $returned->count() === $order->items()->count(),409,'Inspeksi kembali belum lengkap.');
            $damaged = $returned->contains(fn ($condition) => $condition !== 'baik');
            $lines = []; $amount = 0;
            foreach ($quantities as $code => $quantity) {
                $rate = DB::table('tarif_kerusakan')->where('kode',$code)->sharedLock()->first();
                abort_unless($rate,422,'Tarif kerusakan tidak dikenal.');
                $lines[] = ['kode'=>$code,'jumlah'=>$quantity,'tarif'=>(int)$rate->tarif,'satuan'=>$rate->satuan];
                $amount += $rate->tarif * $quantity;
            }
            abort_if(($damaged || $amount > 0) && ! $staff->isOwner(), 403, 'Penilaian kondisi cacat/rusak hanya oleh owner.');
            abort_if(isset($data['biaya_kerusakan']) && (int) $data['biaya_kerusakan'] !== $amount,422,'Nominal harus sama dengan jumlah × tarif acuan.');
            abort_if($amount > 0 && ! $damaged, 422, 'Tidak ada kerusakan tercatat.');
            abort_if($order->total + $amount > 2147482648, 422, 'Nominal melebihi batas.');
            $now = now();
            if ($amount) {
                DB::table('penyesuaian_pesanan')->insert(['pesanan_id' => $id, 'jenis' => 'kerusakan', 'keterangan' => $data['alasan'], 'nominal' => $amount,
                    'pengguna_id' => $staff->id, 'dibuat_pada' => $now]);
                $order->update(['saldo' => $order->saldo + $amount, 'total' => $order->total + $amount, 'penyesuaian' => $order->penyesuaian + $amount]);
            }
            $invoice = $order->saldo > 0 ? $invoices->issue($id, 'pelunasan', $order->saldo, $now) : null;
            DB::table('penutupan_sewa')->insert(['pesanan_id' => $id, 'penilai_id' => $staff->id, 'biaya_kerusakan' => $amount,
                'alasan' => $data['alasan'], 'tagihan_id' => $invoice, 'dinilai_pada' => $now, 'rincian_tarif'=>json_encode($lines, JSON_THROW_ON_ERROR)]);
            return response()->json(['tagihan_id' => $invoice], 201);
        }, 3);
    }

    public function confirmDeposit(Request $request, int $id)
    {
        $amount = (int) $request->validate(['nominal' => 'required|integer|between:100000,150000'])['nominal'];
        DB::transaction(function () use ($request, $id, $amount) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            $review = DB::table('penutupan_sewa')->where('pesanan_id', $id);
            abort_unless($review->exists() && $order->jenis_jaminan === 'deposit' && (int) $order->nominal_jaminan === $amount, 409);
            $review->whereNull('setoran_pada')->update(['setoran_pada' => now(), 'penerima_setoran_id' => $request->user('staff')->id]);
        });
        return response()->json(['setoran' => 'tercatat']);
    }

    public function applyDeposit(Request $request, int $id, \App\Actions\ApplyDamageDeposit $action)
    {
        $data = $request->validate(['nominal' => 'required|integer|between:1,150000', 'alasan' => 'required|string|max:300']);
        try {
            $payment = $action->handle($request->user('staff'), $id, (int) $data['nominal'], $data['alasan']);
        } catch (\DomainException $exception) {
            abort(409, $exception->getMessage());
        }
        return response()->json(['pembayaran_id' => $payment]);
    }

    public function complete(Request $request, int $id)
    {
        $data = $request->validate(['jaminan_dikembalikan' => 'required|boolean', 'catatan' => 'required|string|max:300', 'nominal_kembali' => 'required|integer|min:0']);
        return DB::transaction(function () use ($request, $id, $data) {
            $order = Pesanan::lockForUpdate()->findOrFail($id);
            $review = DB::table('penutupan_sewa')->where('pesanan_id', $id)->first();
            $used = (int) DB::table('pemakaian_deposit')->where('pesanan_id', $id)->value('nominal');
            $refund = (int) $order->nominal_jaminan - $used;
            $returned = ! $used || $refund > 0;
            abort_unless($review && (bool) $data['jaminan_dikembalikan'] === $returned && (int) $data['nominal_kembali'] === $refund, 409, 'Cocokkan nominal jaminan yang dikembalikan.');
            if ($order->status === 'selesai') {
                abort_unless($review->catatan_penutupan === $data['catatan'], 409);
                return response()->json(['status' => 'selesai']);
            }
            abort_unless($order->status === 'sudah_dikembalikan' && $order->saldo === 0 && ($order->jenis_jaminan !== 'deposit' || $review->setoran_pada), 409, 'Saldo, inspeksi, atau setoran jaminan belum selesai.');
            $now = now();
            $order->update(['status' => 'selesai', 'jaminan_kembali_pada' => $returned ? $now : null, 'selesai_pada' => $now]);
            DB::table('penutupan_sewa')->where('pesanan_id', $id)->update(['catatan_penutupan' => $data['catatan']]);
            DB::table('riwayat_status_pesanan')->insert(['pesanan_id' => $id, 'status_lama' => 'sudah_dikembalikan', 'status_baru' => 'selesai',
                'kode_transisi' => 'T11', 'pelaku' => 'staf', 'pengguna_id' => $request->user('staff')->id, 'catatan' => $data['catatan'], 'dibuat_pada' => $now]);
            return response()->json(['status' => 'selesai']);
        }, 3);
    }
}
