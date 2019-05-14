<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\Twitch;
use App\Models\TwitchChannel;
use App\Models\SocialNetwork;
use App\Models\ForumPermission;
use App\Models\Forum;
use App\Models\User;
use App\Models\Site;
use App\Models\Role;
use Validator;
use Gate;
use Auth;

class StaffController extends Controller
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
        if (Gate::denies('manage-staff')) {
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
        // get admins
        $admins = User::whereHas('roles', function($q) {
                $q->where('name', 'administrator')->where('site_id', $this->site->id);
            })->paginate(10);

        // get mods
        $mods = User::whereHas('roles', function($q) {
                $q->where('name', 'moderator')->where('site_id', $this->site->id);
            })->paginate(10);


        return view('dashboard.staff.staff')
            ->with([
                'mods'         => $mods, 
                'admins'       => $admins,
                'pageTitle'    => 'Staff - Dashboard',
                'activeTab'    => 'staff'
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
        $rules = [
            'admin-name'    => 'required|exists:users,name',
            'type'          => 'required|in:admin,mod',
        ];

        // set custom field name
        $attributeNames = [
            'admin-name' => 'name'
        ];

        // create validator
        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributeNames);

        // if validation fails redirect back
        if ($validator->fails())
        {
            return redirect()->back()->withInput()->withErrors($validator->errors());
        }

        // get user
        $user = User::where('name', $request->get('admin-name'))->first();

        // check if user is owner already
        if (!$user->hasRole('owner', $this->site->id))
        {
            // if type is admin
            if ($request->get('type') == 'admin')
            {
                // check if user is already admin
                if ($user->hasRole('administrator', $this->site->id))
                {
                    return redirect()->back()->with(['error' => 'Error!', 'error-msg' => "This user is already an administrator."]);
                }
                $user->removeRole('moderator', $this->site->id);
                $user->giveRole('administrator', $this->site->id);
            }
            // if type is mod
            else if ($request->get('type') == 'mod')
            {
                // check if user is already mod
                if ($user->hasRole('moderator', $this->site->id))
                {
                    return redirect()->back()->with(['error' => 'Error!', 'error-msg' => "This user is already a moderator."]);
                }
                $user->removeRole('administrator', $this->site->id);
                $user->giveRole('moderator', $this->site->id);
            }
        }
        else
        {
            return redirect()->back()->with(['error' => 'Error!', 'error-msg' => "You can't add that user."]);
        }

        return redirect()->back()
            ->with([
                'success'      => 'Success!', 
                'success-msg'  => "Successfully added ".($request->get('type') == 'admin' ? 'administrator' : 'moderator').".",
                'activeTab'    => 'staff'
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
        // get user
        $user = User::find($id);
        if ($role == 'admin')
        {
            $user->removeRole('administrator', $this->site->id);
            return redirect()->back()->with(array('success' => 'Success!', 'success-msg' => "Administrator was successfully demoted."));
        }
        if ($role == 'mod')
        {
            $user->removeRole('moderator', $this->site->id);
            return redirect()->back()->with(array('success' => 'Success!', 'success-msg' => "Moderator was successfully demoted."));
        }
        return redirect()->back()
            ->with([
                'error'        => 'Error!', 
                'error-msg'    => "Something went wrong.",
                'activeTab'    => 'staff'
            ]);
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
