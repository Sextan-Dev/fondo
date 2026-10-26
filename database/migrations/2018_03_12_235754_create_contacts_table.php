<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateContactsTable extends Migration {

	public function up()
	{
		Schema::create('contacts', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->string('category', 30)->nullable();
			$table->string('name', 200)->nullable();
			$table->string('slug', 10)->nullable();
			$table->string('first_name', 50)->nullable();
			$table->string('last_name', 50)->nullable();
			$table->string('email', 50)->nullable();
			$table->string('age', 10)->nullable();
			$table->string('gender', 30)->nullable();
			$table->string('birth', 30)->nullable();
			$table->string('id_number', 30)->nullable();
			$table->string('company', 40)->nullable();
			$table->string('position', 20)->nullable();
			$table->string('nationality', 50)->nullable();
			$table->string('phone', 20)->nullable();
			$table->text('address')->nullable();
			$table->string('city', 50)->nullable();
			$table->string('postal', 20)->nullable();
			$table->string('country', 20)->nullable();
			$table->text('account')->nullable();
			$table->string('contract_type', 50)->nullable();
			$table->string('contract_from', 30)->nullable();
			$table->string('contract_to', 30)->nullable();
			$table->integer('user_id')->unsigned()->nullable()->index();
			$table->integer('task_id')->unsigned()->nullable()->index();
			$table->text('files')->nullable();
			$table->text('comment')->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->integer('company_id')->unsigned()->nullable();
		});
	}

	public function down()
	{
		Schema::drop('contacts');
	}
}