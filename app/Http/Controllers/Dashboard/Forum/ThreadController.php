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
use Gate;
use Auth;

class ThreadController extends Controller
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
    public function index($siteName, $slug)
    {
        // get forum
        $forum = Forum::where('site_id', $this->site->id)->where('slug', $slug)->first();
        // get threads
        $threads = Thread::where('site_id', $this->site->id)->where('forum_id', $forum->id)->with('posts')->orderBy('created_at', 'ASC')->paginate(10);
        // get deleted threads
        $deleted_threads = Thread::onlyTrashed()->where('site_id', $this->site->id)->where('forum_id', $forum->id)->orderBy('created_at', 'ASC')->paginate(10);

        return view('dashboard.forums.threads')
            ->with([
                'forum'              => $forum,
                'threads'            => $threads,
                'deleted_threads'    => $deleted_threads,
                'pageTitle'          => 'Threads - Dashboard',
                'activeTab'          => 'forums'
            ]);
    }

    /**
     * Update the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function restore($siteName, $slug, $id)
    {
        // check permissions
        if (Gate::denies('delete-post')) {
            return redirect()->back()
                ->with([
                    'error'      => 'Error!', 
                    'error-msg'  => "You don't have permission to restore this.",
                ]);
        }
        
        // get thread
        $thread = Thread::withTrashed()->where('id', $id)->where('site_id', $this->site->id)->first();
        if ($thread)
        {
            // restore posts
            $thread->posts()->withTrashed()->restore();
            // restore thread
            $thread->restore();
        }

        return redirect()->back()->with(['success' => 'Success!', 'success-msg' => 'Thread was successfully restored.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($siteName, $slug, $id, Request $request)
    {
        // check permissions
        if (Gate::denies('delete-post')) {
            return redirect()->back()
                ->with([
                    'error'      => 'Error!', 
                    'error-msg'  => "You don't have permission to delete this.",
                ]);
        }

        // get thread
        if ($request->has('force_delete'))
        {
            $thread = Thread::where('id', $id)->where('site_id', $this->site->id)->withTrashed()->first();
        }
        else
        {
            $thread = Thread::where('id', $id)->where('site_id', $this->site->id)->first();
        }
        
        // delete thread and posts
        if ($thread)
        {
            // force delete
            if ($request->has('force_delete'))
            {
                // check permissions
                if (Gate::denies('delete-forum')) {
                    return redirect()->back()
                        ->with([
                            'error'      => 'Error!', 
                            'error-msg'  => "You don't have permission to delete this.",
                        ]);
                }

                // delete posts
                $thread->posts()->withTrashed()->forceDelete();
                // delete forum
                $thread->forceDelete();
            }
            // soft delete
            else
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
                // delete thread
                if ($request->get('force_delete')) {
                    $thread->forceDelete();
                } else {
                    $thread->delete();
                }
            }
        }

        return redirect()->back()
            ->with([
                'success'      => 'Success!', 
                'success-msg'  => 'Thread successfully deleted.',
            ]);
    }

}
