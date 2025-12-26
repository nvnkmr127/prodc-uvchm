<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get or create a default academic year
        $currentYear = DB::table('academic_years')->where('is_current', true)->first();

        if (!$currentYear) {
            // Create a default academic year if none exists
            $yearName = date('Y') . '-' . (date('Y') + 1);
            $currentYearId = DB::table('academic_years')->insertGetId([
                'name' => $yearName,
                'start_date' => date('Y') . '-07-01',
                'end_date' => (date('Y') + 1) . '-06-30',
                'is_current' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $currentYear = DB::table('academic_years')->find($currentYearId);
        }

        // 1. Add academic_year_id to batches table
        if (!Schema::hasColumn('batches', 'academic_year_id')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->foreignId('academic_year_id')
                    ->nullable()
                    ->after('course_id')
                    ->constrained('academic_years')
                    ->onDelete('cascade');
            });
        }

        // Backfill existing batches with current year
        DB::table('batches')
            ->whereNull('academic_year_id')
            ->update(['academic_year_id' => $currentYear->id]);

        // 2. Add academic_year_id to admissions table (if exists)
        if (Schema::hasTable('admissions') && !Schema::hasColumn('admissions', 'academic_year_id')) {
            Schema::table('admissions', function (Blueprint $table) {
                $table->foreignId('academic_year_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('academic_years')
                    ->onDelete('cascade');
            });

            if ($currentYear) {
                DB::table('admissions')
                    ->whereNull('academic_year_id')
                    ->update(['academic_year_id' => $currentYear->id]);
            }
        }

        // 3. Add academic_year_id to enquiries table (if exists)
        if (Schema::hasTable('enquiries') && !Schema::hasColumn('enquiries', 'academic_year_id')) {
            Schema::table('enquiries', function (Blueprint $table) {
                $table->foreignId('academic_year_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('academic_years')
                    ->onDelete('cascade');
            });

            if ($currentYear) {
                DB::table('enquiries')
                    ->whereNull('academic_year_id')
                    ->update(['academic_year_id' => $currentYear->id]);
            }
        }

        // 4. Add academic_year_id to attendances table (if exists)
        if (Schema::hasTable('attendances') && !Schema::hasColumn('attendances', 'academic_year_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->foreignId('academic_year_id')
                    ->nullable()
                    ->after('batch_id')
                    ->constrained('academic_years')
                    ->onDelete('cascade');
            });

            if ($currentYear) {
                DB::table('attendances')
                    ->whereNull('academic_year_id')
                    ->update(['academic_year_id' => $currentYear->id]);
            }
        }

        // 5. Standardize student_fees (add FK column)
        if (Schema::hasTable('student_fees') && !Schema::hasColumn('student_fees', 'academic_year_id')) {
            // Add new FK column
            Schema::table('student_fees', function (Blueprint $table) {
                $table->foreignId('academic_year_id')
                    ->nullable()
                    ->constrained('academic_years')
                    ->onDelete('cascade');
            });

            // Backfill
            if ($currentYear) {
                DB::table('student_fees')
                    ->whereNull('academic_year_id')
                    ->update(['academic_year_id' => $currentYear->id]);
            }

            // Drop old string column if it exists
            if (Schema::hasColumn('student_fees', 'academic_year')) {
                Schema::table('student_fees', function (Blueprint $table) {
                    $table->dropColumn('academic_year');
                });
            }
        }

        // 6. Standardize payments (add FK column)
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'academic_year_id')) {
            // Add new FK column
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('academic_year_id')
                    ->nullable()
                    ->constrained('academic_years')
                    ->onDelete('cascade');
            });

            // Backfill
            if ($currentYear) {
                DB::table('payments')
                    ->whereNull('academic_year_id')
                    ->update(['academic_year_id' => $currentYear->id]);
            }

            // Drop old string column if it exists
            if (Schema::hasColumn('payments', 'academic_year')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->dropColumn('academic_year');
                });
            }
        }

        // 7. Add indexes for performance (with safety checks)
        Schema::table('batches', function (Blueprint $table) {
            if (!$this->indexExists('batches', 'idx_batches_year_course')) {
                $table->index(['academic_year_id', 'course_id'], 'idx_batches_year_course');
            }
        });

        if (Schema::hasTable('student_fees') && Schema::hasColumn('student_fees', 'academic_year_id') && Schema::hasColumn('student_fees', 'student_id')) {
            Schema::table('student_fees', function (Blueprint $table) {
                if (!$this->indexExists('student_fees', 'idx_student_fees_year_student')) {
                    $table->index(['academic_year_id', 'student_id'], 'idx_student_fees_year_student');
                }
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'student_id') && Schema::hasColumn('payments', 'academic_year_id')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!$this->indexExists('payments', 'idx_payments_year_student')) {
                    $table->index(['academic_year_id', 'student_id'], 'idx_payments_year_student');
                }
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                if (!$this->indexExists('attendances', 'idx_attendance_year_student_date')) {
                    $table->index(['academic_year_id', 'student_id', 'attendance_date'], 'idx_attendance_year_student_date');
                }
            });
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $indexName)
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $indexes = $connection->select(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$databaseName, $table, $indexName]
        );

        return count($indexes) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys FIRST (before indexes)
        Schema::table('batches', function (Blueprint $table) {
            if (Schema::hasColumn('batches', 'academic_year_id')) {
                $table->dropForeign(['academic_year_id']);
            }
        });

        if (Schema::hasTable('admissions') && Schema::hasColumn('admissions', 'academic_year_id')) {
            Schema::table('admissions', function (Blueprint $table) {
                $table->dropForeign(['academic_year_id']);
            });
        }

        if (Schema::hasTable('enquiries') && Schema::hasColumn('enquiries', 'academic_year_id')) {
            Schema::table('enquiries', function (Blueprint $table) {
                $table->dropForeign(['academic_year_id']);
            });
        }

        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'academic_year_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropForeign(['academic_year_id']);
            });
        }

        if (Schema::hasTable('student_fees') && Schema::hasColumn('student_fees', 'academic_year_id')) {
            Schema::table('student_fees', function (Blueprint $table) {
                $table->dropForeign(['academic_year_id']);
            });
        }

        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'academic_year_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['academic_year_id']);
            });
        }

        // Drop indexes AFTER foreign keys
        if ($this->indexExists('batches', 'idx_batches_year_course')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->dropIndex('idx_batches_year_course');
            });
        }

        if (Schema::hasTable('student_fees') && $this->indexExists('student_fees', 'idx_student_fees_year_student')) {
            Schema::table('student_fees', function (Blueprint $table) {
                $table->dropIndex('idx_student_fees_year_student');
            });
        }

        if (Schema::hasTable('payments') && $this->indexExists('payments', 'idx_payments_year_student')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('idx_payments_year_student');
            });
        }

        if (Schema::hasTable('attendances') && $this->indexExists('attendances', 'idx_attendance_year_student_date')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex('idx_attendance_year_student_date');
            });
        }

        // Drop columns LAST
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'academic_year_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('academic_year_id');
            });
        }

        if (Schema::hasTable('student_fees') && Schema::hasColumn('student_fees', 'academic_year_id')) {
            Schema::table('student_fees', function (Blueprint $table) {
                $table->dropColumn('academic_year_id');
            });
        }

        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'academic_year_id')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('academic_year_id');
            });
        }

        if (Schema::hasTable('enquiries') && Schema::hasColumn('enquiries', 'academic_year_id')) {
            Schema::table('enquiries', function (Blueprint $table) {
                $table->dropColumn('academic_year_id');
            });
        }

        if (Schema::hasTable('admissions') && Schema::hasColumn('admissions', 'academic_year_id')) {
            Schema::table('admissions', function (Blueprint $table) {
                $table->dropColumn('academic_year_id');
            });
        }

        if (Schema::hasColumn('batches', 'academic_year_id')) {
            Schema::table('batches', function (Blueprint $table) {
                $table->dropColumn('academic_year_id');
            });
        }
    }
};
