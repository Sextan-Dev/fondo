<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCountriesTable extends Migration {

	public function up()
	{
		Schema::create('countries', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->string('name', 200)->nullable();
			$table->string('slug', 20)->nullable();
			$table->text('flag_img')->nullable();
			$table->string('currency_code', 20)->nullable();
			$table->string('currency_char', 5)->nullable();
			$table->integer('lang_id')->unsigned()->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
		});
	}

	public function down()
	{
		Schema::drop('countries');
	}
}