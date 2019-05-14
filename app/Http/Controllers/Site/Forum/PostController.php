<?php

namespace App\Http\Controllers\Site\Forum;

use App\Models\Forum;
use App\Models\Thread;
use App\Models\Post;
use App\Models\Site;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Carbon;
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
    }

    /**
     * Show the posts
     *
     * @param  name  $name
     * @return Response
     */
    public function posts($name, $threadSlug)
    {
        // get thread and forum
        $thread = Thread::where('site_id', $this->site->id)->where('id', substr($threadSlug, strrpos($threadSlug, '-') + 1))->first();
        if (!$thread)
        {
            return redirect()->route('forums', $this->site->domain);
        }
        $thread->increment('views');

        $forum = Forum::where('site_id', $this->site->id)->where('id', $thread->forum_id)->first();

        // check permissions
        if ($forum->restrictedAccess() && Gate::denies('view-forum', $forum)) {
            abort(403);
        }

        // get posts
        $posts = Post::where('site_id', $this->site->id)->where('thread_id', $thread->id)->orderBy('created_at', 'ASC')->paginate(10);

        // return a view with posts
        return view('site.forums.posts')->with(
            [
                'site'          => $this->site,
                'forum'         => $forum,
                'thread'        => $thread,
                'posts'         => $posts,
                'pageTitle'     => $thread->title.' - '.$forum->title.' - Forums',
                'activeTab'     => 'forums',
            ]
        );
    }

    /**
     * Show the edit post page
     *
     * @param  name  $name
     * @return Response
     */
    public function edit($name, $threadSlug, $postId)
    {
        // get thread and forum
        $thread = Thread::where('site_id', $this->site->id)->where('id', substr($threadSlug, strrpos($threadSlug, '-') + 1))->first();
        if (!$thread)
        {
            return redirect()->route('forums', $this->site->domain);
        }
        $forum = Forum::where('site_id', $this->site->id)->where('id', $thread->forum_id)->first();

        // get post
        $post = Post::where('id', $postId)->where('site_id', $this->site->id)->first();
        if (!$post)
        {
            return redirect()->route('forums', $this->site->domain);
        }

        // check permissions
        if (Gate::denies('view-forum', $forum)) {
            abort(403);
        }
        if (Gate::denies('edit-post', $post)) {
            abort(403);
        }

        return view('site.forums.edit')->with(
            [
                'site'      => $this->site,
                'forum'     => $forum,
                'thread'    => $thread,
                'post'      => $post,
                'activeTab' => 'forums'
            ]
        );
    }

    /**
     * Create a post.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store($siteName, $threadSlug, Request $request)
    {
        // set input rules
        $rules = [
            'thread_id'     => 'required|exists:threads,id,site_id,'.session('site_id'),
            'post'          => 'required|string|max:10000',
        ];

        // create validator
        $validator = Validator::make($request->all(), $rules);

        // if validation fails redirect back
        if ($validator->fails())
        {
            return redirect()->back()->withInput()->withErrors($validator->errors());
        }

        // get the thread
        $thread = Thread::find($request->get('thread_id'));
        if ($thread->locked)
        {
            return redirect()->back();
        }

        // check permissions
        if (Gate::denies('view-thread', $thread))
        {
            abort(403);
        }

        // check if user can post links and images
        if (Gate::allows('post-media'))
        {
            $content = clean($request->get('post'), 'subs');
        }
        else
        {
            $content = clean($request->get('post'));
        }

        // create post
        $post = Post::create([
            'site_id'       => session('site_id'),
            'thread_id'     => $request->get('thread_id'),
            'user_id'       => $request->user()->id,
            'content'       => $content,
        ]);

        return redirect()->route('thread', [$siteName, $thread->slug.'-'.$thread->id]);

    }

    /**
     * Update a post.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update($siteName, $threadSlug, $postId, Request $request)
    {
        // set input rules
        $rules = [
            'thread_id'     => 'required|exists:threads,id,site_id,'.session('site_id'),
            'post_id'       => 'required|exists:posts,id,site_id,'.session('site_id'),
            'post'          => 'required|string|max:10000',
            'title'         => 'required_with:first_post|string|max:70'
        ];

        $messages = [
            'required_with' => 'The :attribute field is required.',
        ];

        // create validator
        $validator = Validator::make($request->all(), $rules, $messages);

        // if validation fails redirect back
        if ($validator->fails())
        {
            return redirect()->back()->withInput()->withErrors($validator->errors());
        }

        // get the thread
        $thread = Thread::find($request->get('thread_id'));

        // check permissions
        if (Gate::denies('view-thread', $thread))
        {
            abort(403);
        }

        // get the post
        $post = Post::where('id', $postId)->where('site_id', $this->site->id)->first();

        // check permissions
        if (Gate::denies('edit-post', $post))
        {
            abort(403);
        }

        // check if user can post links and images
        if (Gate::allows('post-media'))
        {
            $post->content = clean($request->get('post'), 'subs');
        }
        else
        {
            $post->content = clean($request->get('post'));
        }
        
        $post->modified_by = Auth::user()->id;
        $post->modified_at = Carbon::now();
        $post->save();

        if ($post->firstPost())
        {
            $thread->title = $request->get('title');
            $thread->slug = str_slug($request->get('title'), '-');
            $thread->modified_by = Auth::user()->id;
            $thread->modified_at = Carbon::now();
            $thread->save();
        }

        return redirect()->route('thread', [$siteName, $thread->slug.'-'.$thread->id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($siteName, $threadSlug, $postId, Request $request)
    {
        $post = Post::find($postId);

        if (!$post->firstPost())
        {
            // check permissions
            if (Gate::denies('delete-post'))
            {
                abort(403);
            }
            // modify deleted by record
            $post->deleted_by = Auth::user()->id;
            $post->save();
            // delete post
            $post->delete();  
        }
        
        return redirect()->back();
    }


}
