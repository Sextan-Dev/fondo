<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\User;
use App\Action;
use App\Notifications;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Illuminate\Support\Facades\Auth;

use Tymon\JWTAuth\Token;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;

class AuthenticateController extends Controller
{
	public function authenticate(Request $request)
	{
		try
		{
			if(!$token = JWTAuth::attempt(array_merge($request->only('user', 'password'), ['active' => 1])))
			{
				return response()->json([ 'success' => false,  'msg' => 'Invalid login credential'], 401);
			}
		}
		catch(JWTException $e)
		{
			report($e);
			return response()->json([ 'success' => false, 'msg' => 'Could not create token'], 500);
		}

		$success = true;
		$user = $request->user();
		$user = setUserPrivileges($user);

		
		//$request->session()->put('user_id', '11');  
		//$request->session()->push('user_id', '11');

		 /**SAVE ACTION NEW LOGIN**/
		$action=new Action;
		$action->fill(['type'=>'LOGIN','name'=>'NEW LOGIN USER : '. authUser('id').'-'.authUser('name') ,'tablename'=>'session','item'=>null,'group_id'=>authUser('group_id'),'user_id'=>authUser('id'),'company_id'=>idCompany()])->save();
		
		
		return response()->json( compact('success', 'token', 'user'));
	}

	public function logout()
	{
		try{
		
			JWTAuth::parseToken()->invalidate();
			\Session::flush();
			Auth::logout();			
		
			return response()->json([ 'success' => true,  'msg' => 'Logout Success!!']);
		}
		catch( \Exception $e ){
			sendNoticationError($e);
			return response()->json(['success'=>false,'msg' =>  _("Oups... Un error ha occurido : ").$e->getMessage()]);
		}
	}
	
}
