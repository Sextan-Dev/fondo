<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'companies';
    public $timestamps = true;
    protected $fillable = array('name', 'slug', 'active');

    public function country()
    {
        return $this->hasOne('App\Country');
    }

}