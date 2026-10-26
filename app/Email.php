<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Email extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'emails';
    public $timestamps = true;
    protected $fillable = array('type', 'subject', 'message', 'date', 'email_from', 'email_to', 'email_cc', 'email_bcc', 'files', 'comment', 'active');

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

}