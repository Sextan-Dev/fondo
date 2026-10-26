<?php

use Illuminate\Database\Seeder;
use App\User;

class UserTableSeeder extends Seeder {

	public function run()
	{
		//DB::table('users')->delete();

		// defaultUser
		User::create(array(
				'user' => 'support',
				'name' => 'Support',
				'email' => 'support@sextansystem.com',
				'password' => '$2y$10$h2sKz9vMNqo7zMAmJ2QeYeqG822F1M4zTH5lZ2KFXYbgQbPxZr2b6',
				'phone' => '55456038',
				'menu_ids' => 1,
				'group_id' => 1,
				'role_id' => 1,
				'created_by' => 1,
				'active' => 1,
				'company_id' => 1
			));
	}
}