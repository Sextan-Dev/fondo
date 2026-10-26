<?php

namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use App\Account;
use Illuminate\Support\Facades\DB;
use DataTables;
use Validator;
class AccountController extends Controller
{

    /**
     * Show Accounts List.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $accounts = Account::where([ 'active' => 1 , 'company_id' => idCompany()] )->get();
            return Datatables::of($accounts)->make(true);

          //  return  $accounts;
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }


    /**
     * Edit User Data
     *
     * @param User $user
     * @return \Illuminate\Http\Response
     */
    public function show( Request $request )
    {
        try{
            $item = Account::where('company_id', idCompany())->findOrFail($request->item);
            return ['success' => true ,'item' => $item];
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**
     * Update User Data
     *
     * @param Group $group
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */

    public function update (Request $request)
    {       
        try{
            if($request->hasfile('img')){
                $filename = storageImage( $request , 'img' ,  $request->slug , '/uploads/accounts/' , false);
            }else{
                $filename = false;
            }

            $newData = [  
                'name' => $request->name,
                'slug' => $request->slug,
                'bank' => $request->bank,
                'account' => $request->account,
                'swift' => $request->swift,
                'iban' => $request->iban
            ];

            if( $filename ){
                $newData['img'] = '/uploads/accounts/'.$filename;
            }
            
           // ebugger( $newData);
            $updateItem = Account::where(['id'=>$request->id, 'active' => 1 , 'company_id' => idCompany()])->first();
            $updateItem->fill( $newData );
            $updateItem->save();

            return [ 'success' => true, 'msg' => _("Elemento modificado correctamente !") ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**
     * Saved Account Data
     *
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */
    public function store( Request $request)
    {   
        try{
            $validator = Validator::make($request->all(), [
                'slug' => 'unique:accounts'
            ]);
            
            if(!$validator->passes()){
                return ['success' => false, 'msg' => 'Oups... La clave corta que elegiste ya esta en uso, por favor seleccione una deiferente.'];
            }

            if($request->hasfile('img')){
                $filename = storageImage( $request , 'img' ,  $request->slug , '/uploads/accounts/' , false);
            }else{
                $filename = false;
            }
            
            $newData = [  
                'name' => $request->name,
                'slug' => $request->slug,
                'bank' => $request->bank,
                'account' => $request->account,
                'swift' => $request->swift,
                'iban' => $request->iban
            ];

            if( $filename ){
                $newData['img'] = '/uploads/accounts/'.$filename;
            }else{
                $newData['img'] = null;
            }

            $newData['company_id'] = idCompany();

            $newItem = new Account;
            $newItem->fill( $newData );
            $newItem->save();
        
            return ['success' => true , 'msg' => _("Elemento creado correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>   _("Oups... Un error ha occurido : ").$e->getMessage()];
        }


    }

    /**
     * Deleted Account Data
     *
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */
    public function delete( Request $request)
    {
        try{
            $item = Account::where('company_id', idCompany())->findOrFail($request->item);
            $item->update(
                [  'active' => 0 ]
            );
            return ['success' => true, 'msg' => _("Elemento suprimido correctamente !")];
        } catch(\Exception $e) {
            sendNoticationError($e);
            $response = ['success' => false , 'msg' =>  $e->getMessage()];
        }
        return $response;  
    }
}