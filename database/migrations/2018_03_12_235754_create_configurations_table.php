<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateConfigurationsTable extends Migration {

	public function up()
	{
		Schema::create('configurations', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->string('name', 200)->nullable();
			$table->string('slug', 20)->nullable();
			$table->string('type', 40)->nullable();
			$table->string('category', 40)->nullable();
			$table->text('comment')->nullable();
			$table->text('value')->nullable();
			$table->string('unit', 40)->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->integer('company_id')->unsigned()->nullable();
		});
	}

	public function down()
	{
		Schema::drop('configurations');
	}
}