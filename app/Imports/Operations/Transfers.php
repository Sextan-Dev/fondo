<?php

namespace App\Imports\Operations;

use App\Contact;
use App\Operation;
use App\Project;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;


class Transfers implements ToCollection
{    
    public function collection(Collection $operations)
    {
        $import_key = 'transfers-' . date('YmdHis');
        $operations = $operations->toArray();        

        // REMOVE FIRTS LINE, THIS LINE CONTAINT TITLE DATA
        unset($operations[0]);        
    
        foreach ($operations as $key => $operation) {                                                    
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($operation[0])->format('Y-m-d');
            
            // CHECK IF DONOR EXIST            
            $donor_origin = Contact::select('id', 'category')
            ->where([
                'name' => trim(str_replace("_x000D_","", $operation[1])),
                'active' => 1
            ])->first();

            if(!$donor_origin){
                $donor_origin = new Contact;
                $donor_origin->fill( [
                    'name' => trim( str_replace("_x000D_","", $operation[1]) ),
                    'category' => "Donador",
                    'company_id' => idCompany(),
                    'active' => 1
                ] );
                $donor_origin->save();                
            }
            $donor_origin_id = $donor_origin->id;
            $donor_origin_category = $donor_origin->category;

            // CHECK IF DESTINY EXIST            
            $donor_destiny = Contact::select('id', 'category')
            ->where([
                'name' => trim(str_replace("_x000D_","", $operation[6])),
                'active' => 1
            ])->first();

            if(!$donor_destiny){
                $donor_destiny = new Contact;
                $donor_destiny->fill( [
                    'name' => trim( str_replace("_x000D_","", $operation[6]) ),
                    'category' => "Donador",
                    'company_id' => idCompany(),
                    'active' => 1
                ] );
                $donor_destiny->save();                
            }
    
            $donor_destiny_id = $donor_destiny->id;
            $donor_destiny_category = $donor_destiny->category;


            // VALIDATE PROJECT ORIGIN
            $project_origin_name =  trim(str_replace("_x000D_","", $operation[5]));

            if(isset($project_origin_name) && $project_origin_name != '' ){
                $project_origin = Project::select('id')
                ->where([
                    'name' => $project_origin_name,
                    'active' => 1
                ])->first();

                if($project_origin){
                    $project_origin_id = $project_origin->id;
                }else{
                    continue;
                }
            }else{
                $project_origin_id = 0;
            }


            // VALIDATE PROJECT DESTINY
            $project_destiny_name =  trim(str_replace("_x000D_","", $operation[9]));

            if(isset($project_destiny_name) && $project_destiny_name != '' ){
                $project_destiny = Project::select('id')
                ->where([
                    'name' => $project_destiny_name,
                    'active' => 1
                ])->first();

                if($project_destiny){
                    $project_destiny_id = $project_destiny->id;
                }else{
                    continue;
                }
            }else{
                $project_destiny_id = 0;
            }

            $transfer_reference = date('YmdHis') . $key;

            // OUTCOME
            $outcome = [
                'date' => $date,
                'contact_id' => $donor_destiny_id,
                'donor_id' => $donor_origin_id,
                'mode_id' => 973,
                'type' => 'outcome',
                'contact_type' => 'Donador',
                'amount' => trim(str_replace("_x000D_", "",$operation[2])),                
                'category' => trim(str_replace("_x000D_", "", $operation[3])),
                'subcategory' => trim(str_replace("_x000D_", "", $operation[4])),
                'project_id' => $project_origin_id,
                'comment' => str_replace("_x000D_", "", $operation[10]),
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
                'date' => $date,
                'contact_id' => $donor_destiny_id,
                'donor_id' => $donor_origin_id,
                'mode_id' => 973,
                'type' => 'income',
                'contact_type' => 'Donador',
                'amount' => trim(str_replace("_x000D_", "",$operation[2])),                
                'category' => trim(str_replace("_x000D_", "", $operation[7])),
                'subcategory' => trim(str_replace("_x000D_", "", $operation[8])),
                'project_id' => $project_destiny_id,
                'comment' => str_replace("_x000D_", "", $operation[10]),
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