<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'menus';
    public $timestamps = true;
    protected $fillable = array('name', 'icon', 'route', 'order', 'parent_id', 'active');

    public function menugroup()
    {
        return $this->belongsTo('App\MenuGroup', 'menu_group_id');
    }

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

}