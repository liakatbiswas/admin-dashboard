<?php

namespace Tests\Feature;

use App\Models\AdmissionOnOff;
use App\Models\User;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    private array $payload = [
        'name' => 'Student One',
        'email' => 'student@example.com',
        'password' => 'Str0ng-Pass-123',
        'password_confirmation' => 'Str0ng-Pass-123',
    ];

    public function test_open_registration_creates_a_student(): void
    {

        $this->get('/register')->assertOk()->assertSee('Confirm password');

        $this->post('/register', $this->payload);

        $user = User::where('email', 'student@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(['student'], $user->getRoleNames()->all());
    }

    public function test_closed_registration_shows_notice_and_blocks_post(): void
    {
        AdmissionOnOff::set(false);

        $this->get('/register')->assertOk()->assertSee('Registration is currently closed');
        $this->post('/register', $this->payload)->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'student@example.com']);
    }

    public function test_super_admin_can_toggle_registration(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('settings.admission.toggle'), ['is_on' => false])->assertRedirect();
        $this->assertFalse(AdmissionOnOff::isOn());

        $this->post(route('settings.admission.toggle'), ['is_on' => true]);
        $this->assertTrue(AdmissionOnOff::isOn());
    }

    public function test_student_cannot_toggle_registration(): void
    {
        $student = User::factory()->create()->assignRole('student');

        $this->actingAs($student)->post(route('settings.admission.toggle'))->assertForbidden();
        $this->assertTrue(AdmissionOnOff::isOn());
    }
}
