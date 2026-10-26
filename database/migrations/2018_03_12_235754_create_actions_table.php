<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateActionsTable extends Migration {

	public function up()
	{
		Schema::create('actions', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->string('name', 200)->nullable();
			$table->string('slug', 10)->nullable();
			$table->string('type', 40)->nullable();
			$table->string('tablename', 40)->nullable();
			$table->integer('item')->nullable();
			$table->integer('group_id')->unsigned()->nullable();
			$table->integer('user_id')->unsigned()->nullable();
			$table->text('updates')->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->integer('company_id')->unsigned()->nullable();
		});
	}

	public function down()
	{
		Schema::drop('actions');
	}
}