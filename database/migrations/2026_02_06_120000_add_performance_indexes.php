<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add indexes to transactions
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'tenant_id') || 
                !$this->hasIndex('transactions', 'transactions_ten id_index')) {
                $table->index('tenant_id', 'transactions_tenant_id_index');
            }
            if (!$this->hasIndex('transactions', 'transactions_type_index')) {
                $table->index('type', 'transactions_type_index');
            }
            if (!$this->hasIndex('transactions', 'transactions_date_index')) {
                $table->index('date', 'transactions_date_index');
            }
            if (!$this->hasIndex('transactions', 'transactions_tenant_type_date_index')) {
                $table->index(['tenant_id', 'type', 'date'], 'transactions_tenant_type_date_index');
            }
        });

        // Add indexes to projects
        Schema::table('projects', function (Blueprint $table) {
            if (!$this->hasIndex('projects', 'projects_tenant_id_index')) {
                $table->index('tenant_id', 'projects_tenant_id_index');
            }
            if (!$this->hasIndex('projects', 'projects_status_index')) {
                $table->index('status', 'projects_status_index');
            }
            if (!$this->hasIndex('projects', 'projects_tenant_status_index')) {
                $table->index(['tenant_id', 'status'], 'projects_tenant_status_index');
            }
        });

        // Add indexes to tasks
        Schema::table('tasks', function (Blueprint $table) {
            if (!$this->hasIndex('tasks', 'tasks_tenant_id_index')) {
                $table->index('tenant_id', 'tasks_tenant_id_index');
            }
            if (!$this->hasIndex('tasks', 'tasks_status_index')) {
                $table->index('status', 'tasks_status_index');
            }
            if (Schema::hasColumn('tasks', 'assigned_to') && !$this->hasIndex('tasks', 'tasks_assigned_to_index')) {
                $table->index('assigned_to', 'tasks_assigned_to_index');
            }
        });

        // Add indexes to whatsapp_chats
        if (Schema::hasTable('whatsapp_chats')) {
            Schema::table('whatsapp_chats', function (Blueprint $table) {
                if (!$this->hasIndex('whatsapp_chats', 'whatsapp_chats_tenant_id_index')) {
                    $table->index('tenant_id', 'whatsapp_chats_tenant_id_index');
                }
                if (!$this->hasIndex('whatsapp_chats', 'whatsapp_chats_status_index')) {
                    $table->index('status', 'whatsapp_chats_status_index');
                }
                if (Schema::hasColumn('whatsapp_chats', 'last_message_at') && 
                    !$this->hasIndex('whatsapp_chats', 'whatsapp_chats_last_message_at_index')) {
                    $table->index('last_message_at', 'whatsapp_chats_last_message_at_index');
                }
            });
        }

        // Add indexes to users
        Schema::table('users', function (Blueprint $table) {
            if (!$this->hasIndex('users', 'users_tenant_id_index')) {
                $table->index('tenant_id', 'users_tenant_id_index');
            }
            if (!$this->hasIndex('users', 'users_role_index')) {
                $table->index('role', 'users_role_index');
            }
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_tenant_id_index');
            $table->dropIndex('transactions_type_index');
            $table->dropIndex('transactions_date_index');
            $table->dropIndex('transactions_tenant_type_date_index');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_tenant_id_index');
            $table->dropIndex('projects_status_index');
            $table->dropIndex('projects_tenant_status_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_tenant_id_index');
            $table->dropIndex('tasks_status_index');
            if (Schema::hasColumn('tasks', 'assigned_to')) {
                $table->dropIndex('tasks_assigned_to_index');
            }
        });

        if (Schema::hasTable('whatsapp_chats')) {
            Schema::table('whatsapp_chats', function (Blueprint $table) {
                $table->dropIndex('whatsapp_chats_tenant_id_index');
                $table->dropIndex('whatsapp_chats_status_index');
                if (Schema::hasColumn('whatsapp_chats', 'last_message_at')) {
                    $table->dropIndex('whatsapp_chats_last_message_at_index');
                }
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_tenant_id_index');
            $table->dropIndex('users_role_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table);
        return isset($indexes[$index]);
    }
};
