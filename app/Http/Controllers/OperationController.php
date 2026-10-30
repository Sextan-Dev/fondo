<?php

namespace App\Http\Controllers;

use App\User;

use DataTables;
use Validator;
use Storage;
use App\Account;
use App\Contact;
use App\Project;
use App\Action;
use App\Task;
use App\Configuration;
use App\Category;
use App\Content;
use App\Operation;
use App\OperationBeta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\AssignOp\Concat;
use Maatwebsite\Excel\Facades\Excel;

class OperationController extends Controller
{
    use \App\Http\Traits\NotificationTrait;

    /**
     * Show Operation List.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $projects = $this->_getProjects();
            $contacts = $this->_getContacts($projects);

            // CATEGORIES
            $type = $this->_getType();
            $type_total = $this->_getTypeTotal();
            array_unshift($type,$type_total);

            $admon = $this->_getAdmon();
            $fee = ($admon) ? $admon->fee : 0;
            $monitoring = ($admon) ? $admon->monitoring : 0;

            $categories = $this->_getCategories();
            
            $success = true;
            
            return compact(
                'success',
                'projects',
                'type',
                'contacts',
                'fee',
                'monitoring',
                'categories'               
            );

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**
     * New Operation Data
     *
     * @return \Illuminate\Http\Response
     */
    public function data(Request $request)
    {
        try{
            $accounts = Account::select('name AS label' , 'id AS value')
            ->where([ 
                'active' => 1,
                'company_id' => idCompany()
            ])
            ->get();            
            
            $projects = $this->_getProjectsActive();
            $contacts = $this->_getConctactsActive($projects);
            $allContacts = $this->_getConctactsActive($projects, false);

            $mode = Category::selectRaw('name AS label , id AS value')
            ->where([
                'category'=> 'account',
                'type' =>'pay_method',
                'active' => 1,
                'company_id' => idCompany()
            ])
            ->get();
            $fee = Category::selectRaw('name AS label , id AS value')
            ->where([
                'category'=> 'account',
                'type' =>'fee',
                'active' => 1,
                'company_id' => idCompany()
            ])
            ->get();
            $monitoring = Category::selectRaw('name AS label , id AS value')
            ->where([
                'category'=> 'account',
                'type' =>'monitoring',
                'active' => 1,
                'company_id' => idCompany()
            ])
            ->get();
            $incomeCategories = config('operations.income_categories');

            $success = true;
            return compact(
                'success',
                'mode',
                'fee',
                'monitoring',
                'projects',
                'contacts',
                'allContacts',
                'accounts',
                'incomeCategories'
            );
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** SELECT ***/
    public function operationsDonorsMonitoring(Request $request)
    {
        try {        
            //QUERY  
            $donors = $request->user()->donors;
            $filter = "o.donor_id <> 0";
            if (!empty($donors)) {
                $filter = "o.donor_id IN(".$donors.") ";
            }  
            $data = DB::select("
                SELECT
                    c.id,
                    p.id as project_id,
                    c.name as donor,
                    o.category, 
                    o.subcategory,
                    p.name as project,
                    p.date_from,
                    p.date_to,
                    p.amount_invertion,
                    p.temporality,
                    p.ministering_value,
                    SUM(
                        IF(o.type = 'income', o.amount, 0)
                    ) as incomes,
                    IF( p.monitoring_type='percent',
                        SUM( 
                            IF(o.type = 'income', 
                                    IF(p.monitoring <> 0 OR p.monitoring IS NOT NULL, ( o.amount * (p.monitoring/100) ), 0)	
                            , 0)
                        ),
                        p.monitoring_fix
                    ) as monitoring
                  
                    
                FROM
                contacts as c
                LEFT JOIN operations as o ON o.donor_id = c.id AND o.active = 1
                LEFT JOIN projects as p ON p.id = o.project_id
                WHERE
                    ".$filter." AND o.donor_id IS NOT NULL
                GROUP BY o.project_id        
                ORDER BY c.name
            ");
            $data = json_decode(json_encode($data), true);
            
            foreach($data as $key => $item){
                $year_monitoring = 0;
                for ($month=1; $month <=12 ; $month++) { 
                    $data[$key]['month_'.$month] = '';
                    if ($item['temporality'] == 1) {
                        if(check_in_range($item['date_from'], $item['date_to'] , date('Y-').$month.date('-d', strtotime($item['date_from'])))){
                            $months = get_months_in_range($item['date_from'], $item['date_to'] );
                            $data[$key]['month_'.$month] = $item['monitoring'] / $months;
                            $year_monitoring = $year_monitoring + $data[$key]['month_'.$month];
                        }
                    } else {
                        $data[$key]['month_'.$month] = '';
                            if (!empty($item["ministering_value"])) {
                                $jsonArray = json_decode($item["ministering_value"]);
                                $tmp = (array) $jsonArray;
                                if (!empty($tmp)) {
                                    //debugger($jsonArray);
                                    // $array = get_object_vars($jsonArray);
                                    $monthData = $this->getInfoMonth($month, $jsonArray);
                                    $data[$key]['month_'.$monthData["monthCode"]] = $monthData["monthAmount"];
                                    $year_monitoring = $year_monitoring + $data[$key]['month_'.$monthData["monthCode"]];
                                }
                            }
                    }
                }
                $data[$key]['monitoring_year'] = $year_monitoring;
            }
            
            return Datatables::of(collect($data))->make(true);

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** REPORT OPERATIONS DONORS ***/
    public function reportOperationsDonors(Request $request)
    {
        try {            
            //QUERY
            $yearPrev = date('Y',strtotime($request->month."- 1 month"));
            $monthPrev = date('m',strtotime($request->month."- 1 month"));          
            $year = date('Y',strtotime($request->month));
            $month = date('m',strtotime($request->month));          
            $data = DB::select("
                SELECT 
                    c.id,
                    c.name as donor,
                    IFNULL(o.category, '-') as category,
                    IFNULL(o.subcategory, '-') as subcategory,
                    m.sumIncomes,
                    m.sumIncomesFee,
                    m.sumFee,
                    IFNULL(mo.monitoring_amount, 0) as monitoring,
                    IFNULL(r.sumReceipt, 0) as sumReceipt,
                    IFNULL(t.sumTransfers, 0) as sumTransfers,
                    IFNULL(e.sumOutcomes, 0) as sumOutcomes,
                    IFNULL(re.sumRemnants, 0) as sumRemnants,
                    IFNULL(b.balancePrev, 0) as balancePrev
                FROM
                    contacts c 
                LEFT JOIN operations o on  (c.id = o.contact_id) AND YEAR(o.date) = ".$year." AND MONTH(o.date) <= ".$month." AND o.category <> 'Remanentes' AND o.active = 1
                -- MOVES
                LEFT JOIN (
                    SELECT
                        c.id,
                        c.name,
                        IFNULL(o.category, '-') as category,
                        IFNULL(o.subcategory, '-') as subcategory,
                        sum(
                            IF(o.type = 'income' AND o.project_id = 0 AND o.transfer = 0, o.amount, 0)
                        ) as sumIncomes,
                        sum(
                            IF(o.type = 'income' AND o.project_id = 0  AND o.transfer = 0, o.amount - IF(o.fee IS NOT NULL AND o.fee <> 0, (o.amount * (o.fee / 100)), 0) , 0)
                        ) as sumIncomesFee,
                        sum(
                            IF(o.type = 'income' AND o.project_id = 0  AND o.transfer =0 , IF(o.fee IS NOT NULL AND o.fee <> 0, (o.amount * (o.fee / 100)), 0) , 0)
                        ) as sumFee
                    FROM
                    contacts c 
                    LEFT JOIN operations o on c.id = o.contact_id AND YEAR(o.date) = ".$year." AND MONTH(o.date) = ".$month." AND o.category <> 'Remanentes' AND o.active = 1
                    WHERE o.category is not null 
                    GROUP BY c.id, o.category , o.subcategory
                ) as m ON m.id = c.id AND o.category = m.category AND o.subcategory = m.subcategory
                
                -- TRANSFERS SENDING
                LEFT JOIN (
                    SELECT 
                        c.id,
                        c.name,
                        IFNULL(o.category, '-') as category,
                        IFNULL(o.subcategory, '-') as subcategory,
                        sum(
                                o.amount
                            ) as sumTransfers
                    FROM
                    contacts c 
                    LEFT JOIN operations o on c.id = o.donor_id AND YEAR(o.date) = ".$year." AND MONTH(o.date) = ".$month." AND o.transfer = 1 AND  o.type = 'outcome' AND o.category <> 'Remanentes' AND o.active = 1
                    WHERE o.category is not null 
                    GROUP BY c.id, o.category , o.subcategory
                ) as t ON t.id = c.id AND o.category = t.category AND o.subcategory = t.subcategory
                
                -- TRANSFER RECEIPT
                LEFT JOIN (
                    SELECT 
                        c.id,
                        c.name,
                        IFNULL(o.category, '-') as category,
                        IFNULL(o.subcategory, '-') as subcategory,
                        sum(
                                o.amount
                            ) as sumReceipt,
                        IF( p.monitoring_type='percent',
                                SUM( 
                                    IF(o.type = 'income' AND  p.monitoring <> 0 AND p.monitoring IS NOT NULL, 
                                        (o.amount * (p.monitoring / 100)),
                                        0
                                    )
                                ),
                                p.monitoring_fix
                        ) as monitoring_amount
                    FROM
                    contacts c 
                    LEFT JOIN operations o on c.id = o.contact_id AND YEAR(o.date) = ".$year." AND MONTH(o.date)= ".$month." AND o.transfer = 1  AND o.type = 'income' AND o.category <> 'Remanentes' AND o.active = 1
                    LEFT JOIN projects p on p.id = o.project_id
                    WHERE o.category is not null
                    GROUP BY c.id, o.category , o.subcategory
                ) as r ON r.id = c.id AND o.category = r.category AND o.subcategory = r.subcategory
                -- OUTCOMES
                LEFT JOIN (
                    SELECT 
                        c.id,
                        c.name,
                        IFNULL(o.category, '-') as category,
                        IFNULL(o.subcategory, '-') as subcategory,
                        sum(
                                o.amount
                            ) as sumOutcomes
                    FROM
                    contacts c 
                    LEFT JOIN operations o on c.id = o.donor_id AND YEAR(o.date) = ".$year." AND MONTH(o.date)= ".$month." AND o.transfer = 0 AND  o.type = 'outcome' AND o.category <> 'Remanentes' AND o.active = 1
                    WHERE o.category is not null
                    GROUP BY c.id, o.category , o.subcategory
                ) as e ON e.id = c.id AND o.category = e.category AND o.subcategory = e.subcategory
                -- REMANTS
                LEFT JOIN (
                    SELECT 
                        c.id,	
                        sum(
                                o.amount
                            ) as sumRemnants
                    FROM
                    contacts c 
                    LEFT JOIN operations o on c.id = o.donor_id AND YEAR(o.date) = ".$year." AND MONTH(o.date) =".$month."  AND  o.type = 'income' AND o.category = 'Remanentes'
                    WHERE o.category is not null
                    GROUP BY c.id, o.category
                ) as re ON re.id = c.id             
                    
                -- BALANCE
                LEFT JOIN (
                    SELECT 
                        c.id,
                        c.name,
                        IFNULL(o.category, '-') as category,
                        IFNULL(o.subcategory, '-') as subcategory,
                        sum(
                        IF(o.type = 'income' AND o.project_id = 0  AND o.transfer = 0, o.amount - IF(o.fee IS NOT NULL AND o.fee <> 0, (o.amount * (o.fee / 100)), 0) , 0)
                    ) as balancePrev
                    FROM
                    contacts c 
                    LEFT JOIN operations o on c.id = o.contact_id AND YEAR(o.date) <= ".$year." AND MONTH(o.date) < ".$month." AND o.category <> 'Remanentes' AND o.active = 1

                    GROUP BY c.id, o.category, o.subcategory
                ) as b ON b.id = c.id  AND o.category = b.category AND o.subcategory = b.subcategory  
                -- MONITORING
                LEFT JOIN (
                    SELECT
                    c.id,
                    c.name,
                    IFNULL(o.category, '-') as category,
                    IFNULL(o.subcategory, '-') as subcategory,
                    IF( p.monitoring_type='percent',
                        0,
                        p.monitoring_fix
                    ) as monitoring_amount
                    FROM
                    contacts c
                    LEFT JOIN operations o on c.id = o.contact_id 
                    LEFT JOIN projects p on p.id = o.project_id 
                    WHERE o.category is not null AND o.project_id <> 0 AND p.monitoring_fix <> 0 AND o.transfer = 1 AND o.type = 'income'
                    GROUP BY c.id, o.category, o.subcategory 
                )as mo ON mo.id = c.id  AND o.category = mo.category AND o.subcategory = mo.subcategory
                WHERE c.category = 'Donador' 
                GROUP BY c.id, o.category, o.subcategory
                ORDER BY c.name
            ");

            
            
            return Datatables::of($data)->make(true);

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function storeBalance(Request $request)
    {
        try {
            $data = $request->data;

            foreach($data as $item){
                $balance = Operation::where([
                    'date' => $item['date'].'-01',
                    'type' => 'balance',
                    'donor_id' => $item['donor_id'],
                    'category' => $item['category'],
                    'subcategory' => $item['subcategory']
                ])->first();

                if($balance){

                }else{
                    $balance = new Operation;
                    $balance->fill([
                        'date' => $item['date'].'-01',
                        'type' => 'balance',
                        'donor_id' => $item['donor_id'],
                        'category' => $item['category'],
                        'subcategory' => $item['subcategory'],
                        'amount' => $item['balance']
                    ])->save();
                }

            }

            return ['success' => true];
            
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function reportOperationsDonorsNew(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        try {            
            //QUERY
            $yearPrev = date('Y',strtotime($request->month."- 1 month"));
            $monthPrev = date('m',strtotime($request->month."- 1 month"));          
            $datePrev = date('Y-m-d',strtotime($request->month."-01 - 1 month"));          
            $year = date('Y',strtotime($request->month));
            $month = date('m',strtotime($request->month)); 
            $operations = [];
            $donors = Contact::select('id','name')->where([
                'category' => 'Donador',
                // 'id' => 110,
                'active' => 1
            ])
            // ->whereIn('id', [110,1, 55,21, 108])
            ->get();
            

            $balances = Operation::where([
                'type' => 'balance',
                'date' => $datePrev
            ])->get();

            
            foreach($donors as $donor){                              

                // GET INCOMES
                $incomes = Operation::selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,
                    SUM(amount) AS sumIncomes,
                    SUM(IF(fee IS NOT NULL AND fee <> 0, amount - (amount * (fee / 100)), amount)) AS sumIncomesFee,
                    SUM( IF(fee IS NOT NULL AND fee <> 0, (amount * (fee / 100)), 0)) as sumFee
                ")
                ->whereRaw("
                    contact_id = ".$donor->id."
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
                ->groupBy('category' , 'subcategory')
                ->get();

                foreach($incomes as $income){
                    $operations[] = [
                        "id" => $donor->id,
                        "donor" => $donor->name,
                        "category" => $income->category,
                        "subcategory" => $income->subcategory,
                        "sumIncomes" => $income->sumIncomes,
                        "sumIncomesFee" => $income->sumIncomesFee,
                        "sumFee" =>  $income->sumFee,
                        "monitoring" => 0,
                        "monitoringTake" => 0,
                        "monitoringsPrev" => 0,
                        "sumTransfersReceipt" => 0,
                        "sumTransfersReceiptPrev" => 0,
                        "sumTransfersSendings" => 0,
                        "sumTransfersSendingsPrev" => 0,
                        "sumTransfersSendingsMonitoring" => 0,
                        "sumTransfersSendingsMonitoringPrev" => 0,
                        "sumOutcomes" => 0,
                        "sumOutcomesPrev" => 0,
                        "sumRemnants" => 0,
                        "sumReleases" => 0,
                        "sumRemnantsPrev" => 0,
                        "sumReleasesPrev" => 0,
                        "balancePrev" => 0
                    ];        
                }                
                
                // GET TRANSFERS RECEIPT
                $transfersReceipt = Operation::selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,                    
                    SUM(IF(MONTH(date) = ".abs($month).", amount, 0)) as sumTranfersReceipt,
                    SUM(IF(MONTH(date) < ".abs($month).", amount, 0)) as sumTranfersReceiptPrev
                ")
                ->whereRaw("
                    contact_id = ".$donor->id."
                    AND YEAR(date) = ".$year." 
                    AND MONTH(date) <= ".$month." 
                    AND transfer = 1 
                    AND type = 'income'   
                    AND (
                        (project_id <> 0 AND donor_id <> ".$donor->id.") OR
                        (project_id = 0)
                    )
                    AND category <> 'Remanentes' 
                    AND category <> 'Liberaciones' 
                    AND category IS NOT NULL
                    AND active = 1"
                )
                ->groupBy('category' , 'subcategory')
                ->get();

                
                foreach($transfersReceipt as $transfer){
                    
                    $operationNeedle = collect($operations)
                    ->where('id', $donor->id)
                    ->where('category', $transfer->category)
                    ->where('subcategory', $transfer->subcategory);

                    if(!empty($operationNeedle->toArray())){
                        foreach($operationNeedle->toArray() as $needle => $data){
                            if(isset($operations[$needle])){
                                $operations[$needle]['sumTransfersReceipt'] = $transfer->sumTranfersReceipt;
                                $operations[$needle]['sumTransfersReceiptPrev'] = $transfer->sumTranfersReceiptPrev;
                            }
                        }
                    }else{
                        $operations[] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $transfer->category,
                            "subcategory" => $transfer->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,
                            "monitoring" => 0,
                            "monitoringTake" => 0,
                            "monitoringsPrev" => 0,
                            "sumTransfersReceipt" => $transfer->sumTranfersReceipt,
                            "sumTransfersReceiptPrev" => $transfer->sumTranfersReceiptPrev,
                            "sumTransfersSendings" => 0,
                            "sumTransfersSendingsPrev" => 0,
                            "sumTransfersSendingsMonitoring" => 0,
                            "sumTransfersSendingsMonitoringPrev" => 0,
                            "sumOutcomes" => 0,
                            "sumOutcomesPrev" => 0,
                            "sumRemnants" => 0,
                            "sumReleases" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleasesPrev" => 0,
                            "balancePrev" => 0
                        ];
                    }
                    
                }
                
                // GET TRANSFERS SENDING
                $sumTransfersSendings = Operation::selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,                    
                    SUM(IF(MONTH(date) = ".abs($month).", amount, 0)) as sumTransfersSendings,
                    SUM(IF(MONTH(date) < ".abs($month).", amount, 0)) as sumTransfersSendingsPrev
                ")
                ->whereRaw("
                    donor_id = ".$donor->id."
                    AND YEAR(date) = ".$year." 
                    AND MONTH(date) <= ".$month." 
                    AND transfer = 1
                    AND type = 'outcome'                    
                    AND category <> 'Remanentes' 
                    AND category <> 'Liberaciones'
                    AND category IS NOT NULL
                    AND active = 1"
                )
                ->groupBy('category' , 'subcategory')
                ->get();                
                
                foreach($sumTransfersSendings as $transfer){
                    
                    $operationNeedle = collect($operations)
                    ->where('id', $donor->id)
                    ->where('category', $transfer->category)
                    ->where('subcategory', $transfer->subcategory);                                        

                    if(!empty($operationNeedle->toArray())){
                        foreach($operationNeedle->toArray() as $needle => $data){
                            if(isset($operations[$needle])){
                                $operations[$needle]['sumTransfersSendings'] = $transfer->sumTransfersSendings;
                                $operations[$needle]['sumTransfersSendingsPrev'] = $transfer->sumTransfersSendingsPrev;

                            }
                        }
                    }else{
                        $operations[] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $transfer->category,
                            "subcategory" => $transfer->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,
                            "monitoring" => 0,
                            "monitoringTake" => 0,
                            "monitoringsPrev" => 0,
                            "sumTransfersReceipt" => 0,
                            "sumTransfersReceiptPrev" => 0,                            
                            "sumTransfersSendings" => $transfer->sumTransfersSendings,
                            "sumTransfersSendingsPrev" => $transfer->sumTransfersSendingsPrev,
                            "sumTransfersSendingsMonitoring" => 0,
                            "sumTransfersSendingsMonitoringPrev" => 0,
                            "sumOutcomes" => 0,
                            "sumOutcomesPrev" => 0,
                            "sumRemnants" => 0,                            
                            "sumReleases" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleasesPrev" => 0,
                            "balancePrev" => 0
                        ];
                    }
                    
                }

                // GET TRANSFERS SENDING monitoring

                // // GET TRANSFERS SENDING
                // $sumTransfersSendingsMonitoring = Operation::selectRaw("
                //     IFNULL(category, '-') as category,
                //     IFNULL(subcategory, '-') as subcategory,                    
                //     SUM(IF(MONTH(date) = ".abs($month).", amount, 0)) as sumTransfersSendings,
                //     SUM(IF(MONTH(date) < ".abs($month).", amount, 0)) as sumTransfersSendingsPrev
                // ")
                // ->whereRaw("
                //     contact_id = ".$donor->id."
                //     AND YEAR(date) = ".$year." 
                //     AND MONTH(date) <= ".$month." 
                //     AND transfer = 1 
                //     AND type = 'income'   
                //     AND project_id <> 0 AND  donor_id = ".$donor->id."
    
                //     AND category <> 'Remanentes' 
                //     AND category <> 'Liberaciones' 
                //     AND category IS NOT NULL
                //     AND active = 1"
                // )
                // ->groupBy('category' , 'subcategory')
                // ->get();

                // GET TRANSFERS RECEIPT
                $sumTransfersSendingsMonitoring = Operation::selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,                    
                    SUM(IF(MONTH(date) = ".abs($month)." , amount, 0) ) as sumTranfersReceipt,
                    SUM(IF(MONTH(date) < ".abs($month).", amount, 0)) as sumTranfersReceiptPrev
                ")
                ->whereRaw("
                    contact_id = ".$donor->id."
                    AND donor_id = ".$donor->id."
                    AND YEAR(date) = ".$year." 
                    AND MONTH(date) <= ".$month." 
                    AND transfer = 1 
                    AND project_id <> 0
                    AND type = 'income'                  
                    AND category <> 'Remanentes' 
                    AND category <> 'Liberaciones' 
                    AND category IS NOT NULL
                    AND active = 1"
                )
                ->groupBy('category' , 'subcategory')
                ->get();

                
                foreach($sumTransfersSendingsMonitoring as $transfer){
                    
                    $operationNeedle = collect($operations)
                    ->where('id', $donor->id)
                    ->where('category', $transfer->category)
                    ->where('subcategory', $transfer->subcategory);                                        

                    if(!empty($operationNeedle->toArray())){
                        foreach($operationNeedle->toArray() as $needle => $data){
                            if(isset($operations[$needle])){
                                $operations[$needle]['sumTransfersSendingsMonitoring'] = $transfer->sumTranfersReceipt;
                                $operations[$needle]['sumTransfersSendingsMonitoringPrev'] = $transfer->sumTranfersReceiptPrev;

                            }
                        }
                    }else{
                        $operations[] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $transfer->category,
                            "subcategory" => $transfer->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,
                            "monitoring" => 0,
                            "monitoringTake" => 0,
                            "monitoringsPrev" => 0,
                            "sumTransfersReceipt" => 0,
                            "sumTransfersReceiptPrev" => 0,                            
                            "sumTransfersSendings" => 0,
                            "sumTransfersSendingsPrev" => 0,
                            "sumTransfersSendingsMonitoring" => $transfer->sumTransfersSendings,
                            "sumTransfersSendingsMonitoringPrev" => $transfer->sumTransfersSendingsPrev,
                            "sumOutcomes" => 0,
                            "sumOutcomesPrev" => 0,
                            "sumRemnants" => 0,                            
                            "sumReleases" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleasesPrev" => 0,
                            "balancePrev" => 0
                        ];
                    }
                    
                }

                // GET OUTCOMES

                $outcomes = Operation::selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,                    
                    SUM(IF(MONTH(date) = ".abs($month).", amount, 0)) as sumOutcomes,
                    SUM(IF(MONTH(date) < ".abs($month).", amount, 0)) as sumOutcomesPrev
                ")
                ->whereRaw("
                    donor_id = ".$donor->id."
                    AND YEAR(date) = ".$year." 
                    AND MONTH(date) <= ".$month." 
                    AND transfer = 0
                    AND type = 'outcome'                    
                    AND category <> 'Remanentes' 
                    AND category <> 'Liberaciones' 
                    AND category IS NOT NULL
                    AND active = 1"
                )
                ->groupBy('category' , 'subcategory')
                ->get();

                
                foreach($outcomes as $outcome){
                    
                    $operationNeedle = collect($operations)
                    ->where('id', $donor->id)
                    ->where('category', $outcome->category)
                    ->where('subcategory', $outcome->subcategory);
                    
                    if(!empty($operationNeedle->toArray())){
                        foreach($operationNeedle->toArray() as $needle => $data){
                            if(isset($operations[$needle])){
                                $operations[$needle]['sumOutcomes'] = $outcome->sumOutcomes;
                                $operations[$needle]['sumOutcomesPrev'] = $outcome->sumOutcomesPrev;
                            }
                        }
                    }else{
                        $operations[] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $outcome->category,
                            "subcategory" => $outcome->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,
                            "monitoring" => 0,
                            "monitoringTake" => 0,
                            "monitoringsPrev" => 0,
                            "sumTransfersReceipt" => 0,
                            "sumTransfersReceiptPrev" => 0,
                            "sumTransfersSendings" => 0,
                            "sumTransfersSendingsPrev" => 0,
                            "sumTransfersSendingsMonitoring" => 0,
                            "sumTransfersSendingsMonitoringPrev" => 0,
                            "sumOutcomes" =>  $outcome->sumOutcomes,
                            "sumOutcomesPrev" =>  $outcome->sumOutcomesPrev,
                            "sumRemnants" => 0,
                            "sumReleases" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleasesPrev" => 0,
                            "balancePrev" => 0
                        ];
                    }                    
                }


                // GET REMNANST AND RELEASES
                $remnants = Operation::selectRaw("                    
                    IFNULL(o.category, '-') as category,
                    IFNULL(o.subcategory, '-') as subcategory,
                    SUM(IF( MONTH(operations.date) = ".abs($month)." AND operations.category = 'Remanentes' , operations.amount, 0)) AS sumRemnants,
                    SUM(IF( MONTH(operations.date) = ".abs($month)." AND operations.category = 'Liberaciones' , operations.amount, 0)) AS sumReleases,
                    SUM(IF( MONTH(operations.date) < ".abs($month)." AND operations.category = 'Remanentes' , operations.amount, 0)) AS sumRemnantsPrev,
                    SUM(IF( MONTH(operations.date) < ".abs($month)." AND operations.category = 'Liberaciones' , operations.amount, 0)) AS sumReleasesPrev
                ")
                ->leftJoin('operations AS o', 'o.transfer_reference', '=' ,'operations.transfer_reference')
                ->whereRaw("
                    operations.donor_id = ".$donor->id."
                    AND YEAR(operations.date) = ".$year." 
                    AND MONTH(operations.date) <= ".$month." 
                    AND operations.transfer = 1                                    
                    AND (operations.category = 'Remanentes' OR operations.category = 'Liberaciones')
                    AND operations.active = 1"
                )
                ->groupBy('o.category' , 'o.subcategory')
                ->get();
                
                                
                foreach($remnants as $remnant){
                    if($remnant->category !='Remanentes' && $remnant->category !='Liberaciones'){
                        $operationNeedle = collect($operations)
                        ->where('id', $donor->id)
                        ->where('category', $remnant->category)
                        ->where('subcategory', $remnant->subcategory);
                        
                        if(!empty($operationNeedle->toArray())){
                            foreach($operationNeedle->toArray() as $needle => $data){
                                if(isset($operations[$needle])){
                                    $operations[$needle]['sumRemnants'] = $remnant->sumRemnants;
                                    $operations[$needle]['sumReleases'] = $remnant->sumReleases;
                                    $operations[$needle]['sumRemnantsPrev'] = $remnant->sumRemnantsPrev;
                                    $operations[$needle]['sumReleasesPrev'] = $remnant->sumReleasesPrev;
                                }
                            }
                        }else{
                            $operations[] = [
                                "id" => $donor->id,
                                "donor" => $donor->name,
                                "category" => $remnant->category,
                                "subcategory" => $remnant->subcategory,
                                "sumIncomes" => 0,
                                "sumIncomesFee" => 0,
                                "sumFee" =>  0,
                                "monitoring" => 0,
                                "monitoringTake" => 0,
                                "monitoringsPrev" => 0,
                                "sumTransfersReceipt" => 0,
                                "sumTransfersReceiptPrev" => 0,
                                "sumTransfersSendings" => 0,
                                "sumTransfersSendingsPrev" => 0,
                                "sumTransfersSendingsMonitoring" => 0,
                                "sumTransfersSendingsMonitoringPrev" => 0,
                                "sumOutcomes" =>  0,
                                "sumOutcomesPrev" => 0,
                                "sumRemnants" => $remnant->sumRemnants,
                                "sumReleases" => $remnant->sumReleases,
                                "sumRemnantsPrev" => $remnant->sumRemnantsPrev,
                                "sumReleasesPrev" => $remnant->sumReleasesPrev,
                                "balancePrev" => 0
                            ];
                        }
                    }
                    
                }


                // // BALANCE PREV
                // $incomesPrev = Operation::selectRaw("
                //     IFNULL(category, '-') as category,
                //     IFNULL(subcategory, '-') as subcategory,                    
                //     SUM( amount - IF(fee IS NOT NULL AND fee <> 0, (amount * (fee / 100)), 0 )) AS balancePrev
                // ")
                // ->whereRaw("
                //     contact_id = ".$donor->id."
                //     AND YEAR(date) <= ".$year." 
                //     AND MONTH(date) < ".$month." 
                //     AND category <> 'Remanentes' 
                //     AND category <> 'Liberaciones' 
                //     AND category IS NOT NULL
                //     AND type = 'income'
                //     AND project_id = 0 
                //     AND transfer = 0
                //     AND active = 1"
                // )
                // ->groupBy('category' , 'subcategory')
                // ->get();

                $incomesPrev = collect($balances)
                ->where('donor_id', $donor->id);
                
                                 
                foreach($incomesPrev as $income){   
                    
                    $operationNeedle = collect($operations)
                    ->where('id', $donor->id)
                    ->where('category', $income->category)
                    ->where('subcategory', $income->subcategory);
                    
                    
                    if(!empty($operationNeedle->toArray())){
                        foreach($operationNeedle->toArray() as $needle => $data){
                            if(isset($operations[$needle])){                                
                                $operations[$needle]['balancePrev'] = $income->amount;
                            }
                        }
                    }else{
                        $operations[] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $income->category,
                            "subcategory" => $income->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,
                            "monitoring" => 0,
                            "monitoringTake" => 0,
                            "monitoringsPrev" => 0,
                            "sumTransfersReceipt" => 0,
                            "sumTransfersReceiptPrev" => 0,
                            "sumTransfersSendings" => 0,
                            "sumTransfersSendingsPrev" => 0,
                            "sumTransfersSendingsMonitoring" => 0,
                            "sumTransfersSendingsMonitoringPrev" => 0,
                            "sumOutcomes" => 0,   
                            "sumOutcomesPrev" => 0,                         
                            "sumRemnants" => 0,
                            "sumReleases" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleasesPrev" => 0,
                            "balancePrev" => $income->amount,
                        ];
                    }                    
                }



                // GET MONITORING
                $subQuery = Operation::selectRaw("
                    IFNULL(operations.category, '-') as category,
                    IFNULL(operations.subcategory, '-') as subcategory,              
                    IF( p.monitoring_type='percent',
                        0,
                        (p.monitoring_fix)
                    ) as monitoringAmount,
                    p.date_from
                    

                ")                
                ->leftJoin('projects AS p',function ($join) use ($request) {
                    $join->on('p.id', '=' ,'operations.project_id');
                    $join->whereRaw('
                        DATE_FORMAT(p.date_from, "%Y-%m") <= "'.$request->month .'" AND
                        DATE_FORMAT(p.date_to, "%Y-%m") >= "'.$request->month .'"
                    ');                    
                })
                ->whereRaw("
                    operations.donor_id = ".$donor->id."                   
                    AND operations.project_id <> 0                    
                    AND operations.active = 1
                    AND p.monitoring_fix <> 0"              
                )
                ->groupBy('p.id');

                $monitorings = DB::query()->fromSub($subQuery, 'o')
                ->selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,              
                    SUM(monitoringAmount) as monitoringAmount,
                    SUM(IF(MONTH(date_from) = ".abs($month).",monitoringAmount, 0)) as monitoringTake
                ")         
                ->groupBy('o.category', 'o.subcategory')
                ->get();
                      
                foreach($monitorings as $monitoring){                    
                    $operationNeedle = collect($operations)
                    ->where('id', $donor->id)
                    ->where('category', $monitoring->category)
                    ->where('subcategory', $monitoring->subcategory);

                    if(!empty($operationNeedle->toArray())){
                        foreach($operationNeedle->toArray() as $needle => $data){
                            if(isset($operations[$needle])){
                                $operations[$needle]['monitoring'] = $monitoring->monitoringAmount;
                                $operations[$needle]['monitoringTake'] = $monitoring->monitoringTake;
                            }
                        }
                    }else{
                        $operations[] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $monitoring->category,
                            "subcategory" => $monitoring->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,
                            "monitoring" => $monitoring->monitoringAmount,
                            "monitoringTake" => $monitoring->monitoringTake,
                            "monitoringsPrev" => 0,
                            "sumTransfersReceipt" => 0,
                            "sumTransfersReceiptPrev" => 0,
                            "sumTransfersSendings" => 0,
                            "sumTransfersSendingsPrev" => 0,
                            "sumTransfersSendingsMonitoring" => 0,
                            "sumTransfersSendingsMonitoringPrev" => 0,
                            "sumOutcomes" => 0,  
                            "sumOutcomesPrev" => 0,
                            "sumRemnants" => 0,
                            "sumReleases" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleasesPrev" => 0,
                            "balancePrev" => 0,
                        ];
                    }                    
                }                
                
                // GET MONITORING
                $subQuery = Operation::selectRaw("
                    IFNULL(operations.category, '-') as category,
                    IFNULL(operations.subcategory, '-') as subcategory,              
                    IF( p.monitoring_type='percent',
                        0,
                        (p.monitoring_fix)
                    ) as monitoringAmount
                ")                
                ->leftJoin('projects AS p',function ($join) use ($request) {
                    $join->on('p.id', '=' ,'operations.project_id');
                    $join->whereRaw('
                        DATE_FORMAT(p.date_from, "%Y-%m") <= "'.date('Y-m',strtotime($request->month."- 1 month")) .'" AND
                        DATE_FORMAT(p.date_to, "%Y-%m") >= "'.date('Y-m',strtotime($request->month."- 1 month")) .'" AND p.active = 1
                    ');                    
                })
                ->whereRaw("
                    operations.donor_id = ".$donor->id."                     
                    AND operations.project_id <> 0                    
                    AND operations.active = 1
                    AND p.monitoring_fix <> 0"              
                )
                ->groupBy('p.id');

                $monitoringsPrev = DB::query()->fromSub($subQuery, 'o')
                ->selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,              
                    SUM(monitoringAmount) as monitoringAmount
                ")         
                ->groupBy('o.category', 'o.subcategory')
                ->get();
                    
                
                foreach($monitoringsPrev as $monitoring){                    
                    $operationNeedle = collect($operations)
                    ->where('id', $donor->id)
                    ->where('category', $monitoring->category)
                    ->where('subcategory', $monitoring->subcategory);

                    if(!empty($operationNeedle->toArray())){
                        foreach($operationNeedle->toArray() as $needle => $data){
                            if(isset($operations[$needle])){
                                $operations[$needle]['monitoringsPrev'] = $monitoring->monitoringAmount;
                            }
                        }
                    }else{
                        $operations[] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $monitoring->category,
                            "subcategory" => $monitoring->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,
                            "monitoring" => 0,
                            "monitoringTake" => 0,
                            "monitoringsPrev" => $monitoring->monitoringAmount,
                            "sumTransfersReceipt" => 0,
                            "sumTransfsersReceiptPrev" => 0,
                            "sumTransfersSendings" => 0,
                            "sumTransfersSendingsPrev" => 0,
                            "sumTransfersSendingsMonitoring" => 0,
                            "sumTransfersSendingsMonitoringPrev" => 0,
                            "sumOutcomes" => 0,  
                            "sumOutcomesPrev" => 0,
                            "sumRemnants" => 0,
                            "sumReleases" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleasesPrev" => 0,
                            "balancePrev" => 0,
                        ];
                    }                    
                }  
            
            }   
            
                         
           

           return Datatables::of($operations)->make(true);

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** SELECT ***/
    public function select(Request $request)
    {
        try {            
            //QUERY
            $filters =[
                'o.active' => 1,
                'o.company_id' => idCompany()
            ];
            $data = DB::table('operations AS o')
            ->selectRaw('
                o.id,
                o.date,
                o.type,
                o.account,
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
                o.transfer_reference,
                o.transfer,
                o.percentage_operating_cost,
                o.val_operating_cost,
                o.history_operation_id'
            )
            ->leftJoin('contacts AS ct', 'o.contact_id', '=', 'ct.id')
            ->leftJoin('contacts AS d', 'o.donor_id', '=', 'd.id')
            ->leftJoin('categories AS ca', 'o.mode_id', '=', 'ca.id' )
            ->leftJoin('projects AS p', 'o.project_id', '=', 'p.id');

            // Parenthesised: a bare OR here used to skip the period, company and type filters.
            if( $request->contact != 891 ){
                $data = $data->whereRaw('o.is_co = 0');
            }else{
                $data = $data->whereRaw('((o.is_co = 1 AND o.type = "income") OR o.is_co = 0)');
            }

            // One import shows all its operations, whatever their date
            if ($request->filled('history')) {
                $data->where('o.history_operation_id', (int) $request->history);
            } else {
                // o.date is a DATE column: comparing it directly (not through DATE()) lets MySQL use an index.
                $data->whereBetween('o.date', [$request->from, $request->to]);
            }

            // Users limited to some donors only see those donors' operations, whichever contact they ask for.
            $donorIds = $this->_assignedDonorIds();
            if (!is_null($donorIds)) {
                $data->where(function ($query) use ($donorIds) {
                    $query->whereIn('o.contact_id', $donorIds)->orWhereIn('o.donor_id', $donorIds);
                });
            }

            if( $request->account !='' ){
                $filters['o.account_id']=$request->account;
            }
            if( $request->contact !='all' ){
                $data = $data->whereRaw('(o.contact_id = ? OR o.donor_id = ?)', [$request->contact, $request->contact]);
            }
            if( $request->project !='all' ){
                $filters['p.id'] = $request->project;
            }
            $data = $data->where($filters);

            if( $request->category !='all' ){
                $data->where('o.type', $request->category ==  '' ? '' :$request->category);
            }else{
                $data = $data->whereRaw('(o.type = "income" OR o.type = "outcome")');
            }

            // Paginated requests (operation list) send their own order; the rest keep the newest first.
            if (!$request->has('order')) {
                $data->orderBy('o.id', 'desc');
            }

            return Datatables::of($data)
                // amount is a varchar: sort it as a number
                ->orderColumn('o.amount', 'CAST(o.amount AS DECIMAL(20,2)) $1')
                ->make(true);

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function balanceDetails(Request $request)
    {
        try{
            
            $comissions = DB::table('operations as o')
            ->selectRaw("
                SUM(                     
                    ( (IFNULL(o.fee,0)/100 ) * o.amount)
                ) AS fee,

                SUM(
                    ( (IFNULL(o.monitoring,0) /100 ) * o.amount)                    
                ) AS monitoring
            ")
            ->leftJoin('projects as p', 'p.id', '=', 'o.project_id')
            ->where('o.active', 1)
            ->where('o.type', 'income');

            $categories = Operation::selectRaw('
                category,
                SUM( IF(type ="income", ( amount - ( amount * (IF(fee = 0 OR fee IS NULL, 0, fee/100)) )  - ( amount * (IF(monitoring = 0 OR monitoring IS NULL, 0, monitoring/100))) ) , -amount)) as total,
                SUM( IF(project_id = 0, IF(type ="income", ( amount - ( amount * (IF(fee = 0 OR fee IS NULL, 0, fee/100)))  ))), -amount), 0)) as totalAvailable,
                SUM( IF(project_id <> 0, IF(type ="income", ( amount -  - ( amount * (IF(monitoring = 0 OR monitoring IS NULL, 0, monitoring/100))) ), -amount), 0)) as totalNotAvailable,
                SUM( IF(type ="income", ( amount * (IF(fee = 0 OR fee IS NULL, 0, fee/100)) )  , 0 )) as fee,
                SUM( IF(type ="income", ( amount * (IF(monitoring = 0 OR fee IS NULL, 0, monitoring/100)) )  , 0 )) as monitoring
            ')->where([
                'active' => 1
            ]);

            if($request->contact!='all'){
                $comissions = $comissions->where('o.contact_id', $request->contact);
                $categories = $categories->whereRaw('(contact_id = ? OR donor_id = ?)', [$request->contact, $request->contact]);
            }
            if($request->project!='all'){
                $comissions = $comissions->where('o.project_id', $request->project);
                $categories = $categories->where('project_id', $request->project);
            }

            $comissions = $comissions->first();
            $categories = $categories->groupBy('category')->get();
            
            $fee = ($comissions) ? $comissions->fee : 0;
            $monitoring = ($comissions) ? $comissions->monitoring : 0;

            return [
                'success' => true,
                'categories' => $categories,
                'fee' => $comissions->fee,
                'monitoring' => $comissions->monitoring
            ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            DB::rollback();
            return ['success' => false , 'msg' =>   _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
    
    public function balanceAccount(Request $request)
    { 
        try{            
            $contact_id = $request->contact_id;            
                        
            if($request->resource == 'categories'){ 
                $operations = Operation::selectRaw('
                    category,
                    0 as total,
                    0 as totalAvailable
                ')->where([
                    'active' => 1
                ])
                ->whereRaw(
                    'contact_id = ? AND category != "RESERVA RETENCIÓN CO"', [$contact_id]
                )->groupBy('category')->get(); 
            }else{
                if($request->resource == 'subcategories'){ 

                    // Same balance as Saldo Donantes (ReportController@donorsBalance): what the
                    // donor received less what it gave, whether or not a project holds the money
                    $operations = Operation::selectRaw('
                        subcategory,
                        SUM( IF( transfer = 1 AND project_id IS NOT NULL, amount, IF( project_id = 0, amount - ( amount * IF(fee = 0 OR fee IS NULL, 0, fee/100)), 0))) as totalAvailable                
                    ')->where([
                        'active' => 1,
                        'type' => 'income',
                        'category' => $request->category,
                        'contact_id' => $contact_id 
                    ])
                    // ->whereRaw('project_id = 0') 
                    ->groupBy('subcategory')
                    ->get()->toArray();

                     //debugger($operations);

                    $operations_outs = Operation::selectRaw('
                        subcategory,
                        SUM( amount ) as totalAvailable                   
                    ')->where([
                        'active' => 1,
                        'type' => 'outcome',
                        'category' => $request->category,
                        'donor_id' => $contact_id 
                    ])
                    ->groupBy('subcategory')
                    ->get()->keyBy('subcategory')->toArray();
                    foreach($operations as $key => $item){
                        if(isset($operations_outs[$item['subcategory']])){
                            $operations[$key]['totalAvailable'] = $operations[$key]['totalAvailable'] - $operations_outs[$item['subcategory']]['totalAvailable'];                            
                        }
                    }
                             
                } else if($request->resource == 'projects'){ 

                    $operations = Operation::selectRaw('
                        p.id,
                        p.name,
                        p.monitoring,
                        SUM(  operations.amount ) as totalAvailable                   
                    ')
                    ->leftJoin('projects as p', 'p.id', '=', 'operations.project_id')
                    ->where([
                        'operations.active' => 1,
                        'operations.type' => 'income',
                        'operations.category' => $request->category,
                        'operations.subcategory' => $request->subcategory,
                        'operations.contact_id' => $contact_id 
                    ])
                    ->whereRaw('operations.project_id IS NOT NULL AND operations.project_id  <> 0') 
                    ->groupBy('p.id')
                    ->get()->toArray();

                   
                    $balances = $this->_projectBalances($contact_id);
                    foreach($operations as $key => $item){
                        $operations[$key]['totalAvailable'] = $balances[$item['id']] ?? 0;
                    }
                  
                } 
            }

            $items = $operations;

            return [
                'success' => true,
                'items' => $items
            ];
            
        } catch(\Exception $e) {
            sendNoticationError($e);
            DB::rollback();
            return ['success' => false , 'msg' =>   _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**
     * Edit Operation Data
     *
     * @param User $user
     * @return \Illuminate\Http\Response
     */
    public function show( Request $request )
    {
        try{
            $item = Operation::select('operations.*')
            ->where([ 'operations.id' => $request->item] )->first();
            
            if($item){
                $item->fee = (int) $item->fee;
                $item->monitoring = (int) $item->monitoring;
               
                return ['success' => true ,'item' => $item];
            }else{
                return ['success' => false ,'code' => 404];
            }

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**
     * Update Operation Data
     *
     * @param Group $group
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */

    public function update (Request $request)
    {   
        try{

            $id = $request->id;
            $form = json_decode($request->form, true);

            $item = Operation::where([
                'id'=>$request->id
            ])->first();

            if($request->hasfile('files')){
                $filename = storageFile( $request , 'files' ,  $request->slug , '/uploads/operations/' , true );
            }else{
                $filename = false;
            }                 
            //debugger($form);
            $subCategory = '';
            $category = '';
            $donorId = '';
            $projectId = '';
            if ($form['type'] == 'income') {
                $category = $form['category'];
                $subCategory = $form['subcategory'];
                $donorId = $form['donor_id'];
                $projectId = is_null($form['project_id']) || $form['project_id'] == '' ? 0 : $form['project_id'];
            }

            if ($form['type'] == 'outcome') {
                $category = $form['origin_category'];
                $subCategory = $form['origin_subcategory'];
                $donorId = $form['origin_donor_id'];
                $projectId = is_null($form['origin_project_id']) || $form['origin_project_id'] == '' ? 0 : $form['origin_project_id'];
            }

            $newData = [  
                'date' => $form['date'],
                'contact_id' =>  $form['contact_id'],
                'type' => $form['type'],                          
                //'category' => $form['origin_category'],
                //'subcategory' => $form['origin_subcategory'],
                'category' =>$category,
                'subcategory' => $subCategory,
                //'donor_id' => $form['origin_donor_id'],
                'donor_id' => $donorId,
                //'project_id' => is_null($form['origin_project_id']) || $form['origin_project_id'] == '' ? 0 : $form['origin_project_id'],
                'project_id' => $projectId,
                'reference' => $form['reference'],                
                'mode_id' => $form['mode_id'],
                'amount' => $form['amount'],
                'monitoring' => $form['monitoring'],                
                'fee' => $form['fee'],                  
                'comment' => $form['comment'],
                'files' =>  (is_array($filename) ? json_encode( $filename ) : null)
            ];
            
            if( is_array($filename) ){
                if( $request->filesOld != '' && $request->filesOld !='null'){
                    $filesOld = json_decode( $request->filesOld ,true);
                    $filename = array_merge($filename,$filesOld);
                }
                $newData['files'] = json_encode( $filename );
            }else{
                $newData['files'] = $request->filesOld;;
            }
            
            //$filesRenew = $newData['files'];
            //UDPATE
            $updateItem =  $item;
            $oldValues = $updateItem->toArray(); unset($oldValues['updated_at']);
            if(is_array(json_decode($oldValues['files'],true))){
                foreach (json_decode($oldValues['files'],true) AS $file) {
                    if (!in_array($file,json_decode($newData['files'],true))) {
                        if ($path = publicUploadPath('/uploads/operations/'.safeUploadName($file['file'], false))) {
                            unlink($path);
                        }
                    }
                }
            }
            
        
            $updateItem->fill( $newData );
            $updateItem->save();
            
            $newValues = $updateItem->toArray();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill([
                'type'=>'MODIFICATION',
                'name'=>'Operation modified : '.$updateItem->name,
                'tablename'=>'operations',
                'item'=>$updateItem->id,
                'updates'=>json_encode(compare2arrays($oldValues,$newValues)),
                'group_id'=>authUser('group_id'),
                'user_id'=>authUser('id'),
                'company_id'=>idCompany()
            ])->save();
            

            return [ 'success' => true, 'msg' => _("Elemento modificado correctamente !"), 'data'=> $item ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**
     * Saved Operation Data
     *
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */
    public function store( Request $request)
    {    
        try{
            $form = json_decode($request->form, true);
            $amountRco = null;
            DB::beginTransaction();
            if($request->hasfile('files')){
                $filename = storageFile( $request , 'files' ,  false , '/uploads/operations/' , true );
            }else{
                $filename = false;
            }           
            
            $transfer_reference = date('YmdHis');
            if($form['type'] == 'transfer'){
                $newData = [  
                    'date' => $form['date'],
                    'contact_id' => $form['target_contact_id'],
                    'donor_id' => $form['origin_donor_id'],
                    'category' => $form['origin_category'],                    
                    'subcategory' => $form['origin_subcategory'],                    
                    'project_id' =>  is_null($form['origin_project_id']) || $form['origin_project_id'] == '' ? 0 : $form['origin_project_id'],
                    'contact_type' => 'Donador',
                    'type' => 'outcome',
                    'mode_id' => $form['mode_id'],
                    'transfer' => 1,
                    'amount' => $form['amount'],
                    'comment' => $form['comment'],
                    'transfer_reference' => $transfer_reference,
                    'files' =>  (is_array($filename) ? json_encode( $filename ) : null),
                    'company_id' => idCompany(),
                    'active' => 1
                ];

                $newItem = new Operation;
                $newItem->fill( $newData );
                $newItem->save();
                $operation = $newItem;

                /**SAVE ACTION**/
                $action=new Action;
                $action->fill([
                    'type'=>'CREATION',
                    'name'=>'Operation created : '.$newItem->id,
                    'tablename'=>'operations',
                    'item'=>$newItem->id,
                    'group_id'=>authUser('group_id'),
                    'user_id'=>authUser('id'),
                    'company_id'=>idCompany()
                ])->save();

                $newData = [  
                    'date' => $form['date'],
                    'contact_id' => $form['target_contact_id'],
                    'category' => $form['target_category'],                    
                    'subcategory' => $form['target_subcategory'],
                    'project_id' =>  is_null($form['target_project_id']) || $form['target_project_id'] == '' ? 0 : $form['target_project_id'],
                    'contact_type' => 'Donador',
                    'donor_id' => $form['origin_donor_id'],
                    'type' => 'income',
                    'transfer' => 1,
                    'mode_id' => $form['mode_id'],
                    'amount' => $form['amount'],     
                    'monitoring' => $form['target_monitoring'],
                    'comment' => $form['comment'],
                    'transfer_reference' => $transfer_reference,
                    'files' =>  (is_array($filename) ? json_encode( $filename ) : null),
                    'company_id' => idCompany(),
                    'active' => 1
                ];

                $newItem = new Operation;
                $newItem->fill( $newData );
                $newItem->save();
                $operation = $newItem;

                /**SAVE ACTION**/
                $action=new Action;
                $action->fill([
                    'type'=>'CREATION',
                    'name'=>'Operation created : '.$newItem->id,
                    'tablename'=>'operations',
                    'item'=>$newItem->id,
                    'group_id'=>authUser('group_id'),
                    'user_id'=>authUser('id'),
                    'company_id'=>idCompany()
                ])->save();
          
            }else{

                $newData = [  
                    'date' => $form['date'],
                    'contact_id' =>  $form['contact_id'],                    
                    'type' => $form['type'],
                    'subtype' => 'account',
                    'reference' => $form['reference'],
                    'mode_id' => $form['mode_id'],
                    'amount' => $form['amount'],                        
                    'category' => $form['category'],
                    'subcategory' => $form['subcategory'],                        
                    'project_id' => 0,            
                    'comment' => $form['comment'],
                    'files' =>  (is_array($filename) ? json_encode( $filename ) : null),
                    'company_id' => idCompany(),
                    'active' => 1
                ];

                if($form['type'] == 'income'){                                                       
                    $newData['fee'] = $form['fee'];
                    $newData['contact_type'] = 'Donador';                    
                }else{                                        
                    $newData['contact_type'] = $form['contact_type'];
                    $newData['donor_id'] = $form['origin_donor_id'];
                    $newData['project_id'] = $form['project_id'];
                    $newData['fee'] = 0;
                    $newData['category'] = $form['origin_category'];                   
                    $newData['subcategory'] = $form['origin_subcategory'];
                    $newData['project_id'] =  is_null($form['origin_project_id']) || $form['origin_project_id'] == '' ? 0 : $form['origin_project_id'];
                }
                $newData['monitoring'] = 0;
                if ($form["retain_operating_cost"] == 1) {
                    $amountRco = $this->_calculatedRetainAmount(
                        $form['amount'],
                        $form['fee'],
                        $form['percentage_operating_cost']
                    );
                    $newData['percentage_operating_cost'] = $form['percentage_operating_cost'];
                    $newData['val_operating_cost'] = $amountRco;
                    $newData['transfer_reference'] = $transfer_reference;
                }
                $newItem = new Operation;
                $newItem->fill( $newData );
                $newItem->save();
                $idmov = $newItem->id;
                $operation = $newItem;
    
                /**SAVE ACTION**/
                $action=new Action;
                $action->fill([
                    'type'=>'CREATION',
                    'name'=>'Operation created : '.$newItem->name,
                    'tablename'=>'operations',
                    'item'=>$newItem->id,
                    'group_id'=>authUser('group_id'),
                    'user_id'=>authUser('id'),
                    'company_id'=>idCompany()
                ])->save();
                

                if($form['project_id'] > 0 && $form['type'] == 'income'){
                    
                    // Seregistra la salida
                    $newData = [  
                        'date' => $form['date'],
                        'contact_id' =>  $form['contact_id'],
                        'donor_id' => $form['contact_id'],
                        'type' => 'outcome',
                        'subtype' => 'account',
                        'contact_type' => 'Donador',
                        'reference' => $form['reference'],
                        'mode_id' => $form['mode_id'],
                        'amount' => $form['amount'],                        
                        'category' => $form['category'],
                        'subcategory' => $form['subcategory'],            
                        'comment' => $form['comment'],
                        'files' =>  (is_array($filename) ? json_encode( $filename ) : null),
                        'company_id' => idCompany(),
                        'active' => 1
                    ];
                    $newItem = new Operation;
                    $newItem->fill( $newData );
                    $newItem->save();
                    $operation = $newItem;
        
                    /**SAVE ACTION**/
                    $action=new Action;
                    $action->fill([
                        'type'=>'CREATION',
                        'name'=>'Operation created : '.$newItem->name,
                        'tablename'=>'operations',
                        'item'=>$newItem->id,
                        'group_id'=>authUser('group_id'),
                        'user_id'=>authUser('id'),
                        'company_id'=>idCompany()
                    ])->save();

                    // Registra la entrada

                    $newData = [  
                        'date' => $form['date'],
                        'contact_id' =>  $form['contact_id'],
                        'donor_id' => $form['contact_id'],
                        'type' => 'income',
                        'subtype' => 'account',
                        'reference' => $form['reference'],
                        'mode_id' => $form['mode_id'],
                        'amount' => $form['amount'],                        
                        'category' => $form['category'],
                        'subcategory' => $form['subcategory'],                        
                        'project_id' => $form['project_id'],            
                        'comment' => $form['comment'],
                        'files' =>  (is_array($filename) ? json_encode( $filename ) : null),
                        'company_id' => idCompany(),
                        'active' => 1
                    ];
                    $newItem = new Operation;
                    $newItem->fill( $newData );
                    $newItem->save();
                    $operation = $newItem;
        
                    /**SAVE ACTION**/
                    $action=new Action;
                    $action->fill([
                        'type'=>'CREATION',
                        'name'=>'Operation created : '.$newItem->name,
                        'tablename'=>'operations',
                        'item'=>$newItem->id,
                        'group_id'=>authUser('group_id'),
                        'user_id'=>authUser('id'),
                        'company_id'=>idCompany()
                    ])->save();
                }
                if ($form["retain_operating_cost"] == 1) {
                    $this->_excuteProcessRetain($form, $filename, $amountRco, $idmov, $transfer_reference);
                }
            }    
          
        
            DB::commit();            

            return ['success' => true , 'msg' => _("Elemento creado correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            DB::rollback();
            return ['success' => false , 'msg' =>   _("Oups... Un error ha occurido : ").$e->getMessage()];
        }


    }

    private function _excuteProcessRetain($form, $filename, $amount, $idmov, $transfer_reference = null)
    {
        $transfer_reference = $transfer_reference ?? date('YmdHis');

        $newData = [  
            'date' => $form['date'],
            'contact_id' => 891,
            'donor_id' => $form['contact_id'],
            'category' => $form['category'],                    
            'subcategory' => $form['subcategory'],                    
            'project_id' =>  is_null($form['project_id']) || $form['project_id'] == '' ? 0 : $form['project_id'],
            'contact_type' => 'Donador',
            'type' => 'outcome',
            'mode_id' => $form['mode_id'],
            'transfer' => 1,
            'amount' => $amount,
            'comment' => $form['comment'],
            'transfer_reference' => $transfer_reference,
            'is_co' => 1,
            'files' =>  (is_array($filename) ? json_encode( $filename ) : null),
            'company_id' => idCompany(),
            'active' => 1,
            'percentage_operating_cost' => $form['percentage_operating_cost'],
            'movement_id' => $idmov
        ];

        $newItem = new Operation;
        $newItem->fill( $newData );
        $newItem->save();
        $operation = $newItem;

        /**SAVE ACTION**/
        $action=new Action;
        $action->fill([
            'type'=>'CREATION',
            'name'=>'Operation created : '.$newItem->id,
            'tablename'=>'operations',
            'item'=>$newItem->id,
            'group_id'=>authUser('group_id'),
            'user_id'=>authUser('id'),
            'company_id'=>idCompany()
        ])->save();

        $newData = [  
            'date' => $form['date'],
            'contact_id' => 891,
            'category' => "RESERVA RETENCIÓN CO",                    
            'subcategory' => "RESERVA RETENCIÓN CO",
            'project_id' =>  is_null($form['project_id']) || $form['project_id'] == '' ? 0 : $form['project_id'],
            'contact_type' => 'Donador',
            'donor_id' => $form['contact_id'],
            'type' => 'income',
            'transfer' => 1,
            'mode_id' => $form['mode_id'],
            'amount' => $amount,     
            'monitoring' => $form['target_monitoring'],
            'comment' => $form['comment'],
            'transfer_reference' => $transfer_reference,
            'is_co' => 1,
            'files' =>  (is_array($filename) ? json_encode( $filename ) : null),
            'company_id' => idCompany(),
            'active' => 1,
            'percentage_operating_cost' => $form['percentage_operating_cost'],
            'movement_id' => $idmov
        ];

        $newItem = new Operation;
        $newItem->fill( $newData );
        $newItem->save();
        $operation = $newItem;

        /**SAVE ACTION**/
        $action=new Action;
        $action->fill([
            'type'=>'CREATION',
            'name'=>'Operation created : '.$newItem->id,
            'tablename'=>'operations',
            'item'=>$newItem->id,
            'group_id'=>authUser('group_id'),
            'user_id'=>authUser('id'),
            'company_id'=>idCompany()
        ])->save();
    }

    private function _calculatedRetainAmount($amount, $fee, $percentageRetain)
    {
        $amountWithFee = round(($amount * $fee) / 100, 2);
        $availableAfterFee = ($amount - $amountWithFee);
        $operatingCostAmount = round(($availableAfterFee / ($percentageRetain + 100)) * $percentageRetain, 2);
        return $operatingCostAmount;
    }

    /*** DELETE ***/
    public function delete(Request $request)
    {
        try{
            if (!actionDelete()) {
                return ['success' => false , 'msg' =>  _("Oups... No cuentas con los permisos necesarios para realizar esta accion. Por favor comunicate con el administrador del sistema.") ];
            }
            //SET UNACTIVE
            $item = Operation::where(['id' => $request->id, 'active' => 1, 'company_id' => idCompany()])->firstOrFail();
            if($item){
                $itemRCo = Operation::where(['movement_id' => $request->id, 'company_id' => idCompany()])->get();
                if ($itemRCo) {
                    foreach ($itemRCo as  $value) {
                        Operation::where(['id' => $value['id']])->delete();
                    }
                }
                Operation::where(['id' => $request->id])->delete();
                // /**SAVE ACTION**/
                $action=new Action;
                $action->fill([
                    'type'=>'ELIMINATION',
                    'name'=>'Operacion deleted : '.$item->name,
                    'tablename'=>'operations',
                    'item'=>$item->id,
                    'group_id'=>authUser('group_id'),
                    'user_id'=>authUser('id'),
                    'company_id'=>idCompany()
                ])->save();
               
                if($item->transfer == 1 && $item->transfer_reference!='' && !is_null($item->transfer_reference)){
                    
                    Operation::where(['transfer_reference' => $item->transfer_reference, 'company_id' => idCompany()])->delete();
                }
            }

            return ['success' => true, 'msg' => _("Elemento suprimido correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
    /*** DELETE ***/
    public function replicate(Request $request)
    {
        try{

            //SET UNACTIVE
            $updateItem = Operation::where('company_id', idCompany())->findOrFail($request->id);
            $newItem = $updateItem->replicate();
            $newItem->save();
            /**SAVE ACTION**/
            $action=new Action;
            $action->fill(['type'=>'REPLICATE','name'=>'Operacion replicated : '.$updateItem->name,'tablename'=>'operations','item'=>$updateItem->id,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
            return ['success' => true, 'msg' => _("Elemento duplicado correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    // REPORT FUNCTIONS

    public function donorsOperations(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        try {            
            //QUERY            
            $month = date('m',strtotime($request->month . '-01'));             
            $monthPrev = date('Y-m',strtotime($request->month . "-01 - 1 month"));
            $year = date('Y',strtotime($request->month . '-01')); 
            $operations = [];

            $donors = Contact::select('id','name')->where([
                'category' => 'Donador',                
                'active' => 1
            ])
            // ->whereIn('id', [1])
            ->get();
                               
            foreach($donors as $donor){
                // GET INCOMES
                $incomes = Operation::selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,
                    SUM(amount) AS sumIncomes,
                    SUM(IF(fee IS NOT NULL AND fee <> 0, amount - (amount * (fee / 100)), amount)) AS sumIncomesFee,
                    SUM( IF(fee IS NOT NULL AND fee <> 0, (amount * (fee / 100)), 0)) as sumFee
                ")
                ->whereRaw("
                    contact_id = ".$donor->id."
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
                ->groupBy('category' , 'subcategory')
                ->get();
                
                foreach($incomes as $income){
                    $operations[$donor->id. '-' . $income->category . '-' .$income->subcategory] = [
                        "id" => $donor->id,
                        "donor" => $donor->name,
                        "category" => $income->category,
                        "subcategory" => $income->subcategory,
                        "sumIncomes" => $income->sumIncomes,
                        "sumIncomesFee" => $income->sumIncomesFee,
                        "sumFee" =>  $income->sumFee,
                        "sumTransfersReceipt" => 0,
                        "sumTransfersReceiptPrev" => 0,
                        "sumTransfersSendings" => 0,
                        "sumTransfersSendingsPrev" => 0,
                        "sumTransfersProjectsReceipt" => 0,
                        "sumTransfersProjectsReceiptPrev" => 0,
                        "sumOutcomes" => 0,
                        "sumOutcomesPrev" => 0,
                        "sumRemnants" => 0,
                        "sumRemnantsPrev" => 0,
                        "sumReleases" => 0,
                        "sumReleasesPrev" => 0,
                        "monitoring" => 0,
                        "monitoringTake" => 0,
                        "incomesPrev" => 0
                    ];        
                }                

                // GET TRANSFERS RECEIPT
                $transfersReceipt = Operation::selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,                    
                    SUM(IF(MONTH(date) = ".abs($month).", amount, 0)) as sumTranfersReceipt,
                    SUM(IF(MONTH(date) < ".abs($month).", amount, 0)) as sumTranfersReceiptPrev
                ")
                ->whereRaw("
                    contact_id = ".$donor->id."
                    AND YEAR(date) = ".$year." 
                    AND MONTH(date) <= ".$month." 
                    AND transfer = 1 
                    AND type = 'income'   
                    AND (
                        (project_id <> 0 AND donor_id <> ".$donor->id.") OR
                        (project_id = 0)
                    )
                    AND category <> 'Remanentes' 
                    AND category <> 'Liberaciones' 
                    AND category IS NOT NULL
                    AND active = 1"
                )
                ->groupBy('category' , 'subcategory')
                ->get();
     
                foreach($transfersReceipt as $transfer){             
                    $needle = $donor->id. '-' . $transfer->category . '-' .$transfer->subcategory;
                    if(isset($operations[$needle])){                      
                        $operations[$needle]['sumTransfersReceipt'] = $transfer->sumTranfersReceipt;
                        $operations[$needle]['sumTransfersReceiptPrev'] = $transfer->sumTranfersReceiptPrev;                                                    
                    }else{
                        $operations[$needle] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $transfer->category,
                            "subcategory" => $transfer->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,
                            "sumTransfersReceipt" => $transfer->sumTranfersReceipt,
                            "sumTransfersReceiptPrev" => $transfer->sumTranfersReceiptPrev,
                            "sumTransfersSendings" => 0,
                            "sumTransfersSendingsPrev" => 0,                            
                            "sumTransfersProjectsReceipt" => 0,
                            "sumTransfersProjectsReceiptPrev" => 0,
                            "sumOutcomes" => 0,
                            "sumOutcomesPrev" => 0,
                            "sumRemnants" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleases" => 0,
                            "sumReleasesPrev" => 0,
                            "monitoring" => 0,
                            "monitoringTake" => 0,                            
                            "incomesPrev" => 0
                        ];
                    }                    
                }
                
                // GET TRANSFERS SENDING
                $sumTransfersSendings = Operation::selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,                    
                    SUM(IF(MONTH(date) = ".abs($month).", amount, 0)) as sumTransfersSendings,
                    SUM(IF(MONTH(date) < ".abs($month).", amount, 0)) as sumTransfersSendingsPrev
                ")
                ->whereRaw("
                    donor_id = ".$donor->id."
                    AND YEAR(date) = ".$year." 
                    AND MONTH(date) <= ".$month." 
                    AND transfer = 1
                    AND type = 'outcome'                    
                    AND category <> 'Remanentes' 
                    AND category <> 'Liberaciones'
                    AND category IS NOT NULL
                    AND active = 1"
                )
                ->groupBy('category' , 'subcategory')
                ->get();                
                
                foreach($sumTransfersSendings as $transfer){
                    $needle = $donor->id. '-' . $transfer->category . '-' .$transfer->subcategory;
                    if(isset($operations[$needle])){                                          
                        $operations[$needle]['sumTransfersSendings'] = $transfer->sumTransfersSendings;
                        $operations[$needle]['sumTransfersSendingsPrev'] = $transfer->sumTransfersSendingsPrev;                                                
                    }else{
                        $operations[$needle] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $transfer->category,
                            "subcategory" => $transfer->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,
                            "sumTransfersReceipt" => 0,
                            "sumTransfersReceiptPrev" => 0,     
                            "sumTransfersSendings" => $transfer->sumTransfersSendings,
                            "sumTransfersSendingsPrev" => $transfer->sumTransfersSendingsPrev,
                            "sumTransfersProjectsReceipt" => 0,
                            "sumTransfersProjectsReceiptPrev" => 0,                       
                            "sumOutcomes" => 0,
                            "sumOutcomesPrev" => 0,
                            "sumRemnants" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleases" => 0,
                            "sumReleasesPrev" => 0,
                            "monitoring" => 0,
                            "monitoringTake" => 0,
                            "incomesPrev" => 0
                        ];
                    }
                    
                }

                // GET TRANSFERS RECEIPT
                $transfersReceiptProjects = Operation::selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,                    
                    SUM(IF(MONTH(date) = ".abs($month).", amount, 0)) as sumTransfersProjectsReceipt,
                    SUM(IF(MONTH(date) < ".abs($month).", amount, 0)) as sumTransfersProjectsReceiptPrev
                ")
                ->whereRaw("
                    contact_id = ".$donor->id."
                    AND YEAR(date) = ".$year." 
                    AND MONTH(date) <= ".$month." 
                    AND transfer = 1 
                    AND type = 'income'   
                    AND project_id <> 0 
                    AND donor_id = ".$donor->id."                    
                    AND category <> 'Remanentes' 
                    AND category <> 'Liberaciones' 
                    AND category IS NOT NULL
                    AND active = 1"
                )
                ->groupBy('category' , 'subcategory')
                ->get();
  
                foreach($transfersReceiptProjects as $transfer){
                    $needle = $donor->id. '-' . $transfer->category . '-' .$transfer->subcategory;
                    if(isset($operations[$needle])){
                        $operations[$needle]['sumTransfersProjectsReceipt'] = $transfer->sumTransfersProjectsReceipt;
                        $operations[$needle]['sumTransfersProjectsReceiptPrev'] = $transfer->sumTransfersProjectsReceiptPrev;                                                    
                    }else{
                        $operations[$needle] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $transfer->category,
                            "subcategory" => $transfer->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,
                            "sumTransfersReceipt" => 0,
                            "sumTransfersReceiptPrev" => 0,
                            "sumTransfersSendings" => 0,
                            "sumTransfersSendingsPrev" => 0,
                            "sumTransfersProjectsReceipt" => $transfer->sumTransfersProjectsReceipt,
                            "sumTransfersProjectsReceiptPrev" => $transfer->sumTransfersProjectsReceiptPrev,
                            "sumOutcomes" => 0,
                            "sumOutcomesPrev" => 0,
                            "sumRemnants" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleases" => 0,
                            "sumReleasesPrev" => 0,
                            "monitoring" => 0,
                            "monitoringTake" => 0,                            
                            "incomesPrev" => 0
                        ];
                    }                    
                }

                // GET OUTCOMES
                $outcomes = Operation::selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,                    
                    SUM(IF(MONTH(date) = ".abs($month).", amount, 0)) as sumOutcomes,
                    SUM(IF(MONTH(date) < ".abs($month).", amount, 0)) as sumOutcomesPrev
                ")
                ->whereRaw("
                    donor_id = ".$donor->id."
                    AND YEAR(date) = ".$year." 
                    AND MONTH(date) <= ".$month." 
                    AND transfer = 0
                    AND type = 'outcome'                    
                    AND category <> 'Remanentes' 
                    AND category <> 'Liberaciones' 
                    AND category IS NOT NULL
                    AND active = 1"
                )
                ->groupBy('category' , 'subcategory')
                ->get();

             
                foreach($outcomes as $outcome){                   
                    $needle = $donor->id. '-' . $outcome->category . '-' .$outcome->subcategory;
                    if(isset($operations[$needle])){
                        $operations[$needle]['sumOutcomes'] = $outcome->sumOutcomes;
                        $operations[$needle]['sumOutcomesPrev'] = $outcome->sumOutcomesPrev;                            
                    }else{
                        $operations[$needle] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $outcome->category,
                            "subcategory" => $outcome->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,                            
                            "sumTransfersReceipt" => 0,
                            "sumTransfersReceiptPrev" => 0,
                            "sumTransfersSendings" => 0,
                            "sumTransfersSendingsPrev" => 0,
                            "sumTransfersProjectsReceipt" => 0,
                            "sumTransfersProjectsReceiptPrev" => 0,
                            "sumOutcomes" =>  $outcome->sumOutcomes,
                            "sumOutcomesPrev" =>  $outcome->sumOutcomesPrev,
                            "sumRemnants" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleases" => 0,
                            "sumReleasesPrev" => 0,
                            "monitoring" => 0,
                            "monitoringTake" => 0,
                            "incomesPrev" => 0
                        ];
                    }
                }

                                
                // GET REMNANST AND RELEASES
                $remnants = Operation::selectRaw("                    
                    IFNULL(o.category, '-') as category,
                    IFNULL(o.subcategory, '-') as subcategory,
                    SUM(IF( MONTH(operations.date) = ".abs($month)." AND operations.category = 'Remanentes' , operations.amount, 0)) AS sumRemnants,
                    SUM(IF( MONTH(operations.date) = ".abs($month)." AND operations.category = 'Liberaciones' , operations.amount, 0)) AS sumReleases,
                    SUM(IF( MONTH(operations.date) < ".abs($month)." AND operations.category = 'Remanentes' , operations.amount, 0)) AS sumRemnantsPrev,
                    SUM(IF( MONTH(operations.date) < ".abs($month)." AND operations.category = 'Liberaciones' , operations.amount, 0)) AS sumReleasesPrev
                ")
                ->leftJoin('operations AS o', 'o.transfer_reference', '=' ,'operations.transfer_reference')
                ->whereRaw("
                    operations.donor_id = ".$donor->id."
                    AND YEAR(operations.date) = ".$year." 
                    AND MONTH(operations.date) <= ".$month." 
                    AND operations.transfer = 1                                    
                    AND (operations.category = 'Remanentes' OR operations.category = 'Liberaciones')
                    AND operations.active = 1"
                )
                ->groupBy('o.category' , 'o.subcategory')
                ->get();
                
                          
                // debugger($remnants->toarray());
                foreach($remnants as $remnant){
                    if($remnant->category !='Remanentes' && $remnant->category !='Liberaciones'){                        
                        $needle = $donor->id. '-' . $remnant->category . '-' .$remnant->subcategory;
                        if(isset($operations[$needle])){   
                            $operations[$needle]['sumRemnants'] = $remnant->sumRemnants;
                            $operations[$needle]['sumReleases'] = $remnant->sumReleases;
                            $operations[$needle]['sumRemnantsPrev'] = $remnant->sumRemnantsPrev;
                            $operations[$needle]['sumReleasesPrev'] = $remnant->sumReleasesPrev;
                        }else{
                            $operations[$needle] = [
                                "id" => $donor->id,
                                "donor" => $donor->name,
                                "category" => $remnant->category,
                                "subcategory" => $remnant->subcategory,
                                "sumIncomes" => 0,
                                "sumIncomesFee" => 0,
                                "sumFee" =>  0,
                                "sumTransfersReceipt" => 0,
                                "sumTransfersReceiptPrev" => 0,
                                "sumTransfersSendings" => 0,
                                "sumTransfersSendingsPrev" => 0,
                                "sumTransfersProjectsReceipt" => 0,
                                "sumTransfersProjectsReceiptPrev" => 0,
                                "sumOutcomes" =>  0,
                                "sumOutcomesPrev" => 0,
                                "sumRemnants" => $remnant->sumRemnants,
                                "sumRemnantsPrev" => $remnant->sumRemnantsPrev,
                                "sumReleases" => $remnant->sumReleases,
                                "sumReleasesPrev" => $remnant->sumReleasesPrev,
                                "monitoring" => 0,
                                "monitoringTake" => 0,
                                "incomesPrev" => 0
                            ];
                        }
                    }
                    
                }


                // INCOMES PREV
                $incomesPrev = Operation::selectRaw("
                    IFNULL(category, '-') as category,
                    IFNULL(subcategory, '-') as subcategory,                    
                    SUM( amount - IF(fee IS NOT NULL AND fee <> 0, (amount * (fee / 100)), 0 )) AS incomesPrev
                ")
                ->whereRaw("
                    contact_id = ".$donor->id."
                    AND YEAR(date) = ".$year." 
                    AND MONTH(date) < ".$month." 
                    AND category <> 'Remanentes' 
                    AND category <> 'Liberaciones' 
                    AND category IS NOT NULL
                    AND type = 'income'
                    AND project_id = 0 
                    AND transfer = 0
                    AND active = 1"
                )
                ->groupBy('category' , 'subcategory')
                ->get();

                        
                foreach($incomesPrev as $income){   
                    $needle = $donor->id. '-' . $income->category . '-' .$income->subcategory;                    
                    if(isset($operations[$needle])){                                
                        $operations[$needle]['incomesPrev'] = $income->incomesPrev;                    
                    }else{
                        $operations[$needle] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $income->category,
                            "subcategory" => $income->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,                            
                            "sumTransfersReceipt" => 0,
                            "sumTransfersReceiptPrev" => 0,
                            "sumTransfersSendings" => 0,
                            "sumTransfersSendingsPrev" => 0,
                            "sumTransfersProjectsReceipt" => 0,
                            "sumTransfersProjectsReceiptPrev" => 0,
                            "sumOutcomes" => 0,   
                            "sumOutcomesPrev" => 0,                         
                            "sumRemnants" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleases" => 0,
                            "sumReleasesPrev" => 0,
                            "monitoring" => 0,
                            "monitoringTake" => 0,
                            "incomesPrev" => $income->incomesPrev,
                        ];
                    }                    
                }

                // GET MONITORING
                $subQuery = Operation::selectRaw("
                   IFNULL(operations.category, '-') as category,
                   IFNULL(operations.subcategory, '-') as subcategory,              
                   IF( p.monitoring_type='percent',
                       0,
                       (p.monitoring_fix)
                   ) as monitoringAmount,
                   p.date_from
                   

               ")                
               ->leftJoin('projects AS p',function ($join) use ($request) {
                   $join->on('p.id', '=' ,'operations.project_id');
                   $join->whereRaw('
                       DATE_FORMAT(p.date_from, "%Y-%m") <= "'.$request->month .'"                       
                   ');                    
               })
               ->whereRaw("
                   operations.donor_id = ".$donor->id."                     
                   AND operations.project_id <> 0    
                   AND operations.monitoring_apply <> 0
                   AND operations.active = 1
                   AND p.monitoring_fix <> 0"              
               )
               ->groupBy('p.id')
               ->orderBy('p.date_from');

               
               $monitorings = DB::query()->fromSub($subQuery, 'o')
               ->selectRaw("
                   IFNULL(category, '-') as category,
                   IFNULL(subcategory, '-') as subcategory,                                   
                   SUM(monitoringAmount) as monitoringTake
               ")         
               ->groupBy('o.category', 'o.subcategory')
               ->get();

               
                     
               foreach($monitorings as $monitoring){                    
                    $needle = $donor->id. '-' . $monitoring->category . '-' .$monitoring->subcategory;                    
                    if(isset($operations[$needle])){                             
                        // $operations[$needle]['monitoring'] = $monitoring->monitoringAmount;
                        $operations[$needle]['monitoringTake'] = $monitoring->monitoringTake;                           
                   }else{
                        $operations[$needle] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $monitoring->category,
                            "subcategory" => $monitoring->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,                            
                            "sumTransfersReceipt" => 0,
                            "sumTransfersReceiptPrev" => 0,
                            "sumTransfersSendings" => 0,
                            "sumTransfersSendingsPrev" => 0,
                            "sumTransfersProjectsReceipt" => 0,
                            "sumTransfersProjectsReceiptPrev" => 0,
                            "sumOutcomes" => 0,  
                            "sumOutcomesPrev" => 0,
                            "sumRemnants" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleases" => 0,
                            "sumReleasesPrev" => 0,
                            "monitoring" => 0,
                            "monitoringTake" => $monitoring->monitoringTake,
                            "incomesPrev" => 0,
                       ];
                   }                    
               }

                // GET MONITORING
                $subQuery = Operation::selectRaw("
                   IFNULL(operations.category, '-') as category,
                   IFNULL(operations.subcategory, '-') as subcategory,              
                   IF( p.monitoring_type='percent',
                       0,
                       (p.monitoring_fix)
                   ) as monitoringAmount,
                   p.date_from
                   

               ")                
               ->leftJoin('projects AS p',function ($join) use ($request, $monthPrev) {
                   $join->on('p.id', '=' ,'operations.project_id');
                   $join->whereRaw('
                       DATE_FORMAT(p.date_from, "%Y-%m") <= "'. $monthPrev .'"                       
                   ');                    
               })
               ->whereRaw("
                   operations.donor_id = ".$donor->id."                     
                   AND operations.project_id <> 0
                   AND operations.monitoring_apply <> 0
                   AND operations.active = 1
                   AND p.monitoring_fix <> 0"              
               )
               ->groupBy('p.id')
               ->orderBy('p.date_from');

               
               $monitorings = DB::query()->fromSub($subQuery, 'o')
               ->selectRaw("
                   IFNULL(category, '-') as category,
                   IFNULL(subcategory, '-') as subcategory,                                   
                   SUM(monitoringAmount) as monitoringAmount
               ")         
               ->groupBy('o.category', 'o.subcategory')
               ->get();

               
                     
               foreach($monitorings as $monitoring){                    
                    $needle = $donor->id. '-' . $monitoring->category . '-' .$monitoring->subcategory;                    
                    if(isset($operations[$needle])){                             
                        $operations[$needle]['monitoring'] = $monitoring->monitoringAmount;                                             
                   }else{
                        $operations[$needle] = [
                            "id" => $donor->id,
                            "donor" => $donor->name,
                            "category" => $monitoring->category,
                            "subcategory" => $monitoring->subcategory,
                            "sumIncomes" => 0,
                            "sumIncomesFee" => 0,
                            "sumFee" =>  0,                            
                            "sumTransfersReceipt" => 0,
                            "sumTransfersReceiptPrev" => 0,
                            "sumTransfersSendings" => 0,
                            "sumTransfersSendingsPrev" => 0,
                            "sumTransfersProjectsReceipt" => 0,
                            "sumTransfersProjectsReceiptPrev" => 0,
                            "sumOutcomes" => 0,  
                            "sumOutcomesPrev" => 0,
                            "sumRemnants" => 0,
                            "sumRemnantsPrev" => 0,
                            "sumReleases" => 0,
                            "sumReleasesPrev" => 0,
                            "monitoring" => $monitoring->monitoringAmount,
                            "monitoringTake" => 0,
                            "incomesPrev" => 0,
                       ];
                   }                    
               }

            }           
           

            
            return Datatables::of($operations)->make(true);

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    public function getTransfers($donor, $month, $year)
    {
        try{

          
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
    
    public function getOutomes(Request $donor)
    {

    }

    public function getRemnants(Request $donor)
    {

    }

    public function getProjectsBalance(Request $donor)
    {

    }

    /**
     * What each project holds of a donor's money, by project id. Counts every classification,
     * since a project can receive money in one and spend it in another.
     */
    private function _projectBalances($donorId)
    {
        $incomes = Operation::selectRaw('project_id, SUM(amount) as total')
            ->where(['active' => 1, 'type' => 'income', 'contact_id' => $donorId])
            ->whereRaw('project_id IS NOT NULL AND project_id <> 0')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        $outcomes = Operation::selectRaw('project_id, SUM(amount) as total')
            ->where(['active' => 1, 'type' => 'outcome', 'donor_id' => $donorId])
            ->whereRaw('project_id IS NOT NULL AND project_id <> 0')
            ->groupBy('project_id')
            ->pluck('total', 'project_id');

        return $incomes->map(fn ($total, $project) => $total - ($outcomes[$project] ?? 0))->all();
    }

    /**
     * Donor ids the user is limited to, or null when they can see every donor.
     */
    private function _assignedDonorIds()
    {
        $donors = $this->getIdDonorAssigned(null);
        if (empty($donors)) {
            return null;
        }
        return array_values(array_filter(array_map('intval', explode(',', $donors))));
    }

     private function _getProjects()
     {
        $proyectAssing = idProyects();
        // Only what the filters show: every column of every project made this response ~2 MB.
        $projects = DB::table('projects as p')->select('p.id', 'p.name')->where([
            'active' => 1
        ])->orderBy('p.name');
        if ($proyectAssing != 'all') {
            $projects->whereRaw('p.id IN('.$proyectAssing.')');
        }
        return $projects->get();
    }

    private function _getProjectsActive()
    {
        $proyectAssing = idProyects();
        $projects = DB::table('projects as p')->select('id', 'name', 'monitoring', 'date_from', 'date_to', 'donors', 'allies')
            ->where([
                'active' => 1
            ]);
        if ($proyectAssing != 'all') {
            $projects->whereRaw('id IN('.$proyectAssing.')');
        }
        return $projects->get();
    }

    private function _getAdmon()
    {
        $proyectAssing = idProyects();
        $admon = DB::table('operations as o')
            ->selectRaw("
                SUM( 
                    IF(o.type='income', 
                        (( IF(o.fee = 0 OR o.fee IS NULL, 0, o.fee/100) ) * o.amount) 
                        ,0 
                    )
                ) AS fee,

                SUM( 
                    IF(o.type='income', 
                        ( (IF(p.monitoring = 0 OR p.monitoring IS NULL, 0, p.monitoring/100)) * o.amount )
                        , 0
                    ) 
                ) AS monitoring
            ")
            ->leftJoin('projects AS p', 'o.project_id', '=', 'p.id')
            ->whereIn('o.type',['outcome', 'income'])
            ->where('o.active', 1);
        $donorsAssing = $this->getIdDonorAssigned($proyectAssing);
        if ($proyectAssing != 'all') {
            $admon->whereRaw('(o.contact_id IN('.$donorsAssing.') OR o.donor_id IN('.$donorsAssing.'))');
        }
        return $admon->first();
    }

    private function _getCategories()
    {
        $proyectAssing = idProyects();
        $categories = Operation::selectRaw('
            operations.category,
            SUM( IF(operations.type ="income", ( operations.amount - ( operations.amount * (IF(operations.fee = 0 OR operations.fee IS NULL, 0, operations.fee/100)) )  - ( operations.amount * (IF(p.monitoring = 0 OR p.monitoring IS NULL, 0, p.monitoring/100))) ) , -amount)) as total,
            SUM( IF(operations.project_id = 0, IF(operations.type ="income", ( operations.amount - ( operations.amount * (IF(operations.fee = 0 OR fee IS NULL, 0, operations.fee/100)) )  - ( amount * (IF(p.monitoring = 0 OR p.monitoring IS NULL, 0, p.monitoring/100))) ), -operations.amount), 0)) as totalAvailable,
            SUM( IF(operations.project_id <> 0, IF(operations.type ="income", ( operations.amount - ( operations.amount * (IF(operations.fee = 0 OR fee IS NULL, 0, operations.fee/100)) )  - ( amount * (IF(p.monitoring = 0 OR p.monitoring IS NULL, 0, p.monitoring/100))) ), -operations.amount), 0)) as totalNotAvailable,
            SUM( IF(operations.type ="income", ( operations.amount * (IF(operations.fee = 0 OR operations.fee IS NULL, 0, operations.fee/100)) )  , 0 )) as fee,
            SUM( IF(operations.type ="income", ( operations.amount * (IF(p.monitoring = 0 OR p.monitoring IS NULL, 0, p.monitoring/100)) )  , 0 )) as monitoring
        ')
        ->leftJoin('projects AS p', 'operations.project_id', '=', 'p.id')
        ->where([
            'operations.active' => 1
        ]);
        if ($proyectAssing != 'all') {
            $categories->whereRaw('operations.project_id IN('.$proyectAssing.')');
        }
        return $categories->groupBy('operations.category')->get();
        
    }

    private function _getType()
    {
        $type = Operation::select(
            DB::raw('count(id) as count, 
                type  as value, 
                IF(type = "income", "'._('Ingresos').'", IF( type="outcome", "'._('Egresos').'", "'._('Balance').'" ) ) as name'
            ))
            ->where([ 'active' => 1 , 'company_id' => idCompany()])
            ->where(
                function($query){
                    $query->where( 'type' ,'=' , "income" )
                            ->orwhere( 'type' , '=' , "outcome")
                            ->orwhere( 'type' , '=' , "balance");
            });
        $donorsAssing = $this->getIdDonorAssigned("");
        if (!empty($donorsAssing)) {
            $type->whereRaw('(contact_id IN('.$donorsAssing.') OR donor_id IN('.$donorsAssing.'))');
        }
        return $type->groupBy('type')->orderBy('count', 'desc')->get()->toArray();
    }
    private function _getTypeTotal()
    {
        $typeTotal = Operation::select(DB::raw('count(id) as count, "all" as value, "'._("TODO").'" as name'))
        ->where([ 'active' => 1 , 'company_id' => idCompany()])
        ->where(
            function($query){
                $query->where( 'type' ,'=' , "income" )
                        ->orwhere( 'type' , '=' , "outcome")
                        ->orwhere( 'type' , '=' , "balance");
        });
        $donorsAssing = $this->getIdDonorAssigned("");
        if (!empty($donorsAssing)) {
            $typeTotal->whereRaw('(contact_id IN('.$donorsAssing.') OR donor_id IN('.$donorsAssing.'))');
        }

        return $typeTotal->get()->toArray()[0];
    }

    private function getInfoMonth($keyMonth, $data)
    {
        $arrayMonth = [
            1 =>"Enero",
            2 =>"Febrero",
            3 =>"Marzo",
            4 =>"Abril",
            5 =>"Mayo",
            6 =>"Junio",
            7 =>"Julio",
            8 =>"Agosto",
            9 =>"Septiembre",
            10 =>"Octubre",
            11 =>"Noviembre",
            12 =>"Diciembre"
        ];
        $currentMonth = $arrayMonth[$keyMonth];
        $monthCode = 1;
        $monthAmount = 0;
        foreach ($data as $key => $value) {
            if (isset($value->month)) {
                if ($value->month == $currentMonth) {
                    $monthAmount = $value->amount;
                }
            }
        }
        return array(
            "monthCode" => $keyMonth,
            "monthAmount" => $monthAmount
        );
    }

    private function _getContacts($projects)
    {
        $donorsAssing = $this->getIdDonorAssigned($projects);
        $contacs = Contact::selectRaw(
            'id,
            name as contact,
            category,
            CONCAT(name, " - ", category) as name, 
            IF(category = "Donador", 1, IF(category = "Aliado", 2, 3)) as category_id
            '
        )
        ->where([
            'active' => 1, 
            'company_id' => idCompany()
        ])
        
        ->whereIn('category', ['Donador', 'Aliado', 'Proveedor'])
        ->orderBy('category_id')
        ->orderBy('name');
        if (!empty($donorsAssing)) {
            $contacs->whereRaw('id IN('.$donorsAssing.')');
        }
        return $contacs->get();
    }

    private function _getConctactsActive($projects, $applyFilter = true)
    {
        $donorsAssing = $this->getIdDonorAssigned($projects);
        $contacs = Contact::select( 'name AS label', 'id AS value', 'category')
            ->where([
                'active' => 1,
                'company_id' => idCompany()
            ]);
        if (!empty($donorsAssing) && $applyFilter) {
            $contacs->whereRaw('id IN('.$donorsAssing.')');
        }
        return $contacs->get();
    }

    private function getIdProyectAssings()
    { 
        $idProyects = null;
        $idDonors = null;
        $proyectAssing = idProyects();
        $donorsAssing = idDonorsAvailabe();
        if ($donorsAssing != 'all') {
            $idDonors = $donorsAssing;
        }
        if (!empty($idDonors) && $proyectAssing != 'all') {
            $idProyectAssings = $idProyects.', '.$idDonors;
        } else if ($proyectAssing != 'all' && empty($idDonors)) {
            $idProyectAssings = $proyectAssing;
        } else if ($proyectAssing === 'all' && !empty($idDonors)) {
            $idProyectAssings = $idDonors;
        } else {
            $idProyectAssings = null;
        }
        
        return $idProyectAssings;
    }

    private function getIdDonorAssigned($projects)
    { 
        $donorsIds = null;
        $donorsAssing = idDonorsAvailabe();
        if ($donorsAssing != 'all') {
            $donorsIds = $donorsAssing;
        }
        return $donorsIds;
    }
}