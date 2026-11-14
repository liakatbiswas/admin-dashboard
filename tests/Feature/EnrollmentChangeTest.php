<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\EnrollmentChange;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class EnrollmentChangeTest extends TestCase
{
    /** @return array{0: Enrollment, 1: Classroom} */
    private function placements(int $grade = 9): array
    {
        $this->travelTo(Carbon::parse('2027-06-15 10:00:00', 'Asia/Dhaka'));
        $year = AcademicYear::factory()->create(['year' => 2027]);
        $source = Classroom::factory()->create(['academic_year_id' => $year->id, 'grade' => $grade]);
        $destination = Classroom::factory()->create(['academic_year_id' => $year->id, 'grade' => $grade, 'shift' => 'Day', 'section' => 'B']);

        return [Enrollment::factory()->create(['classroom_id' => $source->id, 'roll' => 3, 'group' => $grade >= 9 ? 'Science' : null]), $destination];
    }

    /** @return array<string, mixed> */
    private function payload(Enrollment $enrollment, Classroom $destination, array $overrides = []): array
    {
        return array_replace(['expected_classroom_id' => $enrollment->classroom_id, 'expected_roll' => $enrollment->roll,
            'expected_group' => $enrollment->group, 'classroom_id' => $destination->id, 'roll' => 7,
            'effective_date' => '2027-06-15', 'reason' => 'Approved school record change'], $overrides);
    }

    private function url(Enrollment $enrollment, string $operation = 'transfer'): string
    {
        return route('students.'.$operation, [$enrollment->student_id, $enrollment]);
    }

    #[DataProvider('officerRoles')]
    public function test_authorized_officer_transfers_with_history_and_preserves_identity(string $role): void
    {
        [$enrollment, $destination] = $this->placements();
        $sourceId = $enrollment->classroom_id;
        $identity = $enrollment->student->getAttributes();
        $officer = User::factory()->create()->assignRole($role);
        $officer->givePermissionTo(['students.transfers.manage', 'students.rolls.manage']);
        $otherYear = Classroom::factory()->create(['academic_year_id' => AcademicYear::factory()->create(['year' => 2026])->id]);
        $prior = Enrollment::factory()->create(['student_id' => $enrollment->student_id, 'classroom_id' => $otherYear->id]);
        $priorAttributes = $prior->fresh()->getAttributes();

        $this->actingAs($officer)->patch($this->url($enrollment), $this->payload($enrollment, $destination, ['group' => 'Humanities', 'student_number' => 'FORGED']))
            ->assertRedirect(route('students.show', $enrollment->student_id))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'classroom_id' => $destination->id, 'roll' => 7, 'group' => 'Science']);
        $this->assertSame($identity, $enrollment->student->fresh()->getAttributes());
        $this->assertSame($priorAttributes, $prior->fresh()->getAttributes());
        $this->assertSame(0, Classroom::findOrFail($sourceId)->enrollments()->count());
        $change = EnrollmentChange::sole();
        $this->assertSame($sourceId, $change->before['classroom_id']);
        $this->assertSame(3, $change->before['roll']);
        $this->assertSame($destination->id, $change->after['classroom_id']);
        $this->assertSame(7, $change->after['roll']);
        $this->assertSame('2027-06-15', $change->effective_date->toDateString());
        $this->assertSame($officer->id, $change->changed_by);
        $this->assertSame('Approved school record change', $change->reason);
        $this->assertDatabaseHas('activity_log', ['description' => 'Shift or section changed', 'causer_id' => $officer->id]);
        $this->get(route('students.show', $enrollment->student_id))->assertOk()->assertSee('Placement change history')->assertSee('Approved school record change')->assertSee('Transfer student');
    }

    public static function officerRoles(): array
    {
        return ['admin' => ['admin'], 'teacher' => ['teacher'], 'clerk' => ['clerk']];
    }

    public function test_guest_is_redirected_and_nested_enrollment_is_scoped(): void
    {
        [$enrollment, $destination] = $this->placements();
        $this->patch($this->url($enrollment), $this->payload($enrollment, $destination))->assertRedirect(route('login'));
        $other = Student::factory()->create();
        $this->actingAs($this->superAdmin())->patch(route('students.transfer', [$other, $enrollment]), $this->payload($enrollment, $destination))->assertNotFound();
        $this->patch(route('students.group', [$other, $enrollment]), $this->payload($enrollment, $destination, ['group' => 'Humanities']))->assertNotFound();
        $this->assertDatabaseCount('enrollment_changes', 0);
    }

    #[DataProvider('blockedRoles')]
    public function test_ineligible_roles_cannot_use_either_permission(string $role): void
    {
        $user = User::factory()->create(['student_id' => 'NGHS20270600001'])->assignRole($role);
        $user->givePermissionTo(['students.transfers.manage', 'students.groups.manage', 'students.rolls.manage']);
        $this->assertFalse($user->can('students.transfers.manage'));
        $this->assertFalse($user->can('students.groups.manage'));
        [$enrollment, $destination] = $this->placements();
        $this->actingAs($user)->patch($this->url($enrollment), $this->payload($enrollment, $destination))->assertForbidden();
        $this->patch($this->url($enrollment, 'group'), $this->payload($enrollment, $destination, ['group' => 'Humanities']))->assertForbidden();
        $this->assertDatabaseCount('enrollment_changes', 0);
    }

    public static function blockedRoles(): array
    {
        return ['staff' => ['staff'], 'student' => ['student'], 'guardian' => ['guardian']];
    }

    public function test_transfer_requires_both_permissions_and_group_permission_is_separate(): void
    {
        [$enrollment, $destination] = $this->placements();
        $teacher = User::factory()->create()->assignRole('teacher');
        $teacher->givePermissionTo('students.transfers.manage');
        $this->actingAs($teacher)->patch($this->url($enrollment), $this->payload($enrollment, $destination))->assertForbidden();
        $this->get(route('students.show', $enrollment->student_id))->assertOk()->assertSee('Class Roll permission is also required')->assertDontSee('Transfer student');
        $teacher->syncPermissions(['students.rolls.manage']);
        $this->patch($this->url($enrollment), $this->payload($enrollment, $destination))->assertForbidden();
        $this->patch($this->url($enrollment, 'group'), $this->payload($enrollment, $destination, ['group' => 'Humanities']))->assertForbidden();
        $this->assertDatabaseCount('enrollment_changes', 0);
    }

    #[DataProvider('invalidDestinations')]
    public function test_invalid_destination_keeps_the_original_placement(array $attributes): void
    {
        [$enrollment, $destination] = $this->placements();
        if (isset($attributes['year'])) {
            $attributes = ['academic_year_id' => AcademicYear::factory()->create(['year' => $attributes['year']])->id];
        }
        $destination->update($attributes);
        $this->actingAs($this->superAdmin())->patch($this->url($enrollment), $this->payload($enrollment, $destination))->assertSessionHasErrors('classroom_id');
        $this->assertSame($enrollment->classroom_id, $enrollment->fresh()->classroom_id);
        $this->assertDatabaseCount('enrollment_changes', 0);
    }

    public static function invalidDestinations(): array
    {
        return ['inactive' => [['is_active' => false]], 'different class' => [['grade' => 10]], 'different year' => [['year' => 2028]]];
    }

    public function test_same_classroom_and_duplicate_roll_are_rejected(): void
    {
        [$enrollment, $destination] = $this->placements();
        Enrollment::factory()->create(['classroom_id' => $destination->id, 'roll' => 7, 'group' => 'Science']);
        $this->actingAs($this->superAdmin())->patch($this->url($enrollment), $this->payload($enrollment, $enrollment->classroom))->assertSessionHasErrors('classroom_id');
        $this->patch($this->url($enrollment), $this->payload($enrollment, $destination))->assertSessionHasErrors('roll');
        $this->assertSame($enrollment->classroom_id, $enrollment->fresh()->classroom_id);
        $this->assertDatabaseCount('enrollment_changes', 0);
    }

    public function test_full_destination_requires_super_admin_and_audited_override_reason(): void
    {
        [$enrollment, $destination] = $this->placements();
        $destination->update(['capacity' => 1]);
        Enrollment::factory()->create(['classroom_id' => $destination->id, 'group' => 'Science']);
        $officer = User::factory()->create()->assignRole('admin');
        $officer->givePermissionTo(['students.transfers.manage', 'students.rolls.manage']);
        $payload = $this->payload($enrollment, $destination, ['override_reason' => 'Special seat approval']);
        $this->actingAs($officer)->patch($this->url($enrollment), $payload)->assertSessionHasErrors('classroom_id');
        $this->flushSession();
        $this->actingAs($this->superAdmin())->patch($this->url($enrollment), $this->payload($enrollment, $destination))->assertSessionHasErrors('classroom_id');
        $this->assertDatabaseCount('enrollment_changes', 0);
        $this->patch($this->url($enrollment), $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, $destination->enrollments()->count());
        $change = EnrollmentChange::sole();
        $this->assertSame('Special seat approval', $change->override_reason);
        $this->assertSame($this->superAdmin()->id, $change->changed_by);
        $this->assertSame('Special seat approval', Activity::where('description', 'Shift or section changed')->sole()->properties['override_reason']);
    }

    #[DataProvider('groupGrades')]
    public function test_group_permission_changes_only_group_and_keeps_old_snapshot(int $grade): void
    {
        [$enrollment, $destination] = $this->placements($grade);
        $officer = User::factory()->create()->assignRole('teacher');
        $officer->givePermissionTo('students.groups.manage');
        $this->actingAs($officer)->patch($this->url($enrollment, 'group'), $this->payload($enrollment, $destination, ['group' => 'Business Studies']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'classroom_id' => $enrollment->classroom_id, 'roll' => 3, 'group' => 'Business Studies']);
        $change = EnrollmentChange::sole();
        $this->assertSame('Science', $change->before['group']);
        $this->assertSame('Business Studies', $change->after['group']);
        $this->assertSame('group', $change->type);
        $this->assertDatabaseHas('activity_log', ['description' => 'Student group changed']);
        $this->get(route('students.show', $enrollment->student_id))->assertOk()->assertSee('Update group')->assertDontSee('Transfer student');
    }

    public static function groupGrades(): array
    {
        return ['class nine' => [9], 'class ten' => [10]];
    }

    public function test_lower_classes_cannot_change_group_but_can_transfer(): void
    {
        [$enrollment, $destination] = $this->placements(6);
        $this->actingAs($this->superAdmin())->patch($this->url($enrollment, 'group'), $this->payload($enrollment, $destination, ['group' => 'Humanities']))->assertSessionHasErrors('group');
        $this->get(route('students.show', $enrollment->student_id))->assertOk()->assertDontSee('Update group');
        $this->patch($this->url($enrollment), $this->payload($enrollment, $destination))->assertSessionHasNoErrors();
        $this->assertNull($enrollment->fresh()->group);
    }

    #[DataProvider('invalidGroupChanges')]
    public function test_invalid_group_changes_are_rejected(mixed $group): void
    {
        [$enrollment, $destination] = $this->placements();
        $this->actingAs($this->superAdmin())->patch($this->url($enrollment, 'group'), $this->payload($enrollment, $destination, ['group' => $group]))->assertSessionHasErrors('group');
        $this->assertSame('Science', $enrollment->fresh()->group);
        $this->assertDatabaseCount('enrollment_changes', 0);
    }

    public static function invalidGroupChanges(): array
    {
        return ['unchanged' => ['Science'], 'unsupported' => ['Arts'], 'missing' => [null]];
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_transfer_input_does_not_write_history(array $overrides, string $field): void
    {
        [$enrollment, $destination] = $this->placements();
        $this->actingAs($this->superAdmin())->patch($this->url($enrollment), $this->payload($enrollment, $destination, $overrides))->assertSessionHasErrors($field);
        $this->assertSame($enrollment->classroom_id, $enrollment->fresh()->classroom_id);
        $this->assertDatabaseCount('enrollment_changes', 0);
    }

    public static function invalidInput(): array
    {
        return [
            'no reason' => [['reason' => ''], 'reason'],
            'invalid roll' => [['roll' => 0], 'roll'],
            'no date' => [['effective_date' => ''], 'effective_date'],
            'future date' => [['effective_date' => '2027-06-16'], 'effective_date'],
            'wrong year' => [['effective_date' => '2026-06-15'], 'effective_date'],
            'invalid date' => [['effective_date' => '2027-02-30'], 'effective_date'],
            'stale room' => [['expected_classroom_id' => 999999], 'expected_classroom_id'],
            'stale roll' => [['expected_roll' => 2], 'expected_classroom_id'],
            'stale group' => [['expected_group' => 'Humanities'], 'expected_classroom_id'],
        ];
    }

    public function test_change_dates_cannot_rewrite_the_existing_timeline(): void
    {
        [$enrollment, $destination] = $this->placements();
        EnrollmentChange::factory()->create(['enrollment_id' => $enrollment->id, 'effective_date' => '2027-06-14']);
        $this->actingAs($this->superAdmin())->patch($this->url($enrollment), $this->payload($enrollment, $destination, ['effective_date' => '2027-06-13']))->assertSessionHasErrors('effective_date');
        $this->patch($this->url($enrollment, 'group'), $this->payload($enrollment, $destination, ['group' => 'Humanities', 'effective_date' => '2027-06-13']))->assertSessionHasErrors('effective_date');
        $this->assertDatabaseCount('enrollment_changes', 1);
    }

    public function test_old_roll_and_group_forms_cannot_change_a_transferred_enrollment(): void
    {
        [$enrollment, $destination] = $this->placements();
        $oldPayload = $this->payload($enrollment, $destination, ['roll' => 3]);
        $this->actingAs($this->superAdmin())->patch($this->url($enrollment), $oldPayload)->assertSessionHasNoErrors();
        $this->patch(route('students.roll.update', [$enrollment->student_id, $enrollment]), array_replace($oldPayload, ['roll' => 8]))->assertSessionHasErrors();
        $this->patch($this->url($enrollment, 'group'), array_replace($oldPayload, ['group' => 'Humanities']))->assertSessionHasErrors('expected_classroom_id');
        $this->assertSame(3, $enrollment->fresh()->roll);
        $this->assertSame('Science', $enrollment->fresh()->group);
        $this->assertDatabaseCount('enrollment_changes', 1);
    }

    public function test_audit_failure_rolls_back_both_transfer_and_history(): void
    {
        [$enrollment, $destination] = $this->placements();
        $this->actingAs($this->superAdmin());
        Event::listen('eloquent.creating: '.Activity::class, fn () => throw new \RuntimeException('Audit unavailable'));
        $this->patch($this->url($enrollment), $this->payload($enrollment, $destination))->assertServerError();
        $this->assertSame($enrollment->classroom_id, $enrollment->fresh()->classroom_id);
        $this->assertDatabaseCount('enrollment_changes', 0);
    }

    public function test_history_escapes_reasons_and_retains_deleted_actor_name(): void
    {
        [$enrollment, $destination] = $this->placements();
        $officer = User::factory()->create()->assignRole('clerk');
        $officer->givePermissionTo(['students.transfers.manage', 'students.rolls.manage']);
        $this->actingAs($officer)->patch($this->url($enrollment), $this->payload($enrollment, $destination, ['reason' => '<script>alert(1)</script>']))->assertSessionHasNoErrors();
        $officer->delete();
        $this->flushSession();
        $this->actingAs($this->superAdmin())->get(route('students.show', $enrollment->student_id))->assertOk()
            ->assertSee($officer->name)->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
}
