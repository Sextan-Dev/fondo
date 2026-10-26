<?php

namespace App\Http\Controllers;

use App\Project;
use App\Task;
use App\Pivot_task_contact;
use App\Category;
use App\User;
use App\Contact;
use App\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DataTables;

class ProgramController extends Controller {

    public function listing()
    {
        try{
            $pilars = Category::select(['id','name', 'slug','comment', 'category', 'type', 'project_id'])
            ->where( ['active' => 1 , 'category' => 'project', 'type' => 'pilar'])
            ->orderBy('name')
            ->get()
            ->toArray();

            $programs = DB::table('categories')
            ->select(['id', 'name', 'pilar', 'comment AS description', 'others'])
            ->where([
                'type' => 'program',
                'category' => 'project',
                'active' => 1 ,
                'company_id' => idCompany()
            ])
            ->orderBy('name')
            ->get()
            ->map(function($program){
                $program->others = json_decode((string) $program->others, true) ?: [];
                return $program;
            });

            // The page filters and totals these in the browser: one request instead of
            // one project/listing call (with its operations totals) per program tab.
            $proyectAssing = idProyects();
            $projects = DB::table('projects AS p')
            ->select([
                'p.id', 'p.name', 'p.program', 'p.status', 'p.date_from', 'p.date_to',
                'p.beneficiary_direct', 'p.beneficiary_indirect',
                'u.name AS respName', 'u.img AS respImg'
            ])
            ->leftJoin('users AS u', 'u.id', '=', 'p.user_id')
            ->where(['p.active' => 1, 'p.company_id' => idCompany()])
            ->orderBy('p.id', 'desc');
            if ($proyectAssing != 'all') {
                $projects->whereIn('p.id', array_filter(array_map('intval', explode(',', (string) $proyectAssing))));
            }
            $projects = $projects->get();

            $tasks = DB::table('tasks')
            ->selectRaw('project_id, SUM(IF(status<>"canceled",1,0)) AS countTask, SUM(IF(status="done",1,0)) AS countTaskDone')
            ->where('active', 1)
            ->whereIn('project_id', $projects->pluck('id'))
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

            foreach ($projects as $project) {
                $project->countTask = (int) ($tasks[$project->id]->countTask ?? 0);
                $project->countTaskDone = (int) ($tasks[$project->id]->countTaskDone ?? 0);
            }

            return [
                'success' => true,
                'programs' => $programs,
                'projects' => $projects,
                'pilars' => $pilars
            ];
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function store(Request $request)
    {
        try {
            //INSERT
            $newItem = new Category;
            $newItem->fill([
                'type' => 'program',
                'category' => 'project',
                'name' => $request->name,
                'pilar' => $request->pilar,
                'comment' => $request->description,
                'others' => json_encode($request->others, true),
                'company_id' => idCompany()
            ]);
            $newItem->save();
            
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill([
                'type'=>'CREATION',
                'name'=>'Program created : '.$newItem->name,
                'tablename'=>'programs',
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
    
    public function update(Request $request)
    {
        try {            
            //SET DATA            
            $id = $request->id;
            DB::beginTransaction();
           
            //UDPATE
            $updateItem = Category::where([
                'id'=> $id
            ])->first();

            $oldValues = $updateItem->toArray(); 

            $updateItem->fill([
                'name' => $request->name,
                'pilar' => $request->pilar,
                'comment' => $request->description,
                'others' => json_encode($request->others, true)
            ]);
            $updateItem->save();
            $newValues = $updateItem->toArray(); //GET NEW VALUES
            

            DB::table('projects')
            ->where('program', $oldValues['name'])
            ->update(['program' =>  $request->name]);

            /**SAVE ACTION**/
            $action=new Action;
            $action->fill([
                'type'=>'MODIFICATION',
                'name'=>'Program modified : '.$updateItem->name,
                'tablename'=>'programs',
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
                'data' => $updateItem->toArray()
            ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            DB::rollback();
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

}