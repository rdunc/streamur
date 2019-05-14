<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model {

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
     * Get the roles that have this permission
     *
     * @return Roles
     */
    public function roles()
    {
        return $this->belongsToMany('App\Models\Role', 'role_permissions')->withPivot('site_id');
    }
    
}