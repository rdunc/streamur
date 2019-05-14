<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model {

    /**
     * Set timestamps off
     *
     * @var boolean
     */
    public $timestamps = false;
    
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get users with a certain role
     * 
     * @return Users
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_roles')->withPivot('site_id');
    }

    /**
     * Get the permissions for this role
     *
     * @return Permissions
     */
    public function permissions()
    {
        return $this->belongsToMany('App\Models\Permission', 'role_permissions')->withPivot('site_id');
    }

     /**
     * Checks if the role has a permission by its name.
     *
     * @param string|array $name       Permission name or array of permission names.
     * @param bool         $requireAll All permissions in the array are required.
     *
     * @return bool
     */
    public function hasPermission($name, $siteId = null, $requireAll = false)
    {
        if (is_array($name))
        {
            foreach ($name as $permissionName)
            {
                $hasPermission = $this->hasPermission($permissionName, $siteId);
                if ($hasPermission && !$requireAll)
                {
                    return true;
                } 
                elseif (!$hasPermission && $requireAll)
                {
                    return false;
                }
            }
            // If we've made it this far and $requireAll is FALSE, then NONE of the permissions were found
            // If we've made it this far and $requireAll is TRUE, then ALL of the permissions were found.
            // Return the value of $requireAll;
            return $requireAll;
        } 
        else 
        {
            foreach ($this->permissions as $permission) 
            {
                if ($permission->name == $name && $permission->pivot->site_id == $siteId) 
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Save the inputted permissions.
     *
     * @param mixed $inputPermissions
     *
     * @return void
     */
    public function savePermissions($inputPermissions)
    {
        if (!empty($inputPermissions)) 
        {
            $this->permissions()->sync($inputPermissions);
        } 
        else 
        {
            $this->permissions()->detach();
        }
    }

    /**
     * Attach permission to current role.
     *
     * @param object|array $permission
     *
     * @return void
     */
    public function attachPermission($permission)
    {
        if (is_object($permission)) 
        {
            $permission = $permission->getKey();
        }
        if (is_array($permission)) 
        {
            $permission = $permission['id'];
        }
        $this->permissions()->attach($permission);
    }

    /**
     * Detach permission from current role.
     *
     * @param object|array $permission
     *
     * @return void
     */
    public function detachPermission($permission)
    {
        if (is_object($permission))
        {
            $permission = $permission->getKey();
        }
        if (is_array($permission))
        {
            $permission = $permission['id'];
        }
        $this->permissions()->detach($permission);
    }

    /**
     * Attach multiple permissions to current role.
     *
     * @param mixed $permissions
     *
     * @return void
     */
    public function attachPermissions($permissions)
    {
        foreach ($permissions as $permission) 
        {
            $this->attachPermission($permission);
        }
    }
    
    /**
     * Detach multiple permissions from current role
     *
     * @param mixed $permissions
     *
     * @return void
     */
    public function detachPermissions($permissions)
    {
        foreach ($permissions as $permission) 
        {
            $this->detachPermission($permission);
        }
    }

}