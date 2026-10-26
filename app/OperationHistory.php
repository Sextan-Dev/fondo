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
        'user_id',
        'date',
        'status',
        'import_key',
        'original_name'
    );
}