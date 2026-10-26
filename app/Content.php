<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Content extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'contents';
    public $timestamps = true;
    protected $fillable = array('date','category_id','apply_vat','created_by','approved_by','operation_id' ,'type','contact_id','company_id', 'category', 'tags','task_id', 'name', 'slug', 'value', 'user_id', 'status','lang_id', 'share_user_ids', 'head', 'html', 'item', 'seen', 'done', 'priority', 'deadline', 'active', 'files', 'comment');

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function lang()
    {
        return $this->belongsTo('App\Lang', 'lang_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }
    public function task()
    {
        return $this->belongsTo('App\Task', 'task_id');
    }

}