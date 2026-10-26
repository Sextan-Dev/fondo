<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use \App\Concerns\SerializesDates;

    protected $fillable = ['date','message', 'user_id','url', 'group_id','content_id', 'company_id','sender_user_id','project_id','frequency', 'title', 'auth', 'read', 'type', 'addressee'];

    public function sender()
    {
        return $this->belongsTo('App\User', 'sender_user_id');
    }
}
