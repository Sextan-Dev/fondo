<?php

use Illuminate\Database\Seeder;
use App\MenuGroup;

class MenuGroupTableSeeder extends Seeder {

	public function run()
	{
		//DB::table('menu_groups')->delete();

		// defaultMenuGroup
		MenuGroup::create(array(
				'name' => 'Main',
				'company_id' => 1
			));
	}
}