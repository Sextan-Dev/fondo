<?php

namespace App\Http\Middleware;

use App\User;
use App\UserGroup;
use App\UserRole;
use Closure;

class ManageUsers
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        $menus = trim((string) ($user->menu_ids ?: $user->group?->menu_ids), ',');
        $menu = \App\Menu::where('route', 'UserList')->where('active', 1)->value('id');
        if (!isRoot() && !isAdmin() && (!$menu || !in_array((string) $menu, explode(',', $menus), true))) {
            return response()->json(['success' => false, 'msg' => 'Acceso no autorizado.'], 403);
        }
        if ($request->is('api/user/show', 'api/user/delete', 'api/user/update')) {
            $id = $request->is('api/user/update') ? $request->input('id') : $request->input('user');
            $target = User::where('company_id', idCompany())->find($id);
            if (!$target || (!isRoot() && strtolower((string) $target->group?->name) === 'root')) {
                return response()->json(['success' => false, 'msg' => 'Usuario no disponible.'], 403);
            }
        }
        if ($request->is('api/user/store', 'api/user/update')) {
            $group = UserGroup::where('company_id', idCompany())->where('active', 1)->find($request->input('group_id'));
            $role = UserRole::where('company_id', idCompany())->where('active', 1)->find($request->input('role_id'));
            if (!$group || !$role || (!isRoot() && strtolower((string) $group->name) === 'root')) {
                return response()->json(['success' => false, 'msg' => 'Grupo o rol no permitido.'], 403);
            }
        }
        return $next($request);
    }
}
