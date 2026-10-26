<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateEmailsTable extends Migration {

	public function up()
	{
		Schema::create('emails', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->string('type', 50)->nullable();
			$table->string('subject', 200)->nullable();
			$table->text('message')->nullable();
			$table->string('date', 50)->nullable();
			$table->text('email_from')->nullable();
			$table->text('email_to')->nullable();
			$table->text('email_cc')->nullable();
			$table->text('email_bcc')->nullable();
			$table->text('files')->nullable();
			$table->text('comment')->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->integer('company_id')->unsigned()->nullable();
		});
	}

	public function down()
	{
		Schema::drop('emails');
	}
}