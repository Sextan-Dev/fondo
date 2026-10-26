<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder {

	public function run()
	{
		Model::unguard();

		$this->call('LangTableSeeder');
		$this->command->info('Lang table seeded!');

		$this->call('CountryTableSeeder');
		$this->command->info('Country table seeded!');

		$this->call('CompanyTableSeeder');
		$this->command->info('Company table seeded!');

		$this->call('UserGroupTableSeeder');
		$this->command->info('UserGroup table seeded!');

		$this->call('UserRoleTableSeeder');
		$this->command->info('UserRole table seeded!');

		$this->call('MenuGroupTableSeeder');
		$this->command->info('MenuGroup table seeded!');

		$this->call('MenuTableSeeder');
		$this->command->info('Menu table seeded!');

		$this->call('UserTableSeeder');
		$this->command->info('User table seeded!');

		$this->call('ConfigurationTableSeeder');
		$this->command->info('Configuration table seeded!');
	}
}