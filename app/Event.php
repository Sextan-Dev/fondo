<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Event extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'events';
    public $timestamps = true;
    protected $fillable = array('type', 'category', 'name', 'slug', 'user_id', 'description', 'location', 'start', 'end', 'allday', 'share_user_ids', 'comment', 'active','company_id');

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

}