<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\Twitch;
use App\Models\TwitchChannel;
use App\Models\SocialNetwork;
use App\Models\ForumPermission;
use App\Models\Banned;
use App\Models\Forum;
use App\Models\User;
use App\Models\Site;
use App\Models\Role;
use Validator;
use Gate;
use Auth;
use DB;

class UserController extends Controller
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
        if (Gate::denies('manage-users')) {
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
        // get banned users
        $banned = DB::table('banned');      
        $banned->join('users', 'banned.user_id', '=', 'users.id');
        $banned->where('site_id', $this->site->id);
        $banned = $banned->paginate(10);

        return view('dashboard.users.users')
            ->with([
                'users'        => $banned, 
                'pageTitle'    => 'Banned - Dashboard',
                'activeTab'    => 'users'
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
    public function store($siteName, Request $request)
    {

    }

    /**
     * Update the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function ban($siteName, Request $request)
    {   
        $rules = [
            'name'      => 'required|exists:users,name',
            'reason'    => 'required|string|max:250',
            'date'      => 'required|date'
        ];

        // create validator
        $validator = Validator::make($request->all(), $rules);

        // if validation fails redirect back
        if ($validator->fails())
        {
            return redirect()->back()->withInput()->withErrors($validator->errors());
        }

        // check if user already banned
        $user = User::where('name', $request->get('name'))->first();

        // check if user can be banned
        if (!$user->hasRole('owner', $this->site->id))
        {
            $banned = Banned::where('user_id', $user->id)->where('site_id', $this->site->id)->first();
            if (!$banned)
            {
                // remove roles
                $user->removeRole('administrator', $this->site->id);
                $user->removeRole('moderator', $this->site->id);
                // ban
                Banned::create([
                    'user_id'       => $user->id,
                    'site_id'       => $this->site->id,
                    'staff_id'      => Auth::user()->id,
                    'reason'        => $request->get('reason'),
                    'expires'       => date("Y-m-d H:i:s A", strtotime($request->get('date')))
                ]);
            }
            else
            {
                return redirect()->back()->withInput()->with(['error' => 'Error!', 'error-msg' => "That user is already banned."]);
            }
        }
        else
        {
            return redirect()->back()->withInput()->with(['error' => 'Error!', 'error-msg' => "You can't ban that user."]);
        }

        return redirect()->back()->with(['success' => 'Success!', 'success-msg' => "User successfully banned."]);
    }

    /**
     * Update the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function unban($siteName, $id)
    {   
        // check if user already banned
        $banned = Banned::where('user_id', $id)->where('site_id', $this->site->id)->first();
        if ($banned)
        {
            $banned->delete();
        }

        return redirect()->back()->with(['success' => 'Success!', 'success-msg' => "User successfully unbanned."]);
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
