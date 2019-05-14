<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [];

    /**
     * Register any application authentication / authorization services.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function boot(GateContract $gate)
    {
        parent::registerPolicies($gate);

        // always return true for owners of site or staff
        $gate->before(function ($user, $ability) {
            if ($user->hasRole('developer')) {
                return true;
            }
            if ($user->hasRole('owner', session('site_id'))) {
                return true;
            }
            if ($user->hasRole('administrator', session('site_id'))) {
                return true;
            }
        });

        // view offline site
        $gate->define('view-offline-site', function ($user) {
            if ($user->hasRole('moderator', session('site_id'))) {
                return true;
            }
        });

        // view forum
        $gate->define('view-forum', function ($user, $forum) {
            if ($user->hasRole('moderator', session('site_id')) && !$forum->adminOnly()) {
                return true;
            } else {
                return $forum->access($user);
            }
        });

        // view thread
        $gate->define('view-thread', function ($user, $thread) {
            if ($user->hasRole('moderator', session('site_id')) && !$thread->forum->adminOnly()) {
                return true;
            } else {
                return $thread->forum->access($user);
            }
        });

        // view post
        $gate->define('view-post', function ($user, $post) {
            if ($user->hasRole('moderator', session('site_id')) && !$post->thread->forum->adminOnly()) {
                return true;
            } else {
                return $post->thread->forum->access($user);
            }
        });

        // edit post
        $gate->define('edit-post', function ($user, $post) {
            if ($user->hasRole('moderator', session('site_id')) || $post->user_id == $user->id) {
                return true;
            }
        });

        // delete post
        $gate->define('delete-post', function ($user) {
            if ($user->hasRole('moderator', session('site_id'))) {
                return true;
            }
        });

        // manage forums
        $gate->define('manage-forums', function ($user) {
            if ($user->hasRole('moderator', session('site_id'))) {
                return true;
            }
        });

        // manage staff
        $gate->define('manage-staff', function ($user) {
            if ($user->hasRole('administrator', session('site_id'))) {
                return true;
            }
        });

        // manage users
        $gate->define('manage-users', function ($user) {
            if ($user->hasRole('moderator', session('site_id'))) {
                return true;
            }
        });

        // manage settings
        $gate->define('manage-settings', function ($user) {
            if ($user->hasRole('administrator', session('site_id'))) {
                return true;
            }
        });

        // delete forum
        $gate->define('delete-forum', function ($user) {
            if ($user->hasRole('administrator', session('site_id'))) {
                return true;
            }
        });

        // post media
        $gate->define('post-media', function ($user) {
            if ($user->hasRole('administrator', session('site_id'))) {
                return true;
            }
            if ($user->hasRole('moderator', session('site_id'))) {
                return true;
            }
            if ($user->hasRole('subscriber', session('site_id'))) {
                return true;
            }
        });

        // Dynamically register permissions with Laravel's Gate.
        /*foreach ($this->getPermissions() as $permission) {
            $gate->define($permission->name, function ($user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }*/

    }
}
