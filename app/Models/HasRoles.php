<?php

namespace App\Models;

trait HasRoles
{

    /**
     * Get the roles a user has
     *
     * @return Roles
     */
    public function roles()
    {
        return $this->belongsToMany('App\Models\Role', 'user_roles')->withPivot('site_id');
    }

    /**
     * Checks if the user has a role by its name.
     *
     * @param string|array $name       Role name or array of role names.
     * @param bool         $requireAll All roles in the array are required.
     *
     * @return bool
     */
    public function hasRole($name, $siteId = null, $requireAll = false)
    {
        if (is_array($name))
        {
            foreach ($name as $roleName)
            {
                $hasRole = $this->hasRole($roleName, $siteId);
                if ($hasRole && !$requireAll)
                {
                    return true;
                }
                elseif (!$hasRole && $requireAll)
                {
                    return false;
                }
            }
            // If we've made it this far and $requireAll is FALSE, then NONE of the roles were found
            // If we've made it this far and $requireAll is TRUE, then ALL of the roles were found.
            // Return the value of $requireAll;
            return $requireAll;
        } 
        else
        {
            foreach ($this->roles as $role) 
            {
                if ($role->name == $name && $role->pivot->site_id == $siteId) 
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param bool         $requireAll All permissions in the array are required.
     *
     * @return bool
     */
    public function hasPermission($permission, $siteId = null, $requireAll = false)
    {
        if (is_array($permission)) 
        {
            foreach ($permission as $permName) 
            {
                $hasPerm = $this->hasPermission($permName, $siteId);
                if ($hasPerm && !$requireAll) 
                {
                    return true;
                } 
                elseif (!$hasPerm && $requireAll) 
                {
                    return false;
                }
            }
            // If we've made it this far and $requireAll is FALSE, then NONE of the perms were found
            // If we've made it this far and $requireAll is TRUE, then ALL of the perms were found.
            // Return the value of $requireAll;
            return $requireAll;
        } 
        else 
        {
            foreach ($this->roles as $role) 
            {
                // Validate against the Permission table
                foreach ($role->permissions as $perm) 
                {
                    if ($perm->name == $permission && $perm->pivot->site_id == $siteId) 
                    {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $role
     */
    public function attachRole($role, $siteId = null)
    {
        if(is_object($role))
        {
            $role = $role->getKey();
        }
        if(is_array($role))
        {
            $role = $role['id'];
        }
        $this->roles()->attach($role, ['site_id' => $siteId]);
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $role
     */
    public function detachRole($role, $siteId = null)
    {
        if (is_object($role))
        {
            $role = $role->getKey();
        }
        if (is_array($role))
        {
            $role = $role['id'];
        }
        $this->roles()->newPivotStatementForId($role)->where('site_id', $siteId)->delete();
    }

    /**
     * Attach multiple roles to a user
     *
     * @param mixed $roles
     */
    public function attachRoles($roles, $siteId = null)
    {
        foreach ($roles as $role)
        {
            $this->attachRole($role, $siteId);
        }
    }

    /**
     * Detach multiple roles from a user
     *
     * @param mixed $roles
     */
    public function detachRoles($roles, $siteId = null)
    {
        foreach ($roles as $role)
        {
            $this->detachRole($role, $siteId);
        }
    }

    public function giveRole($role, $siteId = null)
    {
        $role = Role::where('name', $role)->first();
        $this->attachRole($role, $siteId);
    }

    public function removeRole($role, $siteId = null)
    {
        $role = Role::where('name', $role)->first();
        $this->detachRole($role, $siteId);
    }

}