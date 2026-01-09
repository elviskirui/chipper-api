<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_guest_can_not_favorite_a_post()
    {
        $post = Post::factory()->create();

        $this->postJson(route('favorites.store', ['post' => $post]))
            ->assertStatus(401);
    }

    public function test_a_user_can_favorite_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $post->id,
            'favoritable_type' => Post::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_remove_a_post_from_his_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $post->id,
            'favoritable_type' => Post::class,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'favoritable_id' => $post->id,
            'favoritable_type' => Post::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_not_remove_a_non_favorited_item()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNotFound();
    }

    public function test_a_user_can_favorite_another_user()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.users.store', ['user' => $user2]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $user2->id,
            'favoritable_type' => User::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_remove_another_user_from_his_favorites()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.users.store', ['user' => $user2]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $user2->id,
            'favoritable_type' => User::class,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.users.destroy', ['user' => $user2]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'favoritable_id' => $user2->id,
            'favoritable_type' => User::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_cannot_favorite_himself()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.users.store', ['user' => $user]))
            ->assertStatus(400);

        $this->assertDatabaseMissing('favorites', [
            'favoritable_id' => $user->id,
            'favoritable_type' => User::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_cannot_remove_a_non_favorited_user()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.users.destroy', ['user' => $user2]))
            ->assertNotFound();
    }

    public function test_a_user_cannot_favorite_the_same_post_multiple_times()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseCount('favorites', 1);

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $post->id,
            'favoritable_type' => Post::class,
            'user_id' => $user->id,
        ]);
    }

    public function test_as_user_cannot_delete_a_favorite_of_another_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user1)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->actingAs($user2)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNotFound();

        $this->assertDatabaseHas('favorites', [
            'favoritable_id' => $post->id,
            'favoritable_type' => Post::class,
            'user_id' => $user1->id,
        ]);
    }
}
