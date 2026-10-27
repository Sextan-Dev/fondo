<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'contacts';
    public $timestamps = true;
    protected $fillable = [
        'category',
        'name',        
        'slug',
        'first_name',
        'last_name',
        'age',
        'gender',
        'email',
        'birth',
        'id_number',
        'company',
        'position',
        'nationality',
        'phone',
        'bank',
        'bank_account',
        'bank_clabe',
        'address',
        'city',
        'postal',
        'country',
        'account',
        'contract_type',
        'contract_from',
        'contract_to',
        'user_id',
        'task_id',
        'files',
        'comment',
        'active',
        'company_id',
        'type',
        'brands',
        'areas_interest',
        'budget',
        'description_html',
        'social',
        'rse',
        'rse_themes',
        'global_strategy',
        'global_strategy_mx',
        'allied_rse',
        'voluntary_fum',
        'budget_rse_themes',
        'groups_affinity',
        'interest',
        'challenge',
        'groups_difficulty',
        'direct_competition',
        'notes',
        'groups',
        'contact_details',
        'activity',
        'deal',
        'name_comercial',
        'name_executive',
        'state',
        'history_operation_id'
    ];

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function task()
    {
        return $this->belongsTo('App\Task', 'task_id');
    }

    public function user()
    {
        return $this->hasOne('App\User', 'user_id');
    }

    public function operations()
    {
        return $this->hasMany('App\Operation');
    }

}