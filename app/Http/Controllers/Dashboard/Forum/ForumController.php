<?php

namespace App\Http\Controllers\Dashboard\Forum;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\Twitch;
use App\Models\TwitchChannel;
use App\Models\SocialNetwork;
use App\Models\ForumPermission;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Thread;
use App\Models\User;
use App\Models\Site;
use App\Models\Role;
use Validator;
use Carbon;
use Gate;
use Auth;

class ForumController extends Controller
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
        if (Gate::denies('manage-forums')) {
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
        // get forums
        $forums = Forum::where('site_id', $this->site->id)->with('threads.posts')->orderBy('created_at', 'ASC')->paginate(10);
        // get deleted forums
        $deleted_forums = Forum::onlyTrashed()->where('site_id', $this->site->id)->orderBy('created_at', 'ASC')->paginate(10);

        return view('dashboard.forums.forums')
            ->with([
                'site'              => $this->site,
                'forums'            => $forums,
                'deleted_forums'    => $deleted_forums,
                'pageTitle'         => 'Forums - Dashboard',
                'activeTab'         => 'forums'
            ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        // get roles
        $roles = Role::where('id', '>', 3)->get();

        return view('dashboard.forums.create')
            ->with([
                'roles'        => $roles,
                'pageTitle'    => 'Forums - Dashboard',
                'activeTab'    => 'forums'
            ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store($siteName, Request $request)
    {
        // set input rules
        $rules = [
            'title'         => 'required|string|max:150|unique:forums,title,NULL,id,site_id,'.$this->site->id,
            'icon'          => 'required|regex:/^#?[a-fA-F0-9]{3,6}$/',
            'description'   => 'string|max:250',
        ];

        // set custom field name
        $attributeNames = [
            'icon' => 'color'
        ];

        // create validator
        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributeNames);

        // if validation fails redirect back
        if ($validator->fails())
        {
            return redirect()->back()->withInput()->withErrors($validator->errors());
        }

        // create forum
        $forum = Forum::create([
            'site_id'       => $this->site->id,
            'title'         => $request->get('title'),
            'icon'          => $request->get('icon'),
            'slug'          => str_slug($request->get('title'), '-'),
            'description'   => ($request->has('description') ? $request->get('description') : null)
        ]);

        // save forum permissions
        $roles = Role::all();
        foreach ($roles as $role)
        {
            if ($request->has('role-'.$role->id) && $request->get('role-'.$role->id))
            {
                ForumPermission::create([
                    'forum_id'  => $forum->id,
                    'site_id'   => $this->site->id,
                    'role_id'   => $role->id
                ]);
            }
        }


        if ($request->has('create-close'))
        {
            return redirect()->route('dashboard-forums', $siteName);
        }
        else
        {
            return redirect()->back()->with(array('success' => 'Success!', 'success-msg' => "Forum was successfully created."));
        }
    }

    /**
     * Update the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function restore($siteName, $id)
    {
        // check permissions
        if (Gate::denies('delete-forum')) {
            return redirect()->back()
                ->with([
                    'error'      => 'Error!', 
                    'error-msg'  => "You don't have permission to restore this.",
                ]);
        }

        // get forum
        $forum = Forum::withTrashed()->where('id', $id)->where('site_id', $this->site->id)->first();
        if ($forum)
        {
            $threads = $forum->threads()->withTrashed()->get();
            foreach ($threads as $thread)
            {
                // restore posts
                $thread->posts()->withTrashed()->restore();
            }
            // restore threads
            $forum->threads()->withTrashed()->restore();
            // restore forum
            $forum->restore();
        }

        return redirect()->back()->with(['success' => 'Success!', 'success-msg' => 'Forum was successfully restored.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($siteName, $id, Request $request)
    {
        // check permissions
        if (Gate::denies('delete-forum')) {
            return redirect()->back()
                ->with([
                    'error'      => 'Error!', 
                    'error-msg'  => "You don't have permission to delete this.",
                ]);
        }

        // get forum
        if ($request->has('force_delete'))
        {
            if (!Auth::user()->hasRole('owner', $this->site->id)) {
                return redirect()->back()
                    ->with([
                        'error'      => 'Error!', 
                        'error-msg'  => 'Only the owner of the site can delete a forum.',
                    ]);
            }
            $forum = Forum::where('id', $id)->where('site_id', $this->site->id)->withTrashed()->first();
        }
        else
        {
            $forum = Forum::where('id', $id)->where('site_id', $this->site->id)->first();
        }
        
        // delete forum, threads and posts
        if ($forum)
        {
            // force delete
            if ($request->has('force_delete'))
            {
                // delete posts
                foreach ($forum->threads as $thread)
                {
                    // delete posts
                    $thread->posts()->withTrashed()->forceDelete();
                }
                // delete threads
                $forum->threads()->withTrashed()->forceDelete();  
                // delete forum
                $forum->forceDelete();
            }
            // soft delete
            else
            {
                // modify deleted by record
                Thread::where('site_id', $this->site->id)->where('forum_id', $forum->id)->update(['deleted_by' => Auth::user()->id]);
                // delete posts
                foreach ($forum->threads as $thread)
                {
                    // modify deleted by record
                    Post::where('site_id', $this->site->id)->where('thread_id', $thread->id)->update(['deleted_by' => Auth::user()->id]);
                    // delete posts
                    if ($request->get('force_delete')) {
                        $thread->posts()->forceDelete();
                    } else {
                        $thread->posts()->delete();
                    }     
                    // modify deleted by record
                    $thread->deleted_by = Auth::user()->id;
                    $thread->save();
                }
                // delete threads
                if ($request->get('force_delete')) {
                    $forum->threads()->forceDelete();  
                } else {
                    $forum->threads()->delete();  
                }
                // modify deleted by record
                $forum->deleted_by = Auth::user()->id;
                $forum->save();
                // delete forum
                if ($request->get('force_delete')) {
                    $forum->forceDelete();
                } else {
                    $forum->delete();
                }
            }
        }

        return redirect()->back()
            ->with([
                'success'      => 'Success!', 
                'success-msg'  => 'Forum successfully deleted.',
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
        // get forum
        $forum = Forum::where('id', $id)->where('site_id', $this->site->id)->first();
        if (!$forum)
        {
            abort('404');
        }

        // get roles
        $roles = Role::where('id', '>', 3)->get();

        return view('dashboard.forums.edit')
            ->with([
                'roles'        => $roles, 
                'forum'        => $forum,
                'pageTitle'    => 'Forums - Dashboard',
                'activeTab'    => 'forums'
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($siteName, Request $request)
    {
        // set rules
        $rules = array(
            'id'            => 'required|exists:forums',
            'title'         => 'required|string|max:150|unique:forums,title,'.$request->get('id').',id,site_id,'.$this->site->id,
            'icon'          => 'required|regex:/^#?[a-fA-F0-9]{3,6}$/',
            'description'   => 'string|max:250',
        );

        // set custom field name
        $attributeNames = [
            'icon' => 'color'
        ];

        // create validator
        $validator = Validator::make($request->all(), $rules);
        $validator->setAttributeNames($attributeNames);

        // if validation fails redirect back
        if ($validator->fails())
        {
            return redirect()->back()->withInput()->withErrors($validator->errors());
        }

        // get forum
        $forum                  = Forum::where('id', $request->get('id'))->where('site_id', $this->site->id)->first();
        $forum->title           = $request->get('title');
        $forum->slug            = str_slug($request->get('title'), '-');
        $forum->description     = ($request->has('description') ? $request->get('description') : null);
        $forum->icon            = $request->get('icon');
        $forum->modified_by     = Auth::user()->id;
        $forum->modified_at     = Carbon::now();
        $forum->save();

        // save forum permissions
        $roles = Role::all();
        foreach ($roles as $role)
        {
            if (($request->has('role-'.$role->id) && $request->get('role-'.$role->id)) && !$forum->hasPermission($role->id))
            {
                $forum->givePermission($role->id);
            }
            elseif (!$request->has('role-'.$role->id) && $forum->hasPermission($role->id))
            {
                $forum->removePermission($role->id);
            }
        }

        if ($request->has('save-close'))
        {
            return redirect()->route('dashboard-forums', $siteName);
        }
        else
        {
            return redirect()->back()->with(array('success' => 'Success!', 'success-msg' => "Forum was successfully updated."));
        }
    }

}
