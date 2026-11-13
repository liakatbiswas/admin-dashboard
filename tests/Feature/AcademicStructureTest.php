<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Database\Seeders\AcademicSeeder;
use Tests\TestCase;

class AcademicStructureTest extends TestCase
{
    public function test_years_classrooms_and_current_year_can_be_managed(): void
    {
        $this->actingAs($this->superAdmin());
        $this->post(route('academics.years.store'), ['year' => 2027])->assertSessionHasNoErrors();
        $year = AcademicYear::where('year', 2027)->sole();
        $this->post(route('academics.classrooms.store'), ['academic_year_id' => $year->id, 'grade' => 6, 'shift' => 'Morning', 'section' => 'a', 'capacity' => 50])->assertSessionHasNoErrors();
        $room = Classroom::sole();
        $this->assertSame('A', $room->section);
        $this->patch(route('academics.years.current', $year))->assertSessionHasNoErrors();
        $next = AcademicYear::factory()->create(['year' => 2028]);
        $this->patch(route('academics.years.current', $next))->assertSessionHasNoErrors();
        $this->assertNull($year->fresh()->is_current);
        $this->assertTrue($next->fresh()->is_current);
        $this->put(route('academics.classrooms.update', $room), ['capacity' => 40, 'is_active' => false])->assertSessionHasNoErrors();
        $this->assertFalse($room->fresh()->is_active);
        $this->get(route('academics.index'))->assertOk()->assertSee('2027')->assertSee('Science');
    }

    public function test_duplicate_or_invalid_classrooms_are_rejected(): void
    {
        $room = Classroom::factory()->create();
        $payload = $room->only(['academic_year_id', 'grade', 'shift', 'section', 'capacity']);
        $this->actingAs($this->superAdmin())->post(route('academics.classrooms.store'), $payload)->assertSessionHasErrors('section');
        $this->post(route('academics.classrooms.store'), [...$payload, 'grade' => 5])->assertSessionHasErrors('grade');
        $this->post(route('academics.classrooms.store'), [...$payload, 'shift' => 'Night'])->assertSessionHasErrors('shift');
        $this->assertSame(1, Classroom::count());
    }

    public function test_permissions_are_enforced_for_read_and_write(): void
    {
        $user = User::factory()->create()->assignRole('teacher');
        $this->actingAs($user)->get(route('academics.index'))->assertForbidden();
        $user->givePermissionTo('academics.view');
        $this->get(route('academics.index'))->assertOk()->assertDontSee('name="capacity"', false);
        $this->post(route('academics.years.store'), ['year' => 2027])->assertForbidden();
        $user->givePermissionTo('academics.manage');
        $this->post(route('academics.years.store'), ['year' => 2027])->assertSessionHasNoErrors();
    }

    public function test_capacity_cannot_be_reduced_below_enrollment(): void
    {
        $room = Classroom::factory()->create();
        Enrollment::factory()->create(['classroom_id' => $room->id]);
        Enrollment::factory()->create(['classroom_id' => $room->id, 'roll' => 2]);
        $this->actingAs($this->superAdmin())->put(route('academics.classrooms.update', $room), ['capacity' => 1, 'is_active' => true])->assertSessionHasErrors('capacity');
        $this->assertSame(50, $room->fresh()->capacity);
    }

    public function test_seeder_does_not_overwrite_current_year(): void
    {
        $year = AcademicYear::factory()->create(['year' => 2027, 'is_current' => true]);
        $this->seed(AcademicSeeder::class);
        $this->seed(AcademicSeeder::class);
        $this->assertTrue($year->fresh()->is_current);
        $this->assertSame(1, AcademicYear::where('is_current', true)->count());
    }
}
