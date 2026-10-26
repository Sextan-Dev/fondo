<?php

use Illuminate\Database\Seeder;
use App\UserGroup;

class UserGroupTableSeeder extends Seeder {

	public function run()
	{
		//DB::table('user_groups')->delete();

		// defaultGroup
		UserGroup::create(array(
				'name' => 'root',
				'menu_ids' => 1,
				'company_id' => 1
			));
	}
}