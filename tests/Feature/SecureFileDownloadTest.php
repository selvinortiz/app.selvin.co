<?php

namespace Tests\Feature;

use App\Models\SecureFile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecureFileDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_can_view_secure_file_download_page(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        // Attach user to tenant
        $user->tenants()->attach($tenant->id);

        $secureFile = SecureFile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('secure-file.show', $secureFile->access_token));

        $response->assertStatus(200);
        $response->assertViewIs('secure-files.download');
        $response->assertViewHas('secureFile', $secureFile);
    }

    public function test_can_download_file_without_password(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        // Attach user to tenant
        $user->tenants()->attach($tenant->id);

        $secureFile = SecureFile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'password' => null,
        ]);

        // Create a fake file
        Storage::put($secureFile->file_path, 'test file content');

        $response = $this->post(route('secure-file.download', $secureFile->access_token));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', $secureFile->mime_type);
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $secureFile->filename . '"');

        $this->assertEquals(1, $secureFile->fresh()->download_count);
    }

    public function test_cannot_download_without_correct_password(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        // Attach user to tenant
        $user->tenants()->attach($tenant->id);

        $secureFile = SecureFile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'password' => 'correct-password',
        ]);

        $response = $this->post(route('secure-file.download', $secureFile->access_token), [
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['password']);

        $this->assertEquals(0, $secureFile->fresh()->download_count);
    }

    public function test_can_download_with_correct_password(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        // Attach user to tenant
        $user->tenants()->attach($tenant->id);

        $secureFile = SecureFile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'password' => 'correct-password',
        ]);

        // Create a fake file
        Storage::put($secureFile->file_path, 'test file content');

        $response = $this->post(route('secure-file.download', $secureFile->access_token), [
            'password' => 'correct-password',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $secureFile->fresh()->download_count);
    }

    public function test_cannot_download_expired_file(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        // Attach user to tenant
        $user->tenants()->attach($tenant->id);

        $secureFile = SecureFile::factory()->expired()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('secure-file.show', $secureFile->access_token));

        $response->assertStatus(404);
    }

    public function test_cannot_download_after_limit_reached(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        // Attach user to tenant
        $user->tenants()->attach($tenant->id);

        $secureFile = SecureFile::factory()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'download_limit' => 1,
            'download_count' => 1,
        ]);

        $response = $this->get(route('secure-file.show', $secureFile->access_token));

        $response->assertStatus(404);
    }
}
