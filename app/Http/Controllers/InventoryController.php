<?php

namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use App\Inventory;
use App\Action;
use Illuminate\Support\Facades\DB;
use DataTables;
use Validator;
class InventoryController extends Controller
{

    // FILTERS
    public function index()
    {
        try {            
            // CATEGORIES
           
            $categories = Inventory::select(DB::raw('count(id) as count, category  as value, category as name'))
            ->where([ 'active' => 1 , 'company_id' => idCompany()])
            ->whereRaw('type = "good" OR type="service"')
            ->groupBy('category')->orderBy('count', 'desc')->get()->toArray();
            
            $categories_total = Inventory::select(DB::raw('count(id) as count, "" as value, "'._("TODO").'" as name'))
            ->where([ 'active' => 1 , 'company_id' => idCompany()])
            ->whereRaw('type = "good" OR type="service"')
            ->get()->toArray()[0];
            array_unshift($categories,$categories_total);
            
            // TYPES
            $types = Inventory::select(DB::raw('count(id) as count, type  as value, IF(type = "good", "'._('Bien').'", "'._('Servicio').'") as name'))
            ->where([ 'active' => 1 , 'company_id' => idCompany()])
            ->whereRaw('type = "good" OR type="service"')
            ->groupBy('type')->orderBy('count', 'desc')->get()->toArray();


            $types_total = Inventory::select(DB::raw('count(id) as count, "" as value, "'._("TODO").'" as name'))
            ->where([ 'active' => 1 , 'company_id' => idCompany()])
            ->whereRaw('type = "good" OR type="service"')
            ->get()->toArray()[0];
            array_unshift($types,$types_total);
            
         
            $success = true;
            return compact('success','categories','types');
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

     /*** DATA ***/
     public function data()
     {
         try{
            $categories = DB::table('inventories AS c')
                         ->where([ 'c.active' => 1 , 'c.company_id' => idCompany()])
                         ->select(DB::raw(' c.category AS value, c.category AS label'))->groupBy('c.category')->get()->toArray();
            $success = true;
            return compact('success','categories');
         } catch(\Exception $e) {
            sendNoticationError($e);
             return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
         }
     }
    /**
     * Show Inventory List.
     *
     * @return \Illuminate\Http\Response
     */
    public function select( Request $request)
    {
        try{
            $inventory = Inventory::where([ 'active' => 1 , 'company_id' => idCompany()] )->whereRaw('type = "good" OR type="service"')->select(DB::raw('inventories.*, IF(type = "good", "'._('Bien').'", "'._('Servicio').'" )as types '));

            if($request->category){
                $inventory = $inventory->where(['category' => $request->category ]); 
            }
            
            if($request->type){
                $inventory = $inventory->where(['category' => $request->type ]); 
            }
            
            
            $inventory = $inventory->get();
            return Datatables::of($inventory)->make(true);

          //  return  $accounts;
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }


    /**
     * Edit User Data
     *
     * @param User $user
     * @return \Illuminate\Http\Response
     */
    public function show( Request $request )
    {
        try{
            $item = Inventory::find($request->item);
            return ['success' => true ,'item' => $item];
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**
     * Update User Data
     *
     * @param Group $group
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */

    public function update (Request $request)
    {       
        try{
            $id = $request->id;
            $newData = $request->except(['id','created_at','updated_at','company_id']);
           
            //UDPATE
            $updateItem = Inventory::where(['id'=>$id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $oldValues = $updateItem->toArray(); 
           
            unset($oldValues['updated_at']); //KEEP OLD VALUES
            $updateItem->fill( $newData );
            $updateItem->save();
            $newValues = $updateItem->toArray(); //GET NEW VALUES

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'MODIFICATION','name'=>'Inventory modified : '.$updateItem->name,'tablename'=>'inventories','item'=>$updateItem->id,'updates'=>json_encode(compare2arrays($oldValues,$newValues)),'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            //SUCCESS
            return [ 'success' => true, 'msg' => _("Elemento modificado correctamente !") ,  'data' => $updateItem->toArray()];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**
     * Saved Account Data
     *
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */
    public function store( Request $request)
    {   
        try{
           

            $newData = [  
                'article' => $request->article,
                'slug' => $request->slug,
                'description' => $request->description,
                'type' => $request->type,
                'category' => $request->category,
                'price' => $request->price,
                'cost' => $request->cost,
                'comment' => $request->comment
            ];

         

            $newData['company_id'] = idCompany();

            $newItem = new Inventory;
            $newItem->fill( $newData );
            $newItem->save();
            
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'CREATION','name'=>'Inventory created : '.$newItem->name,'tablename'=>'inventories','item'=>$newItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
              
            return ['success' => true , 'msg' => _("Elemento creado correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>   _("Oups... Un error ha occurido : ").$e->getMessage()];
        }


    }

    /**
     * Deleted Account Data
     *
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */
    public function delete( Request $request)
    {
        try{
            $item = Inventory::find($request->item);
            $item->update(
                [  'active' => 0 ]
            );
            return ['success' => true, 'msg' => _("Elemento suprimido correctamente !")];
        } catch(\Exception $e) {
            sendNoticationError($e);
            $response = ['success' => false , 'msg' =>  $e->getMessage()];
        }
        return $response;  
    }
}