<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->increments('id');
			$table->timestamp('created_at')->nullable();
			$table->timestamp('updated_at')->nullable();
			$table->string('article', 200)->nullable();
			$table->string('slug', 10)->nullable();
			$table->string('type', 50)->nullable();
			$table->string('category', 50)->nullable();
			//$table->string('unit_val', 40)->nullable();
			$table->text('img')->nullable();
			$table->tinyInteger('active')->nullable()->default('1');
			$table->text('comment')->nullable();
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
        Schema::dropIfExists('inventories');
    }
}
