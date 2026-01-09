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

    public function test_returns_users_user_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.users.store', ['user' => $user2]))
            ->assertCreated();

        $response = $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk();

        $response->assertJsonCount(1, 'data.posts')
            ->assertJsonCount(1, 'data.users');
    }
    public function test_returns_empty_favorites_when_user_has_no_favorites()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk();

        $response->assertJsonCount(0, 'data.posts')
            ->assertJsonCount(0, 'data.users');
    }
    public function test_returns_users_post_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $post2 = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post2]))
            ->assertCreated();

        $response = $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk();

        $response->assertJsonCount(2, 'data.posts')
            ->assertJsonCount(0, 'data.users');
    }

    public function test_returns_correct_post_favorites_for_different_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();

        $this->actingAs($user1)
            ->postJson(route('favorites.store', ['post' => $post1]))
            ->assertCreated();

        $this->actingAs($user2)
            ->postJson(route('favorites.store', ['post' => $post2]))
            ->assertCreated();

        $response1 = $this->actingAs($user1)
            ->getJson(route('favorites.index'))
            ->assertOk();

        $response1->assertJsonCount(1, 'data.posts')
            ->assertJsonCount(0, 'data.users')
            ->assertJsonFragment(['id' => $post1->id])
            ->assertJsonMissing(['id' => $post2->id]);

        $response2 = $this->actingAs($user2)
            ->getJson(route('favorites.index'))
            ->assertOk();

        $response2->assertJsonCount(1, 'data.posts')
            ->assertJsonCount(0, 'data.users')
            ->assertJsonFragment(['id' => $post2->id])
            ->assertJsonMissing(['id' => $post1->id]);
    }



    public function test_returns_empty_post_favorites_when_user_has_no_post_favorites()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.users.store', ['user' => $user2]))
            ->assertCreated();

        $response = $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk();

        $response->assertJsonCount(0, 'data.posts')
            ->assertJsonCount(1, 'data.users');
    }
}
