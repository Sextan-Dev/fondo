<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTasksTable extends Migration {

	public function up()
	{
		Schema::create('tasks', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->string('name', 200)->nullable();
			$table->text('description')->nullable();
			$table->string('slug', 30)->nullable();
			$table->integer('project_id')->unsigned()->nullable()->index();
			$table->integer('user_id')->unsigned()->nullable()->index();
			$table->text('tags')->nullable();
			$table->string('type', 40)->nullable();
			$table->string('category', 50)->nullable();
			$table->string('date_from', 30)->nullable();
			$table->string('date_to', 30)->nullable();
			$table->string('date_done', 30)->nullable();
			$table->string('benef_1', 10)->nullable();
			$table->string('benef_2', 10)->nullable();
			$table->string('benef_3', 10)->nullable();
			$table->string('target_benef_1', 20)->nullable();
			$table->string('target_benef_2', 20)->nullable();
			$table->string('target_benef_3', 20)->nullable();
			$table->text('files')->nullable();
			$table->text('proof')->nullable();
			$table->string('status', 30)->nullable();
			$table->text('comment')->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->integer('company_id')->unsigned()->nullable()->index();
		});
	}

	public function down()
	{
		Schema::drop('tasks');
	}
}