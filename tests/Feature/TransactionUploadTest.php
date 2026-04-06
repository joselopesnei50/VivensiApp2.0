<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionUploadTest extends TestCase
{
    // use RefreshDatabase; // Commented out to avoid wiping existing dev DB if not configured correctly. Using manual cleanup or transaction rollback recommended for local dev.

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_manager_can_upload_pdf_attachment()
    {
        // 1. Create Tenant and Manager
        $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'manager',
            'email' => 'manager_' . uniqid() . '@example.com'
        ]);

        $this->actingAs($manager);

        // 2. Mock PDF file
        $file = UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf');

        // 3. Post to store
        $response = $this->post('/transactions', [
            'description' => 'Despesa com PDF',
            'amount' => '150,00',
            'date' => now()->toDateString(),
            'type' => 'expense',
            'attachment' => $file,
        ]);

        // 4. Assert Redirect & Session Success
        $response->assertRedirect('/transactions');
        $response->assertSessionHas('success', 'Lançamento registrado com sucesso!');

        // 5. Assert Database
        $this->assertDatabaseHas('transactions', [
            'tenant_id' => $tenant->id,
            'description' => 'Despesa com PDF',
            'amount' => 150.00,
            'type' => 'expense',
            'status' => 'paid', // Manager creates approved/paid expense by default logic (or pending if not manager)
        ]);

        // 6. Assert File Exists
        $transaction = Transaction::where('description', 'Despesa com PDF')->latest()->first();
        $this->assertNotNull($transaction->attachment_path);
        Storage::disk('public')->assertExists($transaction->attachment_path);
        
        // Cleanup
        $transaction->delete();
        $manager->delete();
        $tenant->delete();
    }

    public function test_upload_validates_file_type()
    {
        // 1. Setup
        $tenant = Tenant::factory()->create(['subscription_status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'manager', 'email' => 'badfile_' . uniqid() . '@example.com']);
        $this->actingAs($user);

        // 2. Invalid file (txt)
        $file = UploadedFile::fake()->create('texto.txt', 100, 'text/plain');

        $response = $this->post('/transactions', [
            'description' => 'Arquivo Invalido',
            'amount' => '50,00',
            'date' => now()->toDateString(),
            'type' => 'expense',
            'attachment' => $file,
        ]);

        // 3. Assert Error
        $response->assertSessionHasErrors('attachment');
        
        // Cleanup
        $user->delete();
        $tenant->delete();
    }
}
