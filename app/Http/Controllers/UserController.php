<?php

namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use App\User;
use App\UserGroup;
use App\UserRole;
use Validator;
use Auth;
use App\Notifications;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Http\Controllers\Auth\ForgotPasswordController;

use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    use SendsPasswordResetEmails;
   
    /**
     * Set Session.
     *
     * @return \Illuminate\Http\Response
     */
    public function session(Request $request)
	{
        try{

            //SET TIMEZONE
            config( ['app.timezone' => getCfg('timezone','company') ]);
        
            //SET AUTH DATA
            $user = $request->user();
            $user = setUserPrivileges($user);
         
            return $user;
            
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }
    
    /*
    |--------------------------------------------------------------------------
    | Config Users
    |--------------------------------------------------------------------------
    */

    /**
     * Show User List.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $users = User::where([ 'active' => 1 , 'company_id' => idCompany(), ['name' , '!=' , 'support'] ]   )->with('group')->get();
            return ['success' => true ,'users' => $users];
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
            $user = User::find($request->user);
            $user->group_additionnals = explode( ',' , $user->group_additionnals);
            $user->menu_ids =  explode( ',' , $user->menu_ids);
            $user->donors =  ($user->donors!='') ? array_map('intval', explode(',',$user->donors)) : '';
            return ['success' => true ,'user' => $user];
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
            if($request->exists('privilegesPanel')){
                
                $privilegesPanel =  trim($request->privilegesPanel, ',' );
            }else{
                $privilegesPanel = '';
            }

            if($request->hasfile('img')){
               
                $filename = storageImage( $request , 'img' ,  $request->user , '/uploads/users/' , true ,  70 , 70);
            }else{
                $filename = false;
            }
            $newData = [  
                        'user' => $request->user,
                        'name' => $request->name,
                        'date_admission' => $request->date_admission,
                        'email' => $request->email,
                        'phone' => $request->phone,
                        'group_id' => $request->group_id,
                        'role_id' => $request->role_id,
                        'menu_ids' => $privilegesPanel,
                        'donors'=> $request->donors,
                        'conta_data'=> $request->show_data_cont,
                        'action_delete'=> $request->action_delete
            ];
            if($request->password!=''){

                $newData['password'] = \Hash::make( $request->password);
            }
            if( $filename )
            {
                $newData['img'] = '/uploads/users/'.$filename; 
            }

            if( $request->signature != '' )
            {
                $newData['signature'] = $request->signature; 
            }
            
            $updateItem = User::where(['id'=>$request->id, 'active' => 1 , 'company_id' => idCompany()])->first();
            $updateItem->fill( $newData );
            $updateItem->save();

            return [ 'success' => true, 'msg' => _("Elemento modificado correctamente !") ];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**
     * New User Data
     *
     * @return \Illuminate\Http\Response
     */
    public function data()
    {
        try{
            $user = new User;
            $groups = UserGroup::where(['active' => 1, 'company_id' => idCompany(), ['name' , '!=' , 'root'] ])->get();
            $roles = UserRole::where(['active' => 1, 'company_id' => idCompany()])->get();
            $treeview_ = getDataPanelNavigation('' , 0, true);
            $treeview =  makeTreeViewPanelNavigation(getDataPanelNavigation_tree('' , 0, true));
            $success = true;
            return compact('success', 'groups', 'roles', 'treeview' ,'treeview_');
        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()];
        }
    }

    /**
     * Saved User Data
     *
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */
    public function store( Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'user' => 'unique:users'
            ]);
            
            if(!$validator->passes()){
                return ['success' => false, 'msg' => _("Oups... El nombre de usuario ya esta en uso, por favor seleccione uno diferente.")];
            }


            if($request->exists('privilegesPanel')){
                
                $privilegesPanel =  trim($request->privilegesPanel, ',' );
            }else{
                $privilegesPanel = '';
            }

            if($request->hasfile('img')){
                $filename = storageImage( $request , 'img' ,  $request->user , '/uploads/users/' , true ,  70 , 70);
            }else{
                $filename = false;
            }
            $confirmation_code = \Illuminate\Support\Str::random(30);
            $newData = [  
                        'user' => $request->user,
                        'name' => $request->name,
                        'date_admission' => $request->date_admission,
                        'email' => $request->email,
                        'phone' => $request->phone,
                        'group_id' => $request->group_id,
                        'menu_ids' => $privilegesPanel,
                        'color' => $request->color,
                        'token'=>$confirmation_code,
                        'role_id' => $request->role_id,
                        'password'=> \Hash::make( $request->password),
                        'company_id' => idCompany(),
                        'donors'=> $request->donors,
                        'conta_data'=> $request->show_data_cont,
                        'action_delete'=> $request->action_delete
            ];

            if( $filename ){
                $newData['img'] = '/uploads/users/'.$filename; 
            }

            if( $request->signature != '' ){
                $newData['signature'] = $request->signature; 
            }
        
            $user = new User;
            $user->fill( $newData );
            $user->save();
           
            return ['success' => true , 'msg' => _("Elemento creado correctamente !")];

        } catch(\Exception $e) {
            sendNoticationError($e);
            return ['success' => false , 'msg' =>   _("Oups... Un error ha occurido : ").$e->getMessage()];
        }


    }

    protected function broker()
    {
        return Password::broker('name');
    }

    public function delete( Request $request)
    {
        try{
            if (!actionDelete()) {
                return ['success' => false , 'msg' =>  _("Oups... No cuentas con los permisos necesarios para realizar esta accion. Por favor comunicate con el administrador del sistema.") ];
            }
            $user = User::find($request->user);
            $user->update(
                [  'active' => 0 ]
            );
            return ['success' => true, 'msg' => _("Elemento suprimido correctamente !")];
        } catch(\Exception $e) {
            sendNoticationError($e);

            $response = ['success' => false , 'msg' =>  $e->getMessage()];

        }
        return $response;  
    }

    public function userSendNotification(Request $request, User $user )
    {
        $response = \Password::broker()->sendResetLink(
            $request->only('email')
        );
        $response == \Password::RESET_LINK_SENT
                    ? $this->sendResetLinkResponse($response)
                    : $this->sendResetLinkFailedResponse($request, $response);
        
        return response()->json(['success' => true]);
    }
    

    


}