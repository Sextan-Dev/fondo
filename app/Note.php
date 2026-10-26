<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Note extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'notes';
    public $timestamps = true;
    protected $fillable =  [ 'type', 'name', 'category', 'tags', 'share' , 'share_group' , 'content', 'author', 'file', 'comment' ,'active', 'updated_at'];

}