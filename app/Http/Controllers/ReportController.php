<?php

namespace App\Http\Controllers;

use App\Project;
use App\Operation;
use App\Category;
use App\User;
use App\Contact;
use App\Action;
use App\Content;
use App\Configuration;
use App\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller {

    // Fondo de Remanentes y Liberaciones, under both of its contacts: Saldo Donantes doesn't list it
    const REMNANT_FUNDS = [363, 537];

    /*** DATA CONTROLLER ***/
    public function data() {

        try {
            $proyectAssing = idProyects();
            if ($proyectAssing == 'all') {
                $projects = DB::table('projects as p')->select('id', 'name', 'program', 'status', 'date_from', 'date_to')->where([
                    'active' => 1
                ])->get();
            } else {
                $projects = DB::table('projects as p')->select('id', 'name', 'program', 'status', 'date_from', 'date_to')
                ->whereRaw('p.active = 1 AND p.id IN('.$proyectAssing.')')
                ->get();
            }
            $success = true;
            return compact('success', 'projects');

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }


    /*** OPERATIONS REPORT ***/
    public function operations(Request $request) {

        try{

            $operations = DB::table('operations AS o')
            ->selectRaw('
                o.id,
                o.date,
                o.type,
                IFNULL(o.reference, "-") AS reference,
                o.amount,
                o.category,
                o.subcategory,
                o.vat,
                o.fee,
                p.monitoring,
                o.comment,
                o.amount_execute,
                ct.name AS client,
                p.name as project_name,
                ca.name as mode, 
                d.name as donor,
                a.name AS account'
            )
            ->leftJoin('contacts AS ct', 'o.contact_id', '=', 'ct.id')
            ->leftJoin('contacts AS d', 'o.donor_id', '=', 'd.id')
            ->leftJoin('categories AS ca', 'o.mode_id', '=', 'ca.id' )                      
            ->leftJoin('projects AS p', 'o.project_id', '=', 'p.id')
            ->leftJoin('accounts AS a', 'o.account_id', '=', 'a.id')
            ->whereRaw('o.active = 1 AND DATE(o.date) <= date(?) AND (o.type = "income" OR o.type = "outcome")', [$request->month])
            ->orderBy('o.id', 'ASC')
            ->get();

            return [
                'success' => true,
                'operations' => $operations
            ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** PROJECT REPORT ***/
    public function general(Request $request) {

        try{
            $projectId = $request->projectId;
            $taskId = $request->taskId;
            // An activity report is its project's report narrowed to that activity
            if (!$projectId && $taskId) {
                $projectId = Task::where(['id' => $taskId, 'active' => 1])->value('project_id');
            }

            // Users tied to donors only see their donors' projects, as in the project list
            $proyectAssing = idProyects();
            $allowed = $proyectAssing == 'all' || in_array((int) $projectId, array_map('intval', explode(',', (string) $proyectAssing)), true);
            if (!$projectId || !$allowed) {
                return ['success' => false , 'msg' => _("El proyecto no existe o no tienes acceso a él.")];
            }

            return $this->project($projectId, $taskId);

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function project($id, $taskId = null)
    {
        try{
            // Data Project: only the columns the report shows (projects.* also sent the quote and attachments)
            $project = Project::selectRaw('
                projects.id, projects.name, projects.pilar, projects.program, projects.project_umbrella,
                projects.status, projects.progress, projects.description, projects.amount_invertion,
                projects.others_features_invertion, projects.beneficiary_perfil, projects.beneficiary_details,
                projects.beneficiary_direct, projects.beneficiary_indirect, projects.volunteers_required,
                projects.volunteering, projects.volunteering_description, projects.volunteering_hours,
                projects.ods, projects.temporality, projects.ministering_value, projects.date_from, projects.date_to,
                REPLACE(projects.zone_impact_state, ",", ", ") as zone_impact_state,
                REPLACE(projects.zone_impact_municipality, ",", ", ") as zone_impact_municipality,
                REPLACE(projects.zone_impact_locality, ",", ", ") as zone_impact_locality,
                REPLACE(projects.target_audiences, ",", ", ") as target_audiences,
                REPLACE(projects.social_grade, ",", ", ") as social_grade,
                DATE_FORMAT(projects.date_from, "%d/%m/%Y") as dateFrom,
                DATE_FORMAT(projects.date_to, "%d/%m/%Y") as dateTo,
                IF(projects.donors <> "" AND projects.donors IS NOT NULL ,(LENGTH(projects.donors) - LENGTH(REPLACE(projects.donors, ",", "")) + 1), 0) as countDonors,
                IF(projects.allies <> "" AND projects.allies IS NOT NULL ,(LENGTH(projects.allies) - LENGTH(REPLACE(projects.allies, ",", "")) + 1), 0) as countAllies,
                count(t.id) as countTask,
                SUM(IF(t.status="done",1,0)) AS countTaskDone,
                u.name as respName,
                d.name as donorName'
            )
            ->leftJoin('users as u', 'u.id', '=', 'projects.user_id')
            ->leftJoin('contacts as d', 'd.id', '=', 'projects.donors')
            ->leftJoin('tasks as t', function($tasks){
                $tasks->on('t.project_id', '=', 'projects.id')
                         ->where('t.active', 1);
            })
            ->where([
                'projects.id' => $id,
                'projects.active' => 1
            ])
            ->groupBy('projects.id')
            ->first();

            if (!$project) {
                return ['success' => false , 'msg' => _("El proyecto no existe o no tienes acceso a él.")];
            }

            $project->beneficiary_details = json_decode((string) $project->beneficiary_details);
            $project->volunteering = json_decode((string) $project->volunteering);
            $ods = json_decode((string) $project->ods);
            $odsImg = [];
            if(is_array($ods)){
                foreach($ods as $item){
                    if(!in_array($item->slug, $odsImg)){
                        $odsImg[] = $item->slug;
                    }
                }
            }else{
                $ods = [];
            }

            // PROJECT TASKS
            $tasks = Task::selectRaw('
                tasks.id, tasks.name, tasks.status, tasks.description, tasks.description_html,
                DATE_FORMAT(tasks.date_from, "%d/%m/%Y") as dateFrom,
                DATE_FORMAT(tasks.date_to, "%d/%m/%Y") as dateTo,
                u.name as respName
            ')
            ->leftJoin('users as u', 'u.id', '=', 'tasks.user_id')
            ->where([
                'tasks.project_id' => $id,
                'tasks.active' => 1
            ])
            ->when($taskId, function($query) use ($taskId){
                $query->where('tasks.id', $taskId);
            })
            ->orderBy('tasks.date_from')
            ->get();

            // OPERATIONS
            $operationsData = Operation::selectRaw('
                DATE_FORMAT(operations.date, "%d/%m/%Y") as date,
                operations.amount,
                operations.comment,
                operations.fee,
                ca.name as mode,
                operations.category,
                operations.subcategory,
                c.name as client,
                d.name as donor,
                operations.type
            ')
            ->leftJoin('categories AS ca', 'operations.mode_id', '=', 'ca.id' )
            ->leftJoin('contacts as c', 'c.id', '=', 'operations.contact_id')
            ->leftJoin('contacts as d', 'd.id', '=', 'operations.donor_id')
            ->where('operations.project_id', $id)
            ->orderBy('operations.date')
            ->orderBy('operations.id')
            ->get();

            $incomes = 0;
            $outcomes = 0;
            $balance = 0;
            $fee = 0;
            foreach($operationsData as $operation){
                // amount and fee are varchar and some rows hold '', which PHP 8 refuses in arithmetic
                $amount = (float) $operation->amount;
                if($operation->type == 'income' && $operation->client!= 'COSTO OPERATIVO' && $operation->donor != 'FONDO DE REMANENTES Y LIBERACIONES'){
                    $incomes = $incomes + $amount;
                    $balance = $balance + $amount;
                    $fee = $fee + ($amount * ( (float) $operation->fee / 100 ));
                }else if($operation->type == 'outcome'){
                    $outcomes = $outcomes + $amount;
                    $balance = $balance - $amount;
                }
            }

            // The operating cost rows stay out of the list: the amortization section sums them up
            $operations = $operationsData->filter(function($operation){
                return $operation->category != 'Costo Operativo';
            })->values();

            $monitoringTotal = 0;

            $monitoring = Operation::selectRaw("SUM(IF(t.category='Costo Operativo',operations.amount,0)) as total")
            ->leftJoin('operations as t', 't.transfer_reference', '=', 'operations.transfer_reference')
            ->whereRaw('operations.project_id = ? AND operations.type="outcome" AND operations.transfer = 1', [$id])
            ->first();

            if($monitoring){
                $monitoringTotal = $monitoring->toArray()['total'];
            }

            return [
                'success' => true,
                'project' => $project,
                'operations' => $operations,
                'ods' => $ods,
                'odsImg' => $odsImg,
                'incomes' => $incomes,
                'outcomes' => $outcomes,
                'balance' => $balance,
                'fee' => $fee,
                'monitoring' => $monitoringTotal,
                'tasks' => $tasks
            ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** PUBLIC CONTROLLER ***/
    public function public(Request $request)
    {

            //SET FILTERS
            $from = $request->from;
            $to = $request->to;
            //$companyId = Configuration::where(['name' => 'url_'.config('app.env'),'type' => 'config','value' => $request->headers->get('origin')])->first()->company_id;
            //$companyId = Configuration::where(['name' => 'url_'.config('app.env'),'type' => 'config','value' => config('app.url')  ])->first()->company_id;
            $companyId = 1;
            //PROGRAM
            $data = DB::table('categories AS prg')
                        ->join('projects AS p', 'p.program_id', '=', 'prg.id')
                        ->join('tasks AS t', 't.project_id', '=', 'p.id')
                        ->where(['prg.type'=>'program','t.active' => 1 , 't.company_id' => $companyId])
                        ->whereRaw('t.date_done >= ? AND t.date_done <= ?', [$from, $to])
                        ->select(DB::raw(' 
                            prg.id, 
                            prg.comment AS description, 
                            prg.name AS name
                        '))
                        ->groupBy('prg.id')
                        ->orderBy('prg.id','asc');
            $programs = $data->get()->toArray();

            $goals = Category::where( ['type'=>'sgd','category'=>'project' , 'active' => 1 , 'company_id' => idCompany()] )->select(DB::raw( 'id as value , slug, name as label' ))->get()->toArray();
            //PROJECTs
            $data = DB::table('categories AS prg')
                        ->leftJoin('projects AS p', 'p.program_id', '=', 'prg.id')
                        ->leftJoin(DB::raw('(  SELECT 
                                                    tasks.id,
                                                    tasks.name,
                                                    tasks.project_id,
                                                    tasks.date_done,
                                                    tasks.active,
                                                    tasks.company_id,
                                                    status.slug AS status,
                                                    tasks.benef_1, tasks.benef_2, tasks.benef_3,
                                                    SUM(IF(op.type="income",op.amount,0)) AS sumIncome,
                                                    SUM(IF(op.type="outcome",op.amount,0)) AS sumOutcome,
                                                    SUM(IF(op.type="credit",op.amount,0)) AS sumCredit,
                                                    SUM(IF(op.type="debit",op.amount,0)) AS sumDebit
                                                FROM tasks 
                                                    INNER JOIN categories AS status ON status.slug = tasks.status
                                                    LEFT JOIN operations AS op ON op.task_id = tasks.id
                                                GROUP BY tasks.id
                                                ) AS t'), 'p.id', '=', 't.project_id')
                        ->where(['prg.type'=>'program','t.active' => 1 , 't.company_id' => $companyId])
                        ->select(DB::raw(' 
                            p.*,
                            prg.name AS program,
                            SUM(IF(t.status<>"canceled",1,0)) AS countTask,
                            SUM(IF(t.status="done",1,0)) AS countTaskDone,
                            SUM(IF(t.status<>"canceled" AND t.status<>"done",1,0)) AS countTaskPending,
                            SUM(t.sumIncome) AS sumIncome,
                            SUM(t.sumOutcome) AS sumOutcome,
                            SUM(t.sumDebit) AS sumDebit,
                            SUM(t.benef_1+t.benef_2+t.benef_3) AS sumBenef,
                            SUM(t.benef_1) AS sumBenef1,
                            SUM(t.benef_2) AS sumBenef2,
                            SUM(t.benef_3) AS sumBenef3
                        '))
                        ->groupBy('p.id')
                        ->orderBy('p.id','asc');
            $projects = $data->get()->toArray();

            //CHARTs
            $data = DB::table('categories AS prg')
                        ->leftJoin('projects AS p', 'p.program_id', '=', 'prg.id')
                        ->leftJoin('tasks AS t', 't.project_id', '=', 'p.id')
                        ->leftjoin('operations AS o', 'o.task_id', '=', 't.id')
                        ->leftJoin('contacts AS ct', 'o.contact_id', '=', 'ct.id')
                        ->leftJoin('categories AS cat', 'o.category_id', '=', 'cat.id')
                        ->where(['o.active' => 1 , 'o.company_id' => $companyId])
                        ->select(DB::raw(' 
                            IF(prg.name IS NULL,"-",prg.name) AS program,
                            SUM(IF(o.type="income",o.amount,0)) AS sumIncome,
                            SUM(IF(o.type="outcome",o.amount,0)) AS sumOutcome,
                            SUM(IF(o.type="credit",o.amount,0)) AS sumCredit,
                            SUM(IF(o.type="debit",o.amount,0)) AS sumDebit
                        '))
                        ->groupBy('program');
            $chart = $data->get()->toArray();
            $data = DB::table('tasks AS t')
                        ->leftJoin('categories AS c', 'c.id', '=', 't.public')                    
                        ->leftjoin('operations AS o', 'o.task_id', '=', 't.id')
                        ->where(['o.active' => 1 , 'o.company_id' => $companyId])
                        ->select(DB::raw(' 
                            IF(c.name IS NULL,"Sin Definir",c.name) AS public,
                            SUM(IF(o.type="outcome",o.amount,0)) AS sumOutcome
                        '))
                        ->groupBy('public');
            $chartPublic = $data->get()->toArray();

            //RETURN DATA
            $success = true;
            return compact('success', 'programs', 'projects', 'chart','goals','chartPublic');
       
    }

    /*** DASHBOARD CONTROLLER ***/
    public function dashboard(Request $request)
    {
        try {
            //QUERY CONTACTS
            $contacts = DB::table('tasks AS t')
            ->leftJoin('projects AS p', 'p.id', '=', 't.project_id')
            ->where(['t.status' => 'done'])
            ->select(DB::raw('IFNULL(t.benef_1, 0)  AS sumBenef1,
                            IFNULL(t.benef_2, 0)   AS sumBenef2, 
                            IFNULL(t.benef_3, 0)   AS sumBenef3'));

            if($request->filterTime!=''){
                switch ($request->filterTime) {
                    case 'monthCurrent':
                        $contacts = $contacts->whereRaw( 'MONTH(t.date_from) = MONTH(CURRENT_DATE()) AND YEAR(t.date_from) = YEAR(CURRENT_DATE()) ');
                        break;
                    case 'yearCurrent':
                        $contacts = $contacts->whereRaw( ' YEAR(t.date_from) = YEAR(CURRENT_DATE()) ');
                        break;
                    case 'monthPrevious':
                        $contacts = $contacts->whereRaw( 'MONTH(t.date_from) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH ) AND YEAR(t.date_from) = YEAR(CURRENT_DATE()) ');
                        break;
                    case 'yearPrevious':
                        $contacts = $contacts->whereRaw( ' YEAR(t.date_from) = YEAR(CURRENT_DATE() - INTERVAL 1 YEAR ) ');
                        break;
                }
            }
            if( authUser(['group', 'slug']) == 'project'){
                // Parenthesised: a bare OR would bypass the status and period filters above.
                $contacts =  $contacts->whereRaw('(t.user_id = ? OR p.user_id = ?)', [authUser('id'), authUser('id')]);
            }else{
                if($request->filterUser!='' && $request->filterUser!='TODO'){
                    $contacts->where('t.user_id', $request->filterUser);
                }
            }
            $contacts = $contacts->get()->toArray();

            // PROJECTS
            $projects = DB::table('projects AS p')
            ->leftJoin(DB::raw('(  SELECT 
                                        tasks.id,
                                        tasks.name,
                                        tasks.project_id,
                                        status.slug AS status,
                                        tasks.benef_1, tasks.benef_2, tasks.benef_3,
                                        SUM(IF(op.type="income",op.amount,0)) AS sumIncome,
                                        SUM(IF(op.type="outcome",op.amount,0)) AS sumOutcome,
                                        SUM(IF(op.type="credit",op.amount,0)) AS sumCredit,
                                        SUM(IF(op.type="debit",op.amount,0)) AS sumDebit
                                    FROM tasks 
                                        INNER JOIN categories AS status ON status.slug = tasks.status
                                        LEFT JOIN operations AS op ON op.task_id = tasks.id
                                    GROUP BY tasks.id
                                    ) AS t'), 'p.id', '=', 't.project_id')
            ->leftJoin('users AS u', 'p.user_id', '=', 'u.id')
            ->leftJoin('categories AS prg', 'p.program_id', '=', 'prg.id')
            ->where(['p.active' => 1 , 'p.company_id' => idCompany()])
            // Only what the dashboard shows: p.* sent ~2 MB of long text fields for an admin.
            ->select(DB::raw('
                        p.id, p.name, p.img, p.status, p.user_id,
                        prg.name as program,
                        u.name AS respName,
                        u.img AS respImg,
                        COUNT(DISTINCT t.id) AS count,
                        SUM(IF(t.status<>"canceled",1,0)) AS countTask,
                        SUM(IF(t.status="done",1,0)) AS countTaskDone,
                        SUM(IF(t.status<>"canceled" AND t.status<>"done",1,0)) AS countTaskPending,
                        SUM(IF(t.sumIncome is null , 0 , t.sumIncome ))  AS sumIncome,
                        SUM(IF(t.sumOutcome is null , 0 , t.sumOutcome ))  AS sumOutcome,
                        SUM(IF(t.sumCredit is null , 0 , t.sumCredit ))  AS sumCredit,
                        SUM(IF(t.sumDebit is null , 0 , t.sumDebit ))  AS sumDebit,
                        SUM(IF( (t.benef_1+t.benef_2+t.benef_3) is null , 0,t.benef_1+t.benef_2+t.benef_3) ) AS sumBenef,
                        SUM(IF(t.benef_1 is null , 0 , t.benef_1)  ) AS sumBenef1,
                        SUM(IF(t.benef_2 is null , 0 , t.benef_2)  ) AS sumBenef2,
                        SUM(IF(t.benef_3 is null , 0 , t.benef_3)  ) AS sumBenef3
                        '))
            ->groupBy('p.id');
            
            if( authUser(['group', 'slug']) == 'project'){
                $projects =  $projects->whereRaw(' p.user_id = "'.authUser('id').'"  '  )->get()->toArray();
            }else{
                $projects = $projects->get()->toArray();
            }
           
            
            //OPERATIONS
            $operations = DB::table('operations AS o')
                        ->leftJoin('tasks AS t', 't.id', '=', 'o.task_id')
                        ->leftJoin('projects AS p', 'p.id', '=', 't.project_id')
                        ->where(['o.active' => 1 , 'o.company_id' => idCompany(),'t.active' => 1 , 't.company_id' => idCompany()])
                       
                        ->select(DB::raw(' 
                            SUM(IF(o.type="income",o.amount,0)) AS sumIncome,
                            SUM(IF(o.type="outcome",o.amount,0)) AS sumOutcome
                        '))
                        ->groupBy('t.id');

            if($request->filterTime!=''){
                switch ($request->filterTime) {
                    case 'monthCurrent':
                        $operations = $operations->whereRaw( 'MONTH(o.date) = MONTH(CURRENT_DATE()) AND YEAR(o.date) = YEAR(CURRENT_DATE()) ');
                        break;
                    case 'yearCurrent':
                        $operations = $operations->whereRaw( ' YEAR(o.date) = YEAR(CURRENT_DATE()) ');
                        break;
                    case 'monthPrevious':
                        $operations = $operations->whereRaw( 'MONTH(o.date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH ) AND YEAR(o.date) = YEAR(CURRENT_DATE()) ');
                        break;
                    case 'yearPrevious':
                        $operations = $operations->whereRaw( ' YEAR(o.date) = YEAR(CURRENT_DATE() - INTERVAL 1 YEAR ) ');
                        break;
                }
            }

            if( authUser(['group', 'slug']) == 'project'){
                $operations =  $operations->whereRaw('(t.user_id = ? OR p.user_id = ?)', [authUser('id'), authUser('id')]);
            }else{
                if($request->filterUser!='' && $request->filterUser!='TODO'){
                    $operations->where('t.user_id', $request->filterUser);
                }
            }

        

            $operations = $operations->get()->toArray();
            $success = true;
            return compact('success', 'contacts', 'projects', 'operations');
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** DONORS CONTROLLER ***/
    public function donors(Request $request)
    { 

        try{

            //SET FILTERS
            $from = $request->from;
            $to = $request->to;

            //DONORS
            $filter = ['c.category' => 'Donador', 'c.active' => 1 , 'c.company_id' => idCompany()];
            if($request->has('groups') && $request->groups != 'all'){
                $filter['c.groups'] = $request->groups;
            }
            if($request->has('donor') && $request->donor != 'all'){
                $filter['c.id'] = $request->donor;
            }
            if($request->has('deal') && $request->deal != 'all'){
                $filter['c.deal'] = $request->deal;
            }
            $donors = DB::table('contacts AS c')
            ->leftJoin('operations AS o', function($join) use($from, $to) { 
                $join->on( 'o.contact_id', '=', 'c.id'); 
                $join->where( 'o.active', '=', '1'); 
                $join->where( 'o.transfer', '=', 0); 
                $join->where( 'o.date', '>=', $from);
                $join->where( 'o.date', '<=', $to); 
            })
            ->where($filter)
            ->select(DB::raw(' 
                    c.*,
                    SUM( IF(o.type="credit",ifnull(o.amount,0),0) ) AS sumCredit,
                    SUM( IF(o.type="income",ifnull(o.amount,0)  ,0) )AS sumIncome
                '))
            ->groupBy('c.id')
            ->get()->toArray();

            //CATEGORIES
            $filter = ['c.category' => 'Donador', 'c.active' => 1 , 'c.company_id' => idCompany()];
            if($request->has('groups') && $request->groups != 'all'){
                $filter['c.groups'] = $request->groups;
            }
            if($request->has('donor') && $request->donor != 'all'){
                $filter['c.id'] = $request->donor;
            }

            if($request->has('deal') && $request->deal != 'all'){
                $filter['c.deal'] = $request->deal;
            }
            $categories = DB::table('contacts AS c')
                        ->leftJoin('operations AS income', function($join) use($from, $to) { 
                            $join->on( 'income.contact_id', '=', 'c.id'); 
                            $join->where( 'income.active', '=', '1'); 
                            $join->where( 'income.type', '=', 'income'); 
                            $join->where( 'income.date', '>=', $from);
                            $join->where( 'income.date', '<=', $to);                             
                        })
                        ->where($filter)
                        ->select(DB::raw(' 
                            IF(c.type="personal", "'._("Persona Física").'", "'._("Persona Moral").'") AS type,
                            IFNULL(SUM(income.amount),0) AS sumIncome,
                            SUM(  (IFNULL(income.fee,0) /100) * ifnull(income.amount,0)  ) AS sumFee,
                            SUM(  (IFNULL(income.monitoring,0) /100) * ifnull(income.amount,0)  ) AS sumMonitoring
                        '))
                        ->groupBy('c.type')
                        ->get()->toArray();

            //OPERATIONS
            $filter = [
                'o.type' => 'income', 
                'c.category' => 'Donador',
                'c.active' => 1 , 
                'c.company_id' => idCompany(),
                'o.active' => 1 , 
                'o.company_id' => idCompany(),
                'o.transfer' => 0
            ];
            if($request->has('groups') && $request->groups != 'all'){
                $filter['c.groups'] = $request->groups;
            }
            if($request->has('donor') && $request->donor != 'all'){
                $filter['c.id'] = $request->donor;
            }
            if($request->has('deal') && $request->deal != 'all'){
                $filter['c.deal'] = $request->deal;
            }
            $operations = DB::table('contacts AS c')
                                ->join('operations AS o', 'o.contact_id', '=', 'c.id')
                                ->leftJoin('accounts AS a', 'o.account_id', '=', 'a.id')
                                ->leftJoin('categories AS cat', 'o.category_id', '=', 'cat.id')
                                ->leftJoin('tasks AS t', 'o.task_id', '=', 't.id')
                                ->leftJoin('projects AS p', 't.project_id', '=', 'p.id')
                            ->where($filter)
                            ->select(DB::raw(' 
                                        o.*,
                                        c.name AS contact,
                                        c.groups,
                                        t.name AS task,
                                        t.status as task_status,
                                        p.name AS project,
                                        cat.name AS category,
                                        a.name AS account
                                    '))
                            ->groupBy('o.id')
                            ->orderBy('o.date','asc')
                            ->get();
            
            $taskCount = collect($operations)->groupBy('task_id')->count();            
            $taskDone = collect($operations)->where('task_status', 'done')->count();            
            $operationsGroup = collect($operations)->groupBy('groups')->map(function ($item) {
                return $item->groupBy('project')->map(function ($item) {
                    return $item->groupBy('contact')->map(function ($item) {
                        return $item->groupBy('category')->map(function ($item) {
                            return $item->sum('amount');
                        }); 
                    });                        
                });                    
            })->toArray();



            $orders = Content::selectRaw('sum(ifnull(o.amount,0)+ifnull(o.vat,0) ) as totalOutcome')
            ->leftJoin('operations as o', function($leftJoin)
            {
                $leftJoin->on('o.content_id', '=', 'contents.id')
                    ->where('o.type', '=', 'outcome' );
            })
            ->leftJoin('contacts as c', 'c.id', '=', 'contents.contact_id');

            
            if($request->has('groups') && $request->groups != 'all'){
                $orders = $orders->where([
                    'c.groups'=> $request->groups
                ]);
            }
            if($request->has('donor') && $request->donor != 'all'){
                $orders = $orders->where([
                    'c.id'=> $request->donor
                ]);
            }
            if($request->has('deal') && $request->deal != 'all'){
                $orders = $orders->where([
                    'c.deal'=> $request->deal
                ]);                
            }

            
            $orders = $orders->groupBy('contents.id')->get();
           // $orders->sum('amount');
            $totalOutcomes = collect($orders)->sum('totalOutcome');
            //RETURN DATA
            $success = true;
            return compact('success', 'operations','taskDone', 'taskCount','donors', 'categories', 'operationsGroup', 'totalOutcomes');


        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }

    }

    public function donorsData()
    {
        try{
            $donorsGroups = Contact::select('groups')
            ->where([
                ['groups', '<>', 'null'],
                'category' => 'Donador', 
                'active' => 1, 
                'company_id' => idCompany()
            ])->groupBy('groups')->get();
            $donors = Contact::select('id', 'name')
            ->where([
                'category' => 'Donador', 
                'active' => 1, 
                'company_id' => idCompany()
            ])->get();

            return ['success' => true, 'donorsGroups' => $donorsGroups,'optionsDonors' => $donors];
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** DONOR CONTROLLER ***/
    public function donor(Request $request)
    { 
        $donorId = \App\Support\ReportAccess::resolve($request, 'donor');
        if ($donorId === null) {
            return response()->json(['success' => false, 'msg' => 'Enlace no válido. Solicita un nuevo enlace.'], 401);
        }
        try {
            
            //SET DATA
            $from = $request->from;
            $to = $request->to;
            $beneficiary_direct = 0;
            $beneficiary_indirect = 0;
            $ods = [];
            if (is_numeric($donorId)) {

                //DONOR
                $donor = Contact::where([
                    'id' => $donorId
                ])->first();

                //PROJECTs
                $projects = DB::table('projects as p')->selectRaw('
                    p.name,
                    p.program,
                    p.project_umbrella,
                    p.description,
                    p.ods,
                    p.pilar,
                    p.beneficiary_direct,
                    p.beneficiary_indirect,
                    count(t.id) as tasks
                ')
                ->leftJoin('operations as o', 'p.id', '=', 'o.project_id')
                ->leftJoin('tasks as t', 't.project_id', '=', 'p.id')
                ->whereRaw('o.project_id IS NOT NULL AND o.contact_id = ?', [$donorId])
                ->groupBy('p.id')->get(); 
                
                    
                foreach($projects as $project){                    
                    $ods_project = json_decode($project->ods, true);
                    if(is_array($ods_project)){
                        $ods = array_merge($ods, $ods_project);
                    }                        
                }

                //OPERATIONs
                $operationsData = Operation::selectRaw('
                    DATE_FORMAT(operations.date, "%d/%m/%Y") as date,
                    operations.reference,
                    operations.donor_id,
                    operations.contact_id,
                    operations.amount,
                    p.monitoring,
                    operations.fee,
                    ca.name as mode, 
                    operations.category,                    
                    operations.subcategory,                
                    c.name as client,
                    operations.project_id,
                    p.name as project_name,
                    d.name as donor,
                    operations.type                
                ')
                ->leftJoin('projects as p', 'p.id', '=', 'operations.project_id')
                ->leftJoin('categories AS ca', 'operations.mode_id', '=', 'ca.id' )      
                ->leftJoin('contacts as c', 'c.id', '=', 'operations.contact_id')      
                ->leftJoin('contacts as d', 'd.id', '=', 'operations.donor_id')      
                ->whereRaw('(operations.contact_id = ? OR operations.donor_id = ?)', [$donorId, $donorId])
                ->orderBy('operations.id', 'ASC')
                ->get();
                
                
                $incomes = 0;
                $donations = 0;
                $outcomes = 0;
                $balance = 0;
                $balanceAvailable = 0;
                $fee = 0;
                $monitoring = 0;
                foreach($operationsData as $operation){
                    if($operation->type == 'income' ){
                        $donations = $donations + (is_null($operation->donor_id ) ?  $operation->amount : 0);
                        $incomes = $incomes + $operation->amount;                        
                        $fee = $fee +  ( $operation->fee > 0 ? ($operation->amount * ($operation->fee / 100)) : 0);
                        $monitoring = $monitoring +  ( $operation->monitoring > 0 ? ($operation->amount * ($operation->monitoring / 100)) : 0);
                    }else{
                        if($operation->type == 'outcome'){
                            $outcomes = $outcomes + ( $operation->donor_id== $donorId && $operation->contact_id!= $donorId  ?  $operation->amount : 0);                            
                        }
                    }
                }

                $balance = $donations - $outcomes;
                $balanceAvailable = $donations - $outcomes - $fee - $monitoring;
                
                //RETURN DATA
                $success = true;
                $share_token = \App\Support\ReportAccess::token('donor', $donorId);
                return compact(
                    'success',
                    'share_token',
                    'fee',
                    'incomes',
                    'outcomes',
                    'donations',
                    'monitoring',
                    'balanceAvailable',
                    'balance',
                    'donor', 
                    'ods',
                    'projects',
                    'operationsData',
                    'beneficiary_indirect',
                    'beneficiary_direct'
                );

            }
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** ally CONTROLLER ***/
    public function ally(Request $request)
    { 
        $allyId = \App\Support\ReportAccess::resolve($request, 'ally');
        if ($allyId === null) {
            return response()->json(['success' => false, 'msg' => 'Enlace no válido. Solicita un nuevo enlace.'], 401);
        }
        try {
            
            //SET DATA
            $from = $request->from;
            $to = $request->to;
            if (is_numeric($allyId)) {

                //ally
                $ally =  DB::table('contacts AS c')
                            ->where('c.id',$allyId) //LINK TO DONOR
                            ->where(['c.category' => 'Aliado', 'c.active' => 1 , 'c.company_id' => idCompany()])
                                ->select(DB::raw(' 
                                    c.*
                                '))
                            ->groupBy('c.id')
                            ->get()->toArray();

                
                //PROJECTs
                $data = DB::table('categories AS prg')
                            ->leftJoin('projects AS p', 'p.program_id', '=', 'prg.id')
                            ->leftJoin('tasks AS t', 't.project_id', '=', 'p.id')
                            ->leftJoin('users AS u', 'p.user_id', '=', 'u.id')
                            ->leftJoin('contacts AS ct', 'p.contact_id', '=', 'ct.id')
                            ->leftJoin('categories AS pil', 'p.pilar_id', '=', 'pil.id')
                            ->where(['prg.type'=>'program','p.active' => 1 , 'p.company_id' => idCompany()])
                            ->where('t.ally',$allyId) //LINK TO ally
                            ->select(DB::raw(' 
                                p.*,
                                ct.name AS contactName,
                                u.name AS respName,
                                u.img AS respImg,
                                prg.name as program,
                                pil.name AS pilar,
                                COUNT(DISTINCT t.id) AS respCount,
                                SUM(ifnull(t.benef_1,0)+ifnull(t.benef_2,0)+ifnull(t.benef_3,0)) AS sumBenef,
                                SUM(ifnull(t.benef_1,0)) AS sumBenef1,
                                SUM(ifnull(t.benef_2,0)) AS sumBenef2,
                                SUM(ifnull(t.benef_3,0)) AS sumBenef3,
                                SUM(ifnull(t.target_benef_1,0)) AS sumTargetBenef1,
                                SUM(ifnull(t.target_benef_2,0)) AS sumTargetBenef2,
                                SUM(ifnull(t.target_benef_3,0)) AS sumTargetBenef3,
                                DATE(MIN(t.date_from)) AS minFrom,
                                MAX(t.date_to) AS maxTo,
                                SUM(IF(t.status<>"canceled",1,0)) AS countTask,
                                SUM(IF(t.status="done",1,0)) AS countTaskDone,
                                SUM(IF(t.status<>"canceled" AND t.status<>"done",1,0)) AS countTaskPending
                            '))
                            ->groupBy('p.id')
                            ->orderBy('p.id','asc');
                $projects = $data->get()->toArray();

                //TASKs
                $data = DB::table('tasks AS t')
                            ->join('projects AS p', 't.project_id', '=', 'p.id')
                            ->join('categories AS status', function($join){ 
                                $join->on( 't.status', '=', 'status.slug'); 
                                $join->where( 'status.category', '=', 'status'); 
                            })
                            ->leftJoin(DB::raw('(SELECT COUNT(id) AS count, MAX(id) as maxId, task_id FROM contents WHERE type="reminder" GROUP BY task_id) AS r'), 'r.task_id', '=', 't.id')
                            ->leftJoin(DB::raw('(SELECT contents.id, users.name AS user, contents.date, contents.value FROM contents INNER JOIN users ON contents.user_id = users.id WHERE contents.type="reminder" ) AS rLast'), 'r.maxId', '=', 'rLast.id')
                            ->where(['t.active' => 1 , 't.company_id' => idCompany(),'p.active' => 1 , 'p.company_id' => idCompany(), 't.active' => 1 , 't.company_id' => idCompany()])
                            ->where('t.ally',$allyId) //LINK TO ally
                            ->select(DB::raw(' 
                                t.*,
                                status.name AS statusName,
                                status.comment AS statusClass,
                                DATE_FORMAT(rLast.date,"%d/%m/%y" ) AS lastReminderDate, 
                                rLast.value AS lastReminder, 
                                rLast.user AS lastUserReminder
                            '))
                            ->orderBy('t.id','asc');
                $tasks = $data->get()->toArray();

                //PROOFs && FILEs
                $proofs = array();
                foreach($tasks as $key => $task) {
                    $tProofs = (array) json_decode($task->proof,true);
                    foreach($tProofs as $proof) {
                        $proofs[] = array(
                            'file' => $proof['file'],
                            'task' => $task->name
                        );
                    }
                    unset($tasks[$key]->proof);
                }

                //INDICATORS 
                $indicator = DB::table('pivot_tasks_contacts as p')
                                    ->join('categories as c', 'c.id', '=', 'p.category_id')
                                    ->join('tasks AS t', 't.id', '=', 'p.task_id')
                                    ->join('operations AS o', 'o.task_id', '=', 't.id')
                                    ->leftjoin('categories as pr', 't.project_id', '=', 'pr.id')
                                ->select(DB::raw('c.id, c.name, sum(p.meta) as meta, sum(p.target) as target'))
                                ->where('t.ally',$allyId) //LINK TO ALLY
                                ->where(['p.active'=>1, 'p.category'=>_('Indicador'), 'p.company_id'=>idCompany() ]);
                $indicators = $indicator->groupBy('c.id')->get()->toArray();

                //GOALS
                $goals = Category::where( ['type'=>'sgd','category'=>'project' , 'active' => 1 , 'company_id' => idCompany()] )->select(DB::raw( 'id as value , slug, name as label' ))->get()->toArray();

                //RETURN DATA
                $success = true;
                $share_token = \App\Support\ReportAccess::token('ally', $allyId);
                return compact('success', 'share_token', 'operations', 'ally', 'projects', 'tasks', 'indicators', 'goals', 'proofs');

            }
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    // DONORS BALANCE

    /**
     * Monthly balance per donor and source (category + subcategory). Each row carries
     * the month's sums and, suffixed "Prev", the sums up to the end of the previous
     * month; donorsBalanceReport.vue turns them into the balances.
     *
     * Three passes over operations replace the eight queries this used to run. Rows,
     * names and figures are kept exactly as those queries produced them: each source
     * still creates its own row with the spelling of its first operation, so a source
     * written with different capitals in two places still shows up twice.
     */
    public function donorsBalance(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        try {
            $start   = $request->month.'-01';
            $end     = date('Y-m-t', strtotime($start));
            $prevEnd = date('Y-m-d', strtotime($start.' -1 day'));

            $donors = $this->_getDonors($request)->pluck('name', 'id');
            if ($donors->isEmpty()) {
                return ['data' => []];
            }
            $donorIds = implode(',', array_map('intval', $donors->keys()->all()));
            $categories = "category IS NOT NULL
                AND category <> 'Remanentes' AND category <> 'Liberaciones'
                AND category <> 'Costo Operativo' AND category <> 'RESERVA RETENCIÓN CO'";

            // Money the donor receives: its incomes and the transfers made to it
            $income   = "transfer = 0 AND project_id = 0";
            $receipt  = "transfer = 1 AND ((project_id <> 0 AND donor_id <> contact_id) OR project_id = 0)";
            $projects = "transfer = 1 AND project_id <> 0 AND donor_id = contact_id";
            $fee      = "IF(fee IS NOT NULL AND fee <> 0, (amount * (fee / 100)), 0)";
            $received = collect(DB::select("
                SELECT contact_id AS donor_id,
                    MIN(IF({$income} AND date >= '{$start}', id, NULL)) AS incomesFirst,
                    MIN(IF({$receipt}, id, NULL)) AS receiptFirst,
                    MIN(IF({$projects}, id, NULL)) AS projectsFirst,
                    MIN(IF({$income} AND date <= '{$prevEnd}', id, NULL)) AS incomesPrevFirst,
                    SUM(IF({$income} AND date >= '{$start}', amount, 0)) AS sumIncomes,
                    SUM(IF({$income} AND date >= '{$start}', IF(fee IS NOT NULL AND fee <> 0, amount - (amount * (fee / 100)), amount), 0)) AS sumIncomesFee,
                    SUM(IF({$income} AND date >= '{$start}', {$fee}, 0)) AS sumFee,
                    SUM(IF({$income} AND date <= '{$prevEnd}', amount - {$fee}, 0)) AS incomesPrev,
                    SUM(IF({$receipt} AND date >= '{$start}', amount, 0)) AS sumTransfersReceipt,
                    SUM(IF({$receipt} AND date <= '{$prevEnd}', amount, 0)) AS sumTransfersReceiptPrev,
                    SUM(IF({$projects} AND date >= '{$start}', amount, 0)) AS sumTransfersProjectsReceipt,
                    SUM(IF({$projects} AND date <= '{$prevEnd}', amount, 0)) AS sumTransfersProjectsReceiptPrev
                FROM operations
                WHERE contact_id IN ({$donorIds})
                    AND date <= '{$end}' AND type = 'income' AND active = 1 AND {$categories}
                    AND (({$income}) OR ({$receipt}) OR ({$projects}))
                GROUP BY contact_id, category, subcategory
            "))->groupBy('donor_id');

            // Money the donor gives: transfers to other funds and its outcomes
            $given = collect(DB::select("
                SELECT donor_id,
                    MIN(IF(transfer = 1, id, NULL)) AS sendingsFirst,
                    MIN(IF(transfer = 0, id, NULL)) AS outcomesFirst,
                    SUM(IF(transfer = 1 AND date >= '{$start}', amount, 0)) AS sumTransfersSendings,
                    SUM(IF(transfer = 1 AND date <= '{$prevEnd}', amount, 0)) AS sumTransfersSendingsPrev,
                    SUM(IF(transfer = 0 AND date >= '{$start}', amount, 0)) AS sumOutcomes,
                    SUM(IF(transfer = 0 AND date <= '{$prevEnd}', amount, 0)) AS sumOutcomesPrev
                FROM operations
                WHERE donor_id IN ({$donorIds})
                    AND date <= '{$end}' AND type = 'outcome' AND (transfer = 1 OR transfer = 0) AND active = 1 AND {$categories}
                GROUP BY donor_id, category, subcategory
            "))->groupBy('donor_id');

            // Remnants, releases and operating cost, filed under the category of the
            // operation on the other side of the transfer
            $withheld = collect(DB::select("
                SELECT operations.donor_id,
                    MIN(IF(operations.category <> 'Costo Operativo', o.id, NULL)) AS remnantsFirst,
                    MIN(IF(operations.category = 'Costo Operativo', o.id, NULL)) AS monitoringFirst,
                    SUM(IF(operations.date >= '{$start}' AND operations.category = 'Remanentes', operations.amount, 0)) AS sumRemnants,
                    SUM(IF(operations.date <= '{$prevEnd}' AND operations.category = 'Remanentes', operations.amount, 0)) AS sumRemnantsPrev,
                    SUM(IF(operations.date >= '{$start}' AND operations.category = 'Liberaciones', operations.amount, 0)) AS sumReleases,
                    SUM(IF(operations.date <= '{$prevEnd}' AND operations.category = 'Liberaciones', operations.amount, 0)) AS sumReleasesPrev,
                    SUM(IF(operations.date >= '{$start}' AND operations.category = 'Costo Operativo', operations.amount, 0)) AS sumMonitoring,
                    SUM(IF(operations.date <= '{$prevEnd}' AND operations.category = 'Costo Operativo', operations.amount, 0)) AS sumMonitoringPrev
                FROM operations
                JOIN operations AS o ON o.transfer_reference = operations.transfer_reference
                WHERE operations.donor_id IN ({$donorIds})
                    AND operations.date <= '{$end}' AND operations.transfer = 1 AND operations.active = 1
                    AND (
                        (operations.category IN ('Remanentes', 'Liberaciones') AND o.category <> 'Remanentes' AND o.category <> 'Liberaciones')
                        OR (operations.category = 'Costo Operativo' AND o.category <> 'Costo Operativo')
                    )
                GROUP BY operations.donor_id, o.category, o.subcategory
            "))->groupBy('donor_id');

            // The first operation of each source names its row and, for the sources
            // that always set it, gives the row its operating cost fields
            $firstIds = [];
            foreach ([$received, $given, $withheld] as $result) {
                foreach ($result->flatten() as $row) {
                    foreach ($row as $column => $value) {
                        if (str_ends_with($column, 'First') && $value !== null) {
                            $firstIds[] = (int) $value;
                        }
                    }
                }
            }
            $firsts = collect($firstIds ? DB::select("
                SELECT id, IFNULL(category, '-') AS category, IFNULL(subcategory, '-') AS subcategory,
                    percentage_operating_cost, val_operating_cost
                FROM operations WHERE id IN (".implode(',', array_unique($firstIds)).")
            ") : [])->keyBy('id');

            // Source => [result, its sums, whether it sets the operating cost fields]
            $sources = [
                'incomes'     => [$received, ['sumIncomes', 'sumIncomesFee', 'sumFee'], true],
                'receipt'     => [$received, ['sumTransfersReceipt', 'sumTransfersReceiptPrev'], true],
                'sendings'    => [$given, ['sumTransfersSendings', 'sumTransfersSendingsPrev'], true],
                'projects'    => [$received, ['sumTransfersProjectsReceipt', 'sumTransfersProjectsReceiptPrev'], true],
                'outcomes'    => [$given, ['sumOutcomes', 'sumOutcomesPrev'], false],
                'remnants'    => [$withheld, ['sumRemnants', 'sumRemnantsPrev', 'sumReleases', 'sumReleasesPrev'], false],
                'incomesPrev' => [$received, ['incomesPrev'], true],
                'monitoring'  => [$withheld, ['sumMonitoring', 'sumMonitoringPrev'], false],
            ];
            $empty = array_fill_keys([
                'sumIncomes', 'sumIncomesFee', 'sumFee',
                'sumTransfersReceipt', 'sumTransfersReceiptPrev', 'sumTransfersSendings', 'sumTransfersSendingsPrev',
                'sumTransfersProjectsReceipt', 'sumTransfersProjectsReceiptPrev', 'sumOutcomes', 'sumOutcomesPrev',
                'sumRemnants', 'sumRemnantsPrev', 'sumReleases', 'sumReleasesPrev', 'sumMonitoring', 'sumMonitoringPrev',
                'incomesPrev',
            ], 0);

            $operations = [];
            foreach ($donors as $donorId => $donorName) {
                foreach ($sources as $source => [$result, $sums, $setsOperatingCost]) {
                    foreach ($result->get($donorId, []) as $row) {
                        if (!isset($row->{$source.'First'})) {
                            continue;
                        }
                        $first = $firsts[$row->{$source.'First'}];
                        $key = $donorId.'-'.$first->category.'-'.$first->subcategory;
                        if (!isset($operations[$key])) {
                            $operations[$key] = ['id' => $donorId, 'donor' => $donorName, 'category' => $first->category, 'subcategory' => $first->subcategory]
                                + $empty
                                + [
                                    'percentage_operating_cost' => $setsOperatingCost ? $first->percentage_operating_cost : null,
                                    'val_operating_cost' => $setsOperatingCost ? $first->val_operating_cost : null,
                                ];
                        }
                        foreach ($sums as $sum) {
                            $operations[$key][$sum] = $row->$sum;
                        }
                    }
                }
            }

            return ['data' => array_values($operations)];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    private function _getProjects($proyectAssing)
    {
        $projects = DB::table('projects as p')->where([
            'active' => 1
        ]);
        if ($proyectAssing != 'all') {
            $projects->whereRaw('p.id IN('.$proyectAssing.')');
        }
        return $projects->get();
    }
    
    private function _getDonors($request)
    {
        $donorsAssing = idDonorsAvailabe();
        $donors = Contact::select('id','name')->where([
            'category' => 'Donador', 
            'active' => 1
        ])->whereNotIn('id', self::REMNANT_FUNDS);
        if ($donorsAssing != 'all') {
            $donors->whereRaw('id IN('.$donorsAssing.')');
        }
       return $donors->get();
    }
}
