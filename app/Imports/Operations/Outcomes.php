<?php

namespace App\Imports\Operations;

use App\Contact;
use App\Operation;
use App\Project;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;


class Outcomes implements ToCollection
{    
    public function collection(Collection $operations)
    {
        $import_key = 'outcomes-' . date('YmdHis');
        $operations = $operations->toArray();        

        // REMOVE FIRTS LINE, THIS LINE CONTAINT TITLE DATA
        unset($operations[0]);
  
        foreach ($operations as $key => $operation) {                                                    
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($operation[0])->format('Y-m-d');
            // CHECK IF DONOR EXIST            
            $donor = Contact::select('id')
            ->where([
                'name' => str_replace("_x000D_","", $operation[2]),
                'active' => 1
            ])->first();

            if(!$donor){
                $donor = new Contact;
                $donor->fill( [
                    'name' => trim( str_replace("_x000D_","", $operation[2]) ),
                    'category' => "Donador",
                    'company_id' => idCompany(),
                    'active' => 1
                ] );
                $donor->save();                
            }
    
            $donor_id = $donor->id;


            // CHECK IF DESTINY EXIST            
            $provider = Contact::select('id', 'category')
            ->where([
                'name' => str_replace("_x000D_","", $operation[2]),
                'active' => 1
            ])->first();

            if(!$provider){
                $provider = new Contact;
                $provider->fill( [
                    'name' => trim( str_replace("_x000D_","", $operation[2]) ),
                    'category' => trim( str_replace("_x000D_","", $operation[3]) ),
                    'company_id' => idCompany(),
                    'active' => 1
                ] );
                $provider->save();                
            }
    
            $provider_id = $provider->id;
            $provider_category = $provider->category;


            // VALIDATE PROJECT

            $project_name =  trim(str_replace("_x000D_","", $operation[8]));

            if(isset($project_name) && $project_name != '' ){
                $project = Project::select('id')
                ->where([
                    'name' => $project_name,
                    'active' => 1
                ])->first();

                if($project){
                    $project_id = $project->id;
                }else{
                    $project = new Project;
                    $project->fill( [
                        'name' => $project_name,                        
                        'company_id' => idCompany(),
                        'active' => 1
                    ] );
                    $project->save();
                    $project_id = $project->id;
                }
            }else{
                $project_id = 0;
            }


            $data = [
                'date' => $date,
                'contact_id' => $provider_id,
                'donor_id' => $donor_id,
                'mode_id' => 157,            
                'type' => 'outcome',
                'contact_type' => $provider_category,
                'amount' => trim(str_replace("_x000D_", "",$operation[5])),                
                'category' => trim(str_replace("_x000D_", "", $operation[6])),
                'subcategory' => trim(str_replace("_x000D_", "", $operation[7])),
                'project_id' => $project_id,
                'comment' => str_replace("_x000D_", "", $operation[9]),
                'transfer' => 0,
                'active' => 1,
                'company_id' => idCompany(),
                'import_key' => $import_key
            ];

                    
            $storeOperation = new Operation;
            $storeOperation->fill($data);
            $storeOperation->save();
        
        }    
    }
}