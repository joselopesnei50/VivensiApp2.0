<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ai_social_posts', function (Blueprint $table) {
            $table->text('user_context')->nullable()->after('title_theme');
        });
    }

    public function down()
    {
        Schema::table('ai_social_posts', function (Blueprint $table) {
            $table->dropColumn('user_context');
        });
    }
};
