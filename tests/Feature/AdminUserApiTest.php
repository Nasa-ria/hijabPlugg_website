<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserApiTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'name' => 'New Admin',
        'email' => 'New.Admin@example.com',
        'password' => 'secret-pass-123',
        'is_super_admin' => true,
    ];

    public function test_creates_admin_with_valid_token(): void
    {
        config(['app.admin_api_token' => 'test-token']);

        $this->withToken('test-token')->postJson('/api/admin/users', $this->payload)
            ->assertCreated()
            ->assertJsonPath('user.email', 'new.admin@example.com');

        $user = User::where('email', 'new.admin@example.com')->first();
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue(Hash::check('secret-pass-123', $user->password));
    }

    public function test_rejects_wrong_token(): void
    {
        config(['app.admin_api_token' => 'test-token']);

        $this->withToken('wrong')->postJson('/api/admin/users', $this->payload)->assertUnauthorized();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_endpoint_is_off_without_token_configured(): void
    {
        config(['app.admin_api_token' => null]);

        $this->withToken('')->postJson('/api/admin/users', $this->payload)->assertNotFound();
    }

    public function test_regular_admin_needs_a_permission(): void
    {
        config(['app.admin_api_token' => 'test-token']);

        $this->withToken('test-token')
            ->postJson('/api/admin/users', ['is_super_admin' => false] + $this->payload)
            ->assertUnprocessable();

        $this->withToken('test-token')
            ->postJson('/api/admin/users', ['is_super_admin' => false, 'admin_permissions' => ['orders']] + $this->payload)
            ->assertCreated()
            ->assertJsonPath('user.admin_permissions', ['orders']);
    }
}
