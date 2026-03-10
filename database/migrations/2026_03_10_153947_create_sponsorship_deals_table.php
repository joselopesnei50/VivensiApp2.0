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
        Schema::create('sponsorship_deals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('company_name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('expected_value', 10, 2)->default(0);
            $table->string('stage')->default('prospecting'); // prospecting, meeting_scheduled, negotiating, won, lost
            $table->date('contact_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'stage']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sponsorship_deals');
    }
};
