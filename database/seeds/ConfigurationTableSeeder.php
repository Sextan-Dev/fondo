<?php

use Illuminate\Database\Seeder;
use App\Configuration;

class ConfigurationTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // defaultConfig
		Configuration::insert(
            [
                [
                    'type' => 'config',
                    'category' => 'company',
                    'name' => 'color_main',
                    'value' => '#EB333C',
                    'active' => 1,
                    'company_id' => 1
                ],
                [
                    'type' => 'config',
                    'category' => 'company',
                    'name' => 'logo_main',
                    'value' => '/images/logo_main.png',
                    'active' => 1,
                    'company_id' => 1
                ],
                [
                    'type' => 'config',
                    'category' => 'company',
                    'name' => 'timezone',
                    'value' => 'America/Mexico_City',
                    'active' => 1,
                    'company_id' => 1
                ],
                [
                    'type' => 'config',
                    'category' => 'company',
                    'name' => 'lang',
                    'value' => 'es',
                    'active' => 1,
                    'company_id' => 1
                ],
                [
                    'type' => 'config',
                    'category' => 'company',
                    'name' => 'lang_email',
                    'value' => 'es',
                    'active' => 1,
                    'company_id' => 1
                ]
            ]
        );

    }
}
