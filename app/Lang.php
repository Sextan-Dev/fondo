<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lang extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'langs';
    public $timestamps = true;
    protected $fillable = array('name', 'slug', 'active');

}