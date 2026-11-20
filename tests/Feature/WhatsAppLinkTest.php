<?php

namespace Tests\Feature;

use App\Models\Pesanan;
use App\Services\WhatsAppLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_and_legal_pages_work_without_inventing_contact_or_bypassing_otp(): void
    {
        config(['services.whatsapp.admin_number' => null, 'services.whatsapp.token' => null]);
        $this->get(route('kontak'))->assertOk()->assertSee('belum tersedia')->assertDontSee('https://wa.me/');
        $this->get(route('privasi'))->assertOk();
        $this->get(route('syarat-sewa'))->assertOk()->assertSee('DP 50%')->assertDontSee('25%');
        config(['services.whatsapp.admin_number' => '628111111111']);
        $this->post(route('otp.issue'), ['nomor_wa' => '081234567890'])->assertServiceUnavailable()->assertSee('Hubungi Admin lewat WhatsApp');
        $this->postJson(route('otp.issue'), ['nomor_wa' => '081234567890'])->assertServiceUnavailable()->assertJsonPath('bantuan', WhatsAppLinks::contact('Halo Admin, saya perlu bantuan masuk ke SIBERKAH.'));
        $this->assertDatabaseCount('kode_otp', 0);
        $this->assertGuest('customer');
    }

    public function test_order_links_only_draft_messages_and_skip_anonymized_numbers(): void
    {
        Http::fake();
        config(['services.whatsapp.admin_number' => '628111111111']);
        $order = new Pesanan(['nomor' => 'BK-2611-0001', 'nama_pemesan' => 'Ayu & Lia', 'nomor_wa_pemesan' => '628122222222', 'status' => 'menunggu_pembayaran']);
        $link = WhatsAppLinks::order($order, true);
        $this->assertStringStartsWith('https://wa.me/628122222222?text=', $link);
        parse_str(parse_url($link, PHP_URL_QUERY), $parameters);
        $this->assertSame('Halo, terkait pesanan BK-2611-0001 atas nama Ayu & Lia. Status: menunggu pembayaran.', $parameters['text']);
        $this->assertStringStartsWith('https://wa.me/628111111111?', WhatsAppLinks::order($order, false));
        $order->nomor_wa_pemesan = '-';
        $this->assertNull(WhatsAppLinks::order($order, true));
        Http::assertNothingSent();
    }
}
