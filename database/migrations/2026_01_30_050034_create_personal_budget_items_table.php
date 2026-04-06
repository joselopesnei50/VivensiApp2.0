<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('personal_budget_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_budget_id');
            $table->string('category_name');
            $table->decimal('planned_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('personal_budget_id')->references('id')->on('personal_budgets')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('personal_budget_items');
    }
};
