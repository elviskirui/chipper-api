<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Requests\CreateFavoriteRequest;
use App\Models\User;
use Illuminate\Http\Response;

/**
 * @group Favorites
 *
 * API endpoints for managing favorites
 */
class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = $request->user()->favorites;
        return FavoriteResource::collection($favorites);
    }

    public function store(CreateFavoriteRequest $request, Post $post)
    {
        $request->user()->favorites()->firstOrCreate([
            'favoritable_id' => $post->id,
            'favoritable_type' => Post::class,
        ]);

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, Post $post)
    {
        $favorite = $request->user()->favorites()
            ->where('favoritable_id', $post->id)
            ->where('favoritable_type', Post::class)
            ->firstOrFail();

        $favorite->delete();

        return response()->noContent();
    }

    public function storeUserFavourite(CreateFavoriteRequest $request, User $user)
    {

        if ($user->id === $request->user()->id) {
            return response()->noContent(Response::HTTP_BAD_REQUEST);
        }

        $request->user()->favorites()->firstOrCreate([
            'favoritable_id' => $user->id,
            'favoritable_type' => User::class,
        ]);

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroyUserFavourite(Request $request, User $user)
    {
        $favorite = $request->user()->favorites()
            ->where('favoritable_id', $user->id)
            ->where('favoritable_type', User::class)
            ->firstOrFail();

        $favorite->delete();

        return response()->noContent();
    }
}
