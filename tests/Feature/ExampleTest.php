<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
    public function test_home_features_one_published_product_per_visible_category(): void
    {
        foreach (['Buket', 'Papan', 'Arsip'] as $name) {
            $id = DB::table('kategori')->insertGetId(['nama'=>$name,'slug'=>strtolower($name),'aktif'=>$name !== 'Arsip']);
            foreach (['Draf','Utama','Lain'] as $rank=>$suffix) {
                DB::table('produk')->insert(['kategori_id'=>$id,'nama'=>"$name $suffix",'slug'=>strtolower("$name-$suffix"),
                    'jenis'=>'jual','aktif'=>$rank !== 0,'urutan'=>$rank]);
            }
        }
        $this->get('/')->assertOk()->assertSee('Buket Utama')->assertSee('Papan Utama')
            ->assertDontSee('Buket Draf')->assertDontSee('Buket Lain')->assertDontSee('Papan Lain')->assertDontSee('Arsip Utama');
    }
}
