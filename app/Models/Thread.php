<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Thread extends Model
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
     * Returns the forum of the thread.
     *
     * @return Forum
     */
    public function forum()
    {
    	return $this->belongsTo('App\Models\Forum');
    }

    /**
     * Returns the posts of the thread.
     *
     * @return Posts
     */
    public function posts()
    {
        return $this->hasMany('App\Models\Post');
    }

    /**
     * Returns the last post of the thread.
     *
     * @return Post
     */
    public function latestPost()
    {
        return $this->posts()->orderBy('created_at', 'DESC')->first();
    }

}