<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
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
     * Returns the user of the site.
     *
     * @return User
     */
    public function user()
    {
    	return $this->belongsTo('App\Models\User', 'owner', 'id');
    }

    /**
     * Returns the forums of the site.
     *
     * @return Forum
     */
    public function forums()
    {
        return $this->hasMany('App\Models\Forum');
    }

    /**
     * Returns the settings of the site.
     *
     * @return Setting
     */
    public function settings()
    {
        return $this->hasOne('App\Models\Setting');
    }

    /**
     * Returns users that are banned on this site.
     *
     * @return Banned
     */
    public function banned()
    {
        return $this->hasMany('App\Models\Banned');
    }

    /**
     * Returns emoticons for this site
     *
     * @return Emoticon
     */
    public function emoticons()
    {
        return $this->hasMany('App\Models\Emoticon');
    }


}
