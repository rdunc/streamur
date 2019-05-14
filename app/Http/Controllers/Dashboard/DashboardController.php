<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Auth;
use DB;

class DashboardController extends Controller
{

    private $site;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->site = Site::where('domain', session('site_name'))->first();
    }

    /**
     * Show the site for the given name.
     *
     * @return Response
     */
    public function index()
    {
        return view('dashboard.index', ['pageTitle' => 'Dashboard', 'activeTab' => 'home']);
    }

}
