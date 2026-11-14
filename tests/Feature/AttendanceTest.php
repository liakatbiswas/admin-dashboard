<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    private function classroom(): Classroom
    {
        $this->travelTo(Carbon::parse('2027-06-14 20:30:00', 'UTC'));

        return Classroom::factory()->create(['academic_year_id' => AcademicYear::factory()->create(['year' => 2027])->id, 'grade' => 9]);
    }

    private function enrollment(Classroom $classroom, int $roll = 3): Enrollment
    {
        return Enrollment::factory()->create(['classroom_id' => $classroom->id, 'roll' => $roll, 'group' => 'Science']);
    }

    /** @return array<string, mixed> */
    private function payload(Enrollment $enrollment, array $overrides = []): array
    {
        return array_replace(['classroom_id' => $enrollment->classroom_id, 'date' => '2027-06-15',
            'entries' => [['enrollment_id' => $enrollment->id, 'status' => 'Present']]], $overrides);
    }

    #[DataProvider('officerRoles')]
    public function test_authorized_officer_records_daily_statuses_without_marking_blank_students_absent(string $role): void
    {
        $classroom = $this->classroom();
        $officer = User::factory()->create()->assignRole($role);
        $officer->givePermissionTo('attendance.record');
        $present = $this->enrollment($classroom, 1);
        $absent = $this->enrollment($classroom, 2);
        $leave = $this->enrollment($classroom, 3);
        $unmarked = $this->enrollment($classroom, 4);
        $entries = [['enrollment_id' => $present->id, 'status' => 'Present'], ['enrollment_id' => $absent->id, 'status' => 'Absent'],
            ['enrollment_id' => $leave->id, 'status' => 'Approved Leave'], ['enrollment_id' => $unmarked->id, 'status' => '']];

        $this->actingAs($officer)->post(route('attendance.store'), $this->payload($present, ['entries' => $entries]))->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseCount('attendances', 3);
        $this->assertDatabaseHas('attendances', ['student_id' => $present->student_id, 'roll' => 1, 'group' => 'Science', 'status' => 'Present', 'recorded_by' => $officer->id]);
        $this->assertDatabaseHas('attendances', ['student_id' => $absent->student_id, 'status' => 'Absent']);
        $this->assertDatabaseHas('attendances', ['student_id' => $leave->student_id, 'status' => 'Approved Leave']);
        $this->assertDatabaseMissing('attendances', ['student_id' => $unmarked->student_id]);
        $this->assertSame(3, Activity::where('description', 'Attendance recorded')->count());
        $this->get(route('attendance.index', ['classroom_id' => $classroom->id]))->assertOk()->assertSee('2027-06-15')->assertSee('Approved Leave')->assertSee('Save selected attendance');
        $this->get(route('attendance.show', Attendance::first()))->assertOk()->assertDontSee('Save correction');
    }

    public static function officerRoles(): array
    {
        return ['admin' => ['admin'], 'teacher' => ['teacher'], 'clerk' => ['clerk']];
    }

    public function test_guest_and_user_without_attendance_permission_cannot_access_records(): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $attendance = Attendance::factory()->create(['enrollment_id' => $enrollment->id]);
        $this->get(route('attendance.index'))->assertRedirect(route('login'));
        $this->post(route('attendance.store'), $this->payload($enrollment))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create()->assignRole('teacher'))->get(route('attendance.show', $attendance))->assertForbidden();
        $this->post(route('attendance.store'), $this->payload($enrollment))->assertForbidden();
        $this->patch(route('attendance.update', $attendance), ['status' => 'Absent', 'expected_version' => 1, 'reason' => 'Correction'])->assertForbidden();
    }

    #[DataProvider('blockedRoles')]
    public function test_non_academic_roles_cannot_use_stored_attendance_permissions(string $role): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $user = User::factory()->create(['student_id' => 'NGHS20270600001'])->assignRole($role);
        $user->givePermissionTo(['attendance.view', 'attendance.record', 'attendance.correct']);
        $this->assertFalse($user->can('attendance.view'));
        $this->assertFalse($user->can('attendance.record'));
        $this->assertFalse($user->can('attendance.correct'));
        $this->actingAs($user)->get(route('attendance.index'))->assertForbidden();
        $this->post(route('attendance.store'), $this->payload($enrollment))->assertForbidden();
        $this->assertDatabaseCount('attendances', 0);
    }

    public static function blockedRoles(): array
    {
        return ['staff' => ['staff'], 'student' => ['student'], 'guardian' => ['guardian']];
    }

    public function test_view_record_and_correction_permissions_are_independent(): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $attendance = Attendance::factory()->create(['enrollment_id' => $enrollment->id]);
        $officer = User::factory()->create()->assignRole('teacher');
        $officer->givePermissionTo('attendance.view');
        $this->actingAs($officer)->get(route('attendance.show', $attendance))->assertOk()->assertDontSee('Save correction');
        $this->get(route('attendance.index', ['classroom_id' => $room->id]))->assertOk()->assertDontSee('Save selected attendance');
        $this->post(route('attendance.store'), $this->payload($enrollment))->assertForbidden();
        $this->patch(route('attendance.update', $attendance), ['status' => 'Absent', 'expected_version' => 1, 'reason' => 'Correction'])->assertForbidden();
        $officer->syncPermissions(['attendance.record']);
        $this->patch(route('attendance.update', $attendance), ['status' => 'Absent', 'expected_version' => 1, 'reason' => 'Correction'])->assertForbidden();
        $officer->syncPermissions(['attendance.correct']);
        $this->post(route('attendance.store'), $this->payload($enrollment))->assertForbidden();
        $this->patch(route('attendance.update', $attendance), ['status' => 'Absent', 'expected_version' => 1, 'reason' => 'Signed register checked'])->assertSessionHasNoErrors();
        $this->assertSame('Absent', $attendance->fresh()->status);
    }

    #[DataProvider('invalidDates')]
    public function test_invalid_dates_cannot_be_recorded(string $date): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $this->actingAs($this->superAdmin())->post(route('attendance.store'), $this->payload($enrollment, ['date' => $date]))->assertSessionHasErrors();
        $this->assertDatabaseCount('attendances', 0);
    }

    public static function invalidDates(): array
    {
        return ['future' => ['2027-06-16'], 'different year' => ['2026-06-15'], 'invalid calendar date' => ['2027-02-30'], 'missing' => ['']];
    }

    #[DataProvider('invalidEntries')]
    public function test_invalid_or_empty_status_is_not_saved(mixed $status): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $this->actingAs($this->superAdmin())->post(route('attendance.store'), $this->payload($enrollment, ['entries' => [['enrollment_id' => $enrollment->id, 'status' => $status]]]))->assertSessionHasErrors();
        $this->assertDatabaseCount('attendances', 0);
    }

    public static function invalidEntries(): array
    {
        return ['unsupported' => ['Late'], 'blank' => [''], 'array' => [['Present']]];
    }

    public function test_duplicate_student_entries_and_unknown_enrollment_are_rejected(): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $entry = ['enrollment_id' => $enrollment->id, 'status' => 'Present'];
        $this->actingAs($this->superAdmin())->post(route('attendance.store'), $this->payload($enrollment, ['entries' => [$entry, $entry]]))->assertSessionHasErrors('entries.0.enrollment_id');
        $this->post(route('attendance.store'), $this->payload($enrollment, ['entries' => [['enrollment_id' => 999999, 'status' => 'Present']]]))->assertSessionHasErrors('entries');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_wrong_classroom_or_year_rolls_back_the_whole_batch(): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $otherRoom = Classroom::factory()->create(['academic_year_id' => $room->academic_year_id, 'section' => 'B']);
        $other = $this->enrollment($otherRoom);
        $entries = [['enrollment_id' => $enrollment->id, 'status' => 'Present'], ['enrollment_id' => $other->id, 'status' => 'Absent']];
        $this->actingAs($this->superAdmin())->post(route('attendance.store'), $this->payload($enrollment, ['entries' => $entries]))->assertSessionHasErrors('entries');
        $otherRoom->update(['academic_year_id' => AcademicYear::factory()->create(['year' => 2026])->id]);
        $other->update(['academic_year_id' => $otherRoom->academic_year_id]);
        $this->post(route('attendance.store'), $this->payload($enrollment, ['entries' => $entries]))->assertSessionHasErrors('entries');
        $this->assertDatabaseCount('attendances', 0);
        $this->assertSame(0, Activity::where('description', 'Attendance recorded')->count());
    }

    public function test_duplicate_daily_attendance_is_rejected_and_other_dates_are_allowed(): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $this->actingAs($this->superAdmin())->post(route('attendance.store'), $this->payload($enrollment))->assertSessionHasNoErrors();
        $this->post(route('attendance.store'), $this->payload($enrollment))->assertSessionHasErrors('entries');
        $this->assertDatabaseCount('attendances', 1);
        $this->post(route('attendance.store'), $this->payload($enrollment, ['date' => '2027-06-14']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('attendances', 2);
    }

    public function test_inactive_classroom_prevents_new_records_but_allows_historical_corrections(): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $attendance = Attendance::factory()->create(['enrollment_id' => $enrollment->id]);
        $room->update(['is_active' => false]);
        $this->actingAs($this->superAdmin())->post(route('attendance.store'), $this->payload($enrollment))->assertSessionHasErrors('classroom_id');
        $this->get(route('attendance.index', ['classroom_id' => $room->id, 'date' => '2027-06-01']))->assertOk()->assertSee('View record')->assertDontSee('Save selected attendance');
        $this->patch(route('attendance.update', $attendance), ['status' => 'Approved Leave', 'expected_version' => 1, 'reason' => 'Leave approved'])->assertSessionHasNoErrors();
        $this->assertSame('Approved Leave', $attendance->fresh()->status);
    }

    public function test_corrections_preserve_original_placement_and_record_an_escaped_audit_history(): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $attendance = Attendance::factory()->create(['enrollment_id' => $enrollment->id]);
        $original = $attendance->fresh()->only(['student_id', 'enrollment_id', 'classroom_id', 'roll', 'group', 'recorded_by']);
        $createdAt = $attendance->getRawOriginal('created_at');
        $this->actingAs($this->superAdmin())->patch(route('attendance.update', $attendance), ['status' => 'Approved Leave', 'expected_version' => 1,
            'reason' => '<script>check</script>', 'roll' => 99, 'date' => '2027-01-01'])->assertRedirect(route('attendance.show', $attendance))->assertSessionHasNoErrors();
        $this->assertSame($original, $attendance->fresh()->only(array_keys($original)));
        $this->assertSame($createdAt, $attendance->fresh()->getRawOriginal('created_at'));
        $this->assertSame('2027-06-01', $attendance->fresh()->date->toDateString());
        $this->assertSame(2, $attendance->fresh()->version);
        $history = AttendanceCorrection::sole();
        $this->assertSame('Present', $history->old_status);
        $this->assertSame('Approved Leave', $history->new_status);
        $this->assertSame('<script>check</script>', $history->reason);
        $this->assertSame($this->superAdmin()->id, $history->corrected_by);
        $this->assertDatabaseHas('activity_log', ['description' => 'Attendance corrected']);
        $this->get(route('attendance.show', $attendance))->assertOk()->assertSee('&lt;script&gt;check&lt;/script&gt;', false)->assertDontSee('<script>check</script>', false);
    }

    #[DataProvider('invalidCorrections')]
    public function test_invalid_correction_keeps_status_and_history_unchanged(array $overrides, string $field): void
    {
        $room = $this->classroom();
        $attendance = Attendance::factory()->create(['enrollment_id' => $this->enrollment($room)->id]);
        $data = array_replace(['status' => 'Absent', 'expected_version' => 1, 'reason' => 'Correction'], $overrides);
        $this->actingAs($this->superAdmin())->patch(route('attendance.update', $attendance), $data)->assertSessionHasErrors($field);
        $this->assertSame('Present', $attendance->fresh()->status);
        $this->assertDatabaseCount('attendance_corrections', 0);
    }

    public static function invalidCorrections(): array
    {
        return ['same status' => [['status' => 'Present'], 'status'], 'invalid status' => [['status' => 'Late'], 'status'],
            'reason required' => [['reason' => ''], 'reason'], 'stale form' => [['expected_version' => 2], 'expected_version']];
    }

    public function test_stale_correction_cannot_overwrite_a_status_changed_back_to_its_original_value(): void
    {
        $room = $this->classroom();
        $attendance = Attendance::factory()->create(['enrollment_id' => $this->enrollment($room)->id]);
        $this->actingAs($this->superAdmin())->patch(route('attendance.update', $attendance), ['status' => 'Absent', 'expected_version' => 1, 'reason' => 'First correction'])->assertSessionHasNoErrors();
        $this->patch(route('attendance.update', $attendance), ['status' => 'Present', 'expected_version' => 2, 'reason' => 'Register rechecked'])->assertSessionHasNoErrors();
        $this->patch(route('attendance.update', $attendance), ['status' => 'Approved Leave', 'expected_version' => 1, 'reason' => 'Old form'])->assertSessionHasErrors('expected_version');
        $this->assertSame('Present', $attendance->fresh()->status);
        $this->assertDatabaseCount('attendance_corrections', 2);
    }

    public function test_audit_failure_rolls_back_every_attendance_in_the_batch(): void
    {
        $room = $this->classroom();
        $first = $this->enrollment($room, 1);
        $second = $this->enrollment($room, 2);
        $this->actingAs($this->superAdmin());
        $attempts = 0;
        Event::listen('eloquent.creating: '.Activity::class, function () use (&$attempts): void {
            if (++$attempts === 2) {
                throw new \RuntimeException('Audit unavailable');
            }
        });
        $this->post(route('attendance.store'), $this->payload($first, ['entries' => [['enrollment_id' => $first->id, 'status' => 'Present'], ['enrollment_id' => $second->id, 'status' => 'Absent']]]))->assertServerError();
        $this->assertDatabaseCount('attendances', 0);
        $this->assertSame(0, Activity::where('description', 'Attendance recorded')->count());
        $this->assertSame(2, $attempts);
    }

    public function test_correction_audit_failure_rolls_back_status_and_history(): void
    {
        $room = $this->classroom();
        $attendance = Attendance::factory()->create(['enrollment_id' => $this->enrollment($room)->id]);
        $this->actingAs($this->superAdmin());
        Event::listen('eloquent.creating: '.Activity::class, fn () => throw new \RuntimeException('Audit unavailable'));
        $this->patch(route('attendance.update', $attendance), ['status' => 'Absent', 'expected_version' => 1, 'reason' => 'Register checked'])->assertServerError();
        $this->assertSame('Present', $attendance->fresh()->status);
        $this->assertSame(1, $attendance->fresh()->version);
        $this->assertDatabaseCount('attendance_corrections', 0);
    }

    public function test_transfer_keeps_existing_attendance_and_prevents_a_second_record_in_destination(): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $destination = Classroom::factory()->create(['academic_year_id' => $room->academic_year_id, 'grade' => 9, 'shift' => 'Day']);
        $this->actingAs($this->superAdmin())->post(route('attendance.store'), $this->payload($enrollment))->assertSessionHasNoErrors();
        $attendance = Attendance::sole();
        $original = $attendance->getAttributes();
        $this->patch(route('students.transfer', [$enrollment->student_id, $enrollment]), ['classroom_id' => $destination->id, 'roll' => 7,
            'expected_classroom_id' => $room->id, 'expected_roll' => 3, 'expected_group' => 'Science', 'effective_date' => '2027-06-15', 'reason' => 'Approved transfer'])->assertSessionHasNoErrors();
        $this->assertSame($original, $attendance->fresh()->getAttributes());
        $this->post(route('attendance.store'), $this->payload($enrollment, ['classroom_id' => $destination->id]))->assertSessionHasErrors('entries');
        $this->get(route('attendance.index', ['classroom_id' => $destination->id]))->assertOk()->assertSee('Already recorded in another classroom on this date.');
        $this->get(route('attendance.index', ['classroom_id' => $room->id]))->assertOk()->assertSee($enrollment->student->student_number)->assertSee('View record');
        $this->assertDatabaseCount('attendances', 1);
        $this->get(route('attendance.show', $attendance))->assertOk()->assertSee('Morning');
    }

    public function test_historical_roster_and_new_records_follow_transfer_and_group_effective_dates(): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $destination = Classroom::factory()->create(['academic_year_id' => $room->academic_year_id, 'grade' => 9, 'shift' => 'Day']);
        $this->actingAs($this->superAdmin())->patch(route('students.transfer', [$enrollment->student_id, $enrollment]), ['classroom_id' => $destination->id, 'roll' => 7,
            'expected_classroom_id' => $room->id, 'expected_roll' => 3, 'expected_group' => 'Science', 'effective_date' => '2027-06-10', 'reason' => 'Transfer'])->assertSessionHasNoErrors();
        $this->patch(route('students.group', [$enrollment->student_id, $enrollment]), ['group' => 'Humanities', 'expected_classroom_id' => $destination->id,
            'expected_roll' => 7, 'expected_group' => 'Science', 'effective_date' => '2027-06-15', 'reason' => 'Group approved'])->assertSessionHasNoErrors();
        $this->get(route('attendance.index', ['classroom_id' => $room->id, 'date' => '2027-06-09']))->assertOk()->assertSee($enrollment->student->student_number);
        $this->get(route('attendance.index', ['classroom_id' => $destination->id, 'date' => '2027-06-09']))->assertOk()->assertDontSee($enrollment->student->student_number);
        $this->post(route('attendance.store'), $this->payload($enrollment, ['classroom_id' => $destination->id, 'date' => '2027-06-09']))->assertSessionHasErrors('entries');
        $this->post(route('attendance.store'), $this->payload($enrollment, ['date' => '2027-06-09']))->assertSessionHasNoErrors();
        $this->post(route('attendance.store'), $this->payload($enrollment, ['classroom_id' => $destination->id, 'date' => '2027-06-10']))->assertSessionHasNoErrors();
        $this->post(route('attendance.store'), $this->payload($enrollment, ['classroom_id' => $destination->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', ['classroom_id' => $room->id, 'date' => '2027-06-09', 'roll' => 3, 'group' => 'Science']);
        $this->assertSame(3, Attendance::whereDate('date', '2027-06-09')->sole()->roll);
        $this->assertSame('Science', Attendance::whereDate('date', '2027-06-10')->sole()->group);
        $this->assertSame('Humanities', Attendance::whereDate('date', '2027-06-15')->sole()->group);
    }

    public function test_same_day_transfer_and_group_changes_produce_the_correct_daily_placement(): void
    {
        $room = $this->classroom();
        $enrollment = $this->enrollment($room);
        $destination = Classroom::factory()->create(['academic_year_id' => $room->academic_year_id, 'grade' => 9, 'shift' => 'Day']);
        $this->actingAs($this->superAdmin())->patch(route('students.transfer', [$enrollment->student_id, $enrollment]), ['classroom_id' => $destination->id, 'roll' => 7,
            'expected_classroom_id' => $room->id, 'expected_roll' => 3, 'expected_group' => 'Science', 'effective_date' => '2027-06-15', 'reason' => 'Transfer'])->assertSessionHasNoErrors();
        $this->patch(route('students.group', [$enrollment->student_id, $enrollment]), ['group' => 'Humanities', 'expected_classroom_id' => $destination->id,
            'expected_roll' => 7, 'expected_group' => 'Science', 'effective_date' => '2027-06-15', 'reason' => 'Group approved'])->assertSessionHasNoErrors();
        $this->post(route('attendance.store'), $this->payload($enrollment, ['date' => '2027-06-14']))->assertSessionHasNoErrors();
        $this->post(route('attendance.store'), $this->payload($enrollment, ['classroom_id' => $destination->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', ['date' => '2027-06-14', 'classroom_id' => $room->id, 'roll' => 3, 'group' => 'Science']);
        $this->assertDatabaseHas('attendances', ['date' => '2027-06-15', 'classroom_id' => $destination->id, 'roll' => 7, 'group' => 'Humanities']);
    }

    public function test_large_roster_is_paginated_without_omitting_students(): void
    {
        $room = $this->classroom();
        $enrollments = Enrollment::factory()->count(101)->sequence(fn ($sequence) => ['roll' => $sequence->index + 1])
            ->create(['classroom_id' => $room->id, 'group' => 'Science']);
        $this->actingAs($this->superAdmin())->get(route('attendance.index', ['classroom_id' => $room->id]))->assertOk()
            ->assertSee($enrollments->first()->student->student_number)->assertDontSee($enrollments->last()->student->student_number);
        $this->get(route('attendance.index', ['classroom_id' => $room->id, 'page' => 2]))->assertOk()
            ->assertSee($enrollments->last()->student->student_number)->assertDontSee($enrollments->first()->student->student_number);
        $this->post(route('attendance.store'), $this->payload($enrollments->last()))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', ['student_id' => $enrollments->last()->student_id, 'roll' => 101]);
    }
}
