<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Upload de logo do tenant — SVG é vetor de XSS (pode conter <script>)
 * e não pode ser aceito; formatos raster continuam funcionando.
 */
class TenantBrandingUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $tenant = Tenant::factory()->create(['subscription_status' => 'active', 'type' => 'ngo']);
        $this->user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role'      => 'ngo',
            'email'     => 'ngo_' . uniqid() . '@example.com',
        ]);
        $this->actingAs($this->user);
    }

    /** @test */
    public function upload_de_svg_e_rejeitado(): void
    {
        $svg  = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $file = UploadedFile::fake()->createWithContent('logo.svg', $svg);

        $response = $this->post(route('settings.branding.update'), ['brand_logo' => $file]);

        $response->assertSessionHasErrors('brand_logo');
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    /** @test */
    public function upload_de_png_continua_aceito(): void
    {
        // PNG 1x1 real embutido: fake()->image() exige a extensão GD,
        // que nem todo PHP local tem — e as regras image|mimes:png
        // inspecionam o conteúdo, então precisa ser PNG de verdade.
        $png  = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('logo.png', $png);

        $response = $this->post(route('settings.branding.update'), ['brand_logo' => $file]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertNotEmpty(Storage::disk('public')->allFiles());
    }
}
