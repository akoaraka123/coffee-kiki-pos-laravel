<?php

namespace Tests\Feature;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForgotPasswordRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_forgot_password_request_json(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@example.com',
            'role' => 'staff',
        ]);

        $response = $this->postJson('/forgot-password-request', [
            'email' => 'staff@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password reset request sent to admin.');

        $this->assertDatabaseCount('password_reset_requests', 1);
        $this->assertDatabaseHas('password_reset_requests', [
            'user_id' => $user->id,
            'email' => 'staff@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_unknown_email_returns_error_and_creates_nothing(): void
    {
        $response = $this->postJson('/forgot-password-request', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('password_reset_requests', 0);
    }

    public function test_duplicate_pending_request_is_not_created(): void
    {
        User::factory()->create([
            'email' => 'staff@example.com',
            'role' => 'staff',
        ]);

        $this->postJson('/forgot-password-request', ['email' => 'staff@example.com']);
        $this->postJson('/forgot-password-request', ['email' => 'staff@example.com']);

        $this->assertDatabaseCount('password_reset_requests', 1);
    }

    public function test_invalid_email_returns_validation_error(): void
    {
        $response = $this->postJson('/forgot-password-request', [
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_can_list_and_resolve_pending_requests(): void
    {
        $staff = User::factory()->create([
            'email' => 'staff@example.com',
            'role' => 'staff',
        ]);

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->postJson('/forgot-password-request', ['email' => 'staff@example.com']);

        $req = PasswordResetRequest::query()->first();
        $this->assertNotNull($req);

        $this->actingAs($admin);

        $list = $this->getJson('/admin/password-reset-requests?status=pending');
        $list->assertOk();
        $list->assertJsonPath('pending_count', 1);
        $this->assertCount(1, $list->json('data'));

        $patch = $this->patchJson("/admin/password-reset-requests/{$req->id}/resolve");
        $patch->assertOk();
        $this->assertDatabaseHas('password_reset_requests', [
            'id' => $req->id,
            'status' => 'resolved',
        ]);

        $list2 = $this->getJson('/admin/password-reset-requests?status=pending');
        $list2->assertOk();
        $list2->assertJsonPath('pending_count', 0);
    }
}
