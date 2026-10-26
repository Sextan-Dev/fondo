<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'roles';
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo('App\User');
    }

}