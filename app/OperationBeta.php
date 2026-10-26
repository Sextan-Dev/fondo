<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OperationBeta extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'operations_beta';
    public $timestamps = true;
    protected $fillable = array(
        'id',         
        'date',
        'category',
        'subcategory',
        'contact_id',        
        'fee',                        
        'type',
        'mode_id', 
        'amount',        
        'account',
        'reference',
        'project_id',
        'contact_type',
        'donor_id',
        'transfer',
        'transfer_reference',
        'comment', 
        'files',         
        'company_id',
        'active'
    );
}