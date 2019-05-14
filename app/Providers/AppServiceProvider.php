<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Forum;
use App\Models\Thread;
use App\Models\Post;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Forum::restoring(function($forum) {
            $forum->deleted_by = null;
            $forum->save();
            foreach ($forum->threads as $thread)
            {
                $thread->deleted_by = null;
                $thread->save();
                foreach ($thread->posts as $post)
                {
                    $post->deleted_by = null;
                    $post->save();
                }
            }

        });
        Thread::restoring(function($thread) {
            $thread->deleted_by = null;
            $thread->save();
            foreach ($thread->posts as $post)
            {
                $post->deleted_by = null;
                $post->save();
            }
        });
        Post::restoring(function($post) {
            $post->deleted_by = null;
            $post->save();
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
