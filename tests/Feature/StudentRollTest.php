<?php

namespace Tests\Feature;

use App\Models\AdmissionApplication;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class StudentRollTest extends TestCase
{
    private function url(Enrollment $enrollment): string
    {
        return route('students.roll.update', [$enrollment->student_id, $enrollment->id]);
    }

    #[DataProvider('allowedRoles')]
    public function test_authorized_officer_changes_roll_with_history_and_preserves_identity(string $role): void
    {
        $officer = User::factory()->create()->assignRole($role);
        $officer->givePermissionTo('students.rolls.manage');
        $enrollment = Enrollment::factory()->create();
        $student = $enrollment->student;
        $account = User::factory()->create(['student_id' => $student->student_number])->assignRole('student');
        $student->update(['user_id' => $account->id]);
        $identity = $student->only(['student_number', 'admission_year', 'admission_grade', 'admission_serial']);
        $this->actingAs($officer)->patch($this->url($enrollment), ['expected_classroom_id' => $enrollment->classroom_id, 'roll' => 12, 'expected_roll' => 1, 'reason' => 'Corrected school register', 'student_number' => 'Forged'])->assertRedirect(route('students.show', $student))->assertSessionHasNoErrors();
        $this->assertSame(12, $enrollment->fresh()->roll);
        $this->assertSame($identity, $student->fresh()->only(array_keys($identity)));
        $this->assertSame($student->student_number, $account->fresh()->student_id);
        $log = Activity::where('description', 'Class roll changed')->sole();
        $this->assertSame($officer->id, $log->causer_id);
        $this->assertSame(1, $log->properties['old_roll']);
        $this->assertSame(12, $log->properties['new_roll']);
        $this->assertSame('Corrected school register', $log->properties['reason']);
        $this->get(route('students.show', $student))->assertOk()->assertSee('Corrected school register')->assertSee('Change class roll');
    }

    public static function allowedRoles(): array
    {
        return [['admin'], ['teacher'], ['clerk']];
    }

    #[DataProvider('deniedRoles')]
    public function test_other_roles_cannot_change_roll_even_with_stored_permission(string $role): void
    {
        $user = User::factory()->create(['student_id' => 'NGHS20270600001'])->assignRole($role);
        $user->givePermissionTo('students.rolls.manage');
        $enrollment = Enrollment::factory()->create();
        $this->actingAs($user)->patch($this->url($enrollment), ['expected_classroom_id' => $enrollment->classroom_id, 'roll' => 2, 'expected_roll' => 1, 'reason' => 'Attempt'])->assertForbidden();
        $this->assertSame(1, $enrollment->fresh()->roll);
    }

    public static function deniedRoles(): array
    {
        return [['staff'], ['student'], ['guardian']];
    }

    public function test_view_permission_does_not_allow_changing_roll(): void
    {
        $user = User::factory()->create()->assignRole('teacher');
        $user->givePermissionTo('students.view');
        $enrollment = Enrollment::factory()->create();
        $this->actingAs($user)->get(route('students.show', $enrollment->student))->assertOk()->assertDontSee('Change class roll');
        $this->patch($this->url($enrollment), ['expected_classroom_id' => $enrollment->classroom_id, 'roll' => 2, 'expected_roll' => 1, 'reason' => 'Attempt'])->assertForbidden();
    }

    public function test_duplicate_roll_is_rejected_but_the_same_roll_in_another_classroom_is_allowed(): void
    {
        $room = Classroom::factory()->create();
        $enrollment = Enrollment::factory()->create(['classroom_id' => $room->id]);
        Enrollment::factory()->create(['classroom_id' => $room->id, 'roll' => 2]);
        Enrollment::factory()->create(['roll' => 9]);
        $this->actingAs($this->superAdmin())->patch($this->url($enrollment), ['expected_classroom_id' => $enrollment->classroom_id, 'roll' => 2, 'expected_roll' => 1, 'reason' => 'Correction'])->assertSessionHasErrors('roll');
        $this->assertSame(1, $enrollment->fresh()->roll);
        $this->patch($this->url($enrollment), ['expected_classroom_id' => $enrollment->classroom_id, 'roll' => 9, 'expected_roll' => 1, 'reason' => 'Correction'])->assertSessionHasNoErrors();
        $this->assertSame(9, $enrollment->fresh()->roll);
    }

    public function test_stale_form_and_wrong_student_cannot_modify_an_enrollment(): void
    {
        $enrollment = Enrollment::factory()->create(['roll' => 5]);
        $other = Student::factory()->create();
        $this->actingAs($this->superAdmin())->patch($this->url($enrollment), ['expected_classroom_id' => $enrollment->classroom_id, 'roll' => 7, 'expected_roll' => 1, 'reason' => 'Stale form'])->assertSessionHasErrors('expected_roll');
        $this->patch(route('students.roll.update', [$other, $enrollment]), ['expected_classroom_id' => $enrollment->classroom_id, 'roll' => 7, 'expected_roll' => 5, 'reason' => 'Wrong student'])->assertNotFound();
        $this->assertSame(5, $enrollment->fresh()->roll);
    }

    #[DataProvider('invalidChanges')]
    public function test_invalid_changes_do_not_write_history(array $changes, string $field): void
    {
        $enrollment = Enrollment::factory()->create();
        $this->actingAs($this->superAdmin())->patch($this->url($enrollment), ['expected_classroom_id' => $enrollment->classroom_id, ...['roll' => 2, 'expected_roll' => 1, 'reason' => 'Correction'], ...$changes])->assertSessionHasErrors($field);
        $this->assertSame(1, $enrollment->fresh()->roll);
        $this->assertSame(0, Activity::where('description', 'Class roll changed')->count());
    }

    public static function invalidChanges(): array
    {
        return [[['roll' => 0], 'roll'], [['roll' => 10000], 'roll'], [['roll' => 1], 'roll'], [['reason' => ''], 'reason'], [['expected_roll' => null], 'expected_roll']];
    }

    public function test_audit_failure_rolls_back_the_roll_change(): void
    {
        $enrollment = Enrollment::factory()->create();
        $this->actingAs($this->superAdmin());
        Event::listen('eloquent.creating: '.Activity::class, fn () => throw new \RuntimeException('Audit unavailable'));
        $this->patch($this->url($enrollment), ['expected_classroom_id' => $enrollment->classroom_id, 'roll' => 2, 'expected_roll' => 1, 'reason' => 'Correction'])->assertServerError();
        $this->assertSame(1, $enrollment->fresh()->roll);
    }

    public function test_admission_permission_alone_does_not_authorize_initial_roll_assignment(): void
    {
        $officer = User::factory()->create()->assignRole('clerk');
        $officer->givePermissionTo('applications.admit');
        $room = Classroom::factory()->create();
        $application = AdmissionApplication::factory()->create(['academic_year_id' => $room->academic_year_id, 'status' => 'selected', 'selected_shift' => 'Morning', 'published_at' => now()]);
        $payload = ['classroom_id' => $room->id, 'roll' => 1, 'documents_checked' => 1, 'reason' => 'Verified'];
        $this->actingAs($officer)->post(route('applications.admit', $application), $payload)->assertForbidden();
        $this->assertSame(0, Student::count());
        $officer->givePermissionTo('students.rolls.manage');
        $this->post(route('applications.admit', $application), $payload)->assertOk();
        $this->assertSame(1, Student::count());
    }

    #[DataProvider('identityChanges')]
    public function test_permanent_student_identity_cannot_be_rewritten(string $field, mixed $value): void
    {
        $student = Student::factory()->create();
        $original = $student->getAttribute($field);
        try {
            $student->update([$field => $value]);
            $this->fail('Permanent identity change was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('student_number', $exception->errors());
        }
        $this->assertSame($original, $student->fresh()->getAttribute($field));
    }

    public static function identityChanges(): array
    {
        return [['student_number', 'NGHS20240612345'], ['admission_year', 2024], ['admission_grade', 7], ['admission_serial', 100000]];
    }

    public function test_existing_account_student_id_cannot_be_changed(): void
    {
        $user = User::factory()->create(['student_id' => 'NGHS20270600001']);
        try {
            $user->forceFill(['student_id' => 'NGHS20270600002'])->save();
            $this->fail('Account Student ID change was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('student_id', $exception->errors());
        }
        $this->assertSame('NGHS20270600001', $user->fresh()->student_id);
    }
}
