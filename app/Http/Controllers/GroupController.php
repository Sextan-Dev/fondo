<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\UserGroup;
use Validator;
use Auth;

class GroupController extends Controller
{    
    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    */

    /**
     * Show list of Grups.
     *
     * @return \Illuminate\Http\Response
     */
    public function groups()
    {
        $groups = UserGroup::where(['visible' => 1 , 'company_id' => idCompany() , 'active' => 1])->get();
        return view( $this->viewPath . 'groups.list')->with(['groups' => $groups ]);
    }

    /**
     * Edit User Group
     *
     * @param Group $group
     * @return \Illuminate\Http\Response
     */
    public function groupEdit( UserGroup $group )
    {
        $panel =  getDataPanelNavigation('' , 0, true);
        return view( $this->viewPath . 'groups._form')->with(['group' => $group, 'panel' => $panel, 'action' => _('Editar') ]);
    }

    /**
     * Update Group Data
     *
     * @param Group $group
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */
    public function groupUpdate (UserGroup $group, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group' => 'required|',
            'acronym' => 'required|',
            'description' => 'max:255|',
        ]);

        if(!$validator->passes())
        {
            return response()->json(['success' => false, 'error' => $validator->errors()->all()]);
        }

        if($request->exists('treeviewPanel'))
        {
            $privilegesPanel =  implode( ',' ,$request->treeviewPanel );
        }
        else
        {
            $privilegesPanel = '';
        }

        $group->update(
            [  'group' => $request->group , 'acronym' => $request->acronym , 'icon' => $request->icon, 'description' => $request->description, 'menu_id' => $privilegesPanel ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * New Group Data
     *
     * @return \Illuminate\Http\Response
     */
    public function groupNew()
    {
        $group = new UserGroup;
        $panel =  getDataPanelNavigation('' , 0, true);
        return view( $this->viewPath . 'groups._form')->with(['group' => $group, 'panel' => $panel, 'action' => _('Nuevo')]);
    }

    /**
     * Saved Group Data
     *
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */
    public function groupStorage ( Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group' => 'required|',
            'acronym' => 'required|unique:groups,acronym',
            'description' => 'max:255|',
        ]);

        if(!$validator->passes())
        {
            return response()->json(['success' => false ,'error' => $validator->errors()->all()]);
        }

        if($request->exists('treeviewPanel'))
        {
            $privilegesPanel =  implode( ',' ,$request->treeviewPanel );
        }
        else
        {
            $privilegesPanel = '';
        }
        $group = new UserGroup;
        $group->fill(
            [  'group' => $request->group , 'acronym' => $request->acronym ,'icon' => $request->icon, 'company_id' => idCompany() , 'description' => $request->description, 'menu_id' => $privilegesPanel ]
        );

        $group->save();

        return response()->json(['success' => true]);
    }

    /**
     * Delete Group Data
     *
     * @param Request $request
     * @return \Illuminate\Http\Response Json
     */
    public function groupDelete( Request $request )
    {
        if($request->ajax())
        {
            $group = UserGroup::find($request->group);
            $group->update(
                [  'active' => 0 ]
            );
            //$group->delete($request->group);
        }
        else
        {
            return response()->json(['success' => false]);            
        }

        return response()->json(['success' => true]);
    }
}