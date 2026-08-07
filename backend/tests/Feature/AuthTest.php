<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_receives_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']])
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', 'user');
    }

    public function test_user_can_register_and_receives_token(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Nuovo Studente',
            'email' => 'nuovo@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(201)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']])
            ->assertJsonPath('user.email', 'nuovo@example.com')
            ->assertJsonPath('user.role', 'user');

        $this->assertDatabaseHas('users', [
            'email' => 'nuovo@example.com',
            'role' => 'user',
        ]);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/register', [
            'name' => 'Duplicato',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertStatus(422)
            ->assertJsonPath('error', 'email_taken')
            ->assertJsonPath('message', 'Questa email è già registrata.');
    }

    public function test_register_requires_password_confirmation(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Studente',
            'email' => 'studente@example.com',
            'password' => 'password',
            'password_confirmation' => 'diversa',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_requires_minimum_password_length(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Studente',
            'email' => 'studente@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422)
            ->assertJsonPath('error', 'invalid_credentials');
    }

    public function test_login_with_unknown_email_is_rejected(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ])->assertStatus(422);
    }

    public function test_authenticated_user_can_read_profile(): void
    {
        $user = User::factory()->create(['role' => Role::Admin]);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.role', 'admin');
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->assertCount(0, $user->tokens()->get());
    }

    public function test_login_is_throttled_after_many_attempts(): void
    {
        $statuses = [];

        for ($i = 0; $i < 10; $i++) {
            $statuses[] = $this->postJson('/api/auth/login', [
                'email' => 'unknown@example.com',
                'password' => 'wrong',
            ])->getStatusCode();

            if ($statuses[$i] === 429) {
                break;
            }
        }

        $this->assertContains(429, $statuses);
        $this->assertSame(422, $statuses[0]);
    }
}
