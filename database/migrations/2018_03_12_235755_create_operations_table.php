<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOperationsTable extends Migration {

	public function up()
	{
		Schema::create('operations', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->date('date')->nullable();
			$table->integer('contact_id')->unsigned()->nullable()->index();
			$table->string('type', 30)->nullable()->index();
			$table->integer('category_id')->unsigned()->nullable()->index();
			$table->integer('account_id')->unsigned()->nullable()->index();
			$table->integer('mode_id')->unsigned()->nullable()->index();
			$table->string('amount', 30)->nullable()->default('0');
			$table->string('vat', 30)->nullable()->default('0');
			$table->string('ret_vat', 30)->nullable()->default('0');
			$table->string('ret_isr', 30)->nullable()->default('0');
			$table->string('qt', 10)->nullable()->default('1');
			$table->text('files')->nullable();
			$table->integer('task_id')->unsigned()->nullable()->index();
			$table->text('comment')->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->integer('company_id')->unsigned()->nullable();
		});
	}

	public function down()
	{
		Schema::drop('operations');
	}
}