<?php

namespace App\Http\Controllers\Api;

use App\Models\Forum;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Test function
     *
     * @return Response
     */
    public function test()
    {
        $threads = Thread::where('site_id', 11)->get()->toArray();
        return response()->json($threads);
    }

}
