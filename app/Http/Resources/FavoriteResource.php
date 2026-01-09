<?php

namespace App\Http\Resources;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        switch ($this->favoritable_type) {
            case User::class:
                return $this->transformUserFavorite();
            case Post::class:
                return $this->transformPostFavorite();
            default:
                return [];
        }
    }

    private function transformUserFavorite(): array
    {
        return [
            'id' => $this->favoritable->id,
            'name' => $this->favoritable->name,
        ];
    }

    private function transformPostFavorite(): array
    {
        return [
            'id' => $this->favoritable->id,
            'title' => $this->favoritable->title,
            'body' => $this->favoritable->body,
            'user' => $this->favoritable->user
                ? [
                    'id' => $this->favoritable->user->id,
                    'name' => $this->favoritable->user->name,
                ]
                : null,
        ];
    }
}
