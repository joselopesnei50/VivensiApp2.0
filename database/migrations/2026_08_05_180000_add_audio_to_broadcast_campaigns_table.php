<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Broadcast de audio (2026-08-05) — colunas dedicadas.
 *
 * `audio_fingerprint` guarda sha256 do binario, usado em AntiBanManager pra
 * limitar quantos envios do MESMO audio o tenant pode fazer por dia. Audio
 * repetido em massa e vetor classico de ban Meta/Evolution.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->boolean('has_audio')->default(false)->after('image_path');
            $table->string('audio_path')->nullable()->after('has_audio');
            $table->string('audio_mime', 60)->nullable()->after('audio_path');
            $table->string('audio_fingerprint', 64)->nullable()->after('audio_mime')->index();
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_campaigns', function (Blueprint $table) {
            $table->dropIndex(['audio_fingerprint']);
            $table->dropColumn(['has_audio', 'audio_path', 'audio_mime', 'audio_fingerprint']);
        });
    }
};
