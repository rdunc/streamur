<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Site;
use App\Classes\Twitch;

class DataController extends Controller
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
     * Update twitch data for site
     *
     * @return Response
     */
    public function updateData()
    {
        // update site data
        $twitch = new Twitch(session('site_name'));
        $result = $twitch->updateData();
        return $result;
    }

    /**
     * Return twitch channel status
     *
     * @return Json
     */
    public function status($siteName, Request $request)
    {
        // set callback
        if ($request->has('callback'))
        {
            $callback = $request->input('callback');
        }
        else
        {
            $callback = null;
        }

        // get site
        $site = Site::where('id', session('site_id'))->with('user.twitch_stream', 'user.twitch_channel')->first();
        if ($site->user->twitch_stream)
        {
            return response()->json(['live' => true, 'status' => $site->user->twitch_channel->status], 200, [], JSON_UNESCAPED_SLASHES)->setCallback($callback);
        }
        else
        {
            return response()->json(['live' => false, 'status' => $site->user->twitch_channel->status], 200, [], JSON_UNESCAPED_SLASHES)->setCallback($callback);
        }
    }

}
