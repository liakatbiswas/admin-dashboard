<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_english_is_the_default_language(): void
    {
        $this->get('/login')->assertOk()->assertSee('lang="en"', false)->assertSee('Welcome back');
    }

    public function test_language_choice_persists_and_can_be_changed_back(): void
    {
        $this->from('/login')->post(route('locale.update'), ['locale' => 'bn'])
            ->assertRedirect('/login')->assertSessionHas('locale', 'bn')->assertCookie('locale', 'bn');
        $this->get('/login')->assertOk()->assertSee('lang="bn"', false)->assertSee('স্বাগতম');
        $this->post(route('locale.update'), ['locale' => 'en'])->assertSessionHas('locale', 'en');
        $this->get('/login')->assertSee('lang="en"', false)->assertSee('Welcome back');
    }

    public function test_cookie_restores_language_without_a_session_preference(): void
    {
        $this->withCookie('locale', 'bn')->get('/login')->assertOk()->assertSee('lang="bn"', false);
    }

    public function test_invalid_saved_language_falls_back_to_english(): void
    {
        $this->withSession(['locale' => '../../invalid'])->get('/login')
            ->assertOk()->assertSee('lang="en"', false);
    }

    public function test_unsupported_language_is_rejected(): void
    {
        $this->post(route('locale.update'), ['locale' => 'fr'])->assertSessionHasErrors('locale');
        $this->assertNull(session('locale'));
    }

    public function test_switcher_does_not_redirect_to_an_external_referrer(): void
    {
        $this->from('https://example.org/')->post(route('locale.update'), ['locale' => 'bn'])
            ->assertRedirect(url('/'));
    }

    public function test_validation_errors_use_bangla(): void
    {
        $this->withSession(['locale' => 'bn'])->post('/login', [])
            ->assertSessionHasErrors(['login' => 'লগইন পরিচয় অবশ্যই দিতে হবে।', 'password' => 'পাসওয়ার্ড অবশ্যই দিতে হবে।']);
    }

    #[DataProvider('localizedPages')]
    public function test_main_pages_render_in_each_language(string $locale, string $path): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['locale' => $locale, 'auth.password_confirmed_at' => time()])
            ->get($path)->assertOk()->assertSee('lang="'.$locale.'"', false)
            ->assertSee(route('locale.update'), false);
    }

    public static function localizedPages(): array
    {
        $cases = [];
        foreach (['en', 'bn'] as $locale) {
            foreach (['/', '/dashboard', '/profile', '/admin/users', '/admin/users/create', '/admin/roles', '/admin/roles/create', '/admin/settings', '/admin/activity'] as $path) {
                $cases[$locale.' '.$path] = [$locale, $path];
            }
        }

        return $cases;
    }

    public function test_temporary_password_page_uses_the_selected_language(): void
    {
        $this->actingAs($this->superAdmin())->withSession(['locale' => 'bn'])->post(route('users.store'), [
            'name' => 'Teacher', 'email' => 'localized@example.com', 'roles' => ['teacher'],
        ])->assertOk()->assertSee('অস্থায়ী পাসওয়ার্ড')->assertSee('বাংলাদেশ সময়');
    }
}
