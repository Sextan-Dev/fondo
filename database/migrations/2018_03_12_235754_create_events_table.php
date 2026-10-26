<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEventsTable extends Migration {

	public function up()
	{
		Schema::create('events', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->string('type', 40)->nullable();
			$table->string('category', 40)->nullable();
			$table->string('name', 200)->nullable();
			$table->string('slug', 20)->nullable();
			$table->integer('user_id')->unsigned()->nullable();
			$table->text('description')->nullable();
			$table->string('location', 100)->nullable();
			$table->datetime('start')->nullable();
			$table->datetime('end')->nullable();
			$table->tinyInteger('allday')->nullable()->default('0');
			$table->text('share_user_ids')->nullable();
			$table->text('comment')->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->integer('company_id')->unsigned()->nullable();
		});
	}

	public function down()
	{
		Schema::drop('events');
	}
}