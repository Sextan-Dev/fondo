<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every income import gets a row in history_operations and stamps its id on the operations
 * and donors it creates, so the import can be listed, audited and rolled back.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('history_operations', function (Blueprint $table) {
            $table->unsignedInteger('company_id')->nullable()->after('id');
            $table->string('type', 30)->nullable()->after('company_id');
            $table->string('file_path')->nullable()->after('original_name');
            $table->char('file_hash', 64)->nullable()->after('file_path');
            $table->unsignedInteger('rows_count')->default(0)->after('file_hash');
            $table->unsignedInteger('operations_count')->default(0)->after('rows_count');
            $table->decimal('total_amount', 15, 2)->default(0)->after('operations_count');
            $table->decimal('total_fee', 15, 2)->default(0)->after('total_amount');
            $table->decimal('total_co', 15, 2)->default(0)->after('total_fee');
            $table->unsignedInteger('donors_created')->default(0)->after('total_co');
            $table->date('period_from')->nullable()->after('donors_created');
            $table->date('period_to')->nullable()->after('period_from');
            $table->string('revert_reason', 500)->nullable()->after('date_delete');
            $table->index(['company_id', 'type']);
            $table->index('file_hash');
        });

        Schema::table('operations', function (Blueprint $table) {
            $table->unsignedInteger('history_operation_id')->nullable()->index();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedInteger('history_operation_id')->nullable()->index();
        });
    }

    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex(['history_operation_id']);
            $table->dropColumn('history_operation_id');
        });

        Schema::table('operations', function (Blueprint $table) {
            $table->dropIndex(['history_operation_id']);
            $table->dropColumn('history_operation_id');
        });

        Schema::table('history_operations', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'type']);
            $table->dropIndex(['file_hash']);
            $table->dropColumn([
                'company_id', 'type', 'file_path', 'file_hash', 'rows_count', 'operations_count',
                'total_amount', 'total_fee', 'total_co', 'donors_created', 'period_from', 'period_to', 'revert_reason',
            ]);
        });
    }
};
