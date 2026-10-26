<?php

use Illuminate\Database\Seeder;
use App\Country;

class CountryTableSeeder extends Seeder {

	public function run()
	{
		//DB::table('countries')->delete();

		// defaultCountry
		Country::create(array(
				'name' => 'Mexico',
				'slug' => 'mx',
				'currency_code' => 'mxn',
				'currency_char' => '$',
				'lang_id' => 1
			));
	}
}