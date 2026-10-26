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

class OrderPayController extends Controller
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
            $debits = [];
            $clients = [];
            if($request->has('task_id') && $request->task_id){
                $data = DB::table('operations AS p')
                        ->leftjoin('contacts AS c', 'c.id', '=', 'p.contact_id')
                        ->leftjoin('inventories AS i', 'i.id', '=', 'p.inventory_id')
                        ->where(['p.task_id' => $request->task_id, 'p.active' => 1, 'p.type' => 'debit' ])
                        ->select(DB::raw('
                            p.id,
                            p.date,
                            p.cost,
                            p.amount,
                            p.qt,
                            concat(i.id ," - ",i.article) as article,
                            p.comment,
                            c.name, 
                            p.type, 
                            c.id as provider_id'
                        ))
                        ->get()->toArray();
                foreach( $data as $key => $item){
                    $row = [];
                    $row['include'] = false;      
                    $row['id'] = $item->id;                   
                    $row['date'] = $item->date;                   
                    $row['qt'] = $item->qt;
                    $row['article'] =  $item->article ;
                    $row['comment'] = $item->comment;
                    $row['cost'] = $item->cost;
                    $row['amount'] = $item->amount;
                    $debits[$item->provider_id][] = $row;
                    if($item->provider_id){
                        $clients[] = [
                            'value' => $item->provider_id,
                            'label' => $item->name
                        ];     
                    }
                }

                $clients = array_values(
                    array_map("unserialize", array_unique( 
                        array_map("serialize", $clients) 
                    ) 
                ));

                $incomes = DB::table('operations AS o')
                ->leftJoin('contacts as co', 'co.id', '=', 'o.contact_id')
                ->leftJoin('categories as c', 'c.id', '=', 'o.mode_id')
                ->leftJoin('categories as f', 'f.id', '=', 'o.category_id')
                ->leftJoin('tasks as t', 't.id', '=', 'o.task_id')
                ->where([
                    'o.type' => 'income',
                    't.id' => $request->task_id,
                    'o.active' => 1
                ])
                ->selectRaw('o.id as value, concat("[",co.name, "] - [", o.date , "] - $" , format(ifnull(o.amount, 0) - ((ifnull(o.fee, 0)/100) *  ifnull(o.amount, 0)) - ((ifnull(o.monitoring, 0)/100) *  ifnull(o.amount, 0)),2) ) as label')
                ->get();

            }else{
                $clients = Contact::select('id as value','name as label', 'contract_type as category')
                ->where([
                    'active' => 1
                ])->get();                                
            }

            $beneficiaries = Category::selectRaw('name AS label , name AS value')
            ->where([
                'type'=> 'task',
                'category' =>'beneficiary', 
                'active' => 1
            ])
            ->get();
            $investments = Category::selectRaw(' name AS label , name AS value')
            ->where([
                'type'=> 'task',
                'category' =>'investments', 
                'active' => 1
            ])
            ->get();
            $optionConcepts = Category::selectRaw('name AS label , id AS value')
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
           // debugger($optionConcepts);

            $success = true;
            return compact(
                'success',
                'investments',
                'userAuth',
                'beneficiaries',
                'incomes',
                'optionConcepts',
                'clients',
                'debits'
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
            ->leftJoin('users as u', 'u.id', '=', 'contents.approved_by')
            ->leftJoin('users as uc', 'uc.id', '=', 'contents.created_by')
            ->leftJoin('categories as pu', 'pu.id', '=', 't.public')
            ->leftJoin('projects as p', 'p.id', '=', 't.project_id')
            ->leftJoin('categories as cc', 'cc.id', '=', 'p.program_id')
            ->where(['contents.id' => $request->item])
            ->selectRaw('
                contents.*,
                uc.name as user_approved,
                u.name as user_created,
                p.name as project,
                cc.name as program,
                t.name as task,
                pu. name as public,
                cat.name as concept,
                c.name as provider,
                c.id_number,
                c.address,
                c.city,
                c.postal')
            ->first();

            $operation = Operation::selectRaw("c.* ,operations.id as operation_id, operations.amount, operations.date, cat.id as concept_id, cat.name as concept")
            ->leftJoin('contacts as c', 'c.id', '=', 'operations.contact_id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'operations.category_id')
            ->where([
                'operations.id' => $item->operation_id
            ])
            ->first();

            $contact = Contact::where([
                'id' => $item->contact_id
            ])->first();

            $pivots = Operation::where([
                'operations.content_id' => $request->item,
                'operations.active' => 1,
                'operations.type' => 'order_pay'                
            ])
            ->leftJoin('inventories as i', 'i.id' , '=', 'operations.inventory_id')
            ->selectRaw('operations.* ,concat( i.id, " - ", i.article ) as article')
            ->get();
            
            $rows = [];
            $vats = [];
            $totalCost = 0;
            foreach($pivots as $pivot){
                $rows[] = [
                    'reference_id' => $pivot->reference_id,
                    'article' => $pivot->article,
                    'qt' => $pivot->qt,                   
                    'comment' => $pivot->comment,                   
                    'cost' => $pivot->cost,
                    'amount' => $pivot->amount
                ];

                $totalCost = $totalCost + ($pivot->qt * $pivot->cost);
            }

            $success = true;
            return compact( 'success' ,'operation', 'item', 'rows', 'totalCost', 'contact');
            
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
                    ->leftJoin('users as u', 'c.created_by', '=', 'u.id')
                    ->selectRaw('
                                c.id,
                                t.name as task,
                                c.done,
                                c.status,
                                c.created_at,
                                c.category,                                
                                DATE_FORMAT(c.date,"%d/%m/%Y") as date,
                                c.name,
                                u.name as created_by,
                                ct.id as provider_id,
                                ct.name AS provider_name,
                                c.slug,
                                c.apply_vat,                         
                                c.comment,                                                                
                                com.name as company,
                                sum(ifnull(p.cost,0) * ifnull(p.qt,0))  as total
                    ')
                    ->groupBy('c.id')
                    ->orderBy('c.date', 'desc');

            if($request->has('task_id') && $request->task_id){
                $data = $data->where(['c.task_id' => $request->task_id, 'c.type' => 'order_pay']);
            }else{

                
                $data = $data->whereRaw(
                    'c.active = 1   
                    AND c.type = "order_pay"'
                );

               // debugger($data->get()->toArray());
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
            $items = $request->table['rows'];            
            $order = new Content;

            $task = Task::where(['id' => $request->task_id])->first();

            $order->fill([
                'date' => $data['date'],
                'name' => 'OP-' . str_pad((Content::where(['type' => 'order_pay', 'active' => 1,   'company_id' => 1 ])->count() + 1), 4, 0, STR_PAD_LEFT),
                'type' => 'order_pay',
                'status' => 'pending',
                'apply_vat' => $data['apply_vat'],
                'contact_id' => $data['provider_id'],
                'operation_id' => $data['operation_id'],
                'comment' => $data['comment'],
                'company_id' => idCompany(),
                'created_by' => authUser('id'),
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

            $dataPivot = [];
            $pivot = new Operation;
            $totalOrder = 0;
            foreach($items as $item) {
                if(!isset($item['include'])  || (  isset($item['include']) && $item['include'] ) ) {                    
                    $dataPivot[] = [
                        'content_id' => $order->id,
                        'date' => date('Y-m-d'),
                        'qt' => $item['qt'],
                        'cost' => $item['cost'],
                        'amount' => $item['amount'],
                        'type' => 'order_pay',                    
                        'comment' => $item['comment'],
                        'reference_id' => $item['id'],
                        'active' => 1,
                        'inventory_id' => ($item['article'] !='')? (int) explode('-', $item['article'])[0]:null,
                        'company_id' => $order->company_id
                    ];

                    $totalOrder = $totalOrder + $item['cost'];
                }        
            }
            $pivot->insert($dataPivot);
            

            if($request->has('task_id') && $request->task_id){
                $operations = DB::table('operations AS p')
                ->where(['p.task_id' => $request->task_id, 'p.active' => 1 ])
                ->whereIn('p.type', ['debit', 'income', 'outcome', 'credit'])
                ->select(DB::raw('
                    p.type,
                    IF(p.type="debit", 
                        ifnull(p.cost,0), 
                        if(p.type="income", 
                            ( 
                                ifnull(p.amount,0) - ( ( ifnull(p.fee,0)/100 ) * ifnull(p.amount,0) ) -( ( ifnull(p.monitoring,0)/100 ) * ifnull(p.amount,0) )  
                            )
                            , ifnull(p.amount,0)
                        ) 
                    ) as amount')
                )                
                ->get();                
                $totals = collect($operations)
                ->groupBy('type')
                ->map(function ($row) {
                    return $row->sum('amount');
                })->toArray();
                
                
                $incomes = isset($totals['income']) ? $totals['income'] : 0;
                $outcomes = isset($totals['outcome']) ? $totals['outcome'] : 0;
                $credits = isset($totals['credit']) ? $totals['credit'] : 0;
                $debits = isset($totals['debit']) ? $totals['debit'] : 0;
                
                if($totalOrder > ($debits - $outcomes)){
                    DB::rollback();
                    return ['success' => false ,'errorCost'=>true, 'msg' => _("El total de esta orden supera el total de egresos presupuestados !"), 'item'=> $order];
                }
                if($totalOrder > ($incomes - $outcomes)){
                    DB::rollback();
                    return ['success' => false ,'errorCost'=>true, 'msg' => _("El total de esta orden supera el saldo disponible para gastos!") , 'item'=> $order];
                }
               
            }


            DB::commit();
            $users = Category::select('name')->where([
                'category' => 'authorization',
                'type' => 'order'
            ])->get();

            if($users){
                
                foreach($users as $key => $user){

                    $notification = [
                        'title' => _('Nueva Orden de Pago'),
                        'type' => 'text',
                        'message' => "Se solicita autorización para proceder con el pago de la orden con el folio numero:" . $order->name. " en la actividad ".$task->name,
                        'channel_id' => intval($user->name),
                        'addressee' => 'user',
                        'url' => ''
                    ];
                    $this->sendPushNotificaction($notification);
                }
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
            $items = $request->table['rows'];           

            
            $order->update([
                'date' => $data['date'],           
                'name' => $data['name'],   
                'status' => 'pending',   
                //'item' => json_encode($data['item']),
                'apply_vat' => $data['apply_vat'],
                //'html' => json_encode($data['html']),
                //'category_id' =>  $data['category_id'],                
                'contact_id' => $data['provider_id'],
                'operation_id' => $data['operation_id'],
                'comment' => $data['comment']
            ]);
        
            $deleteItem = Operation::where([
                'content_id'=> $order->id, 
                'type'=> 'order_pay'
            ])->delete();

            $dataPivot = [];
            $pivot = new Operation;

            foreach($items as $item) {                
                foreach($items as $item) {                
                    $dataPivot[] = [
                        'content_id' => $order->id,
                        'date' => date('Y-m-d'),
                        'qt' => $item['qt'],
                        'cost' => $item['cost'],
                        'amount' => $item['amount'],
                        'type' => 'order_pay',                    
                        'comment' => $item['comment'],
                        'reference_id' => $item['id'],
                        'active' => 1,
                        'inventory_id' => ($item['article'] !='')? (int) explode('-', $item['article'])[0]:null,
                        'company_id' => 1
                    ];
                }
            }
            $pivot->insert($dataPivot);   
            DB::commit();
            $users = Category::select('name')->where([
                'category' => 'authorization',
                'type' => 'order'
            ])->get();
            
            if($users){

                foreach($users as $key => $user){

                    $notification = [
                        'title' => _('Nueva Orden de Pago'),
                        'type' => 'text',
                        'message' => "Se solicita autorización para proceder con el pago de la orden con el folio numero:" . $order->name. " en la actividad ".$task->name,
                        'channel_id' => intval($user->name),
                        'addressee' => 'user',
                        'url' => ''
                    ];
                    $this->sendPushNotificaction($notification);
                }   
                                
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