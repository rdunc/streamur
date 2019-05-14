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

class PostController extends Controller
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
        // get thread
        $thread = Thread::where('site_id', $this->site->id)->where('id', substr($slug, strrpos($slug, '-') + 1))->first();
        // get posts
        $posts = Post::where('site_id', $this->site->id)->where('thread_id', $thread->id)->orderBy('created_at', 'ASC')->paginate(10);
        // get deleted posts
        $deleted_posts = Post::onlyTrashed()->where('site_id', $this->site->id)->where('thread_id', $thread->id)->orderBy('created_at', 'ASC')->paginate(10);

        return view('dashboard.forums.posts')
            ->with([
                'thread'             => $thread,
                'posts'              => $posts,
                'deleted_posts'      => $deleted_posts,
                'pageTitle'          => 'Posts - Dashboard',
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
        
        // get post
        $post = Post::withTrashed()->where('id', $id)->where('site_id', $this->site->id)->first();
        if ($post)
        {
            // restore post
            $post->restore();
        }

        return redirect()->back()->with(['success' => 'Success!', 'success-msg' => 'Post was successfully restored.']);
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

        // get post
        if ($request->has('force_delete'))
        {
            $post = Post::where('id', $id)->where('site_id', $this->site->id)->withTrashed()->first();
        }
        else
        {
            $post = Post::where('id', $id)->where('site_id', $this->site->id)->first();
        }
        
        // delete post
        if ($post)
        {
            if (!$post->firstPost())
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
                    // delete post
                    $post->forceDelete();
                }
                // soft delete
                else
                {
                    // modify deleted by record
                    $post->deleted_by = Auth::user()->id;
                    $post->save();
                    // delete post
                    if ($request->get('force_delete')) {
                        $post->forceDelete();
                    } else {
                        $post->delete();
                    }
                }
            }
        }

        return redirect()->back()
            ->with([
                'success'      => 'Success!', 
                'success-msg'  => 'Post successfully deleted.',
            ]);
    }

}
