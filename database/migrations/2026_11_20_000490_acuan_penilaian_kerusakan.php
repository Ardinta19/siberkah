<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('penutupan_sewa', fn (Blueprint $t) => $t->json('rincian_tarif')->nullable());
        foreach ([['akrilik_pecah','Akrilik pecah','unit',200000],['bunga_rusak','Bunga hilang atau rusak','tangkai',30000],['easel_patah','Easel patah','unit',100000]] as [$code,$name,$unit,$rate]) {
            DB::table('tarif_kerusakan')->insertOrIgnore(['kode'=>$code,'nama'=>$name,'satuan'=>$unit,'tarif'=>$rate,'dibuat_pada'=>now()]);
        }
    }
    public function down(): void
    {
        Schema::table('penutupan_sewa', fn (Blueprint $t) => $t->dropColumn('rincian_tarif'));
        // Never delete the merchant's rates on rollback.
    }
};
