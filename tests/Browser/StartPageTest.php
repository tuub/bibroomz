<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StartPageTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function testStartPageAppName(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit('/')
                ->assertSee(config('app.name'));
        });
    }

    public function testLanguageSwitchGerman(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit('/')
                ->pressAndWaitFor('#i18n a[title=DE]')
                ->waitForTextIn('.i18n-active', 'DE')
                ->assertCookieValue('locale', 'de');
        });
    }

    public function testLanguageSwitchEnglish(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit('/')
                ->press('#i18n a[title=EN]')
                ->waitForTextIn('.i18n-active', 'EN')
                ->assertCookieValue('locale', 'en');
        });
    }

    public function testLoginModal(): void
    {
        $password = fake()->password();
        $user = User::factory()->create(['password' => bcrypt($password)]);

        $this->browse(function (Browser $browser) use ($user, $password) {
            $browser
                ->visit('/')
                ->press('#auth')
                ->type('input#username', $user->name)
                ->type('input#password', $password)
                ->keys('input#password', '{enter}')
                ->waitForTextIn('#auth', $user->name)
                ->assertAuthenticatedAs($user);
        });
    }
}
