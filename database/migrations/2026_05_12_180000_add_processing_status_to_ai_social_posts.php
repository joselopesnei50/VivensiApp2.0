<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `ai_social_posts` MODIFY `status` ENUM('draft','scheduled','published','failed','processing') DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE `ai_social_posts` MODIFY `status` ENUM('draft','scheduled','published','failed') DEFAULT 'draft'");
        }
    }
};
