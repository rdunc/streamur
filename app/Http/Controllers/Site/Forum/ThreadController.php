<?php

namespace App\Http\Controllers\Site\Forum;

use App\Models\Forum;
use App\Models\Thread;
use App\Models\Post;
use App\Models\Site;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Gate;
use DB;
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
    }

    /**
     * Shows the threads.
     *
     * @param  id  $id
     * @return Response
     */
    public function threads($name, $forumSlug)
    {
        // get forum
        $forum = Forum::where('site_id', $this->site->id)->where('slug', $forumSlug)->first();
        if (!$forum)
        {
            return redirect()->route('forums', $this->site->domain);
        }

        // check permissions
        if ($forum->restrictedAccess() && Gate::denies('view-forum', $forum)) {
            abort(403);
        }

        // get forums
        $forums = Forum::where('site_id', $this->site->id)->orderBy('created_at', 'ASC')->get();

        // get threads sorted by last post
        $threads = Thread::select('threads.*',
            DB::raw('(SELECT created_at FROM posts WHERE thread_id = threads.id ORDER BY created_at DESC LIMIT 1) as latest_post'))
            ->where('threads.site_id', $this->site->id)
            ->where('threads.forum_id', $forum->id)
            ->orderBy('pinned', true)
            ->orderBy('latest_post', 'DESC')
            ->paginate(10);

        // return a view with threads
        return view('site.forums.threads')->with([
            'site'          => $this->site,
            'forum'         => $forum,
            'forums'        => $forums,
            'threads'       => $threads,
            'pageTitle'     => $forum->title . ' - Forums',
            'activeTab'     => 'forums'
        ]);
    }

    /**
     * Shows the create a new thread view.
     *
     * @return View
     */
    public function create()
    {
        $forums = Forum::where('site_id', $this->site->id)->orderBy('created_at', 'ASC')->get();
        $categories = ['' => ''];
        foreach ($forums as $forum)
        {
            // check permissions
            if (!Gate::denies('view-forum', $forum))
            {
                $categories[$forum->id] = $forum->title;
            }
        }
        return view('site.forums.new_thread')
            ->with([
                'site'          => $this->site,
                'forums'        => $forums,
                'pageTitle'     => 'New Discussion - Forums',
                'activeTab'     => 'forums',
                'categories'    => $categories
            ]
        );
    }

    /**
     * Creates a new thread.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        // Let's set our input rules.
        $rules = [
            'category'     => 'required|exists:forums,id,site_id,'.session('site_id'),
            'title'        => 'required|string|max:70',
            'post'         => 'required|string|max:10000',
        ];

        // Since our rules are set we can now make our validator.
        $validator = Validator::make($request->all(), $rules);

        // Check to see if our validator failed. If it did fail
        // then we will redirect back with the validator errors.
        if ($validator->fails())
        {
            return redirect()->back()->withInput()->withErrors($validator->errors());
        }

        // get the forum
        $forum = Forum::find($request->get('category'));

        // check permissions
        if (Gate::denies('view-forum', $forum))
        {
            abort(403);
        }

        // Since the validator didn't fail we can now create
        // a new thread.
        $thread             = new Thread;
        $thread->site_id    = $this->site->id;
        $thread->forum_id   = $request->get('category');
        $thread->user_id    = $request->user()->id;
        $thread->title      = $request->get('title');
        $thread->slug       = str_slug($request->get('title'), '-');
        $thread->save();

        // check if user can post links and images
        if (Gate::allows('post-media'))
        {
            $content = clean($request->get('post'), 'subs');
        }
        else
        {
            $content = clean($request->get('post'));
        }

        // Create first post of thread
        $post               = new Post;
        $post->site_id      = $this->site->id;
        $post->thread_id    = $thread->id;
        $post->user_id      = $request->user()->id;
        $post->content      = $content;
        $post->save();

        return redirect()->route('thread', [$this->site->domain, $thread->slug.'-'.$thread->id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($siteName, $threadSlug, Request $request)
    {
        // get thread and forum
        $thread = Thread::where('site_id', $this->site->id)->where('id', substr($threadSlug, strrpos($threadSlug, '-') + 1))->first();
        if (!$thread)
        {
            return redirect()->route('forums', $this->site->domain);
        }

        // check permissions
        if (Gate::denies('delete-post'))
        {
            abort(403);
        }
        // modify deleted by record
        Post::where('site_id', $this->site->id)->where('thread_id', $thread->id)->update(['deleted_by' => Auth::user()->id]);
        // delete posts
        $thread->posts()->delete();
        // modify deleted by record
        $thread->deleted_by = Auth::user()->id;
        $thread->save();
        // delete thread
        $thread->delete();

        return redirect()->back();
    }

    /**
     * Update the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function pin($siteName, $threadSlug, Request $request)
    {
        // check permissions
        if (Gate::denies('manage-forums')) {
            abort(403);
        }
        
        // get thread
        $thread = Thread::where('site_id', $this->site->id)->where('id', substr($threadSlug, strrpos($threadSlug, '-') + 1))->first();
        if ($thread)
        {
            $thread->pinned = ($thread->pinned ? false : true);
            $thread->save();
        }

        return redirect()->back();
    }

    /**
     * Update the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function lock($siteName, $threadSlug, Request $request)
    {
        // check permissions
        if (Gate::denies('manage-forums')) {
            abort(403);
        }
        
        // get thread
        $thread = Thread::where('site_id', $this->site->id)->where('id', substr($threadSlug, strrpos($threadSlug, '-') + 1))->first();
        if ($thread)
        {
            $thread->locked = ($thread->locked ? false : true);
            $thread->save();
        }

        return redirect()->back();
    }

}
