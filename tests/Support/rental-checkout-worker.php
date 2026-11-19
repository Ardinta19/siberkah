<?php

// Independent Laravel process and PDO connection, test-only. No HTTP entrypoint.
use App\Actions\CreateRentalOrder;
use App\Models\Pelanggan;
use App\Models\Pengguna;
use App\Actions\ReleaseOrderHold;
use App\Actions\VerifyRentalTransfer;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing')) {
    exit(2);
}
$job = json_decode(fgets(STDIN), true, flags: JSON_THROW_ON_ERROR);
config(['database.default' => 'race', 'database.connections.race' => $job['database'], 'checkout.privacy_version' => 'test-v1']);
\Illuminate\Support\Facades\DB::connection()->getPdo();
$barrier = fopen($job['barrier'], 'rb');
echo "READY\n";
flush();
flock($barrier, LOCK_SH);
flock($barrier, LOCK_UN);
fclose($barrier);
if ($job['op'] === 'expire') {
    app(ReleaseOrderHold::class)->expire($job['order']);
    echo "DONE\n";
    exit(0);
}
if ($job['op'] === 'verify') {
    echo app(VerifyRentalTransfer::class)->record(Pengguna::findOrFail($job['staff']), $job['order'], $job['payload'])."\n";
    exit(0);
}
$customer = Pelanggan::findOrFail($job['customer']);
try {
    app(CreateRentalOrder::class)->create($customer, $job['payload']);
    echo "RESERVED\n";
} catch (DomainException $e) {
    if ($e->getMessage() !== 'Unit pada tanggal ini tidak mencukupi.') {
        throw $e;
    }
    echo "UNAVAILABLE\n";
}
