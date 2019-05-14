<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

// Auth routes
Route::get('auth/twitch', 			['as' 	=> 'login', 'uses' => 'Auth\TwitchController@redirectToProvider']);
Route::get('auth/twitch/callback', 	['uses' => 'Auth\TwitchController@handleProviderCallback']);
Route::get('auth/logout', 			['as' 	=> 'logout', 'uses' => 'Auth\TwitchController@getLogout']);

// Main site routes
Route::group(['domain' => env('APP_DOMAIN')], function () {

	// Front page
	Route::get('/', ['as' => 'frontpage', 'uses' => 'HomeController@index']);

	// Authenticated routes
	Route::group(['middleware' => 'auth'], function()
	{

		// Admin routes
		Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => ['admin:developer']], function ()
		{

			// Landing page
			Route::get('/', ['as' => 'admin', 'uses' => 'AdminController@admin']);

			// Emotes
			Route::get('/emotes/save', ['as' => 'admin-emotes-save', 'uses' => 'AdminController@saveEmotes']);

			// Users
			Route::group(['prefix' => 'users'], function()
			{
				Route::get('/', 					['as' => 'admin-users', 			'uses' => 'UserController@index']);
				Route::get('/create', 				['as' => 'admin-users-create', 		'uses' => 'UserController@create']);
				Route::post('/create', 				['as' => 'admin-users-store', 		'uses' => 'UserController@store']);			
				Route::get('/{id}/edit', 			['as' => 'admin-users-edit', 		'uses' => 'UserController@edit']);
				Route::post('/update', 				['as' => 'admin-users-update', 		'uses' => 'UserController@update']);
				Route::delete('/{id}/delete', 		['as' => 'admin-users-delete', 		'uses' => 'UserController@destroy']);
				Route::put('/{id}/active', 			['as' => 'admin-users-active', 		'uses' => 'UserController@active']);
			});

			// Sites
			Route::group(['prefix' => 'sites'], function()
			{
				Route::get('/', 					['as' => 'admin-sites', 			'uses' => 'SiteController@index']);
				Route::get('/create', 				['as' => 'admin-sites-create', 		'uses' => 'SiteController@create']);
				Route::post('/create', 				['as' => 'admin-sites-store', 		'uses' => 'SiteController@store']);
				Route::get('/{id}/edit', 			['as' => 'admin-sites-edit', 		'uses' => 'SiteController@edit']);
				Route::post('/update', 				['as' => 'admin-sites-update', 		'uses' => 'SiteController@update']);
				Route::delete('/{id}/delete', 		['as' => 'admin-sites-delete', 		'uses' => 'SiteController@destroy']);
				Route::put('/{id}/active', 			['as' => 'admin-sites-active', 		'uses' => 'SiteController@active']);
			});

		});

	});

});

// Site routes
Route::group(['domain' => '{name}.'.env('APP_DOMAIN'), 'middleware' => 'site'], function ()
{

	// Home page
	Route::get('/', ['as' => 'site', 'uses' => 'Site\SiteController@index']);

	// Site Twitch data
	Route::get('/data/update', ['as' => 'data-update', 'uses' => 'Site\DataController@updateData']);
	Route::get('/twitch/status', ['as' => 'twitch-status', 'uses' => 'Site\DataController@status']);

	// Forum routes
	Route::group(['prefix' => 'forums', 'namespace' => 'Site\Forum'], function ()
	{

		// Authenticated routes
		Route::group(['middleware' => 'auth'], function()
		{
			// Create thread
			Route::get('/t/new', 							['as' => 'create-thread', 	'uses' => 'ThreadController@create']);
			Route::post('/t/new', 							['as' => 'store-thread', 	'uses' => 'ThreadController@store']);

			// Create post
			Route::post('/t/{threadSlug}/new', 				['as' => 'store-post', 		'uses' => 'PostController@store']);

			// Edit post
			Route::get('/t/{threadSlug}/edit/{id}', 		['as' => 'post-edit', 		'uses' => 'PostController@edit']);
			Route::post('/t/{threadSlug}/edit/{id}', 		['as' => 'post-update', 	'uses' => 'PostController@update']);
			Route::delete('/t/{threadSlug}/delete/{id}', 	['as' => 'post-delete', 	'uses' => 'PostController@destroy']);
			Route::delete('/t/{threadSlug}/delete', 		['as' => 'thread-delete', 	'uses' => 'ThreadController@destroy']);
			Route::put('/t/{threadSlug}/pin', 				['as' => 'pin-thread', 		'uses' => 'ThreadController@pin']);
			Route::put('/t/{threadSlug}/lock', 				['as' => 'lock-thread', 	'uses' => 'ThreadController@lock']);
		});

		Route::get('/', 								['as' => 'forums', 			'uses' => 'ForumController@forums']);
		Route::get('/c/{forumCategory}', 				['as' => 'forum', 			'uses' => 'ThreadController@threads']);
		Route::get('/t/{threadSlug}', 					['as' => 'thread', 			'uses' => 'PostController@posts']);
		Route::get('/discussions', 						['as' => 'discussions', 	'uses' => 'ForumController@discussions']);
		
	});

	// Authenticated routes
	Route::group(['middleware' => 'auth'], function()
	{

		// Dashboard routes
		Route::group(['prefix' => 'dashboard', 'namespace' => 'Dashboard', 'middleware' => ['role:owner,administrator,moderator']], function ()
		{
			
			// Dashboard
			Route::get('/', ['as' => 'dashboard', 'uses' => 'DashboardController@index']);

			// Forums
			Route::group(['prefix' => 'forums', 'namespace' => 'Forum'], function()
			{
				Route::get('/', 									['as' => 'dashboard-forums', 			'uses' => 'ForumController@index']);
				Route::get('/create', 								['as' => 'dashboard-forums-create', 	'uses' => 'ForumController@create']);
				Route::post('/create', 								['as' => 'dashboard-forums-store', 		'uses' => 'ForumController@store']);
				Route::get('/{id}/edit', 							['as' => 'dashboard-forums-edit', 		'uses' => 'ForumController@edit']);
				Route::post('/update', 								['as' => 'dashboard-forums-update', 	'uses' => 'ForumController@update']);
				Route::delete('/{id}/delete', 						['as' => 'dashboard-forums-delete', 	'uses' => 'ForumController@destroy']);
				Route::put('/{id}/restore', 						['as' => 'dashboard-forums-restore', 	'uses' => 'ForumController@restore']);

				Route::get('/c/{forum}', 							['as' => 'dashboard-threads', 			'uses' => 'ThreadController@index']);
				Route::delete('/c/{forum}/{id}/delete', 			['as' => 'dashboard-threads-delete', 	'uses' => 'ThreadController@destroy']);
				Route::put('/c/{forum}/{id}/restore', 				['as' => 'dashboard-threads-restore', 	'uses' => 'ThreadController@restore']);

				Route::get('/t/{threadSlug}', 						['as' => 'dashboard-posts', 			'uses' => 'PostController@index']);
				Route::delete('/t/{threadSlug}/{id}/delete', 		['as' => 'dashboard-posts-delete', 		'uses' => 'PostController@destroy']);
				Route::put('/t/{threadSlug}/{id}/restore', 			['as' => 'dashboard-posts-restore', 	'uses' => 'PostController@restore']);
			});

			// Staff
			Route::group(['prefix' => 'staff'], function()
			{
				Route::get('/', 						['as' => 'dashboard-staff', 			'uses' => 'StaffController@index']);
				Route::get('/create', 					['as' => 'dashboard-staff-create', 		'uses' => 'StaffController@create']);
				Route::post('/create', 					['as' => 'dashboard-staff-store', 		'uses' => 'StaffController@store']);
				Route::get('/{id}/edit', 				['as' => 'dashboard-staff-edit', 		'uses' => 'StaffController@edit']);
				Route::post('/update', 					['as' => 'dashboard-staff-update', 		'uses' => 'StaffController@update']);
				Route::delete('/{id}/delete/{type}',	['as' => 'dashboard-staff-delete', 		'uses' => 'StaffController@destroy']);
				Route::put('/{id}/active', 				['as' => 'dashboard-staff-active', 		'uses' => 'StaffController@active']);
			});

			// Users
			Route::group(['prefix' => 'users'], function()
			{
				Route::get('/', 						['as' => 'dashboard-users', 			'uses' => 'UserController@index']);
				Route::post('/ban', 					['as' => 'dashboard-users-ban', 		'uses' => 'UserController@ban']);
				Route::post('/unban/{id}', 				['as' => 'dashboard-users-unban', 		'uses' => 'UserController@unban']);
			});

			// Settings
			Route::group(['prefix' => 'settings'], function()
			{
				Route::get('/', 					['as' => 'dashboard-settings', 			'uses' => 'SettingController@index']);
				Route::get('/create', 				['as' => 'dashboard-settings-create', 	'uses' => 'SettingController@create']);
				Route::post('/create', 				['as' => 'dashboard-settings-store', 	'uses' => 'SettingController@store']);
				Route::get('/{id}/edit', 			['as' => 'dashboard-settings-edit', 	'uses' => 'SettingController@edit']);
				Route::post('/update', 				['as' => 'dashboard-settings-update', 	'uses' => 'SettingController@update']);
				Route::delete('/{id}/delete', 		['as' => 'dashboard-settings-delete', 	'uses' => 'SettingController@destroy']);
				Route::put('/{id}/active', 			['as' => 'dashboard-settings-active', 	'uses' => 'SettingController@active']);
			});

		});

	});

});
