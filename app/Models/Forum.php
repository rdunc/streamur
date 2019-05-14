<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Forum extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Returns the site of the forum.
     *
     * @return Site
     */
    public function site()
    {
    	return $this->belongsTo('App\Models\Site');
    }

    /**
     * Returns the threads of the forum.
     *
     * @return Threads
     */
    public function threads()
    {
        return $this->hasMany('App\Models\Thread');
    }

    /**
     * Returns the posts of the forum.
     */
    public function posts()
    {
        return $this->hasManyThrough('App\Models\Post', 'App\Models\Thread');
    }

    /**
     * Returns the permissions of the forum.
     *
     * @return Permissions
     */
    public function permissions()
    {
        return $this->hasMany('App\Models\ForumPermission');
    }

    /**
     * Find out if forum has a specific permission
     *
     * @return boolean
     */
    public function hasPermission($roleId)
    {
        return in_array($roleId, array_fetch($this->permissions->toArray(), 'role_id'));
    }

    /**
     * Add permission to forum
     */
    public function givePermission($roleId)
    {
        $roles = array_fetch(Role::all()->toArray(), 'id');

        // check if role exists
        if (in_array($roleId, $roles))
        {
            // check if forum doesn't have permission yet
            if (!$this->hasPermission($roleId))
            {
                ForumPermission::create([
                    'forum_id'  => $this->id,
                    'site_id'   => $this->site_id,
                    'role_id'   => $roleId
                ]);
            }
        }
    }

    /**
     * Remove permission from forum
     */
    public function removePermission($roleId)
    {
        $roles = array_fetch(Role::all()->toArray(), 'id');

        // check if role exists
        if (in_array($roleId, $roles))
        {
            // check if forum has permission
            if ($this->hasPermission($roleId))
            {
                ForumPermission::where('forum_id', $this->id)->where('site_id', $this->site_id)->where('role_id', $roleId)->delete();
            }
        }
    }

    /**
     * Check if forum has restricted access.
     *
     * @return bool
     */
    public function restrictedAccess()
    {
        return $this->permissions()->count() > 0;
    }

    /**
     * Check if forum is admin only.
     *
     * @return bool
     */
    public function adminOnly()
    {
        $adminId = Role::where('name', 'administrator')->value('id');
        if ($this->permissions()->count() == 1)
        {
            foreach($this->permissions()->get() as $permission)
            {
                if ($permission->role_id == $adminId)
                {
                    return true;
                }
            }
            return false;
        }
        else
        {
            return false;
        }
    }

    /**
     * Check if user can access forum.
     *
     * @param User $user
     *
     * @return bool
     */
    public function access($user)
    {
        // check if forum has restricted access
        if ($this->restrictedAccess())
        {
            // loop through all the user's roles
            foreach ($user->roles()->get() as $role)
            {
                // loop through the forum permissions
                foreach($this->permissions()->get() as $permission)
                {
                    // check if one of the user's roles id's matches one of the permission id's, and verify that the site id matches
                    if ($role->id == $permission->role_id && $role->pivot->site_id == $permission->site_id)
                    {
                        return true;
                    }
                }
            }
            return false;
        }
        else
        {
            return true;
        }
    }

}