<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AdmissionApplication;
use App\Models\AdmissionOnOff;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdmissionsWorkflowTest extends TestCase
{
    private function payload(Classroom $room): array
    {
        return ['academic_year_id' => $room->academic_year_id, 'grade' => $room->grade, 'name_bn' => 'শিক্ষার্থী', 'name_en' => 'Student One',
            'gender' => 'Female', 'birth_date' => '2015-01-01', 'religion' => 'Islam', 'present_address' => 'Dhaka', 'same_address' => 1,
            'preferred_shift' => $room->shift, 'guardian_name' => 'Mother', 'guardian_relationship' => 'Mother', 'guardian_phone' => '+8801712345678'];
    }

    private function selected(Classroom $room): AdmissionApplication
    {
        return AdmissionApplication::factory()->create(['academic_year_id' => $room->academic_year_id, 'grade' => $room->grade,
            'status' => 'selected', 'selected_shift' => $room->shift, 'published_at' => now()]);
    }

    private function placement(Classroom $room): array
    {
        return ['classroom_id' => $room->id, 'roll' => 1, 'documents_checked' => 1, 'reason' => 'School records checked'];
    }

    public function test_application_creates_no_account_and_receipt_is_private(): void
    {
        Notification::fake();
        $room = Classroom::factory()->create(['academic_year_id' => AcademicYear::factory()->create(['year' => 2027])->id]);
        $users = User::count();
        $response = $this->post('/register', $this->payload($room))->assertSessionHasNoErrors()->assertRedirect();
        $application = AdmissionApplication::sole();
        $this->assertSame('NGHS-APP-2027-00001', $application->number);
        $this->assertSame('01712345678', $application->guardian['phone']);
        $this->assertSame('Dhaka', $application->details['permanent_address']);
        $this->assertSame($users, User::count());
        $this->assertSame(0, Student::count());
        $this->get($response->headers->get('Location'))->assertOk()->assertSee($application->number);
        $this->get('/applications/'.$application->id.'/receipt')->assertForbidden();
        $this->get(URL::temporarySignedRoute('admissions.receipt', now()->addMinute(), ['application' => $application->id, 'download' => 1]))->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="'.$application->number.'.html"');
        Notification::assertNothingSent();
    }

    public function test_deadline_uses_dhaka_time_and_blocks_previously_open_forms(): void
    {
        $this->travelTo(Carbon::parse('2027-01-01 06:00:00', 'UTC'));
        $room = Classroom::factory()->create();
        $this->actingAs($this->superAdmin())->post(route('settings.admission.deadline'), ['deadline' => '2027-01-01T12:01'])->assertSessionHasNoErrors();
        $this->assertTrue(AdmissionOnOff::isOn());
        $this->post('/logout');
        $this->get('/register')->assertOk()->assertSee('Admission application');
        $this->travel(1)->minutes();
        $this->assertFalse(AdmissionOnOff::isOn());
        $this->post('/register', $this->payload($room))->assertForbidden();
        $this->get('/register')->assertOk()->assertSee('The registration period has ended.');
    }

    public function test_only_super_admin_controls_public_registration(): void
    {
        $user = User::factory()->create()->assignRole('admin');
        $user->givePermissionTo('admission.manage');
        $this->actingAs($user)->post(route('settings.admission.toggle'), ['is_on' => false])->assertForbidden();
        $this->post(route('settings.admission.deadline'), ['deadline' => null])->assertForbidden();
    }

    public function test_group_is_required_only_for_classes_nine_and_ten(): void
    {
        $room = Classroom::factory()->create(['grade' => 9]);
        $this->post('/register', $this->payload($room))->assertSessionHasErrors('preferred_group');
        $this->post('/register', [...$this->payload($room), 'preferred_group' => 'Science'])->assertSessionHasNoErrors();
        $lower = Classroom::factory()->create();
        $this->post('/register', [...$this->payload($lower), 'preferred_group' => 'Science'])->assertSessionHasErrors('preferred_group');
    }

    public function test_selection_publication_and_admission_are_separate(): void
    {
        $room = Classroom::factory()->create(['academic_year_id' => AcademicYear::factory()->create(['year' => 2027])->id]);
        $application = AdmissionApplication::factory()->create(['academic_year_id' => $room->academic_year_id]);
        $this->actingAs($this->superAdmin());
        $this->post(route('applications.admit', $application), $this->placement($room))->assertSessionHasErrors('application');
        $this->post(route('applications.select', $application), ['status' => 'selected', 'selected_shift' => 'Morning', 'reason' => 'Eligible'])->assertSessionHasNoErrors();
        $this->assertSame(0, Student::count());
        $this->get(route('admissions.selected'))->assertDontSee($application->number);
        $this->post(route('applications.publish', $application))->assertSessionHasNoErrors();
        $this->get(route('admissions.selected'))->assertSee($application->number)->assertDontSee($application->guardian['phone']);
        $this->get(route('admissions.selected', ['pdf' => 1]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        AdmissionOnOff::set(false);
        $response = $this->post(route('applications.admit', $application), $this->placement($room))->assertOk();
        $student = Student::sole();
        $password = $response->viewData('password');
        $this->assertSame('NGHS20270600001', $student->student_number);
        $this->assertTrue(Hash::check($password, $student->user->password));
        $this->assertTrue($student->user->must_change_password);
        $this->assertStringNotContainsString($password, Activity::all()->toJson());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->post(route('applications.admit', $application), [...$this->placement($room), 'roll' => 2])->assertSessionHasErrors('application');
        $this->assertSame(1, Student::count());
    }

    public function test_roll_capacity_and_group_failures_leave_no_partial_account(): void
    {
        $room = Classroom::factory()->create(['capacity' => 1]);
        $first = $this->selected($room);
        $second = $this->selected($room);
        $this->actingAs($this->superAdmin())->post(route('applications.admit', $first), $this->placement($room))->assertOk();
        $this->post(route('applications.admit', $second), $this->placement($room))->assertSessionHasErrors('roll');
        $this->post(route('applications.admit', $second), [...$this->placement($room), 'roll' => 2])->assertSessionHasErrors('classroom_id');
        $this->post(route('applications.admit', $second), [...$this->placement($room), 'roll' => 2, 'group' => 'Science'])->assertSessionHasErrors('group');
        $this->assertSame(1, Student::count());
        $this->post(route('applications.admit', $second), [...$this->placement($room), 'roll' => 2, 'override_reason' => 'Approved extra seat'])->assertOk();
        $this->assertDatabaseHas('enrollments', ['override_reason' => 'Approved extra seat']);
    }

    public function test_existing_student_serial_is_preserved_and_cannot_be_duplicated(): void
    {
        AdmissionOnOff::set(false);
        $room = Classroom::factory()->create(['academic_year_id' => AcademicYear::factory()->create(['year' => 2027])->id]);
        $data = [...$this->payload($room), ...$this->placement($room), 'admission_year' => 2024, 'admission_grade' => 6, 'admission_serial' => 12];
        $this->actingAs($this->superAdmin())->post(route('students.store'), $data)->assertOk();
        $student = Student::sole();
        $this->assertSame('NGHS20240600012', $student->student_number);
        $this->post(route('students.store'), [...$data, 'roll' => 2])->assertSessionHasErrors('admission_serial');
        $next = Classroom::factory()->create(['academic_year_id' => AcademicYear::factory()->create(['year' => 2028])->id]);
        $this->post(route('students.enroll', $student), $this->placement($next))->assertOk();
        $this->assertSame(1, Student::count());
        $this->assertSame(2, $student->enrollments()->count());
    }

    public function test_guardian_account_is_verified_and_reused_for_siblings(): void
    {
        $student = Student::factory()->create();
        $sibling = Student::factory()->create();
        $this->actingAs($this->superAdmin());
        $payload = ['verified' => 1, 'reason' => 'Identity and mobile checked'];
        $response = $this->post(route('students.guardian', $student), $payload)->assertOk();
        $guardian = $student->fresh()->guardianUser;
        $this->assertTrue($guardian->hasRole('guardian'));
        $this->assertSame('01712345678', $guardian->verified_phone);
        $this->assertTrue(Hash::check($response->viewData('password'), $guardian->password));
        $this->post(route('students.guardian', $sibling), $payload)->assertOk()->assertViewHas('password', null);
        $this->assertSame($guardian->id, $sibling->fresh()->guardian_user_id);
    }

    public function test_officer_cannot_add_guardian_role_to_existing_teacher(): void
    {
        $teacher = User::factory()->create(['phone' => '01712345678', 'verified_phone' => '01712345678'])->assignRole('teacher');
        $officer = User::factory()->create()->assignRole('clerk');
        $officer->givePermissionTo('guardians.manage');
        $student = Student::factory()->create();
        $data = ['account_lookup' => $teacher->email, 'verified' => 1, 'reason' => 'Identity checked'];
        $this->actingAs($officer)->post(route('students.guardian', $student), $data)->assertForbidden();
        $this->assertFalse($teacher->fresh()->hasRole('guardian'));
        $this->flushSession();
        $this->actingAs($this->superAdmin())->post(route('students.guardian', $student), $data)->assertOk();
        $this->assertTrue($teacher->fresh()->hasAllRoles(['teacher', 'guardian']));
    }

    public function test_staff_cannot_import_even_with_stored_permission(): void
    {
        $user = User::factory()->create()->assignRole('staff');
        $user->givePermissionTo('students.import');
        $this->actingAs($user)->get(route('students.create'))->assertForbidden();
    }

    public function test_application_photo_is_private_and_malicious_uploads_are_rejected(): void
    {
        Storage::fake('local');
        $room = Classroom::factory()->create();
        $this->post('/register', [...$this->payload($room), 'photo' => UploadedFile::fake()->create('attack.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('photo');
        $this->post('/register', [...$this->payload($room), 'photo' => UploadedFile::fake()->image('student.png', 100, 100)])->assertSessionHasNoErrors();
        $application = AdmissionApplication::sole();
        Storage::disk('local')->assertExists($application->photo_path);
        $this->get(route('applications.photo', $application))->assertRedirect(route('login'));
        $this->actingAs($this->superAdmin())->get(route('applications.photo', $application))->assertOk();
    }

    public function test_corrections_preserve_application_number_and_cannot_change_year_or_published_data(): void
    {
        $room = Classroom::factory()->create();
        $application = AdmissionApplication::factory()->create(['academic_year_id' => $room->academic_year_id]);
        $number = $application->number;
        $data = [...$this->payload($room), 'reason' => 'Corrected from school records'];
        $this->actingAs($this->superAdmin())->put(route('applications.update', $application), [...$data, 'name_en' => 'Corrected Student'])->assertSessionHasNoErrors();
        $this->assertSame($number, $application->fresh()->number);
        $this->assertSame('Corrected Student', $application->fresh()->name_en);
        $year = AcademicYear::factory()->create();
        $this->put(route('applications.update', $application), [...$data, 'academic_year_id' => $year->id])->assertSessionHasErrors('academic_year_id');
        $application->update(['status' => 'selected', 'published_at' => now()]);
        $this->put(route('applications.update', $application), $data)->assertUnprocessable();
    }

    public function test_delegated_admission_officer_cannot_override_capacity(): void
    {
        $room = Classroom::factory()->create(['capacity' => 1]);
        $first = $this->selected($room);
        $second = $this->selected($room);
        $officer = User::factory()->create()->assignRole('clerk');
        $officer->givePermissionTo('applications.admit');
        $this->actingAs($officer)->post(route('applications.admit', $first), $this->placement($room))->assertOk();
        $this->post(route('applications.admit', $second), [...$this->placement($room), 'roll' => 2, 'override_reason' => 'Not authorized'])->assertSessionHasErrors('classroom_id');
        $this->assertSame(1, Student::count());
        $this->get(route('students.create'))->assertForbidden();
    }

    public function test_student_password_reset_revokes_access_and_officer_cannot_reset_administrative_guardian(): void
    {
        $studentUser = User::factory()->create()->assignRole('student');
        $guardian = User::factory()->create()->assignRole(['teacher', 'guardian']);
        $student = Student::factory()->create(['user_id' => $studentUser->id, 'guardian_user_id' => $guardian->id]);
        $studentUser->createToken('old');
        $officer = User::factory()->create()->assignRole('clerk');
        $officer->givePermissionTo('students.credentials');
        $this->actingAs($officer)->post(route('students.password', $student), ['account' => 'student', 'reason' => 'Identity checked'])->assertOk();
        $this->assertSame(0, $studentUser->tokens()->count());
        $this->assertSame(1, $studentUser->fresh()->auth_version);
        $this->assertTrue($studentUser->fresh()->must_change_password);
        $this->post(route('students.password', $student), ['account' => 'guardian', 'reason' => 'Identity checked'])->assertForbidden();
    }

    public function test_guardian_link_cannot_verify_the_actors_own_account(): void
    {
        $actor = $this->superAdmin();
        $student = Student::factory()->create(['guardian' => ['name' => $actor->name, 'phone' => $actor->phone, 'relationship' => 'Father']]);
        $this->actingAs($actor)->post(route('students.guardian', $student), ['account_lookup' => $actor->email, 'verified' => 1, 'reason' => 'Self'])->assertForbidden();
        $this->assertNull($student->fresh()->guardian_user_id);
    }

    public function test_new_module_pages_render_in_bangla(): void
    {
        $this->actingAs($this->superAdmin())->withSession(['locale' => 'bn'])->get(route('academics.index'))->assertOk()->assertSee('একাডেমিক কাঠামো');
        $this->get(route('students.create'))->assertOk()->assertSee('পুরাতন শিক্ষার্থী যোগ করুন');
        $this->get('/register')->assertOk()->assertSee('ভর্তির আবেদন');
    }

    public function test_admin_pages_render_and_private_records_are_not_public(): void
    {
        $room = Classroom::factory()->create();
        $application = $this->selected($room);
        $student = Student::factory()->create();
        $this->get(route('applications.show', $application))->assertRedirect(route('login'));
        $this->actingAs($this->superAdmin());
        foreach ([route('applications.index'), route('applications.show', $application), route('students.index'), route('students.create'), route('students.show', $student)] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
