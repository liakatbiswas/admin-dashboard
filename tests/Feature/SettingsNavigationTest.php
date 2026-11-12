<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class SettingsNavigationTest extends TestCase
{
    public function test_dashboard_links_to_each_settings_page(): void
    {
        $this->actingAs($this->superAdmin())->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('settings.registration'), false)
            ->assertSee(route('settings.password'), false)
            ->assertSee(route('settings.site'), false);
    }

    public function test_admin_can_open_each_settings_form(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get(route('settings.registration'))->assertOk()->assertSee('name="is_on"', false);
        $this->get(route('settings.site'))->assertOk()->assertSee('name="site_title"', false);
        $this->get(route('settings.password'))->assertOk()->assertSee('name="current_password"', false);
    }

    public function test_student_only_has_access_to_password_settings(): void
    {
        $student = User::factory()->create()->assignRole('student');

        $this->actingAs($student)->get(route('dashboard'))->assertOk()
            ->assertSee(route('settings.password'), false)
            ->assertDontSee(route('settings.registration'), false)
            ->assertDontSee(route('settings.site'), false);
        $this->get(route('settings.registration'))->assertForbidden();
        $this->get(route('settings.site'))->assertForbidden();
        $this->get(route('settings.password'))->assertOk();
    }

    public function test_guests_are_redirected_to_login_for_settings_pages(): void
    {
        $this->get(route('settings.registration'))->assertRedirect(route('login'));
        $this->get(route('settings.site'))->assertRedirect(route('login'));
        $this->get(route('settings.password'))->assertRedirect(route('login'));
    }
}
