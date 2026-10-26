<?php

namespace App\Http\Controllers;

use App\Project;
use App\Operation;
use App\Task;
use App\Pivot_task_contact;
use App\Category;
use App\User;
use App\Contact;
use App\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DataTables;

class ProjectController extends Controller {

    public function dataForm(){
        try{
           
            // PROGRAMS
            $programs = Category::select('id', 'name', 'comment as description', 'others', 'pilar')
            ->where([
                'category' => 'project',
                'type' => 'program'
            ])->get()->toArray();
            // PILARS
            $pilars = Category::select('id', 'name')
            ->where([
                'category' => 'project', 
                'type' => 'pilar',
                'active' => 1
            ])->get()->toArray();
            // STATUS AVAILABLES
            $status = Category::select('id as value', 'name as label')
            ->where([
                'category' => 'project', 
                'type' => 'status'
            ])->get()->toArray();
            // GET SUSTAINABLE DEVELOPMENT GOALS
            $goals = Category::selectRaw( 'id as value , slug, name as label' )
            ->where([
                'type'=>'sgd',
                'category'=>'project', 
                'active' => 1, 
                'company_id' => idCompany()
            ])->get()->toArray();
            // ALL CONTACTS DONOR, BENEFECIARY, ETC            
            $donors = $this->_getDonors();
            $allies = $this->_getAllies();
            // USERS LIST
            $users = User::select( 'name as label', 'id as value' )
            ->where([
                'active' => 1,
                'company_id' => idCompany(),
                ['user', '!=', 'support']
            ])->get()->toArray();
            // Monitoring
            $monitoring = Category::selectRaw('name AS label , id AS value')
            ->where([
                'category'=> 'account',
                'type' =>'monitoring',
                'active' => 1,
                'company_id' => idCompany()
            ])
            ->get();
            // States
            $zone_impact_state = Category::select(DB::raw( 'name as value, name AS label'))
            ->where([ 'active' => 1, 'type'=> 'task', 'category' => 'state', 'company_id' => idCompany() ] )
            ->orderBy('name')->get();

            // Municipality
            $municipality = Project::select('zone_impact_municipality')
            ->where([ 'active' => 1 , 'company_id' => idCompany()] )
            ->whereRaw(' zone_impact_municipality <> "" AND zone_impact_municipality is not null ')
            ->groupBy('zone_impact_municipality')->get();
            
            $zone_impact_municipality = [];
            foreach($municipality as $key => $item){
                if(!is_null($item) ){
                    $items = explode(',', $item->zone_impact_municipality);
                    $zone_impact_municipality = array_unique( array_merge($zone_impact_municipality, $items) );
                }
            }

            // Locality
            $locality = Project::select( 'zone_impact_locality')
            ->where([ 'active' => 1 , 'company_id' => idCompany()] )
            ->whereRaw(' zone_impact_locality <> "" AND zone_impact_locality is not null ')
            ->groupBy('zone_impact_locality')->get();

            $zone_impact_locality = [];
            foreach($locality as $key => $item){
                if(!is_null($item) ){
                    $items = explode('|', $item->zone_impact_locality);
                    $zone_impact_locality = array_unique( array_merge($zone_impact_locality, $items) );
                }
            }

            $success = true;
            return compact(
                'success',
                'pilars',
                'programs',
                'donors',                
                'users', 
                'goals', 
                'status',                 
                'zone_impact_state',
                'zone_impact_municipality',
                'zone_impact_locality',
                'allies',
                'monitoring'
            );
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
 
    /*** DATA ***/
    public function data()
    {
        try{
            $programs = DB::table('categories AS c')
                        ->leftJoin('projects AS p', function($join){ $join->on( 'p.program_id', '=', 'c.id'); })
                        ->where(['c.type'=>'program','c.category'=>'project', 'c.active' => 1 , 'c.company_id' => idCompany()])
                        ->select(DB::raw('COUNT(p.id) AS count, c.id AS value, c.id AS id, c.comment AS description, c.name AS label, c.name AS name'))->groupBy('c.id')->orderBy('count', 'desc');
            $types = Project::select(DB::raw( 'count(id) as count,type as value, type AS label') )->where([ 'active' => 1 , 'company_id' => idCompany()] )->groupBy('type')->orderBy('count', 'desc');
            $categories = Project::select(DB::raw( 'count(id) as count,category as value, category AS label'))->where([ 'active' => 1 , 'company_id' => idCompany()] )->groupBy('category')->orderBy('count', 'desc');
            $total = Project::select(DB::raw('count(id) as count, "" as value, "" as id, "'._("TODO").'" as label, "'._("Todos los Programas").'" as name, "'._("Datos contabilizados para todos los programas activos.").'" AS description'))->where([ 'active' => 1 , 'company_id' => idCompany()]);
                        

            if( authUser(['group', 'slug']) == 'project'){
                $categories =$categories->whereRaw(' user_id = "'.authUser('id').'"' )->get()->toArray();
                $types =$types->whereRaw(' user_id = "'.authUser('id').'"' )->get()->toArray();
                $programs =$programs->whereRaw(' p.user_id = "'.authUser('id').'"' )->get()->toArray();
                $total =$total->whereRaw(' user_id = "'.authUser('id').'"' )->get()->toArray()[0];
            }else{
                $categories =$categories->get()->toArray();
                $types =$types->get()->toArray();
                $programs =$programs->get()->toArray();
                $total = $total->get()->toArray()[0];
            }
            
            $pilars = Category::select('id as value', 'name as label')->where(['category' => 'project' , 'type' => 'pilar', 'active' => 1])->get()->toArray();
            $status = Category::select('id as value', 'name as label')->where(['category' => 'project' , 'type' => 'status'])->get()->toArray();
            array_unshift($categories,$total);
            array_unshift($programs,$total);
            array_unshift($types,$total);

            // GET SUSTAINABLE DEVELOPMENT GOALS
            $goals = Category::where( ['type'=>'sgd','category'=>'project' , 'active' => 1 , 'company_id' => idCompany()] )->select(DB::raw( 'id as value , slug, name as label' ))->get()->toArray();

            $contacts = Contact::select( 'name as label', 'id as value' )->where(['active' => 1, 'company_id' => idCompany()])->get()->toArray();
            $donors = Contact::select( 'name as label', 'id as value' )
            ->where(['active' => 1, 'company_id' => idCompany(), 'category'=> 'Donador'])
            ->get()->toArray();
            $users = User::select( 'name as label', 'id as value' )->where([ 'active' => 1 , 'company_id' => idCompany(),['user', '!=', 'support']]   )->get()->toArray();
            $success = true;
            return compact('success','programs','donors', 'types','categories', 'contacts', 'users', 'goals', 'status', 'pilars');
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function listing(Request $request)
    {
        try{
            $proyectAssing = idProyects();
            // Only the columns the project and program lists read: projects.* sent ~2 MB of long text.
            $data = Project::selectRaw('
                    projects.id, projects.name, projects.pilar, projects.program, projects.project_umbrella,
                    projects.status, projects.amount_invertion, projects.date_from, projects.date_to,
                    projects.beneficiary_direct, projects.beneficiary_indirect,
                    c.name as donor,
                    u.name as respName
                ')
                ->leftJoin('users as u', 'u.id', '=', 'projects.user_id')  
                ->leftJoin('contacts AS c', 'c.id', '=', 'projects.donors');
            if ($proyectAssing == 'all') {                               
                $data->where([
                    'projects.active' => 1,
                    'projects.company_id' => idCompany()
                ]);
            } else {
                $data->whereRaw('projects.id IN('.$proyectAssing.')');
            }
            $data->groupBy('projects.id');
            
            if($request->has('program') && $request->program != 'Todo' && $request->program != ''){
                $data = $data->where([
                    'projects.program' => $request->program
                ]);
            }

            $projects = [];
            $list = $data->get();
            $ids = $list->pluck('id')->all();

            // One grouped query per total instead of three queries per project (~2,900 for an admin).
            $monitoring = Operation::selectRaw("operations.project_id, SUM(IF(t.category='Costo Operativo',operations.amount,0)) as total")
                ->leftJoin('operations as t', 't.transfer_reference', '=', 'operations.transfer_reference')
                ->whereIn('operations.project_id', $ids)
                ->whereRaw('operations.type="outcome" AND operations.transfer = 1')
                ->groupBy('operations.project_id')
                ->pluck('total', 'project_id');

            $incomes = Operation::selectRaw('operations.project_id, SUM(operations.amount) as total')
                ->leftJoin('contacts as c', 'c.id', '=', 'operations.contact_id')
                ->whereIn('operations.project_id', $ids)
                ->whereRaw('operations.type="income" AND c.name <> "COSTO OPERATIVO"')
                ->groupBy('operations.project_id')
                ->pluck('total', 'project_id');

            $outcomes = Operation::selectRaw('operations.project_id, SUM(operations.amount) as total')
                ->leftJoin('contacts as c', 'c.id', '=', 'operations.contact_id')
                ->whereIn('operations.project_id', $ids)
                ->whereRaw('operations.type="outcome"')
                ->groupBy('operations.project_id')
                ->pluck('total', 'project_id');

            foreach($list as $key => $item){
                $incomesTotal = $incomes[$item['id']] ?? 0;
                $outcomesTotal = $outcomes[$item['id']] ?? 0;
                // The former per-project SUM had no GROUP BY, so it returned NULL rather than 0.
                $monitoringTotal = $monitoring[$item['id']] ?? null;

                $item['incomes_amount'] = $incomesTotal;
                $item['outcomes_amount'] = $outcomesTotal;
                $item['monitoring_transfer'] = $monitoringTotal;
                $item['available'] = $incomesTotal - $outcomesTotal;

                $projects[] = $item;
            }
            return Datatables::of(collect($projects))->make(true);

        } catch(\Exception $e) {
            sendNoticationError($e);
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
    /*** SELECT ***/
    public function select(Request $request)
    {
        try {
            //QUERY
            $data = DB::table('projects AS p')          
            ->leftJoin('users AS u', 'p.user_id', '=', 'u.id')            
            ->leftJoin('operations AS o', 'o.project_id', '=', 'p.id')            
            ->where(['p.active' => 1 , 'p.company_id' => idCompany()])
            ->select(DB::raw('
                        p.*,                    
                        u.name AS respName,
                        u.img AS respImg,                        
                        SUM( 
                            IF(o.type = "income" AND p.monitoring <> 0 AND p.monitoring IS NOT NULL, 
                            o.amount * (p.monitoring / 100),
                            0)
                        ) as monitoring_amount                                     
            '))
            ->groupBy('p.id');
            
            

            if( $request->program !='' ){
                $data->where('prg.id', $request->program);
            }
            if( $request->category !='' ){
                $data->where('p.category', $request->category);
            }
            if( $request->project !='' ){
                $data->where('p.id', $request->project);
            }
      
            if( authUser(['group', 'slug']) == 'project'){
                $data->whereRaw(' p.user_id = "'.authUser('id').'"' );
            }
            return Datatables::of($data)->make(true);

        } catch(\Exception $e) {
            sendNoticationError($e);
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** STORE ***/
    public function store(Request $request)
    {
        try {
            //SET DATA
            $newData = $request->all();
            $newData['volunteering'] = json_encode($newData['volunteering']); 
            $newData['beneficiary_details'] = json_encode($newData['beneficiary_details']); 
            // $newData['donors'] = isset($newData['donors']) ? implode(',',$newData['donors']) : null; 
            $newData['allies'] = isset($newData['allies']) ? implode(',',$newData['allies']) : null;             
            $newData['ods'] = isset($newData['ods']) ? json_encode($newData['ods'], true) : null; 
            $newData['social_grade'] = isset($newData['social_grade']) ? implode(',',$newData['social_grade']) : null; 
            $newData['comunication_type'] = (isset($newData['comunication_type'])) ? implode(',',$newData['comunication_type']):null; 
            $newData['comunication_product'] = (isset($newData['comunication_product'])) ? implode(',',$newData['comunication_product']):null; 
            $newData['target_audiences'] = (isset($newData['target_audiences'])) ? implode(',',$newData['target_audiences']):null; 
            $newData['zone_impact_state'] = (isset($newData['zone_impact_state'])) ? implode(',',$newData['zone_impact_state']):null; 
            $newData['zone_impact_municipality'] = (isset($newData['zone_impact_municipality'])) ? implode(',',$newData['zone_impact_municipality']):null; 
            $newData['zone_impact_locality'] = isset($newData['zone_impact_locality']) ? implode(',',$newData['zone_impact_locality']) : null; 
            $newData['attachments'] = json_encode($newData['attachments']);
            $newData['company_id'] = idCompany();
            $newData['ministering_value'] = json_encode($newData['ministering_value']);

            //INSERT
            $newItem = new Project;
            $newItem->fill( $newData );
            $newItem->save();
            
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill([
                'type'=>'CREATION',
                'name'=>'Project created : '.$newItem->name,
                'tablename'=>'projects',
                'item'=>$newItem->id,
                'group_id'=>authUser('group_id'),
                'user_id'=>authUser('id'),
                'company_id'=>idCompany()
            ])->save();
                    
            //SUCCESS
            return [ 
                'success' => true,
                'msg' => _("Elemento creado correctamente !"), 
                'id' =>  $newItem->id
            ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            //FAIL
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
    
    /*** SHOW ***/
    public function show(Request $request)
    {
        try {
            //QUERY
            $data = Project::selectRaw('projects.*,            
            IF( projects.monitoring_type="percent",
                SUM( 
                    IF(o.type = "income" AND  projects.monitoring <> 0 AND projects.monitoring IS NOT NULL, 
                        (o.amount * (projects.monitoring / 100)),
                        0
                    )
                ),
                projects.monitoring_fix
            ) as monitoring_amount, 
            SUM( 
                IF(o.type = "income", 
                    (o.amount - IF(o.fee<>0 AND o.fee IS NOT NULL, (o.fee / 100),0)),
                    0
                )
            ) as incomes_amount     
            ')
            ->where(['projects.id'=>$request->id])
            ->leftJoin('operations AS o', 'o.project_id', '=', 'projects.id')            
            ->groupBy('projects.id')
            ->first();

            // $data->donors =  ($data->donors!='') ? array_map('intval', explode(',',$data->donors)) : '';
            $data->allies =  ($data->allies!='') ? array_map('intval', explode(',',$data->allies)) : '';
            $data->ods = json_decode($data->ods, true); 
            $data->target_audiences = ($data->target_audiences) ? explode(',',$data->target_audiences) : []; 
            $data->social_grade = ($data->social_grade) ? explode(',',$data->social_grade) : []; 
            $data->zone_impact_state = ($data->zone_impact_state) ? explode(',',$data->zone_impact_state) : []; 
            $data->zone_impact_municipality = ($data->zone_impact_municipality) ? explode(',',$data->zone_impact_municipality) : []; 
            $data->zone_impact_locality = ($data->zone_impact_locality) ? explode(',',$data->zone_impact_locality) : []; 
            $data->attachments = json_decode($data->attachments, true);
            $data->beneficiary_details = json_decode($data->beneficiary_details, true);
            $data->volunteering = json_decode($data->volunteering, true);
            $data->ministering_value = json_decode($data->ministering_value, true);

            $monitoring = $data->monitoring_amount;
            $donations = $data->incomes_amount;

            return [
                'success' => true ,
                'data' => $data,
                'monitoring' => $monitoring,
                'donations' => $donations
            ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** UDPATE ***/
    public function update(Request $request)
    {
        try {
            //SET DATA
            $id = $request->id;
            $newData = $request->except(['id','created_at','updated_at','company_id']);
            // $newData['donors'] = isset($newData['donors']) ? implode(',',$newData['donors']) : null; 
            $newData['beneficiary_details'] = json_encode($newData['beneficiary_details']);
            $newData['volunteering'] = json_encode($newData['volunteering']);  
            $newData['allies'] = isset($newData['allies']) ? implode(',',$newData['allies']) : null;             
            $newData['ods'] = isset($newData['ods']) ? json_encode($newData['ods'], true) : null; 
            $newData['social_grade'] = isset($newData['social_grade']) ? implode(',',$newData['social_grade']) : null; 
            $newData['comunication_type'] = (isset($newData['comunication_type'])) ? implode(',',$newData['comunication_type']):null; 
            $newData['comunication_product'] = (isset($newData['comunication_product'])) ? implode(',',$newData['comunication_product']):null; 
            $newData['target_audiences'] = (isset($newData['target_audiences'])) ? implode(',',$newData['target_audiences']):null; 
            $newData['zone_impact_state'] = (isset($newData['zone_impact_state'])) ? implode(',',$newData['zone_impact_state']):null; 
            $newData['zone_impact_municipality'] = (isset($newData['zone_impact_municipality'])) ? implode(',',$newData['zone_impact_municipality']):null; 
            $newData['zone_impact_locality'] = isset($newData['zone_impact_locality']) ? implode(',',$newData['zone_impact_locality']) : null; 
            $newData['attachments'] = json_encode($newData['attachments']);
            $newData['ministering_value'] = json_encode($newData['ministering_value']);
            DB::beginTransaction();
           
            //UDPATE
            $updateItem = Project::where([
                'id'=>$id
            ])->first();

            $oldValues = $updateItem->toArray(); 

            $updateItem->fill( $newData );
            $updateItem->save();
            $newValues = $updateItem->toArray(); //GET NEW VALUES

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill([
                'type'=>'MODIFICATION',
                'name'=>'Project modified : '.$updateItem->name,
                'tablename'=>'projects',
                'item'=>$updateItem->id,
                'updates'=>json_encode(compare2arrays($oldValues,$newValues)),
                'group_id'=>authUser('group_id'),
                'user_id'=>authUser('id'),
                'company_id'=>idCompany()
            ])->save();
            DB::commit();
            //SUCCESS
            return [ 
                'success' => true, 
                'msg' => _("Elemento modificado correctamente !") , 
                'id' => $id
            ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            DB::rollback();
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** DELETE ***/
    public function delete(Request $request)
    {
        try{
            if (!actionDelete()) {
                return ['success' => false , 'msg' =>  _("Oups... No cuentas con los permisos necesarios para realizar esta accion. Por favor comunicate con el administrador del sistema.") ];
            }
            //SET UNACTIVE
            $updateItem = Project::where(['id'=>$request->id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $updateItem->active = 0;
            $updateItem->save();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'ELIMINATION','name'=>'Project deleted : '.$updateItem->name,'tablename'=>'projects','item'=>$updateItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            return ['success' => true, 'msg' => _("Elemento suprimido correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** DUPLICATE ***/
    public function duplicate(Request $request)
    {
        try{
            DB::beginTransaction();
            //SET UNACTIVE
            $project = Project::where(['id'=>$request->id, 'active' => 1 , 'company_id' => idCompany()])->firstOrFail();
            $newProject = $project->replicate();
            $newProject->name = $newProject->name . '_copy';
            $newProject->save(); 
            
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'DUPLICATE','name'=>'Project duplicate : '.$newProject->name,'tablename'=>'projects','item'=>$newProject->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            
            if($project){
                $tasks = Task::where([
                    'project_id' => $project->id,
                    'active' => 1
                ])->get();

                foreach($tasks as $index => $task){
                    $newTask = $task->replicate();
                    $newTask->project_id = $newProject->id;
                    $newTask->name = $newTask->name . '_copy';
                    $newTask->save();

                    $pivots = Pivot_task_contact::where([
                        'task_id' => $task->id
                    ])->get();
                    
                    foreach($pivots as $key => $pivot){
                        $newPivot = $pivot->replicate();
                        $newPivot->task_id = $newTask->id;
                        $newPivot->save();
                        /**SAVE ACTION**/
                        $action=new Action;
                        $action->fill(['type'=>'DUPLICATE','name'=>'Task Pivot duplicate : '.$newPivot->name,'tablename'=>'pivot_task','item'=>$newPivot->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
                        
                    }

                    /**SAVE ACTION**/
                    $action=new Action;
                    $action->fill(['type'=>'DUPLICATE','name'=>'Task duplicate : '.$newTask->name,'tablename'=>'task','item'=>$newTask->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
                    
                }
            }

            DB::commit();
            return ['success' => true, 'msg' => _("Elemento duplicado correctamente !")];
            
        } catch(\Exception $e) {
            DB::rollback();
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
    
    /*** SHOW PROGRAM ***/
    public function showProgram(Request $request)
    {
        try {

            //QUERY
            $data = DB::table('projects AS p')
                        ->leftJoin('categories AS pgm', 'p.program_id', '=', 'pgm.id')
                        ->join('tasks AS t', 't.project_id', '=', 'p.id')
                        ->whereRaw('t.date_done <> ""')
                        ->where(['p.active' => 1 , 'p.company_id' => idCompany()])
                        ->select(DB::raw('DATE_FORMAT(t.date_done,"%Y-%m") AS month, COUNT(t.id) AS value'))
                        ->groupBy('month')
                        ->orderBy('month','asc');
            if ($request->id>0) {
                $data->where('p.program_id', $request->id);
            }

            return ['success' => true , 'dataX' => $data->pluck('month'), 'dataY' => $data->pluck('value')];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function removeAttachments(Request $request)
    {
        try{
            $project = Project::where([
                'id' =>  $request->id
            ])->first();
            
            if($project){
                $name = safeUploadName($request->file['name'], false);
                $directory = public_path('uploads/projects/attachments/') . $project->id;
                $path = publicUploadPath('uploads/projects/attachments/'.$project->id.'/'.$name);
                if($path){
                    unlink($path);
                }
                DB::beginTransaction();
                $files = \File::files($directory); 

                $attachments = [];
                foreach($files as $file){                   
                    $attachments[] = [
                        'name' => $file->getFileName(),
                        'url' => url('/').'/uploads/projects/attachments/'.$request->id.'/'. $file->getFileName()
                    ];
                }
                

                $project->fill([
                    'attachments' => json_encode($attachments)
                ]);

                $project->save();
                
                DB::commit();

                return [
                    'success' => true,
                    'attachments' => $attachments
                ];

                
            }else{
                return [
                    'success' => false,
                    'msg' =>  _("Oups... Un error ha occurido : Archivo no localizado ")
                ];
            }


        } catch(\Exception $e){
            DB::rollback();
            sendNoticationError($e);
            return [
                'success' => false,
                'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()
            ];
        }
    }

    public function uploadAttachments(Request $request)
    {
        try{
           
            $project = Project::where([
                'id' =>  $request->header('id')
            ])->first();

            if($project && $request->hasfile('file')){
                DB::beginTransaction();
                $fileName = safeUploadName($request->file->getClientOriginalName());
                $directory = public_path('uploads/projects/attachments/') . $project->id;
                $fileName = storeUploadedFile($request->file, $directory, $fileName);

                $files = \File::files($directory); 
                $attachments = [];
                foreach($files as $file){                   
                    $attachments[] = [
                        'name' => $file->getFileName(),
                        'url' => url('/').'/uploads/projects/attachments/'.$project->id.'/'. $file->getFileName()
                    ];
                }

                $project->fill([
                    'attachments' => json_encode($attachments)
                ]);

                $project->save();

                DB::commit();
                return [
                    'name' => $fileName,
                    'url' => url('/').'/uploads/projects/attachments/'.$project->id.'/'. $fileName
                ];
            }
            
        } catch(\Exception $e){
            DB::rollback();
            sendNoticationError($e);
            return [
                'success' => false,
                'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()
            ];
        }
    }


    public function collectMonitoring(Request $request)
    {
        try{
            $filter = [];

            if($request->has('project_id')){
                $filter['id'] = $request->project_id;
            }

            $filter['active'] = 1;

            $projects = Operation::selectRaw("
                p.name as project,
                IFNULL(operations.category, '-') as category,
                IFNULL(operations.subcategory, '-') as subcategory,   
                p.date_from,                
                p.monitoring_fix,
                p.id,
                p.donors
            ")                
            ->leftJoin('projects AS p',function ($join) use ($request) {
                $join->on('p.id', '=' ,'operations.project_id');                             
            })
            ->whereRaw("                                 
                operations.project_id <> 0                    
                AND operations.active = 1
                AND p.monitoring_fix <> 0"              
            )
            ->groupBy('p.id')->get();

          
            $monitoringDonor = Contact::select('id')->where([
                'name' => 'COSTO OPERATIVO',
                'active' => 1
            ])->first();

            foreach($projects as $project){
                
                $donor_id = $project->donors;
                $transfer_reference = date('YmdHis') . $project->id . $donor_id;
                if($donor_id && !is_null($donor_id) && $project->monitoring_fix > 0){
                    
                    // TRANSFER MONITORIG
                    $outcome = [  
                        'date' => $project->date_from,
                        'contact_id' => $monitoringDonor->id,
                        'donor_id' => $donor_id,
                        'category' => $project->category,
                        'subcategory' => $project->subcategory,
                        'project_id' =>  $project->id,
                        'contact_type' => 'Donador',
                        'type' => 'outcome',
                        'transfer' => 1,
                        'amount' => $project->monitoring_fix,
                        'comment' => 'Cobro de Costo Operativo',
                        'transfer_reference' => $transfer_reference,
                        'company_id' => idCompany(),
                        'active' => 1
                    ];
    
                    $transaction = new Operation;
                    $transaction->fill( $outcome );
                    $transaction->save();                    
    
                    /**SAVE ACTION**/
                    $action=new Action;
                    $action->fill([
                        'type'=>'CREATION',
                        'name'=>'Operation created : '.$transaction->id,
                        'tablename'=>'operations',
                        'item'=>$transaction->id,
                        'group_id'=>authUser('group_id'),
                        'user_id'=>authUser('id'),
                        'company_id'=>idCompany()
                    ])->save();
    
                    $income = [  
                        'date' => $project->date_from,
                        'contact_id' => $monitoringDonor->id,
                        'category' => 'Costo Operativo',
                        'subcategory' => 'Costo Operativo',
                        'contact_type' => 'Donador',
                        'donor_id' => $donor_id,
                        'project_id' =>  $project->id,
                        'type' => 'income',
                        'transfer' => 1,
                        'amount' => $project->monitoring_fix,
                        'comment' => 'Cobro de Costo Operativo',
                        'transfer_reference' => $transfer_reference,
                        'company_id' => idCompany(),
                        'active' => 1
                    ];
    
                    $transaction = new Operation;
                    $transaction->fill( $income );
                    $transaction->save();                    
    
                    /**SAVE ACTION**/
                    $action=new Action;
                    $action->fill([
                        'type'=>'CREATION',
                        'name'=>'Operation created : '.$transaction->id,
                        'tablename'=>'operations',
                        'item'=>$transaction->id,
                        'group_id'=>authUser('group_id'),
                        'user_id'=>authUser('id'),
                        'company_id'=>idCompany()
                    ])->save();                    
                }
            }

            return [
                'success' => true,
                'msg' => _("Trasanciones creadas correctamente!")
            ];
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function validateConta()
    {
        try {
            return [ 
                'success' => true,
                'contaData' =>  validConta()
            ];

        } catch(\Exception $e) {
            return [ 
                'success' => true,
                'contaData' =>  0
            ];
        }
    }

    private function _getDonors()
    {
        $donorsAssing = idDonorsAvailabe();
        $donors = Contact::select( 'name as label', 'id as value' )
            ->where([
                'active' => 1, 
                'company_id' => idCompany(), 
                'category'=> 'Donador'
            ]);
        if ($donorsAssing != 'all') {
            $donors->whereRaw('id IN('.$donorsAssing.')');
        }
        return $donors->get()->toArray();
    }

    private function _getAllies()
    {
        $donorsAssing = idDonorsAvailabe();
        $allies = Contact::select( 'name as label', 'id as value' )
        ->where([
            'active' => 1, 
            'company_id' => idCompany()
            // 'category'=> 'Aliado'
        ]);
        if ($donorsAssing != 'all') {
            $allies->whereRaw('id IN('.$donorsAssing.')');
        }
        return $allies->get()->toArray();
    }
}
