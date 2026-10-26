<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use \App\Concerns\SerializesDates;

    protected $fillable = ['message', 'user_id'];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
