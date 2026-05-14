<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddReviewColumnsToReportsTable extends Migration
{
    public function up()
    {
        Schema::table('reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reports', 'status')) {
                $table->string('status', 32)->default('pending')->after('reason');
            }

            if (! Schema::hasColumn('reports', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('status');
            }

            if (! Schema::hasColumn('reports', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('admin_note')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('reports', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });

        DB::table('reports')
            ->whereNull('status')
            ->update([
                'status' => 'pending',
            ]);
    }

    public function down()
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('reports', 'status') ? 'status' : null,
                Schema::hasColumn('reports', 'admin_note') ? 'admin_note' : null,
                Schema::hasColumn('reports', 'reviewed_at') ? 'reviewed_at' : null,
            ]));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
}