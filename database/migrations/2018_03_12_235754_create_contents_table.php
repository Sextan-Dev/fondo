<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateContentsTable extends Migration {

	public function up()
	{
		Schema::create('contents', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->datetime('date')->nullable();
			$table->string('type', 40)->nullable();
			$table->string('category', 40)->nullable();
			$table->text('tags')->nullable();
			$table->string('name', 200)->nullable();
			$table->string('slug', 20)->nullable();
			$table->text('value')->nullable();
			$table->integer('user_id')->unsigned()->nullable()->index();
			$table->integer('lang_id')->unsigned()->nullable()->index();
			$table->integer('task_id')->unsigned()->nullable()->index();
			$table->text('share_user_ids')->nullable();
			$table->text('head')->nullable();
			$table->longText('html')->nullable();
			$table->string('item', 30)->nullable();
			$table->tinyInteger('seen')->nullable()->default('0');
			$table->tinyInteger('done')->nullable()->default('0');
			$table->string('priority', 30)->nullable();
			$table->string('deadline', 40)->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->text('files')->nullable();
			$table->text('comment')->nullable();
			$table->integer('company_id')->unsigned()->nullable()->index();
		});
	}

	public function down()
	{
		Schema::drop('contents');
	}
}