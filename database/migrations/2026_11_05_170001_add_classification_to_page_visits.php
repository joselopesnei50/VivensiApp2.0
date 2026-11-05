<?php

use App\Support\VisitClassifier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Analytics: área (site/app/admin), módulo, visitante (sessão anônima),
 * origem e dispositivo de cada página vista. Reaproveita o histórico:
 * classifica área e módulo e normaliza os caminhos (projects/12 → projects/{id}).
 * Updates em blocos por id (chave primária) — rápido mesmo com muitas linhas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_visits', function (Blueprint $table) {
            $table->string('area', 8)->nullable()->after('path');
            $table->string('module', 16)->nullable()->after('area');
            $table->string('visitor', 16)->nullable()->after('module');
            $table->string('source', 12)->nullable()->after('visitor');
            $table->string('device', 8)->nullable()->after('source');
            $table->string('referrer_host', 100)->nullable()->after('device');
            $table->index(['area', 'created_at'], 'idx_page_visits_area_date');
            $table->index(['tenant_id', 'created_at'], 'idx_page_visits_tenant_date');
        });

        // Ruído antigo: atualizações automáticas que contavam como visita
        DB::table('page_visits')->where('path', 'like', '%/poll')->delete();

        // Área
        $superAdmins = DB::table('users')->where('role', 'super_admin')->pluck('id');
        DB::table('page_visits')->where(fn ($q) => $q->where('path', 'like', 'admin%')->orWhereIn('user_id', $superAdmins))
            ->update(['area' => 'admin']);
        DB::table('page_visits')->whereNull('area')->where(function ($q) {
            foreach (VisitClassifier::CLIENT_PREFIXES as $prefix) {
                $q->orWhere('path', $prefix)->orWhere('path', 'like', $prefix . '/%');
            }
        })->update(['area' => 'clients']);
        DB::table('page_visits')->whereNull('area')->whereNotNull('user_id')->update(['area' => 'app']);
        DB::table('page_visits')->whereNull('area')->update(['area' => 'public']);

        // Visitante único no histórico: quem estava logado vira "u{id}" (anônimos antigos ficam sem)
        $concat = DB::getDriverName() === 'sqlite' ? "'u' || user_id" : "CONCAT('u', user_id)";
        DB::table('page_visits')->whereNotNull('user_id')->update(['visitor' => DB::raw($concat)]);

        // Módulo + caminho normalizado: blocos por id (chave primária), agrupando pelo
        // resultado (projects/1, projects/2… → projects/{id}) para poucas atualizações por bloco
        DB::table('page_visits')->whereNull('module')->select('id', 'path')->orderBy('id')
            ->chunkById(10000, function ($rows) {
                $groups = [];
                foreach ($rows as $r) {
                    $key = VisitClassifier::normalize((string) $r->path) . "\0" . VisitClassifier::module((string) $r->path);
                    $groups[$key][] = $r->id;
                }
                foreach ($groups as $key => $ids) {
                    [$path, $module] = explode("\0", $key, 2);
                    foreach (array_chunk($ids, 5000) as $part) {
                        DB::table('page_visits')->whereIn('id', $part)->update(['path' => $path, 'module' => $module]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Índices antes, em passo separado (no SQLite o dropColumn recria a tabela)
        Schema::table('page_visits', function (Blueprint $table) {
            $table->dropIndex('idx_page_visits_area_date');
            $table->dropIndex('idx_page_visits_tenant_date');
        });
        Schema::table('page_visits', function (Blueprint $table) {
            $table->dropColumn(['area', 'module', 'visitor', 'source', 'device', 'referrer_host']);
        });
    }
};
