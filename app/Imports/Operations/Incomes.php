<?php

namespace App\Imports\Operations;

use App\Contact;
use App\Operation;
use App\Action;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;


class Incomes implements ToCollection
{    
    public function collection(Collection $operations)
    {
        $operations = $operations->toArray();        
        $transfer_reference = date('YmdHis');
        $import_key = 'incomes-' . date('YmdHis');
        // REMOVE FIRTS LINE, THIS LINE CONTAINT TITLE DATA
        unset($operations[0]);
        
        foreach ($operations as $key => $operation) {                                                    
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($operation[0])->format('Y-m-d');
            // CHECK IF DONOR EXIST            
            $donor = Contact::select('id')
            ->where([
                'name' => str_replace("_x000D_","", $operation[1]),
                'active' => 1
            ])->first();

            if(!$donor){
                $donor = new Contact;
                $donor->fill( [
                    'name' => trim( str_replace("_x000D_","", $operation[1]) ),
                    'category' => "Donador",
                    'company_id' => idCompany(),
                    'active' => 1
                ] );
                $donor->save();                
            }
    
            $donor_id = $donor->id;
            $valOperatingCost = null;
            $fee = trim(str_replace('%','',str_replace("_x000D_", "", $operation[3])));
            if (!empty($operation[8]) && is_numeric($operation[8])) {
                $amountValue = trim(str_replace("_x000D_", "",$operation[2]));
                $valOperatingCost = calculatedRetainAmount(
                    $amountValue,
                    $fee,
                    $operation[8]
                );
            }

            $data = [
                'date' => $date,
                'contact_id' => $donor_id,
                'mode_id' => 157,            
                'project_id' => 0, 
                'type' => 'income',
                'contact_type' => 'Donador',
                'amount' => trim(str_replace("_x000D_", "",$operation[2])),
                'fee' => $fee,
                'category' => trim(str_replace("_x000D_", "", $operation[4])),
                'subcategory' => trim(str_replace("_x000D_", "", $operation[5])),
                'comment' => trim(str_replace("_x000D_", "", $operation[6])),
                'account' => trim(str_replace("_x000D_", "",$operation[7])), 
                'transfer' => 0,
                'active' => 1,
                'company_id' => idCompany(),
                'import_key' => $import_key,
                'val_operating_cost' => $valOperatingCost
            ];                    
            if (!empty($operation[8]) && is_numeric($operation[8])) {
                $data['percentage_operating_cost'] = $operation[8];
                $data['transfer_reference'] = $transfer_reference;
            }

            $storeOperation = new Operation;
            $storeOperation->fill($data);
            $storeOperation->save();

            $this->_excuteProcessRetain(
                $operation,
                $donor_id,
                $import_key,
                $valOperatingCost,
                $fee,
                $storeOperation->id,
                $transfer_reference
            );
        }
    }

    private function _excuteProcessRetain(
        $operation,
        $donor_id,
        $import_key,
        $amount,
        $fee,
        $idmov,
        $transfer_reference = null
    ) {
        $transfer_reference = $transfer_reference ?? date('YmdHis');
        if (!empty($operation[8]) && is_numeric($operation[8])) {
            $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($operation[0])->format('Y-m-d');
            $newData = [
                'date' => $date,
                'contact_id' => 891,
                'donor_id' => $donor_id,
                'mode_id' => 157,            
                'project_id' => 0, 
                'type' => 'outcome',
                'contact_type' => 'Donador',
                'amount' => $amount,
                'fee' => $fee,
                'category' => trim(str_replace("_x000D_", "", $operation[4])),
                'subcategory' => trim(str_replace("_x000D_", "", $operation[5])),
                'comment' => trim(str_replace("_x000D_", "", $operation[6])),
                'transfer_reference' => $transfer_reference,
                'is_co' => 1,
                'transfer' => 1,
                'active' => 1,
                'company_id' => idCompany(),
                'import_key' => $import_key,
                'percentage_operating_cost' => $operation[8],
                'movement_id' => $idmov
            ];  

            $newItem = new Operation;
            $newItem->fill( $newData );
            $newItem->save();

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill([
                'type'=>'CREATION',
                'name'=>'Operation created : '.$newItem->id,
                'tablename'=>'operations',
                'item'=>$newItem->id,
                'group_id'=>authUser('group_id'),
                'user_id'=>authUser('id'),
                'company_id'=>idCompany()
            ])->save();

            $newData = [
                'date' => $date,
                'contact_id' => 891,
                'donor_id' => $donor_id,
                'category' => "RESERVA RETENCIÓN CO",                    
                'subcategory' => "RESERVA RETENCIÓN CO",
                'mode_id' => 157,            
                'project_id' => 0, 
                'type' => 'income',
                'contact_type' => 'Donador',
                'amount' => $amount,
                'monitoring' => 0,
                'fee' => $fee,
                'comment' => trim(str_replace("_x000D_", "", $operation[6])),
                'transfer_reference' => $transfer_reference,
                'is_co' => 1,
                'transfer' => 1,
                'active' => 1,
                'company_id' => idCompany(),
                'import_key' => $import_key,
                'percentage_operating_cost' => $operation[8],
                'movement_id' => $idmov
            ];  

            $newItem = new Operation;
            $newItem->fill( $newData );
            $newItem->save();
            $operation = $newItem;

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill([
                'type'=>'CREATION',
                'name'=>'Operation created : '.$newItem->id,
                'tablename'=>'operations',
                'item'=>$newItem->id,
                'group_id'=>authUser('group_id'),
                'user_id'=>authUser('id'),
                'company_id'=>idCompany()
            ])->save();
        }
    }
}