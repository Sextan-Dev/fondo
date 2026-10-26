<?php

namespace App\Http\Controllers;

use App\Contact;
use App\User;
use App\Action;
use App\Category;
use App\Project;
use App\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Mail\NotificationDonorUpdateProject;
use Mail;
use DataTables;

class ContactController extends Controller {

    /*** INDEX ***/
    public function index() {
        try {
            
            //QUERY
            $data = Contact::where([ 'active' => 1 , 'company_id' => idCompany()])->get();


            // foreach($data as $row){
            //     if(!is_null($row->groups)){
            //         $groups = array_map('trim', explode(',', $row->groups));
            //         $areas_interest = array_map('trim', explode(',', $row->areas_interest));
            //         $brands = array_map('trim', explode(',', $row->brands));

            //         $update = Contact::find($row->id);
            //         $update->groups = json_encode($groups);                    
            //         $update->areas_interest = json_encode($areas_interest);                    
            //         $update->brands = json_encode($brands);                    
            //         $update->save();
            //     }
            // }

            // $data = Contact::where([ 'active' => 1 , 'company_id' => idCompany()])->get();

            return ['success' => true , 'data' => $data];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** SELECT ***/
    public function select2() {
        try {
            
            //QUERY
            $data = Contact::select(DB::raw( 'id as value, name AS label'))->where([ 'active' => 1 , 'company_id' => idCompany()])->get();

          

            return ['success' => true , 'data' => $data];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** DATA ***/
    public function data() {
        try {
            $contactsId = null;
            //QUERIES
            $proyectAssing = idProyects();
            if ($proyectAssing != 'all') {
                $projects = $this->_getProjects();
                $contactsId = getContactsFilter($projects);
            }
            if (!empty($contactsId)) {
                $categories = Contact::select(
                    DB::raw(
                        'count(id) as count,
                        category as value,
                        category as name'
                    )
                )
                ->where([ 'active' => 1 , 'company_id' => idCompany()])
                ->whereRaw('category <> "Donador"')
                ->groupBy('category')
                ->orderBy('count', 'desc')
                ->get()
                ->toArray();
                $donor = Contact::select(
                    DB::raw(
                        'count(id) as count,
                        category as value,
                        category as name'
                    )
                )
                ->where([ 'active' => 1 , 'company_id' => idCompany(), 'category' => "Donador"])
                ->whereRaw('id IN('.$contactsId.')')
                ->orderBy('count', 'desc')
                ->get()
                ->toArray();
                
                if (isset($donor[0]) && count($donor[0]) > 0) {
                    array_unshift($categories,$donor[0]);
                }
                $total = 0;
                foreach ($categories as $value) {
                    $total = $total + $value["count"];
                }
                $categories_total = ["count" => $total, "value" => '', "name" =>_("TODO")];
                array_unshift($categories,$categories_total);
            } else {
                $categories = Contact::select(
                    DB::raw(
                        'count(id) as count,
                        category as value,
                        category as name'
                    )
                )
                ->where([ 'active' => 1 , 'company_id' => idCompany()])
                ->groupBy('category')
                ->orderBy('count', 'desc')
                ->get()
                ->toArray();
                $categories_total = Contact::select(DB::raw('count(id) as count, "" as value, "'._("TODO").'" as name'))->where([ 'active' => 1 , 'company_id' => idCompany()])->get()->toArray()[0];
                array_unshift($categories,$categories_total);
            }

            $states = Category::select(DB::raw( 'name as value, name AS label'))
            ->where([ 'active' => 1, 'type'=> 'task', 'category' => 'state', 'company_id' => idCompany() ] )
            ->orderBy('name')->get();
            $groups = Contact::select('groups as value', 'groups as name')
            ->where([ 
                ['groups', '!=', null],
                'active' => 1,
                'company_id' => idCompany()
            ])           
            ->groupBy('groups')->orderBy('groups', 'desc')
            ->get()->toArray();
            
            $brands = [];            
            $brandsList = Contact::select('brands')
            ->where(['active' => 1, 'company_id' => idCompany()])
            ->whereRaw("brands is not null")
            ->get();
            
          
            $brands = array_unique($brands);
            
            $areas = [];
            $areasList = Contact::selectRaw("CONCAT('[',GROUP_CONCAT( replace(replace(areas_interest, ']', ''), '[', '')), ']') as areas")
            ->where(['active' => 1, 'company_id' => idCompany()])
            ->whereRaw("areas_interest <> '[]' AND areas_interest is not null")
            ->first();

            if($areasList->brands){                
                $areas = array_values(
                array_map("unserialize", array_unique( 
                    array_map("serialize", json_decode($areasList->areas, true) )
                    )
                ));  
            }

            $projects = Project::selectRaw("
                projects.name as project_name, 
                projects.id as project_id,
                t.id as task_id,
                t.name as task_name
            ")
            ->leftJoin('tasks as t', function($join) {
                $join->on('t.project_id', '=', 'projects.id');
                $join->whereRaw('t.active = 1');
            })            
            ->where([
                'projects.active' => 1,                
            ])->get()->groupBy('project_id')->map(function ($items){
                return [
                    'id' => $items[0]->project_id,
                    'name' => $items[0]->project_name,
                    'tasks' => $items->toArray()
                ];
            })->toArray();
            
            $success = true;

            return compact(
                'success',
                'categories',
                'brands',
                'areas',
                'groups',
                'projects',
                'states'
            );

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** SHOW ***/
    public function show(Request $request) {
        try {
            
            //QUERY
            $data = Contact::where(['contacts.id'=>$request->id, 'contacts.active' => 1 , 'contacts.company_id' => idCompany()])->first();                  
            
            
            $data->contact_details = json_decode($data->contact_details, true);

            return [
                'success' => true,
                'data' => $data
            ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function storeTransaction(Request $request){
        try{
            $transaction = $request->transaction;
            $contact_id = $request->contact_id;
            $income = new Operation;
            $outcome = new Operation;

            $outcome->fill([
                'date' => date('Y-m-d'),
                'type' => 'outcome',
                'contact_id' => $contact_id,
                'account_id' => $transaction['account_id'],
                'mode_id' => 157,
                'amount' => $transaction['amount'],
                'vat' => 0,
                'company_id' => idCompany(),
                'active' => 1
            ]);


            $income->fill([
                'date' => date('Y-m-d'),
                'type' => 'income',
                'contact_id' => $contact_id,
                'account_id' => $transaction['account_id'],
                'mode_id' => 157,
                'amount' => $transaction['amount'],
                'vat' => 0,
                'task_id' => $transaction['task_id'],
                'monitoring' => $transaction['monitoring'],
                'amount_execute' => $transaction['amount_execute'],
                'company_id' => idCompany(),
                'active' => 1
            ]);

            
            $outcome->save();
            $income->save();            

            return [
                'success' => true,
                'msg' => "Transferencia completada"
            ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** SELECT ***/
    public function select(Request $request) {
        try {
            //QUERY
            $proyectAssing = idProyects();
            $contactsId = null;
            if ($proyectAssing != 'all') {
                $projects = $this->_getProjects();
                $contactsId = getContactsFilter($projects);
            }
            if (!empty($contactsId)) {
                if (!empty($request->category) && $request->category == "Donador") {
                    $data = DB::table('contacts AS c')
                        ->where(['c.active' => 1 , 'c.company_id' => idCompany()])
                        ->whereRaw(' c.category LIKE "%Donador%" AND id IN('.$contactsId.')')
                        ->select(DB::raw('c.*'));
                } 
                if (!empty($request->category) && $request->category != "Donador") {
                    $data = DB::table('contacts AS c')
                        ->where(['c.active' => 1 , 'c.company_id' => idCompany()])
                        ->where('c.category', 'like', '%'.$request->category.'%');
                } 
                if(empty($request->category) ){
                    $donor = DB::table('contacts AS c')
                        ->where(['c.active' => 1 , 'c.company_id' => idCompany()])
                        ->whereRaw(' c.category LIKE "%Donador%" AND id IN('.$contactsId.')')
                        ->select(DB::raw('c.*'));

                    $dataAll = DB::table('contacts AS c')
                    ->where(['c.active' => 1 , 'c.company_id' => idCompany()])
                    ->whereRaw('c.category <> "Donador"');
                    $dataAll->select(DB::raw('c.*'));
                    $d = $donor->get();
                    $a = $dataAll->get();
                    $data = $a->merge($d);
                }
            } else {
                $data = DB::table('contacts AS c')
                        ->where(['c.active' => 1 , 'c.company_id' => idCompany()])
                        ->where('c.category', 'like', '%'.$request->category.'%')
                        ->select(DB::raw('c.*'));
            }
            return Datatables::of($data)->make(true);

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
    /*** STORE ***/
    public function store(Request $request) {
        try {
            $newData = $request->all(); 
            $proyectAssing = idProyects();
            if ($proyectAssing != 'all' && $newData['category'] == 'Donador' ){   
                return ['success' => false , 'msg' =>  _("Oups... No cuentas con los permisos necesarios para realizar esta accion. Por favor comunicate con el administrador del sistema.") ];
            }           
            
            if( $newData['category'] == 'Donador' ){                
                $newData['areas_interest'] = json_encode($newData['areas_interest'] ?? []);
                $newData['groups'] = json_encode($newData['groups'] ?? []);
            }else{
                $newData['category'] = isset($newData['category']['label']) ? $newData['category']['label'] :$newData['category'];
            }
            $newData['contact_details'] = isset($newData['contact_details']) ?  json_encode($newData['contact_details']) : null;
            $newData['company_id'] = idCompany();

            //INSERT
            $newItem = new Contact;
            $newItem->fill( $newData );
            $newItem->save();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'CREATION','name'=>'Contact created : '.$newItem->name,'tablename'=>'contacts','item'=>$newItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
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
            if($request->hasfile('files')){
                $filename = storageFile( $request , 'files' ,  $request->slug , '/uploads/contacts/' , true );
            }else{
                $filename = false;
            }
          
            $data = $request->except(['id','created_at','updated_at','company_id']) ;
            if( $data['category'] == 'Donador' ){                
                $data['areas_interest'] = json_encode($data['areas_interest'] ?? []);
                $data['groups'] = json_encode($data['groups'] ?? []);
            }else{           
                $data['category'] = isset($data['category']['value']) ? $data['category']['value'] :$data['category'];
            }
            
            $data['contact_details'] = isset($data['contact_details']) ?  json_encode($data['contact_details']) : null;
            //SET DATA
            $id = $request->id;
            
            //UDPATE
            $updateItem = Contact::where(['id'=>$id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $oldValues = $updateItem->toArray(); unset($oldValues['updated_at']); //KEEP OLD VALUES

            
            if( is_array($filename) ){
                if( $request->filesOld != '' && $request->filesOld !='null'){
                    $filesOld = json_decode( $request->filesOld ,true);
                    $filename = array_merge($filename,$filesOld);
                }
                $data['files'] = json_encode( $filename );
            }else{
                $data['files'] = $request->filesOld;;
            }
            
            //$filesRenew = $newData['files'];
            //UDPATE
        
            $oldValues = $updateItem->toArray(); unset($oldValues['updated_at']);
            if(is_array(json_decode($oldValues['files'],true))){
                foreach (json_decode($oldValues['files'],true) AS $file) {
                    if (!in_array($file,json_decode($data['files'],true))) {
                        if ($path = publicUploadPath('/uploads/contacts/'.safeUploadName($file['file'], false))) {
                            unlink($path);
                        }
                    }
                }
            }

            $updateItem->fill( $data  );
            $updateItem->save();
            $newValues = $updateItem->toArray(); //GET NEW VALUES

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'MODIFICATION','name'=>'Contact modified : '.$updateItem->name,'tablename'=>'contacts','item'=>$updateItem->id,'updates'=>json_encode(compare2arrays($oldValues,$newValues)),'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
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
            if (!actionDelete()) {
                return ['success' => false , 'msg' =>  _("Oups... No cuentas con los permisos necesarios para realizar esta accion. Por favor comunicate con el administrador del sistema.") ];
            }
            //SET UNACTIVE
            $updateItem = Contact::where(['id'=>$request->id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
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

    /*** DESTROY ***/
    public function destroy(Request $request) {
        try{

            $deleteItem = Contact::where(['id'=>$request->id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $deleteItem->delete();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'DESTRUCTION','name'=>'Contact destroyed : '.$deleteItem->name,'tablename'=>'contacts','item'=>$deleteItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            return ['success' => true, 'msg' => _("Elemento destruido correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function sendNotification(Request $request)
    {
        try{

            $item = Contact::where('company_id', idCompany())->findOrFail($request->id);
                                
            Mail::to($item->email)->send(new NotificationDonorUpdateProject([ 'name'=> $item->name, 'link' => \App\Support\ReportAccess::token('donor', $item->id) ]) );


            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'NOTIFICATION','name'=>'Contact : '.$item->name,'tablename'=>'contacts','item'=>$item->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            return ['success' => true, 'msg' => _("Notificación enviada!")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }


    public function resumeOperations(Request $request)
    {
        $request->validate(['donor' => 'required|integer|min:1', 'month' => 'required|date_format:Y-m']);
        try{
            $donor = $request->donor;
            $year = date('Y',strtotime($request->month));
            $month = date('m',strtotime($request->month)); 
            
            $donorInfo = Contact::select('id','name')->where([
                'id' => $donor
            ])->first();

            $incomes = Operation::selectRaw("
                DATE_FORMAT(date, '%d/%m/%Y') as date,
                IFNULL(category, '-') as category,
                IFNULL(subcategory, '-') as subcategory,
                amount AS income,
                IF(fee IS NOT NULL AND fee <> 0, (amount * (fee / 100)), 0) as fee,
                IF(fee IS NOT NULL AND fee <> 0, amount - (amount * (fee / 100)), amount) AS incomesNoFee,
                comment
            ")
            ->whereRaw("
                contact_id = ".$donor."
                AND YEAR(date) = ".$year." 
                AND MONTH(date) = ".$month." 
                AND category <> 'Remanentes' 
                AND category <> 'Liberaciones' 
                AND category IS NOT NULL
                AND type = 'income'
                AND project_id = 0 
                AND transfer = 0
                AND active = 1"
            )
            ->orderBy('id', 'ASC')
            ->orderBy('date', 'DESC')
            ->orderBy('category', 'DESC')
            ->orderBy('subcategory', 'DESC')
            ->orderBy('amount', 'ASC')
            ->get();


            $transfersReceipt = Operation::selectRaw("
                DATE_FORMAT(operations.date, '%d/%m/%Y') as date,
                IFNULL(operations.category, '-') as category,
                IFNULL(operations.subcategory, '-') as subcategory,
                IFNULL(t.category, '-') as categoryOrigin,
                IFNULL(t.subcategory, '-') as subcategoryOrigin,
                d.name as donor,
                p.name as project,
                IF(MONTH(operations.date) = ".abs($month).", operations.amount, 0) as amount,
                operations.comment           
            ")
            ->leftJoin('operations as t', 't.transfer_reference', '=', 'operations.transfer_reference')
            ->leftJoin('contacts as d', 'd.id', '=', 'operations.donor_id')
            ->leftJoin('projects as p', 'p.id', '=', 'operations.project_id')
            ->whereRaw("
                operations.contact_id = ".$donor."
                AND YEAR(operations.date) = ".$year." 
                AND MONTH(operations.date) = ".$month." 
                AND operations.transfer = 1
                AND operations.type = 'income'                    
                AND operations.category <> 'Remanentes' 
                AND operations.category <> 'Liberaciones' 
                AND operations.category IS NOT NULL
                AND operations.active = 1"
            )
            ->groupBy('operations.transfer_reference')
            ->orderBy('operations.date', 'DESC')
            ->orderBy('operations.category', 'DESC')
            ->orderBy('operations.id', 'ASC')
            ->orderBy('operations.subcategory', 'DESC')
            ->orderBy('operations.amount', 'ASC')
            ->get();


             // GET TRANSFERS SENDING
            $transfersSendings = Operation::selectRaw("
                DATE_FORMAT(operations.date, '%d/%m/%Y') as date,
                IFNULL(operations.category, '-') as category,
                IFNULL(operations.subcategory, '-') as subcategory,  
                IFNULL(t.category, '-') as categoryDestiny,
                IFNULL(t.subcategory, '-') as subcategoryDestiny,
                d.name as receptor,               
                p.name as project,  
                IF(MONTH(operations.date) = ".abs($month).", operations.amount, 0) as amount,
                operations.comment           
            ")
            ->leftJoin('operations as t', 't.transfer_reference', '=', 'operations.transfer_reference')
            ->leftJoin('contacts as d', 'd.id', '=', 'operations.contact_id')
            ->leftJoin('projects as p', 'p.id', '=', 'operations.project_id')
            ->whereRaw("
                operations.donor_id = ".$donor."
                AND YEAR(operations.date) = ".$year." 
                AND MONTH(operations.date) = ".$month." 
                AND operations.transfer = 1
                AND operations.type = 'outcome'                    
                AND operations.category <> 'Remanentes' 
                AND operations.category <> 'Liberaciones'
                AND operations.category IS NOT NULL
                AND operations.active = 1"
            )      
            ->groupBy('operations.transfer_reference')   
            ->orderBy('operations.id', 'ASC')   
            ->get();


            $outcomes = Operation::selectRaw("
                DATE_FORMAT(operations.date, '%d/%m/%Y') as date,
                IFNULL(operations.category, '-') as category,
                IFNULL(operations.subcategory, '-') as subcategory,  
                d.name as receptor,
                SUM(IF(MONTH(operations.date) = ".abs($month).", operations.amount, 0)) as amount,
                operations.comment   
            ")
            ->leftJoin('contacts as d', 'd.id', '=', 'operations.contact_id')
            ->whereRaw("
                operations.donor_id = ".$donor."
                AND YEAR(operations.date) = ".$year." 
                AND MONTH(operations.date) = ".$month." 
                AND operations.transfer = 0
                AND operations.type = 'outcome'                    
                AND operations.category <> 'Remanentes' 
                AND operations.category <> 'Liberaciones' 
                AND operations.category IS NOT NULL
                AND operations.active = 1"
            )
            ->groupBy('operations.id')
            ->orderBy('operations.id', 'ASC')
            ->get();

            
            // GET REMNANST AND RELEASES
            $remnants = Operation::selectRaw("  
                DATE_FORMAT(operations.date, '%d/%m/%Y') as date,                  
                IFNULL(o.category, '-') as category,
                IFNULL(o.subcategory, '-') as subcategory,
                IF( MONTH(operations.date) = ".abs($month).", operations.amount, 0) AS amount,
                o.comment   
            ")
            ->leftJoin('operations AS o', 'o.transfer_reference', '=' ,'operations.transfer_reference')
            ->whereRaw("
                operations.donor_id = ".$donor."
                AND YEAR(operations.date) = ".$year." 
                AND MONTH(operations.date) = ".$month." 
                AND operations.transfer = 1                                    
                AND (operations.category = 'Remanentes' OR operations.category = 'Liberaciones')
                AND operations.active = 1"
            )
            ->groupBy('operations.id')
            ->get();

             // GET MONITORING

            $monitorings = Operation::selectRaw("                    
                IFNULL(o.category, '-') as category,
                IFNULL(o.subcategory, '-') as subcategory,
                p.name as project,
                IFNULL(o.category, '-') as category,
                IFNULL(o.subcategory, '-') as subcategory,   
                DATE_FORMAT(p.date_from, '%d/%m/%Y') as dateFrom,
                DATE_FORMAT(p.date_to, '%d/%m/%Y') as dateTo,
                SUM(IF( MONTH(operations.date) = ".abs($month)." AND operations.category = 'Costo Operativo' , operations.amount, 0)) AS amount                
            ")
            ->leftJoin('operations AS o', 'o.transfer_reference', '=' ,'operations.transfer_reference')
            ->leftJoin('projects as p', 'p.id', '=', 'o.project_id')
            ->whereRaw("
                operations.donor_id = ".$donor."
                AND YEAR(operations.date) = ".$year." 
                AND MONTH(operations.date) = ".$month." 
                AND operations.transfer = 1                                    
                AND operations.category = 'Costo Operativo'
                AND operations.active = 1"
            )
            ->groupBy('o.category' , 'o.subcategory')            
            ->get();
                       

            return [
                'success' => true,
                'donor' => $donorInfo,
                'incomes' => $incomes,
                'transfersReceipt' => $transfersReceipt,
                'transfersSend' => $transfersSendings,
                'outcomes' => $outcomes,
                'remnants' => $remnants,
                'monitorings' => $monitorings
            ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }

    }

    private function _getProjects()
    {
        $proyectAssing = idProyects();
        $projects = DB::table('projects as p')->where([
            'active' => 1
        ]);
        if ($proyectAssing != 'all') {
            $projects->whereRaw('p.id IN('.$proyectAssing.')');
        }
        return $projects->get();
    }

    public function listCategory()
    {
        $listCategory = [
            'Proveedor',
            'Cliente',
            'Donador',
            'Aliado'
        ];
        $proyectAssing = idProyects();
        if ($proyectAssing != 'all') {
            $listCategory = [
                'Proveedor',
                'Cliente',
                'Aliado'
            ]; 
        }
        return [ 'success' => true, 'listCategory' => $listCategory];
    }
}
