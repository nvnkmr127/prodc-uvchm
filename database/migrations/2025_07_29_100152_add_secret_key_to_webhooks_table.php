<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('webhooks', function (Blueprint $table) {
            if (! Schema::hasColumn('webhooks', 'secret_key')) {
                $table->string('secret_key')->nullable()->after('event_name');
            }

            // Also add other useful columns if they don't exist
            if (! Schema::hasColumn('webhooks', 'timeout_seconds')) {
                $table->integer('timeout_seconds')->default(30)->after('secret_key');
            }

            if (! Schema::hasColumn('webhooks', 'consecutive_failures')) {
                $table->integer('consecutive_failures')->default(0)->after('timeout_seconds');
            }

            if (! Schema::hasColumn('webhooks', 'description')) {
                $table->text('description')->nullable()->after('consecutive_failures');
            }
        });
    }

    public function down()
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropColumn(['secret_key', 'timeout_seconds', 'consecutive_failures', 'description']);
        });
    }
};
