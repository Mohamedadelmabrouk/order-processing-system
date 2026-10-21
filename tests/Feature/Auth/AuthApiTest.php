<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesJwtUser;

class AuthApiTest extends TestCase
{
    use CreatesJwtUser;
    use RefreshDatabase;

    /**
     * test user can register
     */
    public function testRegister(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['user', 'access_token', 'token_type', 'expires_in']]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    }

    /**
     * test user can login with valid credentials
     */
    public function testLogin(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['access_token']]);
    }

    /**
     * test login fails with invalid credentials
     */
    public function testLoginFails(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    /**
     * test me requires authentication
     */
    public function testMeUnauthorized(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    /**
     * test authenticated user can view profile
     */
    public function testMe(): void
    {
        $user = $this->actingAsJwt();

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    /**
     * test authenticated user can logout
     */
    public function testLogout(): void
    {
        $this->actingAsJwt();

        $this->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
