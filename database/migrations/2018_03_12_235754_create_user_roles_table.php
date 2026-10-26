<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserRolesTable extends Migration {

	public function up()
	{
		Schema::create('user_roles', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->string('name', 200)->nullable();
			$table->string('slug', 10)->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->integer('company_id')->unsigned()->nullable();
		});
	}

	public function down()
	{
		Schema::drop('user_roles');
	}
}