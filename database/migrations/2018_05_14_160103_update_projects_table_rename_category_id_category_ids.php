<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateProjectsTableRenameCategoryIdCategoryIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
        
            $table->text('category_ids')->after('program_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *assss Saa
     * @return void
     */
    public function down()
    {
        //
    }
}
