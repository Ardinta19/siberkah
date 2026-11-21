<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Models\Pesanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BouquetCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function cart(): array
    {
        config(['checkout.staging' => true, 'checkout.privacy_version' => 'v1']);
        $this->actingAs(Pelanggan::forceCreate(['versi_kebijakan' => 'v1', 'disetujui_pada' => now(), 'nama' => 'Penguji', 'nomor_wa' => '628111111111', 'aktif' => true, 'nomor_terverifikasi_pada' => now()]), 'customer');
        $cat = DB::table('kategori')->insertGetId(['nama' => 'Buket', 'slug' => 'buket']);
        $sale = DB::table('produk')->insertGetId(['kategori_id' => $cat, 'jenis' => 'jual', 'nama' => 'Siap', 'slug' => 'siap']);
        $variant = DB::table('varian_produk')->insertGetId(['produk_id' => $sale, 'nama' => 'Putih', 'harga' => 40000, 'stok' => 3]);
        $custom = DB::table('produk')->insertGetId(['kategori_id' => $cat, 'jenis' => 'custom', 'nama' => 'Custom', 'slug' => 'custom', 'harga_dasar' => 100000]);
        $option = DB::table('opsi_produk')->insertGetId(['produk_id' => $custom, 'nama' => 'Bunga', 'tipe' => 'satuan', 'wajib' => true]);
        $value = DB::table('nilai_opsi')->insertGetId(['opsi_produk_id' => $option, 'label' => 'Mawar', 'harga_tambahan' => 5000]);
        return ['kunci' => (string) Str::uuid(), 'tanggal' => today()->addDays(5)->toDateString(), 'cara_serah' => 'ambil', 'pembayaran' => 'dp', 'versi_privasi' => 'v1', 'setuju_privasi' => true,
            'items' => [['produk_id' => $sale, 'varian_id' => $variant, 'jumlah' => 1], ['produk_id' => $custom, 'jumlah' => 2, 'opsi' => [['id' => $option, 'nilai_id' => $value, 'satuan' => 3]]]]];
    }

    public function test_customer_payment_window_and_destination_rotation_do_not_depend_on_scheduler(): void
    {
        $d = $this->cart();
        $this->postJson(route('pesanan.buket'), $d)->assertOk();
        $order = Pesanan::sole();
        $bill = DB::table('pembayaran')->sole();
        $url = route('payment.show', $order->nomor);
        $post = route('payment.confirm', $order->nomor);
        $this->getJson($url)->assertJsonPath('pembayaran.0.boleh_bayar', false);
        $account = DB::table('rekening_pembayaran')->insertGetId(['nama_bank' => 'BRI', 'nomor_rekening' => '00123456', 'atas_nama' => 'Penguji']);
        $data = ['pembayaran_id' => $bill->id, 'tujuan_id' => $account];
        $end = $order->tahan_hingga_pada;
        $this->travelTo($end->copy()->subSecond());
        $this->get($url)->assertOk()->assertSee('00123456')->assertSee('Bayar persis');
        DB::table('rekening_pembayaran')->where('id', $account)->update(['aktif' => false]);
        $this->postJson($post, $data)->assertConflict();
        DB::table('rekening_pembayaran')->where('id', $account)->update(['aktif' => true]);
        foreach ([$end, $end->copy()->addSecond()] as $time) {
            $this->travelTo($time);
            $this->get($url)->assertOk()->assertDontSee('00123456');
            $this->postJson($post, $data)->assertConflict();
        }
        $this->assertDatabaseHas('pembayaran', ['id' => $bill->id, 'dikonfirmasi_pelanggan_pada' => null]);
        $this->travelTo($end->copy()->subSecond());
        $this->postJson($post, $data)->assertAccepted();
        $this->postJson($post, $data)->assertAccepted();
        $this->get($url)->assertSee('Jangan bayar lagi')->assertDontSee('00123456');
        $other = Pelanggan::create(['nomor_wa' => '628123456789', 'aktif' => true]);
        $this->actingAs($other, 'customer')->getJson($url)->assertNotFound();
        $this->postJson($post, $data)->assertNotFound();
        $this->assertSame(0, $order->fresh()->jumlah_dibayar);
    }

    public function test_optional_transfer_proof_is_private_and_purged_only_after_closed_retention(): void
    {
        $this->postJson(route('pesanan.buket'), $this->cart())->assertOk();
        $order = Pesanan::sole(); $bill = DB::table('pembayaran')->sole();
        $account = DB::table('rekening_pembayaran')->insertGetId(['nama_bank'=>'BRI','nomor_rekening'=>'00123456','atas_nama'=>'Uji']);
        $confirm = route('payment.confirm',$order->nomor);
        $data = ['tujuan_id'=>$account,'pembayaran_id'=>$bill->id];
        $this->postJson($confirm,$data + ['bukti'=>\Illuminate\Http\UploadedFile::fake()->create('x.txt',2,'text/plain')])->assertUnprocessable();
        $this->postJson($confirm,$data + ['bukti'=>\Illuminate\Http\UploadedFile::fake()->image('foto.png',100,100)])->assertAccepted();
        $this->postJson($confirm,$data)->assertAccepted();
        $this->assertDatabaseCount('bukti_transfer',1);
        $this->assertSame(0,$order->fresh()->jumlah_dibayar);
        $url = route('admin.bukti-transfer',[$order->id,$bill->id]);
        $this->getJson($url)->assertUnauthorized();
        $this->actingAs(Pengguna::factory()->create(['peran'=>'staf_kurir']),'staff')->getJson($url)->assertForbidden();
        $this->actingAs(Pengguna::factory()->create(['peran'=>'admin']),'staff')->get($url)->assertOk()->assertHeader('Content-Type','image/webp');
        $this->get(route('admin.bukti-transfer',[$order->id+1,$bill->id]))->assertNotFound();
        $args = ['nomor'=>$order->nomor,'--hapus'=>true];
        $this->artisan('privasi:bukti',$args)->assertFailed();
        $order->update(['status'=>'dibatalkan','dibatalkan_pada'=>now()->subYears(4)]);
        $this->artisan('privasi:bukti',$args)->assertFailed(); // unresolved payment
        DB::table('pembayaran')->where('id',$bill->id)->update(['status'=>'kedaluwarsa','nominal_tertunda'=>null]);
        $this->artisan('privasi:bukti',['nomor'=>$order->nomor])->assertSuccessful();
        $this->assertDatabaseCount('bukti_transfer',1);
        $this->artisan('privasi:bukti',$args)->assertSuccessful();
        $this->get($url)->assertNotFound();
        $this->assertDatabaseCount('pembayaran',1);
    }

    public function test_cash_pickup_only_credits_at_successful_handover_once(): void
    {
        $d = $this->cart();
        $d['items'] = [$d['items'][0]];
        $d['pembayaran'] = 'penuh';
        $d['metode_pelunasan'] = 'tunai_saat_serah';
        $this->postJson(route('pesanan.buket'), $d)->assertOk();
        $this->postJson(route('pesanan.buket'), $d)->assertOk();
        $order = Pesanan::sole();
        $this->assertSame('dikonfirmasi', $order->status);
        $this->assertSame(40000, $order->saldo);
        $this->assertDatabaseCount('pembayaran', 0);
        $this->actingAs(Pengguna::factory()->create(['peran' => 'admin']), 'staff');
        $this->post(route('staf.produksi.mark-ready'), ['pesanan_id' => $order->id])->assertRedirect();
        $url = route('toko.serah', $order->id);
        $proof = ['barang_diperiksa' => true, 'diterima_pembeli' => true, 'tunai_diterima' => true];
        $this->postJson($url, $proof + ['nominal_tunai' => 40000])->assertConflict();
        $this->travelTo($order->tanggal_serah);
        $this->postJson($url, $proof)->assertUnprocessable();
        $this->postJson($url, $proof + ['nominal_tunai' => 39000])->assertConflict();
        $this->assertDatabaseCount('pembayaran', 0);
        for ($i = 0; $i < 2; $i++) {
            $this->postJson($url, $proof + ['nominal_tunai' => 40000])->assertOk();
        }
        $this->assertSame(0, $order->fresh()->saldo);
        $this->assertDatabaseCount('pembayaran', 1);
        $this->assertDatabaseHas('pembayaran', ['tunai_saat_serah' => true, 'nominal_diterima' => 40000]);
        $this->assertDatabaseCount('mutasi_masuk', 0);
    }

    public function test_delivery_cash_after_verified_dp_has_no_second_bank_invoice(): void
    {
        $d = $this->cart();
        $d['cara_serah'] = 'antar';
        $d['alamat_antar'] = 'Alamat uji';
        $d['zona_antar_id'] = DB::table('zona_antar')->insertGetId(['nama' => 'Dekat', 'jarak_maks_km' => 5, 'ongkir' => 10000]);
        $this->postJson(route('pesanan.buket'), $d + ['metode_pelunasan' => 'tunai_saat_serah'])->assertOk();
        $order = Pesanan::sole();
        $this->assertSame('menunggu_pembayaran', $order->status);
        $bill = DB::table('pembayaran')->sole();
        $this->actingAs(Pengguna::factory()->create(['peran' => 'admin']), 'staff');
        $this->postJson(route('admin.pesanan.verify-payment', $order->id), ['referensi' => 'BANK:DP', 'pembayaran_id' => $bill->id, 'nominal' => $bill->nominal_diharapkan])->assertOk();
        $this->assertDatabaseCount('pembayaran', 1);
        $this->post(route('staf.produksi.start'), ['pesanan_id' => $order->id])->assertRedirect();
        $this->post(route('staf.produksi.mark-ready'), ['pesanan_id' => $order->id])->assertRedirect();
        $this->travelTo($order->tanggal_serah);
        $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_kurir']), 'staff')->postJson(route('toko.serah', $order->id),
            ['barang_diperiksa' => true, 'diterima_pembeli' => true, 'tunai_diterima' => true, 'nominal_tunai' => 140000])->assertOk();
        $this->assertDatabaseHas('pembayaran', ['jenis' => 'pelunasan', 'tunai_saat_serah' => true, 'nominal_diterima' => 140000]);
        $cashId = DB::table('pembayaran')->where('tunai_saat_serah', true)->value('id');
        $url = route('kas-antar.record', $cashId);
        $list = route('kas-antar.index', ['tanggal' => today()->toDateString()]);
        $data = ['kunci' => (string) Str::uuid(), 'sebelum' => 0, 'sesudah' => 135000, 'diperiksa_fisik' => true];
        $this->postJson($url, $data)->assertForbidden();
        $this->actingAs(Pengguna::factory()->create(['peran' => 'owner']), 'staff');
        $this->getJson($list)->assertOk()->assertJsonPath('rincian.data.0.status_setoran', 'belum_disetor');
        $this->postJson(route('kas-antar.record', $bill->id), $data)->assertNotFound();
        $this->postJson($url, $data)->assertUnprocessable();
        $data['catatan'] = 'Kurang saat hitung pertama';
        $entry = $this->postJson($url, $data)->assertCreated()->json('id');
        $this->postJson($url, $data)->assertOk()->assertJsonPath('id', $entry);
        $this->postJson($url, array_replace($data, ['sesudah' => 140000]))->assertConflict();
        $this->getJson($list)->assertOk()->assertJsonPath('rincian.data.0.status_setoran', 'kurang');
        $data['kunci'] = (string) Str::uuid();
        $this->postJson($url, $data)->assertConflict();
        $data = array_replace($data, ['sebelum' => 135000, 'sesudah' => 141000, 'catatan' => 'Setoran tambahan']);
        $this->postJson($url, $data)->assertCreated();
        $this->getJson($list)->assertOk()->assertJsonPath('rincian.data.0.status_setoran', 'lebih');
        $this->postJson($url, array_replace($data, ['kunci' => (string) Str::uuid(), 'sebelum' => 141000, 'sesudah' => 140000, 'catatan' => 'Kelebihan dikembalikan']))->assertCreated();
        $this->getJson($list)->assertJsonPath('rincian.data.0.status_setoran', 'cocok');
        $this->getJson(route('kas-antar.history', $cashId))->assertJsonPath('total', 3);
        $this->get(route('kas-antar.history', $cashId), ['Accept'=>'text/html'])->assertOk()
            ->assertSeeInOrder(['Hitung #3','−Rp1.000','Kelebihan dikembalikan','Hitung #2','+Rp6.000','Setoran tambahan','Hitung #1','Kurang saat hitung pertama'])
            ->assertSee('#setoran-'.$cashId);
        $this->assertDatabaseCount('pembayaran', 2);
        $this->assertSame(0, $order->fresh()->saldo);
        $this->assertDatabaseCount('mutasi_masuk', 1);
    }

    public function test_receipt_keeps_ordered_choices_and_prices_after_catalog_changes(): void
    {
        $data = $this->cart();
        $number = $this->postJson(route('pesanan.buket'),$data)->assertOk()->json('nomor');
        DB::table('nilai_opsi')->update(['label'=>'Label baru','harga_tambahan'=>7000]);
        DB::table('varian_produk')->update(['nama'=>'Varian baru','harga'=>50000]);
        $this->get(route('pesanan.show',$number))->assertOk()->assertSee('Varian: Putih')
            ->assertSee('Bunga: Mawar')->assertSee('3 satuan per produk')->assertSee('+Rp15.000 per produk')
            ->assertSee('Rp115.000 per produk × 2')->assertSee('Rp230.000')->assertSee('Rp270.000')
            ->assertDontSee('Label baru')->assertDontSee('Varian baru');
        $this->actingAs(Pengguna::factory()->create(['peran'=>'admin']), 'staff');
        $this->get(route('admin.pesanan.show',Pesanan::sole()->id), ['Accept'=>'text/html'])->assertOk()
            ->assertSee('Varian: Putih')->assertSee('Bunga: Mawar')->assertSee('3 satuan per produk')
            ->assertSee('+Rp15.000 per produk')->assertSee('Rp115.000 per produk × 2')->assertSee('Rp270.000')
            ->assertDontSee('Label baru')->assertDontSee('Varian baru');
    }

    public function test_mixed_cart_from_checkout_through_payment_production_and_handover(): void
    {
        $d = $this->cart();
        $url = route('pesanan.buket');
        $number = $this->postJson($url, $d + ['total' => 1])->assertOk()->assertJsonPath('total', 270000)->json('nomor');
        $this->postJson($url, $d)->assertOk()->assertJsonPath('nomor', $number);
        $this->postJson($url, array_replace($d, ['pembayaran' => 'penuh']))->assertConflict();
        $order = Pesanan::sole();
        $this->getJson(route('payment.show', $number))->assertOk()->assertJsonPath('saldo', 270000);
        $this->assertDatabaseHas('opsi_item_pesanan', ['harga_tambahan' => 15000, 'label_nilai' => 'Mawar']);
        $this->assertDatabaseHas('kapasitas_produksi_harian', ['terpakai' => 2]);
        $this->assertDatabaseHas('varian_produk', ['stok' => 2]);
        $this->actingAs(Pengguna::factory()->create(['peran' => 'admin']), 'staff');
        foreach (['dp', 'pelunasan'] as $type) {
            $bill = DB::table('pembayaran')->where('jenis', $type)->sole();
            $this->postJson(route('admin.pesanan.verify-payment', $order->id), ['referensi' => 'BANK:'.$type, 'pembayaran_id' => $bill->id,
                'nominal' => $bill->nominal_diharapkan])->assertOk()->assertJsonPath('hasil', 'terverifikasi');
        }
        $payload = ['pesanan_id' => $order->id];
        $this->post(route('staf.produksi.mark-ready'), $payload)->assertConflict();
        $this->post(route('staf.produksi.start'), $payload)->assertRedirect();
        $this->post(route('staf.produksi.mark-ready'), $payload)->assertRedirect();
        $proof = ['barang_diperiksa' => true, 'diterima_pembeli' => true];
        $this->postJson(route('toko.serah', $order->id), $proof)->assertConflict();
        $this->travelTo($order->tanggal_serah);
        $this->postJson(route('toko.serah', $order->id), $proof)->assertOk();
        $this->postJson(route('toko.selesai', $order->id))->assertOk();
        $this->assertSame('selesai', $order->fresh()->status);
    }

    public function test_admin_books_custom_product_from_browser_form_without_marking_it_paid(): void
    {
        $cart = $this->cart();
        $customer = Pelanggan::sole();
        $admin = Pengguna::factory()->create(['peran'=>'admin']);
        $url = route('admin.buket.form',[$customer->id,'custom']);
        $this->actingAs($admin,'staff')->get(route('admin.buket.pilih',['pelanggan'=>$customer->id]))->assertOk()->assertSee('Pilih Custom');
        $this->get($url)->assertOk()->assertSee('persetujuan_pelanggan_diperiksa')->assertSee('Bunga');
        $option = $cart['items'][1]['opsi'][0];
        $input = array_diff_key($cart,['items'=>true]) + ['jumlah'=>2,'opsi'=>[$option['id']=>['nilai_id'=>$option['nilai_id'],'satuan'=>3]]];
        $this->from($url)->post($url,$input)->assertSessionHasErrors('persetujuan_pelanggan_diperiksa');
        $input['persetujuan_pelanggan_diperiksa'] = 1;
        $input['pembayaran'] = 'penuh'; // The custom line alone is below the DP threshold.
        $this->post($url,$input)->assertSessionHas('manual_success');
        $order = Pesanan::sole();
        $this->post($url,$input)->assertRedirect(route('admin.pesanan.show',$order->id));
        $this->assertDatabaseHas('pesanan',['id'=>$order->id,'asal'=>'admin','pengguna_id'=>$admin->id,'total'=>230000,'jumlah_dibayar'=>0]);
        $this->assertDatabaseCount('pesanan',1);
    }

    public function test_manual_mixed_checkout_validates_all_lines_then_reserves_once(): void
    {
        $cart = $this->cart();
        [$sale,$custom] = $cart['items'];
        $url = route('admin.buket.submit',[Pelanggan::sole()->id,'siap']);
        $this->actingAs(Pengguna::factory()->create(['peran'=>'admin']),'staff');
        $choice = $custom['opsi'][0];
        $extra = ['produk_id'=>$custom['produk_id'],'jumlah'=>2,'opsi'=>[$choice['id']=>['nilai_id'=>$choice['nilai_id'],'satuan'=>3]]];
        $input = array_diff_key($cart,['items'=>true]) + ['varian_id'=>$sale['varian_id'],'jumlah'=>1,'persetujuan_pelanggan_diperiksa'=>1,'tambahan'=>[$extra]];
        $invalid = $input;
        unset($invalid['tambahan'][0]['opsi']);
        $this->post($url,$invalid)->assertSessionHasErrors();
        $this->assertDatabaseCount('pesanan',0);
        $this->assertDatabaseHas('varian_produk',['id'=>$sale['varian_id'],'stok'=>3]);
        $this->post($url,$input)->assertSessionHas('manual_success');
        $order = Pesanan::sole();
        $this->post($url,$input)->assertRedirect(route('admin.pesanan.show',$order->id));
        $this->assertSame(270000,$order->total);
        $this->assertSame(0,$order->jumlah_dibayar);
        $this->assertCount(2,$order->items);
        $this->assertDatabaseHas('varian_produk',['id'=>$sale['varian_id'],'stok'=>2]);
        $this->assertDatabaseCount('pesanan',1);
    }

    public function test_manual_custom_cancellation_after_production_restores_only_sale_stock(): void
    {
        $d = $this->cart() + ['pelanggan_id' => Pelanggan::sole()->id, 'persetujuan_pelanggan_diperiksa' => true];
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        $id = $this->actingAs($admin, 'staff')->postJson(route('admin.pesanan.manual-buket'), $d)->assertOk()->json('pesanan_id');
        $this->assertDatabaseHas('pesanan', ['id' => $id, 'asal' => 'admin', 'pengguna_id' => $admin->id]);
        $bill = DB::table('pembayaran')->sole();
        $this->postJson(route('admin.pesanan.verify-payment', $id), ['referensi' => 'DP:WA', 'pembayaran_id' => $bill->id, 'nominal' => $bill->nominal_diharapkan])->assertOk();
        $this->post(route('staf.produksi.start'), ['pesanan_id' => $id])->assertRedirect();
        for ($i = 0; $i < 2; $i++) {
            $this->postJson(route('admin.pesanan.cancel-paid', $id), ['alasan' => 'Acara batal'])->assertOk()->assertJsonPath('nominal_refund', 67500);
        }
        $this->assertDatabaseHas('kapasitas_produksi_harian', ['terpakai' => 2]);
        $this->assertDatabaseHas('varian_produk', ['stok' => 3]);
        $this->assertDatabaseCount('stok_ditahan', 0);
        $this->postJson(route('admin.pesanan.record-refund', $id), ['referensi_keluar' => 'OUT:WA', 'nominal' => 67500])->assertCreated();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('availabilityCases')]
    public function test_late_cart_is_rebooked_as_a_whole_or_returned(bool $available): void
    {
        $d = $this->cart();
        $d['items'][] = $d['items'][0]; // same variant twice: test the aggregated requirement
        $this->postJson(route('pesanan.buket'), $d)->assertOk();
        $order = Pesanan::sole();
        $order->update(['tahan_hingga_pada' => now()->subMinute()]);
        $staff = Pengguna::factory()->create(['peran' => 'admin']);
        $this->actingAs($staff, 'staff');
        $bill = DB::table('pembayaran')->sole();
        $this->postJson(route('admin.pesanan.verify-payment', $order->id), ['referensi' => 'LATE', 'pembayaran_id' => $bill->id, 'nominal' => $bill->nominal_diharapkan])->assertAccepted();
        $this->assertDatabaseHas('kapasitas_produksi_harian', ['terpakai' => 0]);
        if (! $available) {
            DB::table('varian_produk')->update(['stok' => 1]);
        }
        $receipt = DB::table('mutasi_masuk')->sole();
        for ($i = 0; $i < 2; $i++) {
            $this->postJson(route('admin.pesanan.reactivate-expired', $order->id), ['mutasi_id' => $receipt->id])->assertOk()
                ->assertJsonPath('hasil', $available ? 'dikonfirmasi' : 'perlu_pengembalian');
        }
        $this->assertDatabaseHas('varian_produk', ['stok' => 1]);
        $this->assertDatabaseCount('stok_ditahan', $available ? 2 : 0);
        $this->assertDatabaseHas('kapasitas_produksi_harian', ['terpakai' => $available ? 2 : 0]);
        $this->assertDatabaseHas('pesanan', ['id' => $order->id, 'jumlah_dibayar' => $available ? $bill->tagihan : 0]);
        $this->postJson(route('admin.rekonsiliasi.pengembalian', $receipt->id), ['referensi_keluar' => 'OUT', 'nominal' => $receipt->nominal, 'alasan' => 'Mutasi keluar diperiksa'])->assertStatus($available ? 409 : 201);
    }

    public static function availabilityCases(): array
    {
        return [[true], [false]];
    }

    public function test_mismatch_is_decided_once_without_waiving_principal_or_double_counting_bank_money(): void
    {
        $d = $this->cart();
        $customer = Pelanggan::sole();
        $this->postJson(route('pesanan.buket'), $d)->assertOk();
        $order = Pesanan::sole();
        $bill = DB::table('pembayaran')->sole();
        $admin = Pengguna::factory()->create(['peran' => 'admin']);
        $this->actingAs($admin, 'staff');
        $url = route('admin.pesanan.reject-payment', $order->id);
        foreach (['SHORT' => $bill->tagihan - 1, 'NO-CODE' => $bill->tagihan] as $ref => $nominal) {
            $this->postJson(route('admin.pesanan.verify-payment', $order->id), ['referensi' => $ref, 'pembayaran_id' => $bill->id, 'nominal' => $nominal])->assertAccepted();
        }
        $queue = route('admin.rekonsiliasi.index');
        $html = ['Accept'=>'text/html'];
        $this->get($queue,$html)->assertOk()->assertSee('Terima selisih kode unik · NO-CODE')->assertDontSee('Terima selisih kode unik · SHORT')->assertSee('Tolak selisih · SHORT');
        $short = DB::table('mutasi_masuk')->where('referensi', 'SHORT')->value('id');
        $data = ['mutasi_id' => $short, 'action' => 'terima', 'catatan' => 'Periksa pokok tagihan'];
        $this->postJson($url, $data)->assertConflict();
        $this->postJson($url, array_replace($data, ['action' => 'tolak']))->assertOk();
        $this->assertSame(0, $order->fresh()->jumlah_dibayar);
        $data['mutasi_id'] = DB::table('mutasi_masuk')->where('referensi', 'NO-CODE')->value('id');
        $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_kurir']), 'staff')->postJson($url, $data)->assertForbidden();
        $this->actingAs($admin, 'staff')->postJson($url, $data)->assertOk();
        $this->postJson($url, $data)->assertOk();
        $this->postJson($url, array_replace($data, ['action' => 'tolak']))->assertConflict();
        $this->assertDatabaseCount('mutasi_masuk', 2);
        $this->get($queue.'?kelompok=dikreditkan',$html)->assertOk()->assertSee('NO-CODE')->assertSee('Periksa pokok tagihan')->assertDontSee('SHORT');
        $this->assertDatabaseCount('keputusan_selisih', 2);
        $this->assertDatabaseCount('pembayaran', 2);
        $this->assertSame((int) $bill->tagihan, $order->fresh()->jumlah_dibayar);
        $this->actingAs($customer, 'customer')->getJson(route('payment.show', $order->nomor))->assertOk()->assertJsonPath('keputusan_selisih.0.keputusan', 'tolak');
    }

    public function test_invalid_options_and_capacity_failure_roll_back_and_cancellation_restores_once(): void
    {
        $d = $this->cart();
        $url = route('pesanan.buket');
        $bad = $d;
        unset($bad['items'][1]['opsi']);
        $this->postJson($url, $bad)->assertUnprocessable();
        DB::table('kapasitas_produksi_harian')->insert(['tanggal' => $d['tanggal'], 'kelompok_kapasitas' => 'buket_custom', 'batas' => 2]);
        $number = $this->postJson($url, $d)->assertOk()->json('nomor');
        $this->postJson($url, array_replace($d, ['kunci' => (string) Str::uuid()]))->assertConflict();
        $this->assertDatabaseCount('pesanan', 1);
        $this->assertDatabaseHas('varian_produk', ['stok' => 2]);
        $this->post(route('pesanan.cancel', $number))->assertRedirect();
        $this->post(route('pesanan.cancel', $number))->assertRedirect();
        $this->assertDatabaseHas('kapasitas_produksi_harian', ['terpakai' => 0]);
        $this->assertDatabaseHas('varian_produk', ['stok' => 3]);
        $this->assertDatabaseCount('stok_ditahan', 0);
    }
}
