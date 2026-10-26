<?php

namespace App\Http\Controllers;

use App\Event;
use App\User;
use App\Action;
use Illuminate\Http\Request;

class EventController extends Controller {

    /*** INDEX ***/
    public function index(Request $request) {
        try {
            //QUERY
            $filterDate = '';
            $data = Event::where([ 'active' => 1 , 'company_id' => idCompany()]);
            
            if($request->has('home')){
                $filterDate =' AND start > now()';
            }
            $data =  $data->whereRaw(' (user_id = "'.authUser('id').'" OR category="public")  '.$filterDate)->with('user')->get();
            return ['success' => true , 'data' => $data];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** SHOW ***/
    public function show(Request $request) {
        try {
            
            //QUERY
            $data = Event::where(['events.id'=>$request->id, 'events.active' => 1 , 'events.company_id' => idCompany()])->join('users', 'users.id', '=', 'events.user_id')->select('events.*', 'users.name as username')->firstOrFail();
            return ['success' => true , 'data' => $data];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** STORE ***/
    public function store(Request $request) {
        try {
            
            //SET DATA
            $newData = [  
                'name' => $request->name,
                'type' => 'EVENT',
                'category' => $request->category,
                'description' => $request->description,
                'location' => $request->location,
                'start' => $request->start,
                'end' => $request->end,
                'allday' => $request->allday,
                'share_user_ids' => $request->share_user_ids,
                'user_id' => authUser('id'),
                'comment' => $request->comment,
                'company_id' => idCompany()
            ];

            //INSERT
            $newItem = new Event;
            $newItem->fill( $newData );
            $newItem->save();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'CREATION','name'=>'Event created : '.$newItem->name,'tablename'=>'events','item'=>$newItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            //SUCCESS
            return [ 'success' => true, 'msg' => _("Elemento creado correctamente !") , 'data' => $newItem->toArray()];

        } catch(\Exception $e) {
            sendNoticationError($e);
            //FAIL
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** UDPATE ***/
    public function update(Request $request) {
        try {
            
            //SET DATA
            $id = $request->id;
            
            //UDPATE
            $updateItem = Event::where(['id'=>$id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $oldValues = $updateItem->toArray(); unset($oldValues['updated_at']); //KEEP OLD VALUES
            $updateItem->fill( $request->except(['id','created_at','updated_at','company_id']) );
            $updateItem->save();
            $newValues = $updateItem->toArray(); //GET NEW VALUES

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'MODIFICATION','name'=>'Event modified : '.$updateItem->name,'tablename'=>'events','item'=>$updateItem->id,'updates'=>json_encode(compare2arrays($oldValues,$newValues)),'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            //SUCCESS
            return [ 'success' => true, 'msg' => _("Elemento modificado correctamente !") ,  'data' => $updateItem->toArray()];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** DELETE ***/
    public function delete(Request $request) {
        try{

            //SET UNACTIVE
            $updateItem = Event::where(['id'=>$request->id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $updateItem->active = 0;
            $updateItem->save();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'ELIMINATION','name'=>'Event deleted : '.$updateItem->name,'tablename'=>'events','item'=>$updateItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            return ['success' => true, 'msg' => _("Elemento suprimido correctamente !")];

            

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** DESTROY ***/
    public function destroy(Request $request) {
        try{

            $deleteItem = Event::find(['id'=>$request->id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $deleteItem->delete();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'DESTRUCTION','name'=>'Event destroyed : '.$deleteItem->name,'tablename'=>'events','item'=>$deleteItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            return ['success' => true, 'msg' => _("Elemento destruido correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

}
