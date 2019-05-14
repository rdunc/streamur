<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\Twitch;
use App\Models\TwitchChannel;
use App\Models\SocialNetwork;
use App\Models\User;
use App\Models\Site;
use App\Models\Role;
use Validator;
use DB;

class UserController extends Controller
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
        $users = User::all();

        return view('admin.users.users')
            ->with([
                'users'         => $users,
                'pageTitle'     => 'Users - Dashboard',
                'activeTab'     => 'users'
            ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('admin.users.create')
            ->with([
                'pageTitle'     => 'Users - Dashboard',
                'activeTab'     => 'users'
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
        $user = User::where('id', $id)->with('site')->first();

        // delete forums, threads and posts
        if (!is_null($user->site->forums))
        {
            // delete threads
            foreach ($user->site->forums as $forum) 
            {
                // delete posts
                foreach ($forum->threads as $thread)
                {
                    $thread->posts()->withTrashed()->forceDelete();
                }
                $forum->threads()->withTrashed()->forceDelete();
            }         
            // delete forums
            $user->site->forums()->withTrashed()->forceDelete();
        }
        // delete settings
        $user->site->settings()->delete();
        // delete roles
        DB::table('user_roles')->where('site_id', $user->site->id)->delete();
        // delete access checks
        DB::table('access_checks')->where('site_id', $user->site->id)->delete();
        // delete forum permissions
        DB::table('forum_permissions')->where('site_id', $user->site->id)->delete();
        // delete banned records
        $user->site->banned()->delete();
        // delete emoticons
        $user->site->emoticons()->delete();
        // delete site
        $user->site()->delete();
        // delete twitch channel
        $user->twitch_channel()->delete();
        // delete twitch stream
        $user->twitch_stream()->delete();

        // delete user
        $user->delete();

        // redirect
        return redirect()->back()->with(array('success' => 'Success!', 'success-msg' => "User was successfully deleted."));

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
