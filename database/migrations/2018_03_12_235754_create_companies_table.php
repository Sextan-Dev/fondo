<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCompaniesTable extends Migration {

	public function up()
	{
		Schema::create('companies', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->string('name', 200)->nullable();
			$table->string('slug', 10)->nullable();
			$table->integer('country_id')->unsigned();
			$table->tinyInteger('active')->nullable()->default('1');
		});
	}

	public function down()
	{
		Schema::drop('companies');
	}
}