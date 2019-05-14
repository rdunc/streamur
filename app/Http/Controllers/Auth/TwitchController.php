<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Site;
use App\Models\TwitchChannel;
use App\Models\TwitchStream;
use App\Models\Role;
use App\Models\Emoticon;
use App\Models\Setting;
use App\Models\Forum;
use App\Models\ForumPermission;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Http\Request;
use App\Classes\Twitch;
use App\Classes\TwitchData;
use Socialite;
use Auth;

class TwitchController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    protected $redirectPath = '/';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'getLogout']);
    }

    /**
     * Redirect the user to the Twitch authentication page.
     *
     * @return Response
     */
    public function redirectToProvider()
    {
        return Socialite::with('twitch')->scopes(['user_read user_subscriptions'])->redirect();
    }

    /**
     * Obtain the user information from Twitch.
     *
     * @return Response
     */
    public function handleProviderCallback(Request $request)
    {
        if ($request->has('error'))
        {
            return redirect('/');
        }

        try
        {
            $user = Socialite::driver('twitch')->user();
        }
        catch (Exception $e)
        {
            return redirect('auth/twitch');
        }

        // find user or create new one
        $authUser = $this->findOrCreateUser($user);

        // login
        Auth::login($authUser, true);

        // find site or create one
        $this->createSite();

        return redirect()->back();
    }

    /**
     * Return user if exists; create and return if doesn't
     *
     * @param $twitchUser
     * @return User
     */
    private function findOrCreateUser($twitchUser)
    {
        if ($authUser = User::where('twitch_id', $twitchUser->id)->first())
        {
            $authUser->twitch_token = $twitchUser->token;
            $authUser->save();
            return $authUser;
        }

        return User::create([
            'twitch_id'             => $twitchUser->id,
            'name'                  => $twitchUser->name,
            'email'                 => $twitchUser->email,
            'avatar'                => $twitchUser->avatar,
            'twitch_token'          => $twitchUser->token,
        ]);
    }

    /**
     * Create a new site.
     *
     * @return Response
     */
    public function createSite()
    {
        // check if user has a website already or if one has to be made
        if (Site::where('owner', Auth::user()->id)->count() == 0)
        {

            // create site
            $site = Site::create([
                'owner'     => Auth::user()->id,
                'domain'    => str_slug(Auth::user()->name, '-')
            ]);

            // create site settings
            Setting::create([
                'site_id'    => $site->id
            ]);

            // get twitch channel
            $twitch = new Twitch(Auth::user()->name);
            $channel = $twitch->getChannel();
            $stream = $twitch->getStream();

            // create twitch channel
            TwitchChannel::create([
                'owner'                             => Auth::user()->id,
                'mature'                            => $channel['mature'],
                'status'                            => $channel['status'],
                'broadcaster_language'              => $channel['broadcaster_language'],
                'display_name'                      => $channel['display_name'],
                'game'                              => $channel['game'],
                'language'                          => $channel['language'],
                'twitch_id'                         => $channel['_id'],
                'name'                              => $channel['name'],
                'twitch_created_at'                 => $channel['created_at'],
                'twitch_updated_at'                 => $channel['updated_at'],
                'logo'                              => $channel['logo'],
                'banner'                            => $channel['banner'],
                'video_banner'                      => $channel['video_banner'],
                'background'                        => $channel['background'],
                'profile_banner'                    => $channel['profile_banner'],
                'profile_banner_background_color'   => $channel['profile_banner_background_color'],
                'partner'                           => $channel['partner'],
                'sub_badge'                         => $twitch->getBadge(),
                'url'                               => $channel['url'],
                'views'                             => $channel['views'],
                'followers'                         => $channel['followers'],
            ]);
    
            // only add stream if they are currently live
            if (isset($stream['stream']) && !is_null($stream['stream']))
            {
                // create twitch channel
                TwitchStream::create([
                    'owner'                             => Auth::user()->id,
                    'game'                              => $stream['stream']['game'],
                    'name'                              => $stream['stream']['channel']['name'],
                    'viewers'                           => $stream['stream']['viewers'],
                    'average_fps'                       => $stream['stream']['average_fps'],
                    'video_height'                      => $stream['stream']['video_height'],
                    'twitch_created_at'                 => $stream['stream']['created_at'],
                    'twitch_id'                         => $stream['stream']['_id'],
                    'delay'                             => $stream['stream']['delay'],
                    'is_playlist'                       => $stream['stream']['is_playlist'],
                    'preview'                           => $stream['stream']['preview']['template']
                ]);
            }

            // create general forum
            $generalForum = Forum::create([
                'site_id'       => $site->id,
                'title'         => 'General',
                'icon'          => '#787878',
                'slug'          => 'general',
                'description'   => 'General forums.'
            ]);
            // create sub only forum if partnered
            if ($channel['partner'])
            {
                 // create general forum
                $subForum = Forum::create([
                    'site_id'       => $site->id,
                    'title'         => 'Subscriber Only',
                    'icon'          => '#6441a5',
                    'slug'          => 'subscriber-only',
                    'description'   => 'Subscriber only forums.'
                ]);
                // save forum permissions
                ForumPermission::create([
                    'forum_id'  => $subForum->id,
                    'site_id'   => $site->id,
                    'role_id'   => Role::where('name', 'subscriber')->value('id')
                ]);
                // save emoticons
                $twitchData = new TwitchData();
                $emoticons = $twitchData->getEmoticons($site->domain);
                foreach ($emoticons['emoticons'] as $emoticon)
                {
                    if ($emoticon['subscriber_only'])
                    {
                        $emote                      = Emoticon::firstOrCreate(['regex' => $emoticon['regex']]);
                        $emote->site_id             = $site->id;
                        $emote->regex               = $emoticon['regex'];
                        $emote->width               = $emoticon['width'];
                        $emote->height              = $emoticon['height'];
                        $emote->url                 = $emoticon['url'];
                        $emote->state               = $emoticon['state'];
                        $emote->subscriber_only     = $emoticon['subscriber_only'];
                        $emote->save();
                    }  
                }
            }

            // give user owner role
            Auth::user()->attachRole(Role::where('name', 'owner')->first(), $site->id);

        }

        return redirect()->route('site', [str_slug(Auth::user()->name, '-')]);
       
    }
}
