<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'tasks';
    public $timestamps = true;
    protected $fillable = array(
        'name',
        'ally',
        'public',
        'zone_impact_1',
        'zone_impact_2',
        'org_beneficiary',
        'zone_impact_3',
        'social',
        'description_html',
        'description',
        'slug',
        'project_id',
        'user_id',
        'tags',
        'type',
        'category',
        'date_from', 
        'date_to', 
        'date_done',
        'benef_1', 
        'benef_2',
        'benef_3', 
        'target_benef_1',
        'target_benef_2', 
        'target_benef_3', 
        'files', 
        'proof', 
        'status', 
        'comment', 
        'active', 
        'links',
        'company_id');

    public function project()
    {
        return $this->belongsTo('App\Project', 'project_id');
    }

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function operations()
    {
        return $this->hasMany('App\Operation');
    }

    public function contacts()
    {
        return $this->hasMany('App\Contact');
    }
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
}