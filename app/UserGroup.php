<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'user_groups';
    public $timestamps = true;
    protected $fillable = array('name', 'slug', 'active');

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function users()
    {
        return $this->hasMany('App\User');
    }

}