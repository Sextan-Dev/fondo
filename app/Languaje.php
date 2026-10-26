<?php

namespace app;

use Illuminate\Database\Eloquent\Model;

class Languaje extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'languajes';
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo('App\App\User');
    }

}