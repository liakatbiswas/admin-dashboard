<?php

namespace App\Console\Commands;

use DomainException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('sms:align-academic-migrations')]
#[Description('Run before migrate on an existing database: replace the legacy academic migration history without changing application data')]
class AlignAcademicMigrationHistory extends Command
{
    public const LEGACY = '2026_11_13_172031_create_academic_and_admission_tables';

    public const MIGRATIONS = [
        '2026_11_13_172031_create_academic_years_table' => 'academic_years',
        '2026_11_13_172032_create_classrooms_table' => 'classrooms',
        '2026_11_13_172033_create_admission_applications_table' => 'admission_applications',
        '2026_11_13_172034_create_student_sequences_table' => 'student_sequences',
        '2026_11_13_172035_create_students_table' => 'students',
        '2026_11_13_172036_create_enrollments_table' => 'enrollments',
        '2026_11_13_172037_add_deadline_to_admission_on_off_table' => 'admission_on_off',
    ];

    public function handle(): int
    {
        $table = config('database.migrations.table', 'migrations');
        if (! Schema::hasTable($table)) {
            $this->info('Fresh database: no history alignment is needed. Run php artisan migrate.');

            return self::SUCCESS;
        }
        try {
            $changed = DB::transaction(function () use ($table): bool {
                $legacy = DB::table($table)->where('migration', self::LEGACY)->lockForUpdate()->first();
                if (! $legacy) {
                    return false;
                }
                if (DB::table($table)->whereIn('migration', array_keys(self::MIGRATIONS))->exists()) {
                    throw new DomainException('Mixed legacy and split migration history found. No changes were made.');
                }
                foreach (self::MIGRATIONS as $migration => $schemaTable) {
                    if (! is_file(database_path('migrations/'.$migration.'.php')) || ! Schema::hasTable($schemaTable)) {
                        throw new DomainException('A migration file or expected table is missing. No changes were made.');
                    }
                }
                if (! Schema::hasColumn('admission_on_off', 'deadline')) {
                    throw new DomainException('The expected deadline column is missing. No changes were made.');
                }
                foreach (array_keys(self::MIGRATIONS) as $migration) {
                    DB::table($table)->insert(['migration' => $migration, 'batch' => $legacy->batch]);
                }
                DB::table($table)->where('id', $legacy->id)->delete();

                return true;
            });
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->info($changed
            ? 'Migration history aligned in the original batch. Application tables and data were not changed.'
            : 'No legacy migration found. No changes were needed.');

        return self::SUCCESS;
    }
}
