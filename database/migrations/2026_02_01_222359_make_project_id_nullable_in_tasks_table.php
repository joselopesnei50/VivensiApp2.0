<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE tasks MODIFY project_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Attention: verify if there are null values before reversing
        DB::statement('UPDATE tasks SET project_id = 0 WHERE project_id IS NULL'); 
        DB::statement('ALTER TABLE tasks MODIFY project_id BIGINT UNSIGNED NOT NULL');
    }
};
