<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->string('paper_size')->default('a4')->after('body');
            $table->string('orientation')->default('portrait')->after('paper_size');
            $table->string('background_image')->nullable()->after('orientation');
            $table->string('content_type')->default('full')->after('background_image'); // 'full' or 'letterhead'
            $table->integer('margin_top')->default(10)->after('content_type'); // mm
            $table->integer('margin_right')->default(10)->after('margin_top'); // mm
            $table->integer('margin_bottom')->default(10)->after('margin_right'); // mm
            $table->integer('margin_left')->default(10)->after('margin_bottom'); // mm
            $table->string('filename_format')->default('[student_name]-[template_name]')->after('margin_left');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropColumn([
                'paper_size',
                'orientation',
                'background_image',
                'content_type',
                'margin_top',
                'margin_right',
                'margin_bottom',
                'margin_left',
                'filename_format'
            ]);
        });
    }
};
