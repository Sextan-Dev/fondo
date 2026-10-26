<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Project extends Model 
{
    use \App\Concerns\SerializesDates;


    protected $table = 'projects';
    public $timestamps = true;
    protected $fillable = array(
        "name",
        "pilar",
        "program",
        "project_umbrella",
        "description",
        "date_from",
        "date_to",
        "slug",
        "user_id",
        "ods",
        "icon",
        "img",
        "status",
        "comment",
        "donors",
        "allies",
        "progress",
        "zone_impact_state",
        "indicators",
        "contact_id",
        "donors_confirm",
        "zone_impact_municipality",
        "zone_impact_locality",
        "amount_invertion",
        "others_features_invertion",
        "beneficiary_perfil",
        "location_info",
        "target_general",
        "target_specifics",
        "beneficiary_ages",
        "beneficiary_direct",
        "beneficiary_indirect",
        "target_audiences",
        "social_grade",
        "org_beneficiary",
        "comunication_required",
        "volunteers_required",
        "comunication_product",
        "comunication_type",
        "company_id",
        "files",
        "active",
        "program_id",
        "status_id",
        "category_ids",
        "pilar_id",
        "category",
        "quote_html",
        "type",
        "description_html",
        "beneficiary_details",
        "monitoring",
        "monitoring_fix",
        "monitoring_type",
        "volunteering",
        "volunteering_description",
        "volunteering_hours",
        "attachments",
        "temporality",
        "ministering_value"
        
    );

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id');
    }

    public function company()
    {
        return $this->belongsTo('App\Company', 'company_id');
    }

    public function task()
    {
        return $this->hasMany('App\Task');
    }

}