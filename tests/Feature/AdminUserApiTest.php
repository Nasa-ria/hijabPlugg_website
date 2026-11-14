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

    protected function setUp(): void
    {
        parent::setUp();
        // The 5-per-minute limit would trip across these tests; it isn't what they check
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

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

    public function test_repairs_existing_user_with_plain_text_password(): void
    {
        config(['app.admin_api_token' => 'test-token']);

        // Simulate a row typed into the database by hand: password stored as plain text
        $id = \DB::table('users')->insertGetId([
            'name' => 'Old', 'email' => 'new.admin@example.com', 'password' => 'plain-text',
            'role' => 'customer', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->withToken('test-token')->postJson('/api/admin/users', $this->payload)
            ->assertOk()
            ->assertJsonPath('message', 'Admin updated.');

        $user = User::find($id);
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue(Hash::check('secret-pass-123', $user->password));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_validation_errors_are_json_without_accept_header(): void
    {
        config(['app.admin_api_token' => 'test-token']);

        // Plain form post, no Accept header: must not redirect to the home page
        $this->withToken('test-token')->post('/api/admin/users', ['email' => 'not-an-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
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
