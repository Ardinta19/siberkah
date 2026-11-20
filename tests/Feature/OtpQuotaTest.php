<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtpQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_and_resend_share_rolling_quota_for_phone_and_ip_including_provider_failure(): void
    {
        config(['services.whatsapp' => ['token' => 'fake', 'phone_id' => '123', 'template' => 'otp', 'language' => 'id', 'version' => 'v1.0']]);
        Http::fake(['graph.facebook.com/*' => Http::response([], 503)]);
        $issue = function ($route, $number, $ip) {
            return $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson(route($route), ['nomor_wa' => $number]);
        };
        $a = '081234567890';
        $ip = '192.0.2.1';
        $issue('otp.issue', $a, $ip)->assertServiceUnavailable();
        $issue('otp.resend', '+6281234567890', $ip)->assertTooManyRequests();
        $this->travel(61)->seconds();
        $issue('otp.resend', $a, $ip)->assertServiceUnavailable();
        $this->travel(61)->seconds();
        $issue('otp.issue', $a, $ip)->assertServiceUnavailable();
        $this->travel(61)->seconds();
        $issue('otp.resend', $a, '192.0.2.2')->assertTooManyRequests(); // different IP, same number
        $issue('otp.issue', '081234567891', $ip)->assertTooManyRequests(); // different number, same IP
        Http::assertSentCount(3);
        $this->assertDatabaseCount('pelanggan', 0);
        $this->travel(16)->minutes();
        $issue('otp.issue', $a, $ip)->assertServiceUnavailable();
        Http::assertSentCount(4);
    }
}
