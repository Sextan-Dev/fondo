<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'inventories';
    public $timestamps = true;
    protected $fillable = array('article', 'slug', 'category', 'type','price','cost', 'img', 'description','active', 'comment', 'company_id');

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

}