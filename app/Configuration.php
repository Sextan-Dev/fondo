<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'configurations';
    public $timestamps = true;
    protected $fillable = array('name', 'slug', 'type', 'category', 'comment', 'value', 'unit', 'active', 'company_id');

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

}