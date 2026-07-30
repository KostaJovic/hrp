<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function spa(): static
    {
        // Sanctum treats requests as stateful based on the Origin/Referer host.
        return $this->withHeader('Referer', 'http://localhost:5173');
    }

    public function test_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->spa()
            ->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password_fails_validation(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->spa()
            ->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_user_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/user')->assertUnauthorized();
    }

    public function test_authenticated_user_endpoint_returns_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->spa()->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'secret-password'])->assertOk();
        $this->spa()->postJson('/api/v1/logout')->assertNoContent();

        // Guards memoize the resolved user within one test process; a real
        // browser sends a fresh request, so drop the cache before asserting.
        $this->app['auth']->forgetGuards();

        $this->spa()->getJson('/api/v1/user')->assertUnauthorized();
    }
}
