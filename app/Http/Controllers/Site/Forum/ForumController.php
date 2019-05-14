<?php

namespace App\Http\Controllers\Site\Forum;

use App\Models\Forum;
use App\Models\Thread;
use App\Models\Post;
use App\Models\Site;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use DB;

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
    }

    /**
     * Show the forums
     *
     * @return Response
     */
    public function forums()
    {
        // get forums
        $forums = Forum::where('site_id', $this->site->id)->orderBy('created_at', 'ASC')->get();

        // get forums and threads
        $latest_threads = [];
        foreach ($forums as $forum)
        {
            $latest_threads[$forum->slug] = Thread::select('threads.*',
                DB::raw('(SELECT created_at FROM posts WHERE thread_id = threads.id ORDER BY created_at DESC LIMIT 1) as latest_post'))
                ->where('threads.site_id', $this->site->id)
                ->where('threads.forum_id', $forum->id)
                ->orderBy('latest_post', 'DESC')
                ->take(4)->get();
        }

        // return view with forums and threads
        return view('site.forums.forums')->with(
            [
                'site'              => $this->site,
                'forums'            => $forums,
                'latest_threads'    => $latest_threads,
                'pageTitle'         => 'Forums',
                'activeTab'         => 'forums'
            ]
        );
    }

    /**
     * Show the forum discusssions
     *
     * @return Response
     */
    public function discussions()
    {
        // get forums
        $forums = Forum::where('site_id', $this->site->id)->orderBy('created_at', 'ASC')->get();

        // get threads sorted by last post
        $threads = Thread::select('threads.*',
            DB::raw('(SELECT created_at FROM posts WHERE thread_id = threads.id ORDER BY created_at DESC LIMIT 1) as latest_post'))
            ->where('threads.site_id', $this->site->id)
            ->orderBy('latest_post', 'DESC')
            ->paginate(10);

        // return view with forums and threads
        return view('site.forums.discussions')->with(
            [
                'site'              => $this->site,
                'forums'            => $forums,
                'threads'           => $threads,
                'pageTitle'         => 'Forums',
                'activeTab'         => 'forums'
            ]
        );
    }

}
