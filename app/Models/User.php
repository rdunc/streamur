<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Carbon;
use DB;

class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, HasRoles;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['remember_token'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['remember_token', 'twitch_token'];

    /**
     * Returns the site of the user.
     *
     * @return Site
     */
    public function site()
    {
        return $this->hasOne('App\Models\Site', 'owner', 'id');
    }

    /**
     * Returns the twitch channel of the user.
     *
     * @return TwitchChannel
     */
    public function twitch_channel()
    {
        return $this->hasOne('App\Models\TwitchChannel', 'owner', 'id');
    }

    /**
     * Returns the twitch stream of the user.
     *
     * @return TwitchStream
     */
    public function twitch_stream()
    {
        return $this->hasOne('App\Models\TwitchStream', 'owner', 'id');
    }

    /**
     * Returns the access checks.
     *
     * @return AccessCheck
     */
    public function access_checks()
    {
        return $this->hasMany('App\Models\AccessCheck');
    }

    /**
     * Check if user is banned.
     *
     * @param int $siteId
     *
     * @return bool
     */
    public function banned($siteId)
    {
        return DB::table('banned')->where('user_id', $this->id)->where('site_id', $siteId)->count() > 0;
    }

    /**
     * Return access check for site.
     *
     * @param int $siteId
     * @return AccessCheck
     */
    public function accessCheck($siteId)
    {
        $check = AccessCheck::where('user_id', $this->id)->where('site_id', $siteId)->first();
        if (!$check)
        {
            $check = new AccessCheck;
            $check->user_id = $this->id;
            $check->site_id = $siteId;
            $check->updated_at = Carbon::now()->subHours(1);
            $check->save();
            return $check;
        }
        else
        {
            return $check;
        }
    }

    /**
     * Return highest rank for site
     *
     * @param int $siteId
     * @return int
     */
    public function rank($siteId)
    {
        return DB::table('user_roles')->where('user_id', $this->id)->where('site_id', $siteId)->orderBy('role_id', 'ASC')->value('role_id');
    }

    /**
     * Returns the sites the user can moderate.
     *
     * @return Site
     */
    public function staffSites()
    {
        // get role IDs
        $adminId = Role::where('name', 'administrator')->value('id');
        $modId = Role::where('name', 'moderator')->value('id');

        // get sites you have permission to moderate
        $sitesIds = array_fetch(DB::table('user_roles')->where('user_id', $this->id)->whereIn('role_id', [$adminId, $modId])->get(), 'site_id');
        $sites = Site::whereIn('id', $sitesIds)->get();
        return $sites;
    }

}
