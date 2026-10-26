<?php

use Illuminate\Database\Seeder;
use App\Company;

class CompanyTableSeeder extends Seeder {

	public function run()
	{
		//DB::table('companies')->delete();

		// defaultCompany
		Company::create(array(
				'name' => 'Fondo Unido',
				'slug' => 'fondo',
				'country_id' => 1
			));
	}
}