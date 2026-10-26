<?php

namespace App\Http\Controllers;

use App\Project;
use App\Task;
use App\Category;
use App\User;
use App\Contact;
use App\Content;
use App\Action;
use App\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use DataTables;
use Validator;
use App\Pivot_task_contact;
use App\Inventory;
use App\Mail\NotificationNewTaskComment;
use Mail;
// TASK STATUS
// pending
// approved
// working
// done
// canceled
class TaskController extends Controller {

    public function data(Request $request)
    {
        try{
            $proyectAssing = idProyects();
            if ($proyectAssing == 'all') {
                $projects = DB::table('projects AS p')
                        ->leftJoin('tasks AS t', function($join){ $join->on( 't.project_id', '=', 'p.id'); })
                        ->where(['p.active' => 1 , 'p.company_id' => idCompany()])
                        ->select(DB::raw('COUNT(t.id) AS count, p.id AS value, p.name AS label'))->groupBy('p.id')->orderBy('count', 'desc');
            } else {
                $projects = DB::table('projects AS p')
                        ->leftJoin('tasks AS t', function($join){ $join->on( 't.project_id', '=', 'p.id'); })
                        ->whereRaw('p.id IN('.$proyectAssing.')')
                        ->select(DB::raw('COUNT(t.id) AS count, p.id AS value, p.name AS label'))->groupBy('p.id')->orderBy('count', 'desc');
            }
            //GET DATA
            
            $users = DB::table('users AS u')
                        ->join('tasks AS t', function($join){ $join->on( 't.user_id', '=', 'u.id'); })
                        ->where(['u.active' => 1 , 'u.company_id' => idCompany() ])
                        ->select(DB::raw('COUNT(t.id) AS count, u.id AS value, u.name AS label'))->groupBy('u.id')->orderBy('count', 'desc')->get()->toArray();
           
            $status = DB::table('categories AS cat')
                        ->leftJoin('tasks AS t', function($join){ $join->on( 't.status', '=', 'cat.id'); })
                        ->where(['cat.type' => 'task' , 'cat.category' => 'status' , 'cat.active' => 1 ])
                        ->select(DB::raw('COUNT(t.id) AS count, cat.id,cat.name,cat.slug,cat.comment'))->groupBy('cat.id')->orderBy('count', 'desc')->get()->toArray();

            $categories = Task::select(DB::raw( 'category as value, category AS label'))->where([ 'active' => 1 , 'company_id' => idCompany()] )->groupBy('category')->get()->toArray();
            
            $public = Category::select(DB::raw( 'id as value, name AS label'))
            ->where([ 'active' => 1, 'type'=> 'task', 'category' => 'public', 'company_id' => idCompany() ] )
            ->get();

            // States
            $zone_impact_1 = Category::select(DB::raw( 'name as value, name AS label'))
            ->where([ 'active' => 1, 'type'=> 'task', 'category' => 'state', 'company_id' => idCompany() ] )
            ->orderBy('name')->get();

            // Municipality
            $municipality = Task::select(DB::raw( 'zone_impact_2'))
            ->where([ 'active' => 1 , 'company_id' => idCompany()] )
            ->whereRaw(' zone_impact_2 <> "" AND zone_impact_2 is not null ')
            ->groupBy('zone_impact_2')->get();
            $zone_impact_2 = [];
            foreach($municipality as $key => $item){
                if(!is_null($item) ){
                    $items = explode('|', $item->zone_impact_2);
                    $zone_impact_2 = array_unique( array_merge($zone_impact_2, $items) );
                }
            }

            // Locality
            $locality = Task::select(DB::raw( 'zone_impact_3'))
            ->where([ 'active' => 1 , 'company_id' => idCompany()] )
            ->whereRaw(' zone_impact_3 <> "" AND zone_impact_3 is not null ')
            ->groupBy('zone_impact_3')->get();

            $zone_impact_3 = [];
            foreach($locality as $key => $item){
                if(!is_null($item) ){
                    $items = explode('|', $item->zone_impact_3);
                    $zone_impact_3 = array_unique( array_merge($zone_impact_3, $items) );
                }
            }

            $social =   Category::select(DB::raw( 'id as value, name AS label'))
            ->where([ 'active' => 1, 'type'=> 'task', 'category' => 'social', 'company_id' => idCompany() ] )
            ->get();

            $projects_all = Project::select(DB::raw( 'id as value, name AS label'))->where([ 'active' => 1 , 'company_id' => idCompany() ]);
            $users_all = User::select(DB::raw( 'id as value, name AS label'))->where([ 'active' => 1 , 'company_id' => idCompany(), ['user', '!=', 'support']] )->get()->toArray();
            $types = Task::select(DB::raw( 'type as value, type AS label'))->where([ 'active' => 1 , 'company_id' => idCompany()] )->groupBy('type')->get()->toArray();
            $ally = Contact::select( 'name as label', 'id as value' )->where(['active' => 1, 'company_id' => idCompany(), 'category' => 'Aliado'])->get()->toArray();
            $success = true;


            $projects =  $projects->get()->toArray();
            $projects_all = $projects_all->get()->toArray();
            
            $task = DB::table('tasks AS t')
            ->leftJoin('projects AS p', 't.project_id', '=', 'p.id')
            ->where([ 't.active' => 1 , 't.company_id' => idCompany()] )
            ->select(DB::raw('concat(t.name, " [",p.name, "]") AS label , t.id AS value'));
            
            $indicators = [];

           
            if($request->has('project_id')){
                $task = $task->where(['t.project_id' => $request->project_id]);
                $indicators = Category::select(['id as value','name as label', 'slug','comment', 'category', 'type', 'project_id'])
                ->whereRaw('type="indicator" AND category="task" AND (project_id = ? OR project_id is null)', [$request->project_id])
                ->get();
            }else{
                $proyectAssing = idProyects();

                if ($proyectAssing != 'all') {                        
                    $task = $task->whereRaw('t.project_id IN ('.$proyectAssing.')');
                }   
            }
            $task = $task->orderBy('p.name')->orderBy('t.name')->get()->toArray();
            
            $operations = [];
            if($request->has('task_id')){
                $operations = DB::table('operations as o')->where([
                    'o.task_id' => $request->task_id,
                    'o.type' => 'income',
                    'o.active' => 1
                ])
                ->leftJoin('contacts as c', 'c.id', '=', 'o.contact_id')
                ->leftJoin('operations as i', 'i.reference_id', '=', 'o.id')
                ->selectRaw('
                concat("[",c.name, "] - $ ", 
                    (   
                        ifnull(o.amount,0) 
                        - ( (ifnull(o.fee,0) /100) * ifnull(o.amount,0) )
                        - ( (ifnull(o.monitoring,0) /100) * ifnull(o.amount,0) )
                    )
                ) as label, o.id as value,
                ifnull(o.amount,0) 
                - ( (ifnull(o.fee,0) /100) * ifnull(o.amount,0) )
                - ( (ifnull(o.monitoring,0) /100) * ifnull(o.amount,0) ) as income,
                sum(ifnull(i.amount,0)) as outcomes')
                ->groupBy('o.id')
                ->get();
            }

            //FORMAT DATA
            $status = collect($status)->keyBy('slug');
            //RETURN DATA
            return compact('success','projects','projects_all','operations','users','task','users_all', 'status', 'categories', 'types','public', 'zone_impact_1', 'zone_impact_2', 'zone_impact_3', 'social', 'ally', 'indicators');

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
    
    /*** SELECT ***/
    public function selectReminder(Request $request)
    {
        try{
            
        
            $data = DB::table('projects AS p')
            ->join('tasks AS t', function($join){ $join->on( 't.project_id', '=', 'p.id'); })
            ->join('contents AS c', function($join){ $join->on( 'c.task_id', '=', 't.id'); })
            ->join('users AS u', function($join){ $join->on( 'c.user_id', '=', 'u.id'); })
            ->where(['c.type'=>'reminder' ,'c.active' => 1,  't.active' => 1, 'p.active' => 1, 'p.company_id' => idCompany()])
            ->select(DB::raw('c.value, u.name, t.name as task_name, t.id as task_id, c.date'));
            
            if( authUser(['group', 'slug']) == 'project'){
                $data =  $data->whereRaw(' t.user_id = "'.authUser('id').'" OR p.user_id = "'.authUser('id').'"'  );
            }else{
                if($request->filterUser!='' && $request->filterUser!='TODO'){
                    $data = $data->where('p.user_id', $request->filterUser);
                }
            }

            if($request->has('filterTime')){
                $data = $data->whereDate('c.date', '>', date('Y-m-d H:i:s'));
            }

            $data= $data->get()->toArray();

            //RETURN DATA
            return ['success' => true , 'data' =>$data];
        }catch(\Exception $e) {
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** SELECT ***/
    public function dataOperations() {
        try {
            //QUERY
            $contacts = Contact::select(DB::raw( 'id as value, name AS label'))->whereIn('category', ['Aliado', 'Proveedor'])->where([ 'active' => 1 , 'company_id' => idCompany()])->get();
            $donor = Contact::select(DB::raw( 'id as value, name AS label'))->where(['category' => 'Donador'])->where([ 'active' => 1 , 'company_id' => idCompany()])->get();
            $inventories = Inventory::select(DB::raw( 'id as value, article AS label, price, cost'))->where([ 'active' => 1 , 'company_id' => idCompany()])->get();
            $success = true;
            
            return compact('success','contacts', 'donor','inventories');
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** SELECT ***/
    public function select(Request $request)
    {
        try {
            //FILTERS
            $projectFilter = ($request->project>0) ? $request->project : '%%';
            $userFilter = ($request->user>0) ? $request->user : '%%';

            //QUERY
            $data = DB::table('tasks AS t')
                        ->join('projects AS p', function($join){ $join->on( 't.project_id', '=', 'p.id'); })
                        ->leftJoin('users AS u', function($join){ $join->on( 't.user_id', '=', 'u.id'); })
                        ->leftJoin('categories AS program', function($join){ $join->on( 'p.program_id', '=', 'program.id'); })
                        ->leftJoin(DB::raw('(SELECT COUNT(id) AS count, MAX(id) as maxId, task_id FROM contents WHERE type="comment" GROUP BY task_id) AS c'), 'c.task_id', '=', 't.id')
                        ->leftJoin(DB::raw('(SELECT COUNT(id) AS count, MAX(id) as maxId, task_id FROM contents WHERE type="reminder" GROUP BY task_id) AS r'), 'r.task_id', '=', 't.id')
                        ->leftJoin(DB::raw('(SELECT contents.id, users.name AS user, contents.date, contents.value FROM contents INNER JOIN users ON contents.user_id = users.id WHERE contents.type="comment" ) AS cLast'), 'c.maxId', '=', 'cLast.id')
                        ->leftJoin(DB::raw('(SELECT contents.id, users.name AS user, contents.date, contents.value FROM contents INNER JOIN users ON contents.user_id = users.id WHERE contents.type="reminder" ) AS rLast'), 'r.maxId', '=', 'rLast.id')
                        ->where(['t.active' => 1, 'p.active' => 1, 't.company_id' => idCompany()])
                        ->where('t.project_id', 'like', $projectFilter)
                        ->where('t.user_id', 'like', $userFilter)
                        ->select(DB::raw('t.id, 
                                            t.name, 
                                            t.project_id, 
                                            t.status AS status, 
                                            u.name AS user, 
                                            p.name AS project, 
                                            program.name AS program, 
                                            c.count AS countComment, 
                                            DATE_FORMAT(cLast.date,"%d/%m/%y") AS lastCommentDate, 
                                            cLast.value AS lastComment, 
                                            cLast.user AS lastUser,
                                            r.count AS countReminder, 
                                            DATE_FORMAT(rLast.date,"%d/%m/%y" ) AS lastReminderDate, 
                                            rLast.value AS lastReminder, 
                                            rLast.user AS lastUserReminder'
                                            ));
            if($request->filterTime!=''){
                $data = $data->whereRaw( 't.status != "done" and t.status != "canceled"');
            }


            if( authUser(['group', 'slug']) == 'project'){
                $data =  $data->whereRaw(' t.user_id = "'.authUser('id').'" OR p.user_id = "'.authUser('id').'"'  );
            }else{

                $proyectAssing = idProyects();

                if ($proyectAssing != 'all') {                        
                    $data = $data->whereRaw('t.project_id IN ('.$proyectAssing.')');
                } else{

                    if($request->filterUser!='' && $request->filterUser!='TODO'){
                        $data->where('p.user_id', $request->filterUser);
                    }
                }  

            }

            if($request->datable!=''){
                return Datatables::of($data)->make(true);
            }else{
                $data = $data->get()->toArray();
            }

            //FORMAT DATA
            if ($request->groupBy!='') {
                $data = collect($data)->groupBy($request->groupBy)->all();
            }
            if ($request->keyBy!='') {
                $data = collect($data)->keyBy($request->keyBy)->all();
            }

           
            //RETURN DATA
            return ['success' => true , 'data' =>$data];


        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** SELECT  PIVOT ***/ 
    public function pivot(Request $request)
    {
        try {
            $table = [];
            if($request->type ==_('Indicador')){
                $data = DB::table('pivot_tasks_contacts AS p')
                            ->join('categories AS c', 'c.id', '=', 'p.category_id')
                            ->where(['p.task_id' => $request->id ])
                            ->where(['p.category' => $request->type ])
                            ->select(DB::raw('c.*, p.meta, p.target'))->get()->toArray();
            }else{
                $data = DB::table('pivot_tasks_contacts AS p')
                        ->leftjoin('contacts AS c', 'c.id', '=', 'p.contact_id')
                        ->where(['p.task_id' => $request->id ])
                        ->where(['p.category' => $request->type ])
                        ->select(DB::raw('c.*, p.points, p.hours, p.done'))->get()->toArray();
            }
                      //  debugger($data);
            foreach( $data as $key => $item){
                
                $row = [];
                if($request->type ==_('Indicador')){
                    $row['category'] = [ 'value' => $item->id , 'label' => $item->name ];
                    $row['meta'] = $item->meta;
                    $row['target'] = $item->target;
                }else{

                    $row['contact'] =  (is_null($item->name)) ? null : [ 'value' => $item->id , 'label' => $item->name ];
                    $row['email'] = $item->email;
                    $row['age'] = $item->age;
                    $row['gender'] = (is_null($item->gender)) ? null : [ 'value' => $item->gender , 'label' => strtoupper($item->gender) ] ;
                    if($request->type ==_('Voluntario')){
                        $row['hours'] = $item->hours;
                        $row['points'] = $item->points;
                        $row['done'] = $item->done;
                    }
                    $row['comment'] = $item->comment;
                }
                $row['id'] = $item->id;
                
                $table[] = $row;
            }
            
            //RETURN DATA
            return ['success' => true , 'data' =>$table];


        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
    
    /*** SELECT TABLE OPERATIONS ***/
    public function selectOperations(Request $request)
    {
        try {
            $credits = [];
            $incomes = [];
            $outcomes = [];
            $accounts = [];
            $debits = [];
            $data = DB::table('operations AS p')
                        ->leftjoin('contacts AS c', 'c.id', '=', 'p.contact_id')
                        ->leftJoin('accounts as a', 'a.id', '=', 'p.account_id')
                        ->leftjoin('inventories AS i', 'i.id', '=', 'p.inventory_id')
                        ->where(['p.task_id' => $request->id, 'p.active' => 1 ])
                        ->select(DB::raw('p.id,
                            p.date,
                            p.cost,
                            a.name as account,
                            a.id as account_id,
                            p.amount,
                            p.fee,
                            p.monitoring,
                            p.qt,
                            concat(i.id ," - ",i.article) as article,
                            p.comment,
                            c.name,
                            p.type,
                            c.id as client_id'))
                        ->get()->toArray();
            
            foreach( $data as $key => $item){
                $row = [];
                if( $item->type != 'credit' && $item->type !='debit'){
                    $account = (array) $item;
                    $account['amount'] = $account['amount'] - ( ($account['fee']/100) * $account['amount'] ) -  ( ($account['monitoring']/100) * $account['amount'] );
                    $accounts[$item->account][] = (array) $account; 
                }
                switch ($item->type) {
                    case 'income':
                        $item = (array) $item;
                        $item['amount'] = $item['amount'] - ( ($item['fee']/100) * $item['amount'] ) -  ( ($item['monitoring']/100) * $item['amount'] );
                        $incomes[] = (array) $item;
                        break;
                    case 'outcome':
                        $outcomes[] =  (array) $item;
                        break;
                    case 'credit':
                        $row['in_date'] = $item->date;
                        $row['client'] = (is_null($item->client_id) )? null : [ 'value' => $item->client_id , 'label' => $item->name ];                     
                        $row['in_comment'] = $item->comment;
                        $row['in_amount'] = $item->amount;
                        $row['id'] = $item->id;
                        $credits[] = $row;
                        break;
                    case 'debit':
                        $row['out_date'] = $item->date;
                        $row['provider'] = (is_null($item->client_id) )? null : [ 'value' => $item->client_id , 'label' => $item->name ];
                        $row['out_qt'] = $item->qt;
                        $row['out_article'] =  $item->article ;
                        $row['out_comment'] = $item->comment;
                        $row['out_cost'] = $item->cost;
                        $row['out_amount'] = $item->amount;
                        $row['id'] = $item->id;
                        $debits[] = $row;
                        break;
                }
            }

            //RETURN DATA
            return ['success' => true , 'debits' => $debits , 'credits'=> $credits, 'incomes'=> $incomes, 'outcomes' => $outcomes, 'accounts' => $accounts];


        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function transferOperations(Request $request)
    {
        try{
            DB::beginTransaction();
            
            
            $operations = DB::table('operations AS p')         
            ->where(['p.task_id' => $request->task_id, 'p.active' => 1 ])
            ->whereIn('p.type', ['income', 'outcome'])
            ->select(DB::raw('
                sum( IF(p.type = "income", (IFNULL(p.amount,0) -  (IFNULL(p.fee,0)/100 * IFNULL(p.amount,0)) - (IFNULL(p.monitoring,0)/100 * IFNULL(p.amount,0))  ), -(IFNULL(p.amount,0) -  (IFNULL(p.fee,0)/100 * IFNULL(p.amount,0)) - (IFNULL(p.monitoring,0)/100 * IFNULL(p.amount,0))  )  )) as total
            '))
            ->groupBy('p.task_id')
            ->first();
            $balance = 0;

            if($operations){
                $balance = $operations->total;
            }

            if($balance<$request->operation['amount']){
                return ['success' => false, 'msg' => _('No tiene saldo suficiente para realizar la tranferencia')];
            }
            
            
            $operation = Operation::where('company_id', idCompany())->findOrFail($request->operation['operation_id']['value']);

            if($operation){

                $incomeBalance = $request->operation['operation_id']['income'] - $request->operation['operation_id']['outcomes'] ;

                if($incomeBalance < $request->operation['amount'] ){
                    return ['success' => false, 'msg' => _('No tiene saldo suficiente en este ingreso para realizar la tranferencia')];
                }
                
                $outcome = $operation->replicate();
                $outcome->type = date("Y-m-d");
                $outcome->reference_id =  $request->operation['operation_id']['value'];
                $outcome->type = 'outcome';
                $outcome->amount = $request->operation['amount'];
                $outcome->comment =  $request->operation['comment'];
                $outcome->save();
                
                $income = $operation->replicate();                
                $income->type = date("Y-m-d");
                $income->type = 'income';
                $income->transfer = 1;
                $income->task_id = $request->operation['task_id']['value'];
                $income->amount = $request->operation['amount'];
                $income->comment =  $request->operation['comment'];
                $income->save();
                


            }

            DB::commit();
            return ['success' => true, 'msg' => _('Tranferencia Exitosa')];
        }catch(\Exception $e){
            DB::rollback();
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** DUPLICATE TASK ***/
    public function duplicate(Request $request)
    {
        try{
            // DUPLICATE JUST INFO GENERAL
            $duplicate['name'] = $request->name;
            $duplicate['description'] = $request->description;
            $duplicate['project_id'] = $request->project_id;
            $duplicate['user_id'] = $request->user_id;
            $duplicate['date_from'] = $request->date_from;
            $duplicate['date_to'] = $request->date_to;
            $duplicate['status'] = $request->status;
            $duplicate['company_id'] = idCompany();

            $newItem = new Task;
            $newItem->fill( $duplicate );
            $newItem->save();
            $task = $newItem;
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'CREATION','name'=>'Task Duplicate : '.$newItem->name,'tablename'=>'tasks','item'=>$newItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
        
            return ['success' => true ,'msg' => _("Elemento creado correctamente !"), 'task_id' => $task->id];
       

        }catch(Exception $e){
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
    
    /*** STORE ***/
    public function store(Request $request)
    {
        try{
            $comment = '';
            $reminder = '';
            if($request->has('comment')){
                $comment = $request->comment;
            }
            if($request->has('reminder')){
                $milestone = $request->milestone;
                $reminder = $request->reminder;
            }
            $newData = $request->except(['comment', 'remider']);
            $task = [];
            if(is_array($newData)){
                foreach(  $newData as $key => $item  ){
                    if(is_array($item)){
                        if(array_key_exists('value', $item)){
                            $task[$key] = $item['value'];
                        }else{
                            $task[$key] = implode('|',$item);
                        }
                    }else{
                        $task[$key] = $item;
                    }
                }
                $task['company_id'] = idCompany();
            }

            
            //INSERT NEW TASK
            $newItem = new Task;
            $newItem->fill( $task );
            $newItem->save();
            $task = $newItem;
            /**SAVE ACTION NEW TASK**/
            $action=new Action;
            $action->fill(['type'=>'CREATION','name'=>'Task created : '.$newItem->name,'tablename'=>'tasks','item'=>$newItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            // IF HAS COMMENT REGISTER AND SEND MAIL
            if( $comment!= '' ){
                $newItem = new Content;
                $newItem->fill( ['task_id'=> $task->id, 'user_id'=>authUser('id'),'date'=>date('Y-m-d'), 'value' => $comment, 'type'=>'comment', 'company_id' => idCompany() ] );
                $newItem->save();
                //$user = User::find($task['user_id']);
                // debugger(['user'=> $user->name, 'task'=>$newItem->name, 'comment' => $comment ]);
                // Mail::to($user->email)->send(new NotificationNewTaskComment(['user'=> $user->name, 'task'=>$newItem->name, 'comment' => $comment ]) );
             
                // /**SAVE ACTION NEW COMMENT**/
                // $action=new Action;
                // $action->fill(['type'=>'CREATION','name'=>'Comment created : '.$newItem->name,'tablename'=>'contents','item'=>$newItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            }

            // IF HAS REMINDER REGISTER
            if( $reminder!= '' ){
                $newItem = new Content;
                $newItem->fill( ['task_id'=> $task->id, 'user_id'=>$task->user_id,'date'=>$milestone , 'value' => $reminder, 'type'=>'reminder', 'company_id' => idCompany() ] );
                $newItem->save();

                /**SAVE ACTION NEW REMINDER**/
                $action=new Action;
                $action->fill(['type'=>'CREATION','name'=>'Reminder created : '.$newItem->name,'tablename'=>'contents','item'=>$newItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            }
            
            // REQUEST NEW DATA AND RETURN TO VUE COMPONENT
            $data = Task::where(['id'=>$task->id, 'active' => 1 , 'company_id' => idCompany()])
                    ->firstOrFail();
            
            // REQUEST ALL COMMENTS AND REMINDER    
            $contents = Content::where([ 'task_id' => $task->id,'active' => 1, 'company_id' => idCompany() ])
            ->where(function ($query) {
                $query->where('type', '=', 'reminder')
                      ->orWhere('type', '=', 'comment');   
            })                  
            ->with('user')->get()->toArray();
            $newData['milestone'] = date('Y-m-d');
            $content['reminders'] = [];
            $content['comments'] = [];
            foreach($contents as $key => $item){
                if($item['type']=='reminder'){                    
                    $content['reminders'][] = $item;
                }else{
                    $content['comments'][] = $item;
                }
            }
            $data['comments']= $content['comments'];
            $data['reminders']= $content['reminders'];
            $data['zone_impact_1'] = is_null($data['zone_impact_1']) || $data['zone_impact_1'] == '' ?  null : explode('|', $data['zone_impact_1'] );
            $data['zone_impact_2'] = is_null($data['zone_impact_2']) || $data['zone_impact_2'] == '' ?  null : explode('|', $data['zone_impact_2'] );
            $data['zone_impact_3'] = is_null($data['zone_impact_3']) || $data['zone_impact_3'] == '' ?  null : explode('|', $data['zone_impact_3'] );
            // RETURN DATA
            return ['success' => true ,'msg' => _("Elemento creado correctamente !"), 'task' => $data];

        }catch(Exception $e){
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** SHOW ***/
    public function show(Request $request)
    {
        try {
            //QUERY
            $data = Task::where(['id'=>$request->id, 'active' => 1 , 'company_id' => idCompany()])
                    ->firstOrFail();
            $reminders=[];
            $comments=[];
            $contents = Content::where([ 'task_id' => $request->id,'active' => 1, 'company_id' => idCompany() ])
            ->where(function ($query) {
                $query->where('type', '=', 'reminder')
                        ->orWhere('type', '=', 'comment');   
            })                  
            ->with('user')->get()->toArray();
            foreach($contents as $key => $item){
                if($item['type']=='reminder'){                    
                    $reminders[] = $item;
                }else{
                    $comments[] = $item;
                }
            }
            $data['zone_impact_1'] = is_null($data['zone_impact_1']) || $data['zone_impact_1'] == '' ?  null : explode('|', $data['zone_impact_1'] );
            $data['zone_impact_2'] = is_null($data['zone_impact_2']) || $data['zone_impact_2'] == '' ?  null : explode('|', $data['zone_impact_2'] );
            $data['zone_impact_3'] = is_null($data['zone_impact_3']) || $data['zone_impact_3'] == '' ?  null : explode('|', $data['zone_impact_3'] );
            $data['comments'] = $comments;
            $data['reminders'] = $reminders;
            return ['success' => true , 'data' => $data];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** UDPATE TASK LIST ***/
    public function update(Request $request)
    {
        try {            
            //SET DATA
            $id = $request->id;
            $newData = $request->except(['id','created_at','updated_at','company_id','filesOld']);
            
            if($newData['status'] == 'done'){
                $newData['date_done'] = date('Y-m-d H:i:s');
            }

          
            
            //UDPATE
            $updateItem = Task::where(['id'=>$id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            if($newData['status'] != 'done' && $updateItem->status=='done'){
                $newData['date_done'] = null;
            }
            $oldValues = $updateItem->toArray(); unset($oldValues['updated_at']); //KEEP OLD VALUES
            $updateItem->fill( $newData );
            $updateItem->save();
            $newValues = $updateItem->toArray(); //GET NEW VALUES

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'MODIFICATION','name'=>'Task modified : '.$updateItem->name,'tablename'=>'tasks','item'=>$updateItem->id,'updates'=>json_encode(compare2arrays($oldValues,$newValues)),'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            //SUCCESS
            return [ 'success' => true, 'msg' => _("Elemento modificado correctamente !") ,  'data' => $updateItem->toArray()];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** UDPATE FORM GENERAL***/
    public function updateGeneral(Request $request)
    {
        try {
            //debugger($request->all());
            $id = $request->id;
            // GET OLD DATA
            $oldData = Task::where(['id'=>$id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $newData = $oldData->toArray();

            // CHECK IF CONTAINT COMMENT
            $comment = '';
            $reminder = '';
            if($request->has('comment')){
                $comment = $request->comment;
            }
            if($request->has('reminder')){
                $milestone = $request->milestone;
                $reminder = $request->reminder;
            }
            
            if($request->status == 'done'){
                $newData['date_done'] = date('Y-m-d H:i:s');
            }
            
            if($request->status != 'done' && $oldData['status']=='done'){
                $newData['date_done'] = null;
            }

            // UPDATE OLD DATA
            $newData['name'] = $request->name;
            $newData['description'] = $request->description;
            $newData['slug'] = $request->slug;
            $newData['project_id'] = $request->project_id;
            $newData['user_id'] = $request->user_id;
            $newData['org_beneficiary'] = $request->org_beneficiary;
            $newData['date_from'] = $request->date_from;
            $newData['date_to'] = $request->date_to;
            $newData['status'] = $request->status;
            $newData['public'] = $request->public;
            $newData['zone_impact_1'] = ($request->zone_impact_1 == '') ? null : trim(implode('|',$request->zone_impact_1),'|');
            $newData['zone_impact_2'] = ($request->zone_impact_2 == '') ? null : trim(implode('|',$request->zone_impact_2),'|');
            $newData['zone_impact_3'] = ($request->zone_impact_3 == '') ? null : trim(implode('|',$request->zone_impact_3),'|');
            $newData['description_html'] = $request->description_html;
            $newData['ally'] = $request->ally;
            $newData['social'] = $request->social;
                     
            //UDPATE
           
            $oldValues = $oldData->toArray(); unset($oldValues['updated_at']); //KEEP OLD VALUES
            $oldData->fill( $newData );
            $oldData->save();
            $newValues = $oldData; //GET NEW VALUES

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'MODIFICATION','name'=>'Task modified : '.$newValues->name,'tablename'=>'tasks','item'=>$newValues->id,'updates'=>json_encode(compare2arrays($oldValues,$newValues->toArray())),'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            // IF CONTAINT COMMENT

            if( $comment!= '' ){
                $newItem = new Content;
                $newItem->fill( ['task_id'=> $id, 'user_id'=>authUser('id'),'date'=>date('Y-m-d H:i:s'), 'value' => $comment, 'type'=>'comment', 'company_id' => idCompany() ] );
                $newItem->save();

                // SEND MAIL NOTIFICATION
                // $user = User::find($oldData->user_id);
                // $project = Project::find($oldData->project_id);
                // Mail::to($user->email)->send(new NotificationNewTaskComment(['user'=> $user->name, 'task'=>$oldData->name, 'project' => $project->name  ]) );
                // /**SAVE ACTION**/
                // $action=new Action;
                // $action->fill(['type'=>'CREATION','name'=>'Comment created : '.$newItem->name,'tablename'=>'contents','item'=>$id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();            
            }

            if( $reminder!= '' ){
                $newItem = new Content;
                $newItem->fill( ['task_id'=> $id, 'user_id'=>$request->user_id,'date' => $milestone, 'value' => $reminder, 'type'=>'reminder', 'company_id' => idCompany() ] );
                $newItem->save();

                /**SAVE ACTION**/
                $action=new Action;
                $action->fill(['type'=>'CREATION','name'=>'Reminder created : '.$newItem->name,'tablename'=>'contents','item'=>$newItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            }
            
            $contents = Content::where([ 'task_id' => $id,'active' => 1, 'company_id' => idCompany() ])
            ->where(function ($query) {
                $query->where('type', '=', 'reminder')
                      ->orWhere('type', '=', 'comment');   
            })                  
            ->with('user')->get()->toArray();
            $newData['milestone'] = date('Y-m-d');
            $newData['reminders'] = [];
            $newData['comments'] = [];
            foreach($contents as $key => $item){
                if($item['type']=='reminder'){                    
                    $newData['reminders'][] = $item;
                }else{
                    $newData['comments'][] = $item;
                }
            }

            $newData['zone_impact_1'] = is_null($newData['zone_impact_1']) || $newData['zone_impact_1'] == '' ?  null : explode('|', $newData['zone_impact_1'] );
            $newData['zone_impact_2'] = is_null($newData['zone_impact_2']) || $newData['zone_impact_2'] == '' ?  null : explode('|', $newData['zone_impact_2'] );
            $newData['zone_impact_3'] = is_null($newData['zone_impact_3']) || $newData['zone_impact_3'] == '' ?  null : explode('|', $newData['zone_impact_3'] );
            // RETURN DATA
            return ['success' => true ,'msg' => _("Elemento modificado correctamente !"), 'task' => $newData];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** UDPATE FORM OPERATIONS***/
    public function updateOperations(Request $request)
    {
        try {
        //debugger($request->all());
        DB::beginTransaction();
            $id = $request->id;
            $credits = $request->creditTable['rows'];
            $debits = $request->debitTable['rows'];
           
            // GET OLD DATA
            $updateItem = Task::where(['id'=>$id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $newData = $updateItem->toArray();
            $deleteItem = Operation::where(['task_id'=>$id , 'type'=> 'credit'] )->delete();
            if(!empty($credits)){
                foreach( $credits as $key => $credit ){
                    if($credit['client'] == '' && !is_array($credit['client'])){
                        $contact_id =  null;
                    }else{
                        if(!is_array($credit['client'])){
                            $credit['client']= ['label'=>$credit['client']];
                        }
                        if( array_key_exists( 'value', $credit['client'] )){
                            $contact_id = $credit['client']['value'];
                        }else{
                            // IF NOT EXIST CREATED
                            $newContact = new Contact;
                            $dataContact = [
                                'name' => $credit['client']['label'],
                                'category' => _('Cliente'),
                                'company_id' => idCompany()
                            ];
                            $newContact->fill( $dataContact );
                            $newContact->save();
                            $contact_id = $newContact->id;
                        }
                    }
                    $newData = [  
                        'date' => date("Y-m-d", strtotime( str_replace('/','-', ($credit['in_date'] =='' ? date("Y/m/d"): $credit['in_date'] ) ))),
                        'contact_id' => $contact_id,
                        'type' => 'credit',
                        'task_id' => $id,
                        'amount' =>  $credit['in_amount'],
                        'comment' => $credit['in_comment'],
                        'company_id' => idCompany()
                    ];                     
                    $newOperation = new Operation;
                    $newOperation->fill( $newData );
                    $newOperation->save();
                  
                }
            }
            $deleteItem = Operation::where(['task_id'=>$id , 'type'=> 'debit'] )->delete();
            if(!empty($debits)){
                foreach( $debits as $key => $debit ){
                    
                    if($debit['provider'] == '' && !is_array($debit['provider'])){
                        $contact_id =  null;
                    }else{
                        if(!is_array($debit['provider'])){
                            $debit['provider']= ['label'=>$debit['provider']];
                        }
                        if( array_key_exists( 'value', $debit['provider'] )){
                            $contact_id = $debit['provider']['value'];
                        
                        }else{
                            // IF NOT EXIST CREATED
                            
                                $newContact = new Contact;
                                $dataContact = [
                                    'name' => $debit['provider']['label'],
                                    'category' => _('Proveedor'),
                                    'company_id' => idCompany()
                                ];
                                $newContact->fill( $dataContact );
                                $newContact->save();
                                $contact_id = $newContact->id;
                            
                            
                            
                        }
                    }
                    $newData = [  
                        'date' => date("Y-m-d", strtotime( str_replace('/','-', ($debit['out_date'] =='' ? date("Y/m/d"): $debit['out_date'] )))),
                        'contact_id' => $contact_id,
                        'type' => 'debit',
                        'task_id' => $id,
                        'inventory_id' => ($debit['out_article'] !='')?explode('-', $debit['out_article'])[0]:null,
                        'amount' =>  $debit['out_amount'],
                        'cost' =>  $debit['out_cost'],
                        'qt' =>  $debit['out_qt'],
                        'comment' => $debit['out_comment'],
                        'company_id' => idCompany()
                    ];                     
                    $newOperation = new Operation;
                    $newOperation->fill( $newData );
                    $newOperation->save();
                }

            }            
            DB::commit();
            return ['success' => true ,'msg' => _("Elementos modificados correctamente !"), 'task' => $newData];

        }catch(Exception $e){
            DB::rollback();
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** UDPATE FORM CONTACTS***/
    public function updateContacts(Request $request)
    {
        try {
            $contacts = $request->contacts;    
            $form = $request->form;    
            $id = $request->form['id'];
            // GET OLD DATA
            $updateItem = Task::where(['id'=>$id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $newData = $updateItem->toArray();
            
            $deleteItem = Pivot_task_contact::where(['task_id'=>$id, 'category'=>_('Beneficiario')] )->delete();
            if(is_array($contacts) && !empty($contacts)){
                foreach( $contacts as $key => $item ){
                    if(!is_array($item['contact'])){
                        $item['contact']= ['label'=>$item['contact']];
                    }
                    // IF CONTACT EXIST UPDATE INFO IN DIR
                    if( array_key_exists( 'value', $item['contact'] )){
                        $contact_id = $item['contact']['value'];                        
                        // UPDATE INFO CONTACT
                        $updateContact = Contact::where(['id'=>$contact_id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
                       
                        $oldValuesContact = $updateItem->toArray(); unset($oldValuesContact['updated_at']); //KEEP OLD VALUES
                        $updateContact->fill(
                            [
                                'email' => $item['email'],
                                'age' => $item['age'],
                                'gender' => $item['gender']['value'],
                                'comment' => $item['comment'],
                                'category' => _('Beneficiario')
                                ]
                            );

                        $updateContact->save();
                        $newValuesContact = $updateContact->toArray();  
                        // SAVE PIVOT
                        $dataPivot['task_id'] = $id; 
                        $dataPivot['category'] = _('Beneficiario'); 
                        $dataPivot['contact_id'] = $contact_id;
                        $dataPivot['company_id'] = idCompany();
                        
                        $newPivot = new Pivot_task_contact;
                        $newPivot->fill( $dataPivot );
                        $newPivot->save();                          
                    }else{
                        // IF NOT EXIST CREATED
                        $newContact = new Contact;
                        $dataContact = [
                            'name' => $item['contact']['label'],
                            'email' => $item['email'],
                            'age' => $item['age'],
                            'gender' => $item['gender']['value'],
                            'comment' => $item['comment'],
                            'category' => _('Beneficiario'),
                            'company_id' => idCompany()
                        ];
                        $newContact->fill( $dataContact );
                        $newContact->save();
                        $contact_id = $newContact->id;
                        // SAVE PIVOT
                        $dataPivot['task_id'] = $id; 
                        $dataPivot['contact_id'] = $contact_id;
                        $dataPivot['category'] = _('Beneficiario'); 
                        $dataPivot['company_id'] = idCompany();
                        
                        $newPivot = new Pivot_task_contact;
                        $newPivot->fill( $dataPivot );
                        $newPivot->save();
                    }
                }
            }

            // UPDATE OLD DATA
            $newData['benef_1'] = $request->form['benef_1'];
            $newData['benef_2'] = $request->form['benef_2'];
            $newData['benef_3'] = $request->form['benef_3'];
            $newData['target_benef_1'] = $request->form['target_benef_1'];
            $newData['target_benef_2'] = $request->form['target_benef_2'];
            $newData['target_benef_3'] = $request->form['target_benef_3'];


            //UDPATE
           
            $oldValues = $updateItem->toArray(); unset($oldValues['updated_at']); //KEEP OLD VALUES
            $updateItem->fill( $newData );
            $updateItem->save();
            $newValues = $updateItem->toArray(); //GET NEW VALUES

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'MODIFICATION BENEFICIARIOS','name'=>'Task modified : '.$updateItem->name,'tablename'=>'tasks','item'=>$updateItem->id,'updates'=>json_encode(compare2arrays($oldValues,$newValues)),'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            
            return ['success' => true ,'msg' => _("Elementos modificados correctamente !")];


        }catch(Exception $e){
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** UDPATE FORM VOLUNTEERS***/
    public function updateVolunteers(Request $request)
    {
        try {
           
            $contacts = $request->contacts;    
            $form = $request->form;    
            $id = $request->form['id'];
            // GET OLD DATA
            $updateItem = Task::where(['id'=>$id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $newData = $updateItem->toArray();
            
            $deleteItem = Pivot_task_contact::where(['task_id'=>$id ,'category'=>_('Voluntario')] )->delete();
            
            if(is_array($contacts) && !empty($contacts)){
                foreach( $contacts as $key => $item ){
                    //debugger($item);
                    if($item['contact'] == '' && !is_array($item['contact'])){
                        $contact_id =  null;
                        // SAVE PIVOT
                        $dataPivot['task_id'] = $id; 
                        $dataPivot['contact_id'] = $contact_id;
                        $dataPivot['hours'] = $item['hours'];
                        $dataPivot['category'] = _('Voluntario');
                        if((boolean) getCfg('rewards','company') ){
                            $dataPivot['points'] = $item['points']; 
                        }
                        $dataPivot['done'] = $item['done']; 
                        $dataPivot['company_id'] = idCompany();
                        
                        $newPivot = new Pivot_task_contact;
                        $newPivot->fill( $dataPivot );
                        $newPivot->save();
                    }else{
                        if(!is_array($item['contact'])){
                            $item['contact']= ['label'=>$item['contact']];
                        }
                        if( array_key_exists( 'value', $item['contact'] )){
                            $contact_id = $item['contact']['value'];                        
                            // UPDATE INFO CONTACT
                           // debugger($request->all());  
                            $updateContact = Contact::where(['id'=>$contact_id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
                            $oldValuesContact = $updateItem->toArray(); unset($oldValuesContact['updated_at']); //KEEP OLD VALUES
                        
                            $updateContact->fill(
                                [
                                    'email' => $item['email'],
                                    'age' => $item['age'],
                                    'gender' => $item['gender']['value'],
                                    'comment' => $item['comment'],
                                    'category' => _('Voluntario')
                                ]
                            );
                            $updateContact->save();
                            $newValuesContact = $updateContact->toArray();
                            // SAVE PIVOT
                            $dataPivot['task_id'] = $id; 
                            $dataPivot['contact_id'] = $contact_id;
                            $dataPivot['hours'] = $item['hours'];
                            $dataPivot['category'] = _('Voluntario');
                            if((boolean) getCfg('rewards','company') ){
                                $dataPivot['points'] = $item['points']; 
                            }
                            $dataPivot['done'] = $item['done']; 
                            $dataPivot['company_id'] = idCompany();
                            
                            $newPivot = new Pivot_task_contact;
                            $newPivot->fill( $dataPivot );
                            $newPivot->save();                          
                        }else{
                            // IF NOT EXIST CREATED
                            $newContact = new Contact;
                            $dataContact = [
                                'name' => $item['contact']['label'],
                                'email' => $item['email'],
                                'age' => $item['age'],
                                'gender' => $item['gender']['value'],
                                'comment' => $item['comment'],
                                'hours' => $item['hours'],
                                'category' => _('Voluntario'),
                                'company_id' => idCompany()
                            ];
                            $newContact->fill( $dataContact );
                            $newContact->save();
                            $contact_id = $newContact->id;
                            // SAVE PIVOT
                            $dataPivot['task_id'] = $id; 
                            $dataPivot['contact_id'] = $contact_id;
                            $dataPivot['hours'] = $item['hours'];
                            $dataPivot['category'] = _('Voluntario');
                            $dataPivot['done'] = $item['done']; 
                            if((boolean) getCfg('rewards','company') ){
                                $dataPivot['points'] = $item['points']; 
                            }
                            $dataPivot['company_id'] = idCompany();
                            
                            $newPivot = new Pivot_task_contact;
                            $newPivot->fill( $dataPivot );
                            $newPivot->save();
                        }
                    }
                }
            }


            return ['success' => true ,'msg' => _("Elementos modificados correctamente !")];


        }catch(Exception $e){
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

     /*** UDPATE FORM INDICATORS***/
    public function updateIndicators(Request $request)
    {
        try {
        
            $indicators = $request->indicators;    
            $form = $request->form;    
            $id = $request->form['id'];
            // GET OLD DATA
            $updateItem = Task::where(['id'=>$id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $newData = $updateItem->toArray();
            
            $deleteItem = Pivot_task_contact::where(['task_id'=>$id ,'category'=>_('Indicador')] )->delete();
            
            if(is_array($indicators) && !empty($indicators)){
                foreach( $indicators as $key => $item ){
                
                if( array_key_exists( 'value', $item['category'] )){
                    $category_id = $item['category']['value'];                        
                    
                    // SAVE PIVOT
                    $dataPivot['task_id'] = $id; 
                    $dataPivot['category_id'] = $category_id;
                    $dataPivot['meta'] = $item['meta'];
                    $dataPivot['category'] = _('Indicador'); 
                    $dataPivot['target'] = $item['target']; 
                    $dataPivot['company_id'] = idCompany();
                    
                    $newPivot = new Pivot_task_contact;
                    $newPivot->fill( $dataPivot );
                    $newPivot->save();                          
                    }
                }
            }


            return ['success' => true ,'msg' => _("Elementos modificados correctamente !")];


        }catch(Exception $e){
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    
     /*** UDPATE FORM DOCUMENTS ***/
    public function updateDocuments(Request $request)
    {
        try {
            $id = $request->id;

            // GET OLD DATA
            $updateItem = Task::where(['id'=>$id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $newData = $updateItem->toArray();
            
            //FILES
            if($request->hasfile('files')){
                $filename = storageFile( $request , 'files' ,  false , '/uploads/task/files/' , true );
            }else{
                $filename = false;
            }
            
            if($request->hasfile('proof')){
                $proof = storageFile( $request , 'proof' , false, '/uploads/task/proof/' ,  true );
            }else{
                $proof = false;
            }
          
            if( is_array($filename) ){
                if( $request->filesOld != ''){
                    $filesOld = json_decode( $request->filesOld ,true);
                    if (is_array($filesOld)) { 
                        $filename = array_merge($filename,$filesOld);
                    }
                }
                $newData['files'] = json_encode( $filename );
                $renewData['files'] = json_encode( $filename );

            }else{
                $newData['files'] = $request->filesOld;
                $renewData['files'] = $request->filesOld;
            }

            if( is_array($proof) ){
                if( $request->proofOld != ''){
                    $proofOld = json_decode( $request->proofOld ,true);
                    if (is_array($proofOld)) { 
                        $proof = array_merge($proof,$proofOld);
                    }
                }
                $newData['proof'] = json_encode( $proof );
            }else{
                $newData['proof'] = $request->proofOld;
            }
            $oldValues = $updateItem->toArray();

            if (is_array(json_decode($oldValues['files'],true))) {
                foreach (json_decode($oldValues['files'],true) AS $file) {
                    if (!in_array($file,json_decode($newData['files'],true))) {
                        if ($path = publicUploadPath('/uploads/task/files/'.safeUploadName($file['file'], false))) {
                            unlink($path);
                        }
                    }
                }
            }
            
            if (is_array(json_decode($oldValues['proof'],true))) {
                foreach (json_decode($oldValues['proof'],true) AS $file) {
                    if (!in_array($file,json_decode($newData['proof'],true))) {
                        if ($path = publicUploadPath('/uploads/task/proof/'.safeUploadName($file['file'], false))) {
                            unlink($path);
                        }
                    }
                }
            }

            unset($oldValues['updated_at']); //KEEP OLD VALUES
            $updateItem->fill( $newData );
            $updateItem->save();
            $newValues = $updateItem->toArray(); //GET NEW VALUES

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'MODIFICATION','name'=>'Task modified : '.$updateItem->name,'tablename'=>'tasks','item'=>$updateItem->id,'updates'=>json_encode(compare2arrays($oldValues,$newValues)),'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            return ['success' => true ,'msg' => _("Elemento modificados correctamente !"), 'task' => $newData];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** DELETE STEP***/
    public function deleteStep(Request $request)
    {
        try{
            //SET UNACTIVE
            $updateItem = Content::where(['id'=>$request->id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $updateItem->active = 0;
            $updateItem->save();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'ELIMINATION','name'=>'Task Step deleted : '.$updateItem->name,'tablename'=>'contents','item'=>$updateItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            return ['success' => true, 'msg' => _("Elemento suprimido correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** DELETE ***/
    public function delete(Request $request)
    {
        try{
            //SET UNACTIVE
            $updateItem = Task::where(['id'=>$request->id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $updateItem->active = 0;
            $updateItem->save();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'ELIMINATION','name'=>'Task deleted : '.$updateItem->name,'tablename'=>'tasks','item'=>$updateItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            return ['success' => true, 'msg' => _("Elemento suprimido correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** DESTROY ***/
    public function destroy(Request $request)
    {
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
