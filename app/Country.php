<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Country extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'countries';
    public $timestamps = true;
    protected $fillable = array('name', 'slug', 'flag_img', 'currency_code', 'currency_char', 'active');

    public function lang()
    {
        return $this->hasOne('App\App\Lang');
    }

}