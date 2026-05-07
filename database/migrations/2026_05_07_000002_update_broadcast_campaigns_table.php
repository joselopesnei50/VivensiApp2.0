<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->string('status')->default('completed')->after('audience_type'); // scheduled, processing, completed, failed
            $table->timestamp('scheduled_at')->nullable()->after('status');
            $table->integer('cadence')->default(3)->after('scheduled_at');
            $table->json('group_ids')->nullable()->after('cadence');
            $table->text('phones')->nullable()->after('group_ids');
            $table->string('image_path')->nullable()->after('has_image');
        });
    }

    public function down()
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->dropColumn(['status', 'scheduled_at', 'cadence', 'group_ids', 'phones', 'image_path']);
        });
    }
};
