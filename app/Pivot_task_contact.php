<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pivot_task_contact extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'pivot_tasks_contacts';
    public $timestamps = true;
    protected $fillable = array( 'task_id','hours','category','points', 'meta','target','done','category_id','contact_id', 'company_id');

    

}