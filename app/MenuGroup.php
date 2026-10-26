<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MenuGroup extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'menu_groups';
    public $timestamps = true;
    protected $fillable = array('name', 'order', 'active');

    public function menus()
    {
        return $this->hasMany('App\Menu');
    }

}