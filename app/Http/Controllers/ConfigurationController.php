<?php

namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use App\Account;
use App\Action;
use App\Configuration;
use App\Category;
use App\Company;
use Illuminate\Support\Facades\DB;

class ConfigurationController extends Controller
{   
    // SHOW DATA COMPANY
    public function company()
    {
        try{
            $company = getCfg('','company');
            $success = true;
            return compact('success', 'company');
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    // SHOW LIST COMPANIES
    public function listCompany()
    {
        try{
        $companies = Company::where(['active' => 1])->get();

        
        $success= true;
        return compact('success', 'companies');
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    // UPDATE COMPANY
    public function updateConfigurationCompany(Request $request)
    {
        try{
            $data = $request->all();
            
            if($request->hasfile('img')){
                $data['logo_main'] = '/images/'.storageImage( $request , 'img' ,  'logo_main' , '/images/' , false );
                unset($data['img']);
            }
            if(is_array($data)){
                
                foreach($data as $key => $value)
                {
                    $configuration = Configuration::updateOrCreate([ 'name' => $key, 'type'=> 'config', 'category'=>'company' , 'company_id'=>idCompany() ],['value'=> $value ]);
                }
            }
            
            $success = true;
            return ['success' => true , 'msg' => _("Elemento actualizado correctamente !")];
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' => _('Oups... Un error ha ocurrido : '). $e->getMessage()];
        }
    }

    // SHOW ALL PARAMETERS
    public function parameters()
    {
        try{
            $categories = Category::select(['id','name', 'slug','comment', 'category', 'type', 'project_id'])->where( ['active' => 1 , 'company_id' => idCompany()] )
                ->orderBy('type')->orderBy('name')
                ->get()
                ->toArray();

                $success = true;
                return compact('success', 'categories');
            
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    // UPDATE CATEGORIES
    public function updateParametersCategories(Request $request)
    {
        try{
            $categories = $request->all();
            $update = [];
            $insert = [];
            $delete = [];

            if( isset($categories['rowsDelete']) && $categories['rowsDelete']){
                Category::where(['category'=>'authorization' ])->delete();
            }
            if(is_array($categories)){
                foreach( $categories as $key => $category){
                    if($key!='rowsDelete'){

                        foreach( $category['rows'] as $index => $row ){
                            $row['category']=$category['category'];
                            $row['type']=$category['type'];
                            if( $category['category']!= 'authorization' && $row['id']!=''){
                                $update[] = $row;
                            } else {
                                unset($row['id']);
                                $insert[] = $row;
                            }
                        }
                        foreach( $category['rowsDelete'] as $index => $row ){
                            $delete[] = $row;
                        }
                    }
                }

                
     
                // If Array Update is not empty update all items
                if(!empty($update)){
                    foreach($update as $item){                       
                        //UDPATE
                        $updateItem = Category::where(['id'=>$item['id'] ])->first();
                        $oldValues = $updateItem->toArray(); unset($oldValues['updated_at']);
                        $updateItem->fill( $item );
                        $updateItem->save();
                        $newValues = $updateItem->toArray();
                        /**SAVE ACTION**/
                        // $action=new Action;
                        // $action->fill(['type'=>'MODIFICATION','name'=>'Categories modified : '.$updateItem->name,'tablename'=>'categories','item'=>$updateItem->id,'updates'=>json_encode(compare2arrays($oldValues,$newValues)),'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();                        
                    }
                }
                // Insert if not is empty array Insert
                if(!empty($insert)){
                    
                    foreach($insert as $item){
                        $item['company_id'] = idCompany();
                        $newItem = new Category;
                        $newItem->fill( $item );
                        $newItem->save();                        
                        /**SAVE ACTION**/
                        // $action=new Action;
                        // $action->fill(['type'=>'CREATION','name'=>'Categories created : '.$newItem->name,'tablename'=>'categories','item'=>$newItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
                    }
                }
                // Delete if not is empty array Delete
                if(!empty($delete)){
                    foreach($delete as $item){                       
                        //UDPATE
                        $updateItem = Category::where(['id'=>$item['id'] ])->first();
                        $oldValues = $updateItem->toArray(); unset($oldValues['updated_at']);
                        $item['active'] = 0;
                        $updateItem->fill( $item );
                        $updateItem->save();
                        $newValues = $updateItem->toArray();
                        /**SAVE ACTION**/
                        // $action=new Action;
                        // $action->fill(['type'=>'ELIMINATION','name'=>'Category deleted : '.$updateItem->name,'tablename'=>'categories','item'=>$updateItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
                    }
                }
            }
            return ['success' => true , 'msg' => _("Elementos guardado correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>   _("Oups... Un error ha occurido : ").$e->getMessage()];
        }

    }
}