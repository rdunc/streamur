<?php

namespace App\Http\Controllers\Site;

use App\Models\User;
use App\Models\Site;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Auth;

class SiteController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Show the site for the given name.
     *
     * @param  string  $siteName
     * @return Response
     */
    public function index($siteName)
    {
        $site = Site::where('domain', $siteName)->with('user.twitch_channel', 'user.twitch_stream', 'forums.threads.posts')->first();
        
        if ($site)
        {
            return redirect()->route('forums', $siteName);
            //return view('site.home', ['site' => $site, 'activeTab' => 'home']);
        }
        else
        {
            abort(404);
        }
    }

}
