<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

use function Laravel\Prompts\info;

class SendFavoritesNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public $user;
    public $post;
    /**
     * Create a new job instance.
     */
    public function __construct(Post $post, User $user)
    {
        $this->post = $post;
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get users who favorited this user
        $userFavorites = $this->user->favorited()
            ->where('favoritable_type', User::class)
            ->get();

        if ($userFavorites->count() < 1) {
            return;
        }

        // Extract the actual user models from the favoritable relationship unique by id to avoid any duplicate emails
        $usersToNotify = $userFavorites->map(function ($favorite) {
            return $favorite->user;
        })->unique('id');

        Log::info('Sending new post notifications to users who favorited this user.', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'email_count' => $usersToNotify->count(),
        ]);

        // Notify each user about the new post
        Notification::send(
            $usersToNotify,
            new NewPostNotification($this->post, $this->user)
        );
    }
}
