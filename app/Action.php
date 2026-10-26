<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Action extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'actions';
    public $timestamps = true;
    protected $fillable = array('name', 'slug', 'type', 'tablename', 'item', 'user_id', 'updates', 'active', 'company_id');

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function user()
    {
        return $this->hasOne('App\User', 'user_id');
    }

    public function group()
    {
        return $this->belongsTo('App\UserGroup', 'group_id');
    }

}