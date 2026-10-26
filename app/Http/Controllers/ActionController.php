<?php

namespace App\Http\Controllers;

use App\Action;
use App\User;
use DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ActionController extends Controller {

    /*** INDEX ***/
    public function index() {
        try {
            
            //QUERY
            $data = Action::where([ 'active' => 1 , 'company_id' => idCompany()])->get();
            return ['success' => true , 'data' => $data];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** SELECT ***/
    public function select(Request $request) {
        try {

            //QUERY
            $data = DB::table('actions as a')
                        ->where(['a.active' => 1 , 'a.company_id' => idCompany()])
                        ->whereRaw('DATE(a.updated_at) >= ? AND DATE(a.updated_at) <= ?', [$request->from, $request->to])
                        ->join('users as u', 'u.id', '=', 'a.user_id')
                        ->select(DB::raw('a.updated_at, u.user, a.type, a.name, CONCAT(a.tablename,"->",a.item) as element, a.updates'));
            return Datatables::of($data)
                    ->rawColumns(['updates'])
                    ->make(true);

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /*** SHOW ***/
    public function show(Request $request) {
        try {
            
            //QUERY
            $data = Action::where(['actions.id'=>$request->id, 'actions.active' => 1 , 'actions.company_id' => idCompany()])->join('users', 'users.id', '=', 'actions.user_id')->select('actions.*', 'users.user as user')->firstOrFail();
            return ['success' => true , 'data' => $data];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

}
