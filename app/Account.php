<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Account extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'accounts';
    public $timestamps = true;
    protected $fillable = array('name', 'slug', 'bank', 'country_id', 'swift', 'account', 'iban', 'img', 'active', 'comment', 'company_id');

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function country()
    {
        return $this->belongsTo('App\Country', 'country_id');
    }

    public function operations()
    {
        return $this->hasMany('App\Operation');
    }

}