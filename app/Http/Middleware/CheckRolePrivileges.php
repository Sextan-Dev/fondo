<?php

namespace App\Http\Middleware;

use Session;
use Closure;

class CheckRolePrivileges {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle($request, Closure $next, ...$rolesAccepted) {
        
        if( in_array( authUser( ['role', 'slug' ]), $rolesAccepted ) ){
            return $next($request);
        }else{
            return response(['success' => false , 'msg' =>  _("Oups... No cuentas con los permisos necesarios para realizar esta accion. Por favor comunicate con el administrador del sistema.") ]);
        }

    }
}
