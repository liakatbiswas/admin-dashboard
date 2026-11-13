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
        $student = User::factory()->create(['student_id' => 'NGHS20270600001'])->assignRole('student');

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

    public function test_profile_has_only_the_profile_form(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('profile.edit'))->assertOk()
            ->assertSee('name="bio"', false)
            ->assertDontSee('action="'.route('user-password.update').'"', false)
            ->assertDontSee('action="'.route('two-factor.enable').'"', false);
    }

    public function test_two_factor_page_requires_password_confirmation(): void
    {
        $this->actingAs($this->superAdmin())->get(route('settings.two-factor'))
            ->assertRedirect(route('password.confirm'));
    }

    public function test_confirmed_user_can_open_two_factor_settings_from_sidebar(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['auth.password_confirmed_at' => time()]);

        $this->get(route('dashboard'))->assertOk()->assertSee(route('settings.two-factor'), false);
        $this->get(route('settings.two-factor'))->assertOk()->assertSee('action="'.route('two-factor.enable').'"', false);
    }

    public function test_guest_cannot_open_two_factor_settings(): void
    {
        $this->get(route('settings.two-factor'))->assertRedirect(route('login'));
    }
}
