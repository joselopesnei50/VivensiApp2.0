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
        Schema::create('landing_page_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('page_key'); // e.g., 'ngo', 'manager', 'personal'
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('registrations')->default(0);
            $table->date('date');
            $table->timestamps();

            $table->unique(['page_key', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('landing_page_metrics');
    }
};
