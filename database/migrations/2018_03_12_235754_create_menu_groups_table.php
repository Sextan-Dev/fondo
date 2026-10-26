<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMenuGroupsTable extends Migration {

	public function up()
	{
		Schema::create('menu_groups', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->string('name', 200)->nullable();
			$table->string('order', 25)->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->integer('company_id')->unsigned()->nullable();
		});
	}

	public function down()
	{
		Schema::drop('menu_groups');
	}
}