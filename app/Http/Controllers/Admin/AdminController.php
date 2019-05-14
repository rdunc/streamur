<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\TwitchData;
use App\Models\Emoticon;

class AdminController extends Controller
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
     * Show the admin panel
     *
     * @return Response
     */
    public function admin()
    {
        $emoticons = Emoticon::where('subscriber_only', false)->paginate(10);

        return view('admin.index')
            ->with([
                'pageTitle'     => 'Dashboard',
                'activeTab'     => 'home',
                'emoticons'     => $emoticons
            ]);
    }

    public function saveEmotes()
    {
        $twitchData = new TwitchData();
        $emoticons = $twitchData->getEmoticons('global');

        foreach ($emoticons['emoticons'] as $emoticon)
        {
            $emote                      = Emoticon::firstOrCreate(['regex' => $emoticon['regex']]);
            $emote->site_id             = null;
            $emote->regex               = $emoticon['regex'];
            $emote->width               = $emoticon['width'];
            $emote->height              = $emoticon['height'];
            $emote->url                 = $emoticon['url'];
            $emote->state               = $emoticon['state'];
            $emote->subscriber_only     = $emoticon['subscriber_only'];
            $emote->save();
        }

        return redirect()->back()->with(['success' => 'Success!', 'success-msg' => "Emotes successfully updated."]);
    }

}
