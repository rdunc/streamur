<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumPermission extends Model {

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
     * Get the forum that has this permission
     *
     * @return Forum
     */
    public function forum()
    {
        return $this->belongsTo('App\Models\Forum')->withPivot('site_id');
    }
    
}