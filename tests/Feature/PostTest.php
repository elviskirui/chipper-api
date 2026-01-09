<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile;
use App\Notifications\NewPostNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\DatabaseMigrations;

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

    public function test_can_store_image_with_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post with Image',
            'body' => 'This is a test post with an image.',
            'image' => UploadedFile::fake()->image('post-image.jpg')
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'image_url',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Post with Image',
                    'body' => 'This is a test post with an image.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post with Image',
            'body' => 'This is a test post with an image.',
        ]);
    }

    public function test_will_store_png_image_with_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post with PNG Image',
            'body' => 'This is a test post with a PNG image.',
            'image' => UploadedFile::fake()->image('post-image.png')
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'image_url',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Post with PNG Image',
                    'body' => 'This is a test post with a PNG image.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post with PNG Image',
            'body' => 'This is a test post with a PNG image.',
        ]);
    }

    public function test_will_store_webp_image_with_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post with WEBP Image',
            'body' => 'This is a test post with a WEBP image.',
            'image' => UploadedFile::fake()->image('post-image.webp')
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'image_url',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Post with WEBP Image',
                    'body' => 'This is a test post with a WEBP image.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post with WEBP Image',
            'body' => 'This is a test post with a WEBP image.',
        ]);
    }

    public function test_will_store_jpg_image_with_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post with JPG Image',
            'body' => 'This is a test post with a JPG image.',
            'image' => UploadedFile::fake()->image('post-image.jpg')
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'image_url',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Post with JPG Image',
                    'body' => 'This is a test post with a JPG image.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post with JPG Image',
            'body' => 'This is a test post with a JPG image.',
        ]);
    }

    public function test_will_store_gif_image_with_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post with GIF Image',
            'body' => 'This is a test post with a GIF image.',
            'image' => UploadedFile::fake()->image('post-image.gif')
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'image_url',
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Post with GIF Image',
                    'body' => 'This is a test post with a GIF image.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post with GIF Image',
            'body' => 'This is a test post with a GIF image.',
        ]);
    }

    public function test_will_not_store_invalid_image_type_with_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post with Invalid Image',
            'body' => 'This is a test post with an invalid image.',
            'image' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);

        $this->assertDatabaseMissing('posts', [
            'title' => 'Test Post with Invalid Image',
            'body' => 'This is a test post with an invalid image.',
        ]);
    }
}
