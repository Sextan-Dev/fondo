<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProjectsTable extends Migration {

	public function up()
	{
		Schema::create('projects', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->integer('program_id')->unsigned()->nullable()->index();
			$table->string('name', 200)->nullable();
			$table->text('description')->nullable();
			$table->string('slug', 30)->nullable();
			$table->integer('user_id')->unsigned()->nullable();
			$table->text('files')->nullable();
			$table->string('type', 40)->nullable();
			$table->string('category', 50)->nullable();
			$table->integer('contact_id')->unsigned()->nullable()->index();
			$table->string('icon', 40)->nullable();
			$table->text('img')->nullable();
			$table->string('status', 40)->nullable();
			$table->text('comment')->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->integer('company_id')->unsigned()->nullable();
		});
	}

	public function down()
	{
		Schema::drop('projects');
	}
}