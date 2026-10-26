<?php

use Illuminate\Database\Seeder;
use App\Lang;

class LangTableSeeder extends Seeder {

	public function run()
	{
		//DB::table('langs')->delete();

		// defaultLang
		Lang::create(array(
				'name' => 'es',
				'slug' => 'es_ES'
			));
	}
}