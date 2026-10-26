<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccountsTable extends Migration {

	public function up()
	{
		Schema::create('accounts', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->string('name', 200)->nullable();
			$table->string('slug', 10)->nullable();
			$table->string('bank', 50)->nullable();
			$table->integer('country_id')->unsigned()->nullable();
			$table->string('swift', 40)->nullable();
			$table->string('account', 100)->nullable();
			$table->string('iban', 100)->nullable();
			$table->text('img')->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->text('comment')->nullable();
			$table->integer('company_id')->unsigned();
		});
	}

	public function down()
	{
		Schema::drop('accounts');
	}
}