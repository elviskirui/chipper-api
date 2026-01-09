<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewPostNotification;
use Tests\TestCase;

class PostTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_guest_can_not_create_a_post()
    {
        $response = $this->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertStatus(401);
    }

    public function test_a_user_can_create_a_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Post',
                    'body' => 'This is a test post.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);
    }

    public function test_a_user_can_update_a_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Original title',
            'body' => 'Original body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($user)->putJson(route('posts.update', ['post' => $id]), [
            'title' => 'Updated title',
            'body' => 'Updated body.',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => 'Updated title',
                    'body' => 'Updated body.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Updated title',
            'body' => 'Updated body.',
            'id' => $id,
        ]);
    }

    public function test_a_user_can_not_update_a_post_by_other_user()
    {
        $john = User::factory()->create(['name' => 'John']);
        $jack = User::factory()->create(['name' => 'Jack']);

        $response = $this->actingAs($john)->postJson(route('posts.store'), [
            'title' => 'Original title',
            'body' => 'Original body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($jack)->putJson(route('posts.update', ['post' => $id]), [
            'title' => 'Updated title',
            'body' => 'Updated body.',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'title' => 'Original title',
            'body' => 'Original body.',
            'id' => $id,
        ]);
    }

    public function test_a_user_can_destroy_one_of_his_posts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'My title',
            'body' => 'My body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($user)->deleteJson(route('posts.destroy', ['post' => $id]));

        $response->assertNoContent();

        $this->assertDatabaseMissing('posts', [
            'id' => $id,
        ]);
    }

    public function test_notification_is_sent_to_multiple_favorite_users()
    {
        Notification::fake();

        $postCreator = User::factory()->create();
        $favoriteUser1 = User::factory()->create();
        $favoriteUser2 = User::factory()->create();
        $nonFavoriteUser = User::factory()->create();

        $this->actingAs($favoriteUser1)->postJson(route('favorites.storeUserFavourite', ['user' => $postCreator->id]));
        $this->actingAs($favoriteUser2)->postJson(route('favorites.storeUserFavourite', ['user' => $postCreator->id]));


        $response = $this->actingAs($postCreator)->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertCreated();

        Notification::assertSentTo($favoriteUser1, NewPostNotification::class);
        Notification::assertSentTo($favoriteUser2, NewPostNotification::class);

        Notification::assertNotSentTo($nonFavoriteUser, NewPostNotification::class);
    }

    public function test_notification_is_not_sent_when_no_favorite_users()
    {
        Notification::fake();

        $postCreator = User::factory()->create();

        $response = $this->actingAs($postCreator)->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertCreated();

        Notification::assertNothingSent();
    }
}
