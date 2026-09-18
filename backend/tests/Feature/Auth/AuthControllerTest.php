<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

describe('register', function () {
    it('creates a user and returns a token', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.name', 'Ada Lovelace')
            ->assertJsonPath('user.email', 'ada@example.com')
            ->assertJsonMissingPath('user.password')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);

        $user = User::query()->where('email', 'ada@example.com')->first();

        expect($user)->not->toBeNull()
            ->and(Hash::check('password', $user->password))->toBeTrue()
            ->and($user->tokens)->toHaveCount(1);
    });

    it('returns 422 when required fields are missing', function () {
        $this->postJson('/api/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name' => 'The name field is required.',
                'email' => 'The email field is required.',
                'password' => 'The password field is required.',
            ]);
    });

    it('returns 422 when the email is invalid', function () {
        $this->postJson('/api/register', [
            'name' => 'Ada Lovelace',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email' => 'The email field must be a valid email address.',
            ]);
    });

    it('returns 422 when the email has already been taken', function () {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email' => 'The email has already been taken.',
            ]);
    });

    it('returns 422 when the password confirmation does not match', function () {
        $this->postJson('/api/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'password' => 'The password field confirmation does not match.',
            ]);
    });

    it('returns 422 when the password is shorter than 8 characters', function () {
        $this->postJson('/api/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'password' => 'The password field must be at least 8 characters.',
            ]);
    });
});

describe('login', function () {
    it('returns a token for valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'ada@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'ada@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'ada@example.com')
            ->assertJsonMissingPath('user.password')
            ->assertJsonStructure(['token']);

        expect($user->fresh()->tokens)->toHaveCount(1);
    });

    it('returns 422 when required fields are missing', function () {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email' => 'The email field is required.',
                'password' => 'The password field is required.',
            ]);
    });

    it('returns 422 when the credentials do not match', function () {
        User::factory()->create([
            'email' => 'ada@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'ada@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email' => 'These credentials do not match our records.',
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });
});

describe('user', function () {
    it('returns 401 when no token is provided', function () {
        $this->getJson('/api/user')->assertUnauthorized();
    });

    it('returns the authenticated user', function () {
        $user = User::factory()->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('name', 'Ada Lovelace')
            ->assertJsonPath('email', 'ada@example.com')
            ->assertJsonMissingPath('password');
    });
});

describe('logout', function () {
    it('returns 401 when no token is provided', function () {
        $this->postJson('/api/logout')->assertUnauthorized();
    });

    it('revokes the current token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertNoContent();

        expect(PersonalAccessToken::query()->count())->toBe(0);

        // The auth guard caches the user on the shared app instance between HTTP calls.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    });
});
