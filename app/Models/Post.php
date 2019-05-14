<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = ['thread'];

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
     * Returns the thread of the post.
     *
     * @return Thread
     */
    public function thread()
    {
    	return $this->belongsTo('App\Models\Thread');
    }

    /**
     * Returns true if post is first post
     *
     * @return bool
     */
    public function firstPost()
    {
        $firstPost = Post::where('thread_id', $this->thread->id)->orderBy('created_at', 'ASC')->value('id');
        if ($this->id != $firstPost)
        {
            return false;
        }
        else
        {
            return true;
        }
    }

}