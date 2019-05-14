<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\Twitch;
use App\Models\TwitchChannel;
use App\Models\SocialNetwork;
use App\Models\ForumPermission;
use App\Models\Setting;
use App\Models\Forum;
use App\Models\User;
use App\Models\Site;
use App\Models\Role;
use Validator;
use Gate;
use Auth;

class SettingController extends Controller
{

    private $site;

    /**
     * Create a new controller instance.
     *
     * @param  name  $name
     * @return void
     */
    public function __construct()
    {
        $this->site = Site::where('domain', session('site_name'))->first();

        // check permissions
        if (Gate::denies('manage-settings')) {
            abort(403);
        }
    }

    /**
     * Show the sites page to the user.
     *
     * @return Response
     */
    public function index()
    {
        $settings = Setting::where('site_id', $this->site->id)->first();
        if (!$settings)
        {
            // create site settings
            $settings = Setting::create([
                'site_id'    => $this->site->id
            ]);
        }
        return view('dashboard.settings.settings')
            ->with([
                'settings'     => $settings, 
                'pageTitle'    => 'Settings - Dashboard',
                'activeTab'    => 'settings'
            ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $rules = [
            'live'          => 'boolean',
            'twitter'       => 'alpha_num|max:150',
            'facebook'      => 'alpha_num|max:150',
            'youtube'       => 'alpha_num|max:150',
            'instagram'     => 'alpha_num|max:150',
            'store'         => 'url|max:250',
            'donations'     => 'url|max:250',
        ];

        // create validator
        $validator = Validator::make($request->all(), $rules);

        // if validation fails redirect back
        if ($validator->fails())
        {
            return redirect()->back()->withInput()->withErrors($validator->errors());
        }

        $settings = Setting::where('site_id', session('site_id'))->first();
        if (!$settings)
        {
            $settings               = new Setting;
            $settings->site_id      = session('site_id');
        }
        $settings->live             = $request->get('live');
        $settings->twitter          = (!empty($request->get('twitter')) ? $request->get('twitter') : null);
        $settings->facebook         = (!empty($request->get('facebook')) ? $request->get('facebook') : null);
        $settings->youtube          = (!empty($request->get('youtube')) ? $request->get('youtube') : null);
        $settings->instagram        = (!empty($request->get('instagram')) ? $request->get('instagram') : null);
        $settings->store            = (!empty($request->get('store')) ? $request->get('store') : null);
        $settings->donations        = (!empty($request->get('donations')) ? $request->get('donations') : null);
        $settings->save();

        return redirect()->back()
            ->with([
                'success'      => 'Success!', 
                'success-msg'  => "Settings successfully saved.",
            ]);
    }

    /**
     * Update the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function active($siteName, $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($siteName, $id, $role)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($siteName, $id)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($siteName, Request $request)
    {

    }

}
