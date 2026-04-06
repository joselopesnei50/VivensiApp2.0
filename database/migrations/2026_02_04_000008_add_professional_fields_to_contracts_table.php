<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('contracts', 'public_sign_expires_at')) {
                $table->timestamp('public_sign_expires_at')->nullable()->index();
            }
            if (!Schema::hasColumn('contracts', 'public_viewed_at')) {
                $table->timestamp('public_viewed_at')->nullable()->index();
            }
            if (!Schema::hasColumn('contracts', 'signer_user_agent')) {
                $table->string('signer_user_agent', 255)->nullable();
            }
            if (!Schema::hasColumn('contracts', 'document_hash')) {
                $table->string('document_hash', 64)->nullable()->index();
            }
            if (!Schema::hasColumn('contracts', 'signature_hash')) {
                $table->string('signature_hash', 64)->nullable()->index();
            }
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $drop = [];
            foreach (['public_sign_expires_at', 'public_viewed_at', 'signer_user_agent', 'document_hash', 'signature_hash'] as $col) {
                if (Schema::hasColumn('contracts', $col)) {
                    $drop[] = $col;
                }
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};

