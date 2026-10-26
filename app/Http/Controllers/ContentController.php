<?php

namespace App\Http\Controllers;

use App\Content;
use App\User;
use App\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DataTables;

class ContentController extends Controller {

    use \App\Http\Traits\NotificationTrait;
       /*
    |--------------------------------------------------------------------------
    | All contents
    |--------------------------------------------------------------------------
    */

    /**
     * Show Documents List.
     *
     * @return \Illuminate\Http\Response
     */
    public function select(Request $request)
    {
        try{
            $data = DB::table('contents AS c')->where( [ 'c.type' => $request->type, 'c.active' => 1 ] )
                            ->join('users AS u', 'c.user_id', '=', 'u.id')
                            ->where('c.category', 'like', '%'.$request->category.'%')
                            ->where(
                                function($query){
                                    $query->where( 'user_id' ,'=' ,authUser('id') )
                                            ->orwhere( 'share_user_ids' , '=' , null)
                                            ->orwhere( 'share_user_ids' , 'like' , '%' .authUser('id') . '%');
                        })->select(DB::raw('c.*, u.name as author'));

            return Datatables::of($data)->make(true);
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** DATA ***/
    public function data(Request $request)
    {
        try {            
            //QUERIES
            $categories = Content::select(DB::raw('count(id) as count, category as value, category as name'))->where(['type' => $request->type, 'active' => 1 , 'company_id' => idCompany()])->groupBy('category')->orderBy('count', 'desc')->get()->toArray();
            $categories_total = Content::select(DB::raw('count(id) as count, "" as value, "'._("TODO").'" as name'))->where([ 'type' => $request->type, 'active' => 1 , 'company_id' => idCompany()])->get()->toArray()[0];
            array_unshift($categories,$categories_total);

            $users = User::select(DB::raw(' id as value, name as label'))->where([ 'active' => 1 , 'company_id' => idCompany(), ['id', '!=', 1]]   )->get()->toArray();
            $success = true;

            return compact('success','categories', 'users');

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**
     * Saved Document Data
     *
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */
    public function store( Request $request )
    {

        if($request->hasfile('files'))
        {
            $filename = storageFile( $request , 'files' ,  str_replace( ' ' , '_', $request->name) , '/uploads/files/' , true);
        }else{
            $filename = false;
        }

        $newData = [
            'date' => date('Y-m-d- H:i:s'),
            'type' => 'file',
            'name' => $request->name,
            'share_user_ids' => $request->share_user_ids,
            'comment' => $request->comment,
            'category' => $request->category,
            'files' => '/uploads/files/'.$filename,
            'user_id' => authUser('id'),
            'company_id' => idCompany()
        ];
        try{
            $newItem = new Content;
            $newItem->fill( $newData );
            $newItem->save();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'CREATION','name'=>'Content created : '.$newItem->name,'tablename'=>'contents','item'=>$newItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
                      
            $response = ['success' => true,'msg' =>  _('El documento se almaceno de forma correcta.') ];
        }
        catch(\Exception $e)
        {
            $response = ['success' => false , 'msg' =>  _('Se presento un Error al Intentar guardar el Documento: ').$e->getMessage()];
        }
        return response()->json( $response );
    }

    /*** DELETE ***/
    public function delete(Request $request)
    {
        try{
            if (!actionDelete()) {
                return ['success' => false , 'msg' =>  _("Oups... No cuentas con los permisos necesarios para realizar esta accion. Por favor comunicate con el administrador del sistema.") ];
            }
            //SET UNACTIVE
            $updateItem = Content::where(['id'=>$request->id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $updateItem->active = 0;
            $updateItem->save();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'ELIMINATION','name'=>'Contact deleted : '.$updateItem->name,'tablename'=>'contacts','item'=>$updateItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            return ['success' => true, 'msg' => _("Elemento suprimido correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function downloadFile( $query )
    {
        $root = realpath(storage_path('app'));
        $path = realpath(storage_path('app/'.ltrim($query, '/')));
        abort_unless($root && $path && is_file($path) && str_starts_with($path, $root.DIRECTORY_SEPARATOR), 404);
        return response()->download($path);
    }

    public function updateProofs( Request $request)
    {
        try{
            $newData = [];
            if($request->hasfile('files')){
                $filename = storageFile( $request , 'files' ,  $request->slug , '/uploads/documents/' , true );
            }else{
                $filename = false;
            }

            if( is_array($filename) ){
                if( $request->filesOld != '' && $request->filesOld !='null'){
                    $filesOld = json_decode( $request->filesOld ,true);
                    $filename = array_merge($filename,$filesOld);
                }
                $newData['files'] = json_encode( $filename );
            }else{
                $newData['files'] = $request->filesOld;;
            }
            

            //SET UNACTIVE
            $data = Content::where(['id' =>$request->id])->first();
            
            $data->fill($newData);
            $data->save();
            
            $oldValues = $data->toArray();
            if(is_array(json_decode($oldValues['files'],true))){
                foreach (json_decode($oldValues['files'],true) AS $file) {
                    if (!in_array($file,json_decode($newData['files'],true))) {
                        if ($path = publicUploadPath('/uploads/documents/'.safeUploadName($file['file'], false))) {
                            unlink($path);
                        }
                    }
                }
            }

            //SEND NOTIF IF OC o PF  && from other company
            // if ( ($data->type=='order' || $data->type=='billing') && $data->company_id != idCompany()) {
            //     // TRIGGER PUSH NOTIFICATION
            //     $userAuth = Category::select('name as user')->where([ 'category' => 'authorization',  'type' => 'order', 'company_id' => $data->company_id ])->first();
            //     $type = ($data->type=='order') ? 'Orden de Compra' : 'PreFactura' ;
            //     $notification = [
            //         'title' => 'Nuevos Comprobantes de '.$type.' Subidos',
            //         'type' => 'text',
            //         'message' => 'Nuevos comprobantes han sido subido a la '.$type.' "'.$data->name.'" .',
            //         'channel_id' => $userAuth->user, 
            //         'addressee' => 'user',
            //         'url' => ''
            //     ];
            //     $this->sendPushNotificaction($notification);
            // }

            return ['success' => true, 'data'=> $data, 'msg' => _("Elemento adjuntado correctamente !")];

        } catch(\Exception $e){
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function show( Request $request)
    {
        try{
            //SET UNACTIVE
            $data = Content::where(['contents.id'=>$request->id])
            ->leftJoin('operations as o', function($leftJoin)
            {
                $leftJoin->on('contents.id', '=', 'o.content_id');
                $leftJoin->on(DB::raw('o.type'), DB::raw('='),DB::raw("'outcome'"));


            })
            ->selectRaw("contents.*, o.files as paidProofs")
            ->first();            
            return ['success' => true, 'data'=> $data];

        } catch(\Exception $e){
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }


    public function updatePriority (Request $request)
    {       
        try{
            
            $updateItem = Content::find(intval($request->id));
            $oldValues = $updateItem->toArray(); 
            $newData = [  
                'status' => $request->priority,
                'approved_by' => (($request->priority == 'approved') ?  authUser('id') : null),
                'comment' => $updateItem->comment.' - VALIDATION MSG : '.$request->comment
            ];
            $updateItem->fill( $newData );
            $updateItem->save();
            $newValues = $updateItem;

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill([
                'type'=>'MODIFICATION',
                'name'=>'Document modified : '.$newValues->name,
                'tablename'=>'Contents',
                'item'=> $updateItem->id,
                'updates'=> json_encode(compare2arrays($oldValues,$newValues->toArray())),
                'group_id'=> authUser('group_id'),
                'user_id'=> authUser('id'),
                'company_id'=> idCompany()
            ])->save();
            
            // TRIGGER PUSH NOTIFICATION
            
            $user = Action::where([
                'item' => intval($request->id),
                'type' => 'CREATE'
            ])->first();

            //debugger($user->toArray());
           // $userAuth = Category::select('name as user')->where([ 'category' => 'authorization',  'type' => 'order', 'company_id' => $oldValues['company_id'] ])->first();
            if($user){

                $type = ($updateItem->type=='order_pay') ? 'Orden de Pago' : 'Orden de Cobro' ;
                $notification = [
                    'title' => $type.(($request->priority=='approved')?' Aceptada':' Rechazada'),
                    'type' => 'text',
                    'message' => 'La '.$type.' "'.$newValues->name.'" ha sido '.(($request->priority=='approved')?' Aceptada':' Rechazada').'. <br> Mensaje asociado a la validación : <br><em>'.$request->comment.'</em>',
                    'channel_id' => $user->user_id, 
                    'addressee' => 'user',
                    'url' => ''
                ];
                $this->sendPushNotificaction($notification);
            }

            return [ 'success' => true, 'msg' => _("Elemento modificado correctamente !") ];

        }catch(\Exception $e){
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
}
