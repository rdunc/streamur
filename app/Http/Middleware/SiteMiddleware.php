<?php

namespace App\Http\Middleware;

use App\Models\Site;
use App\Classes\Twitch;
use App\Models\TwitchChannel;
use App\Models\Role;
use App\Models\Banned;
use Closure;
use Carbon;
use Auth;
use Gate;

class SiteMiddleware
{
    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // set the site id & name
        session(['site_id' => Site::where('domain', $request->route()->parameters()['name'])->value('id')]);
        session(['site_name' => $request->route()->parameters()['name']]);

        // redirect if site not live
        if (!Site::find(session('site_id'))->settings->live)
        {
            // check permissions
            if (Gate::denies('view-offline-site')) {
                return redirect()->route('frontpage');
            }
        }

        // update site data
        $twitch = new Twitch(session('site_name'));

        // update user data
        if (Auth::check())
        {
            $user = Auth::user();
            $check = $user->accessCheck(session('site_id'));

            // ban check
            if ($user->banned(session('site_id')))
            {
                // get ban record
                $banned = Banned::where('user_id', $user->id)->where('site_id', session('site_id'))->first();
                // check if user needs to be unbanned
                if ($banned->expires < Carbon::now())
                {
                    $banned->delete();
                }
                else
                {
                    return view('site.suspended')->with(compact('banned'));
                }
            }

            // check only once every minute
            if ($check->updated_at < Carbon::now()->subMinutes(1))
            {
                // following
                if ($twitch->isFollowing($user))
                {
                    // check if user doesn't already have role
                    if (!$user->hasRole('follower', session('site_id')))
                    {
                        $user->giveRole('follower', session('site_id'));
                    }
                }
                else
                {
                    $user->removeRole('follower', session('site_id'));
                }
                // subscribed
                if ($twitch->isSubscribed($user))
                {
                    // check if user doesn't already have role
                    if (!$user->hasRole('subscriber', session('site_id')))
                    {
                        $user->giveRole('subscriber', session('site_id'));
                    }
                }
                else
                {
                    $user->removeRole('subscriber', session('site_id'));
                }
                $check->touch();
            }
        }

        return $next($request);
    }

}