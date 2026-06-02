<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->string('cpf_bidx', 64)->nullable()->after('cpf');
            $table->string('nis_bidx', 64)->nullable()->after('nis');
            $table->index('cpf_bidx');
            $table->index('nis_bidx');
        });

        Schema::table('ngo_donors', function (Blueprint $table) {
            $table->string('document_bidx', 64)->nullable()->after('document');
            $table->index('document_bidx');
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE beneficiaries MODIFY cpf TEXT NULL');
            DB::statement('ALTER TABLE beneficiaries MODIFY nis TEXT NULL');
            DB::statement('ALTER TABLE ngo_donors MODIFY document TEXT NULL');
        }

        DB::table('beneficiaries')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $updates = [];

                if (!empty($row->cpf) && !str_starts_with($row->cpf, 'eyJ')) {
                    $updates['cpf']      = Crypt::encryptString($row->cpf);
                    $updates['cpf_bidx'] = hash_hmac('sha256', $row->cpf, config('app.key'));
                } elseif (!empty($row->cpf) && str_starts_with($row->cpf, 'eyJ')) {
                    try {
                        $plain = Crypt::decryptString($row->cpf);
                        $updates['cpf_bidx'] = hash_hmac('sha256', $plain, config('app.key'));
                    } catch (\Illuminate\Contracts\Encryption\DecryptException) {}
                }

                if (!empty($row->nis) && !str_starts_with($row->nis, 'eyJ')) {
                    $updates['nis']      = Crypt::encryptString($row->nis);
                    $updates['nis_bidx'] = hash_hmac('sha256', $row->nis, config('app.key'));
                } elseif (!empty($row->nis) && str_starts_with($row->nis, 'eyJ')) {
                    try {
                        $plain = Crypt::decryptString($row->nis);
                        $updates['nis_bidx'] = hash_hmac('sha256', $plain, config('app.key'));
                    } catch (\Illuminate\Contracts\Encryption\DecryptException) {}
                }

                if (!empty($updates)) {
                    DB::table('beneficiaries')->where('id', $row->id)->update($updates);
                }
            }
        });

        DB::table('ngo_donors')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $updates = [];

                if (!empty($row->document) && !str_starts_with($row->document, 'eyJ')) {
                    $updates['document']      = Crypt::encryptString($row->document);
                    $updates['document_bidx'] = hash_hmac('sha256', $row->document, config('app.key'));
                } elseif (!empty($row->document) && str_starts_with($row->document, 'eyJ')) {
                    try {
                        $plain = Crypt::decryptString($row->document);
                        $updates['document_bidx'] = hash_hmac('sha256', $plain, config('app.key'));
                    } catch (\Illuminate\Contracts\Encryption\DecryptException) {}
                }

                if (!empty($updates)) {
                    DB::table('ngo_donors')->where('id', $row->id)->update($updates);
                }
            }
        });
    }

    public function down(): void
    {
        DB::table('beneficiaries')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $updates = [];
                foreach (['cpf', 'nis'] as $field) {
                    $val = $row->{$field};
                    if (!empty($val) && str_starts_with($val, 'eyJ')) {
                        try {
                            $updates[$field] = Crypt::decryptString($val);
                        } catch (\Illuminate\Contracts\Encryption\DecryptException) {}
                    }
                }
                if (!empty($updates)) {
                    DB::table('beneficiaries')->where('id', $row->id)->update($updates);
                }
            }
        });

        DB::table('ngo_donors')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                if (!empty($row->document) && str_starts_with($row->document, 'eyJ')) {
                    try {
                        $plain = Crypt::decryptString($row->document);
                        DB::table('ngo_donors')->where('id', $row->id)->update(['document' => $plain]);
                    } catch (\Illuminate\Contracts\Encryption\DecryptException) {}
                }
            }
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE beneficiaries MODIFY cpf VARCHAR(255) NULL');
            DB::statement('ALTER TABLE beneficiaries MODIFY nis VARCHAR(255) NULL');
            DB::statement('ALTER TABLE ngo_donors MODIFY document VARCHAR(255) NULL');
        }

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropIndex(['cpf_bidx']);
            $table->dropIndex(['nis_bidx']);
            $table->dropColumn(['cpf_bidx', 'nis_bidx']);
        });

        Schema::table('ngo_donors', function (Blueprint $table) {
            $table->dropIndex(['document_bidx']);
            $table->dropColumn('document_bidx');
        });
    }
};
