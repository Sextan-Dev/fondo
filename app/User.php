<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\ResetPassword;
//use App\Notifications\Welcome;


class User extends Authenticatable implements \Tymon\JWTAuth\Contracts\JWTSubject
{
    use \App\Concerns\SerializesDates;

    use Notifiable;
    protected $table = 'users';
    public $timestamps = true;
    protected $fillable = array(
        'id',
        'user',
        'password',
        'date_admission',
        'name',
        'email',
        'remember_token',
        'phone',
        'menu_ids',
        'group_id',
        'group_additionnals',
        'role_id',
        'online',
        'color',
        'img',
        'signature',
        'created_by',
        'company_id',
        'conta_data',
        'active',
        'action_delete',
        'donors',
    );
    protected $hidden = ['password', 'remember_token'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }
    
    public function group()
    {
        return $this->belongsTo('App\UserGroup', 'group_id');
    }

    public function role()
    {
        return $this->belongsTo('App\UserRole', 'role_id');
    }

    public function createdby()
    {
        return $this->hasOne('App\User', 'created_by');
    }

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function contact()
    {
        return $this->hasOne('App\Contact');
    }

    public function events()
    {
        return $this->hasMany('App\Event');
    }

    public function projects()
    {
        return $this->hasMany('App\Project');
    }


    public function actions()
    {
        return $this->hasMany('App\Action');
    }

    public function message()
    {
        return $this->hasMany('App\Message');
    }
}