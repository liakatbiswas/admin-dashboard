<?php

namespace Tests\Feature;

use App\Console\Commands\AlignAcademicMigrationHistory;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademicMigrationSplitTest extends TestCase
{
    public function test_alignment_preserves_records_and_original_batch_and_is_repeatable(): void
    {
        $room = Classroom::factory()->create();
        $student = Student::factory()->create();
        Enrollment::factory()->create(['classroom_id' => $room->id, 'student_id' => $student->id]);
        DB::table('admission_on_off')->where('id', 1)->update(['deadline' => '2027-12-31 17:59:00']);
        $tables = array_unique([...array_values(AlignAcademicMigrationHistory::MIGRATIONS), 'users']);
        $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
        $this->legacyHistory();

        $this->assertSame(0, Artisan::call('sms:align-academic-migrations'));
        $this->assertDatabaseMissing('migrations', ['migration' => AlignAcademicMigrationHistory::LEGACY]);
        foreach (array_keys(AlignAcademicMigrationHistory::MIGRATIONS) as $name) {
            $this->assertDatabaseHas('migrations', ['migration' => $name, 'batch' => 9]);
        }
        foreach ($before as $table => $records) {
            $this->assertSame($records, DB::table($table)->orderBy('id')->get()->toJson());
        }
        $this->assertSame(0, Artisan::call('sms:align-academic-migrations'));
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        $this->assertStringContainsString('Nothing to migrate', Artisan::output());
    }

    public function test_mixed_history_is_rejected_without_changing_metadata(): void
    {
        $this->legacyHistory();
        DB::table('migrations')->insert(['migration' => array_key_first(AlignAcademicMigrationHistory::MIGRATIONS), 'batch' => 10]);
        $before = DB::table('migrations')->orderBy('id')->get()->toJson();
        $this->assertSame(1, Artisan::call('sms:align-academic-migrations'));
        $this->assertSame($before, DB::table('migrations')->orderBy('id')->get()->toJson());
    }

    public function test_fresh_installation_can_roll_back_and_reapply_the_seven_migrations(): void
    {
        $userCount = DB::table('users')->count();
        $this->assertSame(0, Artisan::call('migrate:rollback', ['--step' => DB::table('migrations')->where('migration', '>=', array_key_first(AlignAcademicMigrationHistory::MIGRATIONS))->count(), '--force' => true]));
        foreach (['academic_years', 'classrooms', 'admission_applications', 'student_sequences', 'students', 'enrollments'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        $this->assertFalse(Schema::hasColumn('admission_on_off', 'deadline'));
        $this->assertSame($userCount, DB::table('users')->count());
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));
        $this->assertTrue(Schema::hasColumn('admission_on_off', 'deadline'));
        $room = Classroom::factory()->create();
        $this->assertDatabaseHas('classrooms', ['id' => $room->id]);
        $this->assertSame(0, Artisan::call('sms:align-academic-migrations'));
    }

    private function legacyHistory(): void
    {
        DB::table('migrations')->whereIn('migration', array_keys(AlignAcademicMigrationHistory::MIGRATIONS))->delete();
        DB::table('migrations')->insert(['migration' => AlignAcademicMigrationHistory::LEGACY, 'batch' => 9]);
    }
}
