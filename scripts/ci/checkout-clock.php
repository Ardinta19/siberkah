<?php
// PHP CLI web server prepend, isolated schema only. No clock-change HTTP endpoint.
if (PHP_SAPI !== 'cli-server' || getenv('GITHUB_ACTIONS') !== 'true' || getenv('DB_DATABASE') !== 'siberkah_checkout_ci' || getenv('APP_ENV') !== 'testing') { exit(2); }
$clock = getenv('CHECKOUT_CI_CLOCK');
if ($clock && is_file($clock)) {
    require_once __DIR__.'/../../vendor/autoload.php';
    Illuminate\Support\Carbon::setTestNow(new DateTimeImmutable(trim(file_get_contents($clock)), new DateTimeZone('Asia/Jakarta')));
}
