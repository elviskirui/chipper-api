<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportUsersFromUrlCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_url_does_not_proceed(): void
    {
        $this->artisan('command:import-users-from-url')
            ->expectsQuestion('Enter the URL:', '')
            ->expectsOutput('URL is required.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_limit_does_not_proceed(): void
    {
        $mockUsers = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];

        Http::fake([
            'https://testurl.com/users' => Http::response($mockUsers),
        ]);

        $this->artisan('command:import-users-from-url')
            ->expectsQuestion('Enter the URL:', 'https://testurl.com/users')
            ->expectsQuestion('Enter the limit:', '0')
            ->expectsOutput('Enter a valid limit.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }
    public function test_correct_limit_is_stored_in_database(): void
    {
        $mockUsers = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['name' => 'Bob Johnson', 'email' => 'bob@example.com'],
            ['name' => 'Alice Brown', 'email' => 'alice@example.com'],
        ];

        Http::fake([
            'https://testurl.com/users' => Http::response($mockUsers),
        ]);

        $this->artisan('command:import-users-from-url')
            ->expectsQuestion('Enter the URL:', 'https://testurl.com/users')
            ->expectsQuestion('Enter the limit:', '2')
            ->expectsOutput('2 Users imported successfully.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', ['name' => 'John Doe', 'email' => 'john@example.com']);
        $this->assertDatabaseHas('users', ['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'bob@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'alice@example.com']);

        // Verify passwords are hashed and not empty
        $johnUser = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($johnUser->password);
        $this->assertNotEquals('john@example.com', $johnUser->password);
    }

    public function test_negative_limit_does_not_proceed(): void
    {
        $mockUsers = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
        ];

        Http::fake([
            'https://example.com/users' => Http::response($mockUsers),
        ]);

        $this->artisan('command:import-users-from-url')
            ->expectsQuestion('Enter the URL:', 'https://example.com/users')
            ->expectsQuestion('Enter the limit:', '-5')
            ->expectsOutput('Enter a valid limit.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }
    public function test_duplicate_users_are_skipped(): void
    {

        User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $mockUsers = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];

        Http::fake([
            'https://testurl.com/users' => Http::response($mockUsers),
        ]);

        $this->artisan('command:import-users-from-url')
            ->expectsQuestion('Enter the URL:', 'https://testurl.com/users')
            ->expectsQuestion('Enter the limit:', '2')
            ->expectsOutput('2 Users imported successfully.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', ['name' => 'Jane Smith', 'email' => 'jane@example.com']);
        $this->assertDatabaseHas('users', ['name' => 'John Doe', 'email' => 'john@example.com']);
    }
}
