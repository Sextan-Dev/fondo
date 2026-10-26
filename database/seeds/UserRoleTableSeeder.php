<?php

use Illuminate\Database\Seeder;
use App\UserRole;

class UserRoleTableSeeder extends Seeder {

	public function run()
	{
		//DB::table('user_roles')->delete();

		// defaultRole
		UserRole::create(array(
				'name' => 'Admin',
				'slug' => 'admin',
				'company_id' => 1
			));
	}
}