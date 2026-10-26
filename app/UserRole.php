<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'user_roles';
    public $timestamps = true;
    protected $fillable = array('name', 'slug', 'active');

    public function users()
    {
        return $this->hasMany('App\User');
    }

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

}