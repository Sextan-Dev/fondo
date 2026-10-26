<?php
use Illuminate\Support\Facades\DB;
/**
  * Obtain and assembled structure for Menu Panel 
  * @param int $parent_id
  * @return array
  */

function getDataPanelNavigation( $privileges , $parent_id = 0 , $internal = false )
{
	$dataPanel = [];
	$children = DB::table('menus')
					->select(DB::raw( "count(*) as childs_count, parent_id" ))
					->where('menus.active', 1);
	$children = ( isRoot() || $internal || isAdmin() ) ? $children->groupby('parent_id') : $children->whereIn('menus.id', explode(',', trim( $privileges, ',') ) )->groupby('parent_id' );
	$structure =  DB::table('menus')->select( 
										'menus.id', 
										'menu_groups.name as menu_group',
										'menu_groups.order as menu_group_order', 
										'menus.name', 
										'menus.route', 
										'menus.icon', 
										'menus.parent_id', 
										'menus.order',
										'parent.childs_count')
				->leftJoin('menu_groups', 'menu_groups.id', '=', 'menus.menu_group_id')
				->leftJoin( DB::raw( "({$children->toSql()}) as parent" ), 'menus.id' , '=' , 'parent.parent_id') 
				->mergeBindings($children)
				->where('menus.parent_id', $parent_id )
				->where('menus.active', 1)->orderby('menu_groups.order')->orderby('menus.order');

	$structure = ( isRoot() || $internal || $privileges == '' || !$privileges ) ? $structure->get() : $structure->whereIn('menus.id', explode(',', trim( $privileges, ',')))->get();
	$structure = collect($structure)->map(function($x){ return (array) $x; })->toArray(); 

	foreach ( $structure as $key => $item )
	{
		if( $item['childs_count'] > 0 )
		{
			$item['childs'] =   getDataPanelNavigation( $privileges, $item['id'] , $internal );					
		}
		else
		{
			$item['childs'] = null;					
		}
		if( $item['parent_id'] == 0 )
		{
			$dataPanel[$item['menu_group_order']][$item['menu_group']][] = $item;
		}
		else
		{
			$dataPanel[] = $item;
		}
	}


    return $dataPanel;
}

/**
  * Obtain and assembled structure for Menu Panel 
  * @param int $parent_id
  * @return array
  */

function getDataPanelNavigation_tree( $privileges , $parent_id = 0 , $internal = false )
{
	$dataPanel = [];
	$children = DB::table('menus')
					->select(DB::raw( "count(*) as childs_count, parent_id" ))
					->where('menus.active', 1);
	$children = ( isRoot() || $internal || isAdmin() ) ? $children->groupby('parent_id') : $children->whereIn('menus.id', explode(',', trim( $privileges, ',') ) )->groupby('parent_id' );
	$structure =  DB::table('menus')->select( 
										'menus.id', 
										'menu_groups.name as menu_group',
										'menu_groups.order as menu_group_order', 
										'menus.name', 
										'menus.route', 
										'menus.icon', 
										'menus.parent_id', 
										'menus.order',
										'parent.childs_count')
				->leftJoin('menu_groups', 'menu_groups.id', '=', 'menus.menu_group_id')
				->leftJoin( DB::raw( "({$children->toSql()}) as parent" ), 'menus.id' , '=' , 'parent.parent_id') 
				->mergeBindings($children)
				->where('menus.parent_id', $parent_id )
				->where('menus.active', 1)->orderby('menu_groups.order')->orderby('menus.order');

	$structure = ( isRoot() || $internal || isAdmin()) ? $structure->get() : $structure->whereIn('menus.id', explode(',', trim( $privileges, ',')))->get();
	$structure = collect($structure)->map(function($x){ return (array) $x; })->toArray(); 

	foreach ( $structure as $key => $item )
	{
		if( $item['childs_count'] > 0 )
		{
			$item['childs'] =   getDataPanelNavigation( $privileges, $item['id'] , $internal );					
		}
		else
		{
			$item['childs'] = null;					
		}
		if( $item['parent_id'] == 0 )
		{
		$dataPanel[ $item['menu_group'] ][] = $item;
		}
		else
		{
			$dataPanel[] = $item;
		}
	}


	return $dataPanel;
}

/**
 * Build the HTML of Menu Panel with user privileges
 * @param array $menu
 * @param array $uri
 * @return string
 */

function makePanelNavigation( $menu , $activeModule = 'home' )
{
	$panel = '';
	$classActive = 'active';
	if( is_array( $menu ) )
	{
		foreach ($menu as $key => $items ) 
		{
			if( !is_numeric( $key ) )
			{
				$panel .= '<p class="menu-title">' . $key . '</p>';
				$panel .= '<ul>' . makePanelNavigation( $items , $activeModule ) . '</ul>';
			}
			else
			{
				if( $activeModule != $items['menu'] )
				{
					$classActive = '';    				
				}

    			if( $items['childs_count'] > 0 )
    			{
    				$panel .=	'<li class="'.$classActive.'">
									<a href="javascript: void(0);">
										<span class="arrow"></span>
										<i class="'.$items['icon'].'"></i>
										<span class="title">'.$items['menu'].'</span>
									</a>
									<ul class="sub-menu" >
									'.makePanelNavigation( $items['childs'] , $activeModule ).'
									</ul>
								</li>';
    			}
    			else
    			{
    				$panel .=	'<li class="'.$classActive.'">
										<a href="'. url($items['route']) .'" class="waves-effect  waves-light">
											<i class="'.$items['icon'].'"></i>
											<span class="title">'.$items['menu'].'</span>
										</a>
									</li>';
    			}
			}    			
		}
	}
	return $panel;
}

/**
 * Build the HTML Treeview for Groups
 * @param array $menu
 * @param array $selected
 * @return string
 */

function makeTreeViewPanelNavigation( $menu , $selected = false )
{
	$panel = '';
	$selecteds = ( is_array( explode(',' , $selected ) ) ) ? explode(',' , $selected ) : []; 
	if( is_array( $menu ) )
	{
		foreach ($menu as $key => $items ) 
		{
			//debugger($menu);
			if( !is_numeric( $key ) )
			{
				$panel .=  makeTreeViewPanelNavigation( $items , $selected);
			}
			else
			{	
				
				$checkbox = ( in_array( $items['id'] , $selecteds )) ? 'checked = "true"' : '';
    			if( $items['childs_count'] > 0 )
    			{
    				$panel .=	'<li class="collapsable">
    								<div class="hitarea collapsable-hitarea"></div>
    								<div class="checkbox check-primary">
									  	<input id="module_'.$items['id'].'" name="treeviewPanel[]" value="'.$items['id'].'" type="checkbox" '.$checkbox.' >
									  	<label for="module_'.$items['id'].'"> <i class="'.$items['icon'].'"></i> '.$items['name'].'</label>
									</div>
									<ul style="display: block;" >
										'.makeTreeViewPanelNavigation( $items['childs'] , $selected).'
									</ul>
								</li>';
    			}
    			else
    			{
    				$panel .=	'<li>
    								<div class="checkbox check-primary">
									  	<input id="module_'.$items['id'].'" name="treeviewPanel[]" value="'.$items['id'].'" type="checkbox" '.$checkbox.'>
									  	<label for="module_'.$items['id'].'"> <i class="'.$items['icon'].'"></i> '.$items['name'].'</label>
									</div>								
								</li>';
    			}
			}    			
		}
	}
	return $panel;
}

/**
 * Set Route of Menu Panel with User privileges
 * @param array $menu
 * @param array $uri
 * @param string $module
 * @return string
 */

function setRoutesPrivileges(  $array  )
{
	$result = '';
	if( is_array( $array ) )
	{
		foreach ($array as $key => $value) {
			if( !is_numeric( $key ) ){
				$result .= setRoutesPrivileges( $value );
			
			}else{
				if( $value['childs_count'] > 0 ){
					$result .= setRoutesPrivileges( $value );
				}else{
					$result .=  $value['route'].'|' ;
				}
			}
		}
	}
	return $result;
}

/**
 * Check if the User belongs to the root group 
 * @return bolean
 */

function isRoot()
{	
	return (md5(strtolower( authUser( ['group' , 'name'] ) ) ) == '63a9f0ea7bb98050796b649e85481845' ) ? true : false;
}
/**
 * Check if the User belongs to the Admin group 
 * @return bolean
 */

function isAdmin()
{	
	return (md5(strtolower( authUser( ['group' , 'name'] ) ) ) == '21232f297a57a5a743894a0e4a801fc3' ) ? true : false;
}


/**
 * Get Id Company
 * @return int
 */

function idCompany()
{	
	if (Auth::user()) {
		$company_current = authUser('company_current');
		if (authUser('company_id')!='') {
			$company_id = ( !is_null($company_current) && $company_current!='') ? $company_current : authUser('company_id');
		} else {
			$company_id=1;
		}
	} else {
		$company_id=1;
	}

	return $company_id;
}

/**
 * Get name Company 
 * @return string
 */

function idCompanyName()
{	
	return  config('app.company_name');
}

/**
 * Get Pkey Company
 * @return string
 */

function companyPkey()
{	
	return  config('app.company_pkey');
}

/**
 * Set Vars Session panelNavigation & routesPrivileges for check privileges
 * @return void
 */

function setUserPrivileges($user = [])
{
	$role = authUser( ['role']);
	$menuGroupPrivileges = trim( authUser( ['group' , 'menu_ids'] ) , ',' );
	$menusUserPrivileges = trim( authUser( [ 'menu_ids'] ) , ',' );

	$privileges = ( $menusUserPrivileges == '' ) ? $menuGroupPrivileges : $menusUserPrivileges;   

	$dataPanelNavigation = getDataPanelNavigation( $privileges );         
	//$routesPrivileges = explode('|', trim( setRoutesPrivileges($dataPanelNavigation) , '|'));
	//$setUserPrivileges['routes'] = $routesPrivileges;

	$user['privileges'] = [ 'panelNavigation' => $dataPanelNavigation ];
	$user['company'] =  getCfg('','company');
	$user['company_logo'] = getCfg('logo_main','company');
	$user['company_color'] = getCfg('color_main','company');
	$user['company_lang'] = getCfg('lang','company');
	$user['company_lang_email'] = getCfg('lang_email','company');

	return $user;
}

/**
 * Get Id´s proyects
 * @return int
 */

 function idProyects()
 {	
	$projects = "all";
	
	if (Auth::user()) {
		if (!empty(authUser('donors'))) {
			$idDonors = authUser('donors');
			$data = DB::table('projects')
				->select('id')
				->whereIn('donors', explode(',', trim( $idDonors, ',')))->get()->toArray();
			$ids = (isset($data[0]) && isset($data[0]))  ? json_decode(json_encode($data), true) : 0;
			$projects = null;
			if (is_array($ids)) {
				$cont = 0;
				foreach ($ids as $value) {
					if ($cont == 0) {
						$projects = $value['id'];
					} else {
						$projects = $projects .', '.$value['id'];
					}
					$cont++;
				}
			} else {
				$projects = 0;
			}
			
		}
	}
	return $projects;
}

/**
 * Get flag conta data show
 * @return int
 */

function validConta()
{
	return authUser('conta_data');
}

/**
 * Get flag delete data
 * @return int
 */

 function actionDelete()
 {
	 return authUser('action_delete');
 }

/**
 * Get Id´s proyects
 * @return int
 */

 function idDonorsAvailabe()
 {	
	$donors = "all";
	if (Auth::user()) {
		if (!empty(authUser('donors'))) {
			$donors = authUser('donors');
		}
	}
 
	 return $donors;
}
