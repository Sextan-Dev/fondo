<?php

namespace App\Imports\Operations;

use App\Contact;
use App\Operation;
use App\Project;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;


class Projects implements ToCollection
{    
    public function collection(Collection $operations)
    {
        $import_key = 'projects-' . date('YmdHis');
        $operations = $operations->toArray();        

        // REMOVE FIRTS LINE, THIS LINE CONTAINT TITLE DATA
        unset($operations[0]);
    
        foreach ($operations as $key => $operation) {                                                    
            $date_start = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($operation[5])->format('Y-m-d');
            $date_end = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($operation[6])->format('Y-m-d');
            
            // CHECK IF DONOR EXIST            
            $donor_origin = Contact::select('id', 'category')
            ->where([
                'name' => trim(str_replace("_x000D_","", $operation[2])),
                'active' => 1
            ])->first();

            if(!$donor_origin){
                $donor_origin = new Contact;
                $donor_origin->fill( [
                    'name' => trim( str_replace("_x000D_","", $operation[2]) ),
                    'category' => "Donador",
                    'company_id' => idCompany(),
                    'active' => 1
                ] );
                $donor_origin->save();                
            }
            $donor_origin_id = $donor_origin->id;
            $donor_origin_category = $donor_origin->category;


            // VALIDATE PROJECT ORIGIN
            $project_origin_name =  trim(str_replace("_x000D_","", $operation[0]));

            if(isset($project_origin_name) && $project_origin_name != '' ){
                $project_origin = Project::select('id')
                ->where([
                    'name' => $project_origin_name,
                    'active' => 1
                ])->first();

                if($project_origin){
                    $project_origin->fill( [
                        'date_from' => $date_start,                        
                        'date_to' => $date_end,
                        'amount_invertion' => $operation[1],
                        'donors' => $donor_origin_id,
                        'active' => 1
                    ] );
                    $project_origin->save();

                    $project_origin_id = $project_origin->id;
                }else{
                    $project_origin = new Project;
                    $project_origin->fill( [
                        'name' => $project_origin_name,
                        'date_from' => $date_start,                        
                        'date_to' => $date_end,          
                        'amount_invertion' => $operation[1],
                        'donors' => $donor_origin_id,           
                        'company_id' => idCompany(),
                        'active' => 1
                    ] );
                    $project_origin->save();
                    $project_origin_id = $project_origin->id;
                }
            }else{
                $project_origin_id = 0;
            }

            $transfer_reference = date('YmdHis') . $key;

            // MMONTO DE INVERCION 
            // OUTCOME
            $outcome = [
                'date' => $date_start,
                'contact_id' => $donor_origin_id,
                'donor_id' => $donor_origin_id,
                'mode_id' => 973,
                'type' => 'outcome',
                'contact_type' => 'Donador',
                'amount' => trim(str_replace("_x000D_", "",$operation[1])),                
                'category' => trim(str_replace("_x000D_", "", $operation[7])),
                'subcategory' => trim(str_replace("_x000D_", "", $operation[8])),
                'transfer' => 1,
                'transfer_reference' => $transfer_reference,
                'active' => 1,
                'company_id' => idCompany(),
                'import_key' => $import_key
            ];

            $storeOperation = new Operation;
            $storeOperation->fill($outcome);
            $storeOperation->save();

            // INCOME
            $income = [
                'date' => $date_start,
                'contact_id' => $donor_origin_id,
                'donor_id' => $donor_origin_id,
                'mode_id' => 973,
                'type' => 'income',
                'contact_type' => 'Donador',
                'amount' => trim(str_replace("_x000D_", "",$operation[1])),                
                'category' => trim(str_replace("_x000D_", "", $operation[7])),
                'subcategory' => trim(str_replace("_x000D_", "", $operation[8])),
                'project_id' => $project_origin_id,
                'transfer' => 1,
                'transfer_reference' => $transfer_reference,
                'active' => 1,
                'company_id' => idCompany(),
                'import_key' => $import_key
            ];            

            $storeOperation = new Operation;
            $storeOperation->fill($income);
            $storeOperation->save();

            // COSTO OPERATIVO
            if($operation[3]>0){

                $transfer_reference = date('YmdHis') . '2' . $key;
    
                 // OUTCOME
                 $outcome = [
                    'date' => $date_start,
                    'contact_id' => 412,
                    'donor_id' => $donor_origin_id,
                    'mode_id' => 973,
                    'type' => 'outcome',
                    'contact_type' => 'Donador',
                    'amount' => trim(str_replace("_x000D_", "",$operation[3])),                
                    'category' => trim(str_replace("_x000D_", "", $operation[7])),
                    'subcategory' => trim(str_replace("_x000D_", "", $operation[8])),
                    'project_id' => $project_origin_id,
                    'transfer' => 1,
                    'transfer_reference' => $transfer_reference,
                    'active' => 1,
                    'company_id' => idCompany(),
                    'import_key' => $import_key
                ];
    
                $storeOperation = new Operation;
                $storeOperation->fill($outcome);
                $storeOperation->save();
    
                // INCOME
                $income = [
                    'date' => $date_start,
                    'contact_id' => 412,
                    'donor_id' => $donor_origin_id,
                    'mode_id' => 973,
                    'type' => 'income',
                    'contact_type' => 'Donador',
                    'amount' => trim(str_replace("_x000D_", "",$operation[3])),                
                    'category' => 'Costo Operativo',
                    'subcategory' => 'Costo Operativo',
                    'project_id' => $project_origin_id,
                    'transfer' => 1,
                    'transfer_reference' => $transfer_reference,
                    'active' => 1,
                    'company_id' => idCompany(),
                    'import_key' => $import_key
                ];            
    
                $storeOperation = new Operation;
                $storeOperation->fill($income);
                $storeOperation->save();
            
            }
        }    
    }
}