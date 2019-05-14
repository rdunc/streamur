<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banned extends Model {
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'banned';

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
    
}