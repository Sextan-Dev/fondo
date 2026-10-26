<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'categories';
    public $timestamps = false;
    
    protected $fillable = array('name', 'slug', 'type', 'category', 'comment', 'active','project_id', 'pilar', 'others', 'company_id');

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function lang()
    {
        return $this->belongsTo('App\Lang', 'lang_id');
    }
}