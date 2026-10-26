<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable extends Migration {

	public function up()
	{
		Schema::create('users', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamps();
			$table->string('user', 30)->unique()->nullable();
			$table->string('name', 200)->nullable()->default('NUL');
			$table->string('email', 200)->nullable();
			$table->string('password', 200)->nullable();
			$table->string('remember_token', 100)->nullable();
			$table->string('phone', 20)->nullable();
			$table->text('menu_ids')->nullable();
			$table->integer('group_id')->unsigned()->nullable();
			$table->text('group_additionnals')->nullable();
			$table->integer('role_id')->unsigned()->nullable();
			$table->tinyInteger('online')->nullable()->default('0');
			$table->string('color', 20)->nullable();
			$table->text('img')->nullable();
			$table->text('signature')->nullable();
			$table->integer('created_by')->unsigned()->nullable()->index();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->integer('company_id')->unsigned();
		});
	}

	public function down()
	{
		Schema::drop('users');
	}
}