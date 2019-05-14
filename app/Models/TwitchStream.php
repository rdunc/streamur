<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TwitchStream extends Model
{

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
     * Returns the user of the stream.
     *
     * @return User
     */
    public function user()
    {
    	return $this->belongsTo('App\Models\User', 'owner', 'id');
    }


}
