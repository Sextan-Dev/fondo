<?php
namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use App\Action;
use App\Category;
use Illuminate\Support\Facades\DB;

class ParametersController extends Controller
{   

    public function listing()
    {
        try{
            $categories = Category::select(['id','name', 'slug','comment', 'category', 'type'])->where([
                'active' => 1, 
                'company_id' => idCompany()
            ])
            ->orderBy('name')
            ->get()->toArray();
            

            $success = true;
            return [
                'success' => true,
                'categories' => $categories
            ];
            
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function delete(Request $request)
    {
        try{
            $deleteItem = Category::find(['id'=>$request->id])->first();
            $deleteItem->delete();

            $action=new Action;
            $action->fill(['type'=>'DESTRUCTION','name'=>'Parameter destroyed : '.$deleteItem->name,'tablename'=>'categories','item'=>$deleteItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            return ['success' => true, 'msg' => _("Elemento destruido correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function store(Request $request) {
        try {
         
            //INSERT
            $newItem = new Category;
            $newItem->fill([
                'name' => $request->value,
                'slug' => $request->value,
                'category' => $request->category,
                'type' => $request->type,
                'active' => 1,
                'company_id' => idCompany()
            ]);
            $newItem->save();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'CREATION','name'=>'Category created : '.$newItem->name,'tablename'=>'categories','item'=>$newItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            //SUCCESS
            return [ 'success' => true, 'msg' => _("Elemento creado correctamente !") , 'data' => $newItem->toArray()];

        } catch(\Exception $e) {
            sendNoticationError($e);
            //FAIL
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function update(Request $request) {
        try {
            $updateItem = Category::where(['id'=>$request->id])->first();
            $oldValues = $updateItem->toArray(); unset($oldValues['updated_at']); //KEEP OLD VALUES
        
            $updateItem->fill([
                'name' => $request->value,
                'slug' => $request->value
            ]);
            $updateItem->save();
            $newValues = $updateItem->toArray();

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'MODIFICATION','name'=>'Category modified : '.$updateItem->name,'tablename'=>'categories','item'=>$updateItem->id,'updates'=>json_encode(compare2arrays($oldValues,$newValues)),'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
                        
            //SUCCESS
            return [ 'success' => true, 'msg' => _("Elemento actualizado correctamente !") ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            //FAIL
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

}