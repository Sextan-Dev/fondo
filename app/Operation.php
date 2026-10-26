<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Operation extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'operations';
    public $timestamps = true;
    protected $fillable = array(
        'date',
        'id', 
        'amount_execute', 
        'project_id',
        'transfer',
        'category',
        'subcategory',
        'company_id',
        'contact_id',
        'reference_id',
        'fee',
        'monitoring',
        'origin', 'account_id',
        'inventory_id' ,
        'cost',
        'qt',
        'type',
        'content_id',
        'category_id', 
        'mode_id', 
        'amount', 
        'vat', 
        'ret_vat', 
        'ret_isr', 
        'qt', 
        'files', 
        'content_id',
        'task_id', 
        'project_id',
        'donor_id',
        'comment', 
        'reference',
        'subtype',
        'reference',
        'contact_type',
        'account',
        'transfer_reference',
        'monitoring_apply',
        'active',
        'import_key',
        'percentage_operating_cost',
        'val_operating_cost',
        'movement_id',
        'is_co'
    );

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function account()
    {
        return $this->belongsTo('App\Account', 'account_id');
    }

    public function contact()
    {
        return $this->belongsTo('App\Contact', 'contact_id');
    }

    public function task()
    {
        return $this->belongsTo('App\Task', 'task_id');
    }

}