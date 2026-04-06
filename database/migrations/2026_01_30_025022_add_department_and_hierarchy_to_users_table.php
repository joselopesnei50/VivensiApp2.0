<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('role');
            }
            
            if (!Schema::hasColumn('users', 'supervisor_id')) {
                $table->unsignedBigInteger('supervisor_id')->nullable()->after('department');
            } else {
                // Manual SQL change to avoid doctrine/dbal dependency
                DB::statement('ALTER TABLE users MODIFY supervisor_id INT UNSIGNED NULL');
            }
        });

        // Add foreign key only if it doesn't exist
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('supervisor_id')->references('id')->on('users')->onDelete('set null');
            });
        } catch (\Exception $e) {
            // Already exists or other error
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn(['department', 'supervisor_id']);
        });
    }
};
