<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\Twitch;
use App\Classes\TwitchData;
use App\Models\TwitchChannel;
use App\Models\SocialNetwork;
use App\Models\Emoticon;
use App\Models\User;
use App\Models\Site;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Forum;
use App\Models\ForumPermission;
use Validator;

class SiteController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @param  name  $name
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Show the sites page to the user.
     *
     * @return Response
     */
    public function index()
    {
        $sites = Site::all();

        return view('admin.sites.sites')
            ->with([
                'sites'         => $sites,
                'pageTitle'     => 'Sites - Dashboard',
                'activeTab'     => 'sites'
            ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('admin.sites.create')
            ->with([
                'pageTitle'     => 'Sites - Dashboard',
                'activeTab'     => 'sites'
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $rules = array(
            'name' => 'required|unique:users,name|alpha_num|max:25'
        );

        // create validator
        $validator = Validator::make($request->all(), $rules);

        // if validation fails redirect back
        if ($validator->fails())
        {
            return redirect()->back()->withInput()->withErrors($validator->errors());
        }

        // get twitch channel
        $twitch = new Twitch($request->get('name'));
        $channel = $twitch->getChannel();
        $stream = $twitch->getStream();
        
        // check if twitch user exists
        if (isset($channel['error']))
        {
            return redirect()->back()->with(array('error' => 'Error!', 'error-msg' => "Twitch user doesn't exist."));
        }

     

        // create user
        $user = User::create([
            'twitch_id'             => $channel['_id'],
            'name'                  => $channel['name'],
            'email'                 => str_random(20)."@subonly.io",
            'avatar'                => $channel['logo'],
            'twitch_token'          => str_random(20),
        ]);

        // create site
        $site = Site::create([
            'owner'     => $user->id,
            'domain'    => str_slug($user->name, '-')
        ]);

        // create site settings
        Setting::create([
            'site_id'    => $site->id
        ]);

        // create twitch channel
        TwitchChannel::create([
            'owner'                             => $user->id,
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
                'owner'                             => $user->id,
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
        $user->attachRole(Role::where('name', 'owner')->first(), $site->id);




        if ($request->has('create-close'))
        {
            return redirect()->route('admin-sites');
        }
        else
        {
            return redirect()->back()->with(array('success' => 'Success!', 'success-msg' => "Site was successfully created."));
        }
    }

    /**
     * Update the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function active($id)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
 
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request)
    {
        
    }

}
