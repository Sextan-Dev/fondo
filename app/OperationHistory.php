<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OperationHistory extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'history_operations';
    public $timestamps = false;
    protected $fillable = array(
        'id',
        'company_id',
        'type',
        'user_id',
        'user_id_delete',
        'date',
        'date_delete',
        'status',
        'import_key',
        'original_name',
        'file_path',
        'file_hash',
        'rows_count',
        'operations_count',
        'total_amount',
        'total_fee',
        'total_co',
        'donors_created',
        'period_from',
        'period_to',
        'revert_reason'
    );

    const ACTIVE = 1;
    const REVERTED = 0;

    public function operations()
    {
        return $this->hasMany('App\Operation', 'history_operation_id');
    }

    public function contacts()
    {
        return $this->hasMany('App\Contact', 'history_operation_id');
    }
}