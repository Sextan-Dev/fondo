<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function(Blueprint $table) {
			$table->increments('id');
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->string('type', 20)->nullable();
			$table->string('category', 50)->nullable();
			$table->string('name', 200)->nullable();
			$table->string('slug', 40)->nullable();
            $table->text('comment')->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
            $table->integer('company_id')->unsigned();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('categories');
    }
}
