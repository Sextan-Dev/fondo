<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMenusTable extends Migration {

	public function up()
	{
		Schema::create('menus', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->string('name', 200)->nullable();
			$table->text('icon')->nullable();
			$table->string('route', 125)->nullable();
			$table->string('order', 25)->nullable();
			$table->integer('parent_id')->unsigned()->nullable()->index();
			$table->integer('menu_group_id')->unsigned()->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->integer('company_id')->unsigned()->nullable();
		});
	}

	public function down()
	{
		Schema::drop('menus');
	}
}