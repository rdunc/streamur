<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Site;
use Illuminate\Http\Request;
use Auth;

class HomeController extends Controller
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
     * Route to the proper page
     *
     * @return Response
     */
    public function index(Request $request)
    {
        if (Auth::check())
        {
            return redirect()->route('site', $request->user()->name);
        }
        else
        {
            return view('frontpage.home');
        }
    }

}
