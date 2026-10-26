<?php
namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use App\Action;
use App\Task;
use App\Content;
use App\Category;
use App\Operation;
use App\Contact;
use Illuminate\Support\Facades\DB;
use DataTables;
use Validator;

class OrderCollectController extends Controller
{
    use \App\Http\Traits\NotificationTrait;

    public function index(Request $request)
    {
        try {  
            $success = true;
            return compact('success','types');
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Une erreur est survenue : ").$e->getMessage()];
        }
    }

    /** DATA */
    public function data(Request $request)
    {
        try{
            $credits = [];
            $clients = [];
            if($request->has('task_id') && $request->task_id){
                $data = DB::table('operations AS p')
                        ->leftjoin('contacts AS c', 'c.id', '=', 'p.contact_id')
                        ->leftjoin('inventories AS i', 'i.id', '=', 'p.inventory_id')
                        ->where(['p.task_id' => $request->task_id, 'p.active' => 1, 'p.type' => 'credit' ])
                        ->select(DB::raw('
                            p.id,
                            p.date,
                            p.cost, 
                            p.amount,
                            p.qt,
                            i.id as inventory_id,
                            concat(i.id ," - ",i.article) as article, 
                            p.comment, 
                            c.name,
                            p.type,
                            c.name as provider_name,
                            c.id as provider_id'
                        ))
                        ->get()->toArray();
                foreach( $data as $key => $item){
                    $row = [];
                    $row['include'] = false;                   
                    $row['id'] = $item->id;                   
                    $row['date'] = $item->date;                   
                    $row['qt'] = $item->qt;
                    $row['provider_name'] = $item->provider_name;
                    $row['provider_id'] = $item->provider_id;
                    $row['article'] =  $item->article;
                    $row['inventory_id'] =  $item->inventory_id;
                    $row['comment'] = $item->comment;
                    $row['amount'] = $item->amount;
                    $credits[] = $row;
                    $clients[] = [
                        'value' => $item->provider_id,
                        'label' => $item->name
                    ];     
                }

                $clients = array_values(
                    array_map("unserialize", array_unique( 
                        array_map("serialize", $clients) 
                    ) 
                ));
            }else{
            }
            $clients = Contact::select('id as value', 'name as label', 'contract_type as category')
            ->where([
                'active' => 1
            ])->get();                                
            $optionConcepts = Category::selectRaw('concat(slug, " - ", name) AS label , id AS value')
            ->where([
                'category'=> 'account',
                'type' =>'pay_concept', 
                'active' => 1
            ])
            ->get();
            $userAuthorization = Category::select('name as user')->where([
                'category' => 'authorization',
                'type' => 'order'
            ])->first();
            if($userAuthorization){
                $userAuth= $userAuthorization->user;
            }else{
                $userAuth = null;
            }
            $success = true;
            return compact(
                'success',
                'userAuth',
                'optionConcepts',
                'clients',
                'credits'
            );

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Une erreur est survenue : ").$e->getMessage()];
        }
    }

    /**SHOW */
    public function show( Request $request )
    {
        try{
            // QUERY DATA ORDER
            $item = Content::leftJoin('contacts as c', 'c.id', '=', 'contents.contact_id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'contents.category_id')
            ->leftJoin('tasks as t', 't.id', '=', 'contents.task_id')
            ->leftJoin('projects as p', 't.id', '=', 't.project_id')
            ->where(['contents.id' => $request->item])
            ->selectRaw('contents.*, p.name as project, t.name as task , cat.name as concept')
            ->first();

            $contact = Contact::where([
                'id' => $item->contact_id
            ])->first();

            $operations = Operation::where([
                'operations.content_id' => $request->item,
                'operations.active' => 1,
                'operations.type' => 'order_collect'                
            ])
            ->leftJoin('inventories as i', 'i.id' , '=', 'operations.inventory_id')
            ->selectRaw('operations.* , concat(i.id ," - ",i.article) as article')
            ->get();
            
            $rows = [];
            $totalAmount = 0;
            foreach($operations as $operation){
                $rows[] = [
                    'comment' => $operation->comment,
                    'article' => $operation->article,
                    'reference_id' => $operation->reference_id,
                    'qt' => $operation->qt,
                    'amount' => $operation->amount
                ];
                $totalAmount = $totalAmount + ($operation->qt * $operation->amount);
            }

            $success = true;
            return compact( 'success' , 'item', 'rows', 'contact','totalAmount');
            
        }catch(\Ecxeption $e){
            return ['success' => false , 'msg' =>  _("Oups... Une erreur est survenue : ").$e->getMessage()];
        }
    }

    /** SELECT */
    public function select(Request $request)
    {
        try {      

            //QUERY
            $data = DB::table('contents AS c')
                    ->leftJoin('contacts AS ct', 'c.contact_id', '=', 'ct.id' )
                    ->leftJoin('tasks AS t', 'c.task_id', '=', 't.id' )
                    ->leftJoin('companies as com', 'c.company_id', '=', 'com.id')
                    ->leftJoin('operations as p', 'p.content_id', '=', 'c.id')
                    ->selectRaw('
                                c.id,
                                c.done,
                                t.name as task,
                                c.status,
                                c.created_at,
                                c.category,                                
                                DATE_FORMAT(c.date,"%d/%m/%Y") as date,
                                c.name,
                                ct.name AS provider_name,
                                c.slug,                               
                                c.comment,                                                                
                                com.name as company,
                                sum(ifnull(p.amount,0) * ifnull(p.qt,0))  as total
                    ')
                    ->groupBy('c.id')
                    ->orderBy('c.date', 'desc');

            if($request->has('task_id') && $request->task_id){
                $data = $data->where(['c.task_id' => $request->task_id, 'c.type' => 'order_collect']);
            }else{
                $data = $data->whereRaw(
                    'c.active = 1   
                    AND c.type = "order_collect"                        
                    AND  DATE_FORMAT(c.date,"%Y-%m-%d")  >= "'.$request->from.'" 
                    AND  DATE_FORMAT(c.date,"%Y-%m-%d")  <= "'.$request->to.'"'
                );
            }
            return Datatables::of($data)->make(true);

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**STORE */
    public function store(Request $request)
    {
        try{
            DB::beginTransaction();
            $data = $request->form; 
            $order = new Content;

            $task = Task::where(['id' => $request->task_id])->first();
            $order->fill([
                'date' => $data['date'],
                'name' => 'Orden-Cobro-' . str_pad((Content::where(['type' => 'order_collect', 'active' => 1,   'company_id' => 1 ])->count() + 1), 4, 0, STR_PAD_LEFT),
                'type' => 'order_collect',
                'status' => 'pending',
                'contact_id' => $data['provider_id'],
                'comment' => $data['comment'],
                'category_id' =>  $data['category_id'],
                'company_id' => idCompany(),
                'task_id' => (($request->has('task_id') && $request->task_id) ? $request->task_id : null) ,
                'active' => 1
            ]);
            $order->save();

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill([
                'type'=>'CREATE',
                'name'=>'content create : '.$order->name,
                'tablename'=>'Content',
                'item'=>$order->id,
                'group_id'=>authUser('group_id'),
                'user_id'=>authUser('id'),
                'company_id'=>idCompany()
            ])->save();
            
            $cedits = $data['credits'];
            $dataPivot = [];
            $store = [];
            $operations = new Operation;
            foreach( $cedits as $item) {
                if(!isset($item['include'])  || (  isset($item['include']) && $item['include'] ) ) {
                    
                    $store[] = [
                        'content_id' => $order->id,
                        'date' => date('Y-m-d'),
                        'qt' => $item['qt'],
                        'cost' => $item['amount'],
                        'amount' => $item['amount'],
                        'type' => 'order_collect',                    
                        'comment' => $item['comment'],
                        'inventory_id' => $item['inventory_id'],
                        'reference_id' => $item['id'],
                        'active' => 1,
                        'company_id' => $order->company_id
                    ];
                  
                }        
            }
            $operations->insert($store);
            DB::commit();
            
            $noty = Category::select('name as user')->where([
                'category' => 'authorization',
                'type' => 'order'
            ])->first();
            if($noty){
                
                $notification = [
                    'title' => _('Nueva Orden de Cobro'),
                    'type' => 'text',
                    'message' => "Se solicita autorización para proceder con el cobro de la orden con el folio numero:" . $order->name. " en la actividad ".$task->name,
                    'channel_id' => intval($noty->user),
                    'addressee' => 'user',
                    'url' => ''
                ];
                
                $this->sendPushNotificaction($notification);
            }

            return ['success' => true ,'errorCost'=>false, 'msg' => _("Elemento creado correctamente !") , 'item'=> $order];
            

        } catch(\Exception $e) {
            sendNoticationError($e);
            DB::rollback();
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**UPDATE */
    public function update(Request $request)
    {
        try{
            DB::beginTransaction();
            $order = Content::find($request->form['id']);
            $task = Task::where(['id' => $request->task_id])->first();
            $data = $request->form;       
            
            $order->update([
                'date' => $data['date'],           
                'name' => $data['name'],  
                'status' => 'pending',     
                'category_id' =>  $data['category_id'],         
                'contact_id' => $data['provider_id'],
                'comment' => $data['comment']
            ]);
            $cedits = $data['credits'];
            $deleteItem = Operation::where([
                'content_id'=> $order->id, 
                'type'=> 'order_collect'
            ])->delete();

            $dataPivot = [];
            $store = [];
            $operations = new Operation;
            foreach( $cedits as $item) {
                if(!isset($item['include'])  || (  isset($item['include']) && $item['include'] ) ) {
                    
                    $store[] = [
                        'content_id' => $order->id,
                        'date' => date('Y-m-d'),
                        'qt' => $item['qt'],
                        'cost' => $item['amount'],
                        'amount' => $item['amount'],
                        'type' => 'order_collect',                    
                        'comment' => $item['comment'],
                        'inventory_id' => $item['inventory_id'],
                        'reference_id' => $item['id'],
                        'active' => 1,
                        'company_id' => $order->company_id
                    ];
                  
                }        
            }
            $operations->insert($store);
            
            DB::commit();
            $noty = Category::select('name as user')->where([
                'category' => 'authorization',
                'type' => 'order'
            ])->first();
            
            if($noty){
                
                $notification = [
                    'title' => _('Nueva Orden de Cobro'),
                    'type' => 'text',
                    'message' => "Se solicita autorización para proceder con el cobro de la orden con el folio numero:" . $order->name. " en la actividad ".$task->name,
                    'channel_id' => intval($noty->user),
                    'addressee' => 'user',
                    'url' => ''
                ];               
    
                $this->sendPushNotificaction($notification);
            }
             

            return ['success' => true , 'msg' => _("Elemento actualizado correctamente !") ]; 
        } catch(\Exception $e) {
            sendNoticationError($e);
            DB::rollback();
            return ['success' => false , 'msg' =>  _("Oups... Une erreur est survenue : ").$e->getMessage()];
        }
    }

    /**DELETE */
    public function delete( Request $request)
    {
        try{
            $item = Content::find($request->item);
            $item->update(
                [  'active' => 0 ]
            );
            return ['success' => true, 'msg' => _("Élément supprimé correctement !")];
        } catch(\Exception $e) {
            sendNoticationError($e);
            $response = ['success' => false , 'msg' =>  $e->getMessage()];
        }
        return $response;  
    }


    
}