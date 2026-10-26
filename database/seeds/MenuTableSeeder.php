<?php

use Illuminate\Database\Seeder;
use App\Menu;

class MenuTableSeeder extends Seeder {

	public function run()
	{
		//DB::table('menus')->delete();

		// defaultMenu
		Menu::create(array(
				'name' => 'Dashboard',
				'icon' => 'fa fa-home',
				'route' => 'index',
				'menu_group_id' => 1,
				'active' => 1,
				'company_id' => 1
			));
	}
}