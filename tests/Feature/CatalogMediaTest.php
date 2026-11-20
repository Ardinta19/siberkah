<?php

namespace Tests\Feature;

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_can_publish_normalized_scoped_gallery_and_manage_cover(): void
    {
        Storage::fake('public');
        $cat = DB::table('kategori')->insertGetId(['nama' => 'Buket', 'slug' => 'buket']);
        $product = fn ($slug) => DB::table('produk')->insertGetId(['nama' => $slug, 'slug' => $slug, 'jenis' => 'jual', 'kategori_id' => $cat]);
        $id = $product('merah');
        $other = $product('biru');
        $variant = DB::table('varian_produk')->insertGetId(['produk_id' => $other, 'nama' => 'L', 'harga' => 20000]);
        $url = route('admin.katalog.manage-images', $id);
        $upload = fn () => ['foto' => UploadedFile::fake()->image('lokasi-rahasia.jpg', 800, 450), 'teks_alternatif' => 'Buket merah dengan pita putih'];
        $this->actingAs(Pengguna::factory()->create(['peran' => 'admin']), 'staff')->postJson($url, $upload())->assertForbidden();
        $this->actingAs(Pengguna::factory()->create(['peran' => 'owner']), 'staff');
        $this->postJson($url, $upload() + ['varian_produk_id' => $variant])->assertUnprocessable();
        $this->postJson($url, ['foto' => UploadedFile::fake()->image('kecil.png', 799, 200), 'teks_alternatif' => 'Kecil'])->assertUnprocessable();
        $this->postJson($url, ['foto' => $upload()['foto']])->assertUnprocessable();
        $this->assertDatabaseCount('gambar_produk', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $a = $this->postJson($url, $upload())->assertCreated()->json();
        $b = $this->postJson($url, $upload())->assertCreated()->json();
        $bytes = Storage::disk('public')->get($a['jalur_berkas']);
        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring($bytes)[2]);
        $this->assertStringNotContainsString('lokasi', $a['jalur_berkas']);
        $this->assertDatabaseHas('produk', ['id' => $id, 'foto_utama' => $a['jalur_berkas']]);
        $sort = route('admin.katalog.images.order', $id);
        $this->putJson($sort, ['urutan' => [$b['id']]])->assertConflict();
        $this->putJson($sort, ['urutan' => [$b['id'], $a['id']]])->assertNoContent();
        $this->getJson(route('admin.katalog.images', $id))->assertOk()->assertJsonPath('0.id', $b['id']);
        $this->deleteJson(route('admin.katalog.images.delete', [$other, $b['id']]))->assertNotFound();
        $this->deleteJson(route('admin.katalog.images.delete', [$id, $b['id']]))->assertNoContent();
        Storage::disk('public')->assertMissing($b['jalur_berkas']);
        $this->assertDatabaseHas('produk', ['id' => $id, 'foto_utama' => $a['jalur_berkas']]);
        $this->assertDatabaseHas('gambar_produk', ['id' => $a['id'], 'urutan' => 1]);
        $this->assertDatabaseCount('log_aktivitas', 4);
    }
}
