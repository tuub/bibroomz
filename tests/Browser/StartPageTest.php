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
                ->pressAndWaitFor('button.language-switch[title=DE]')
                ->waitForTextIn('.locale-active', 'DE')
                ->assertCookieValue('locale', 'de');
        });
    }

    public function testLanguageSwitchEnglish(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit('/')
                ->press('button.language-switch[title=EN]')
                ->waitForTextIn('.locale-active', 'EN')
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
                ->press('.login-logout-button')
                ->type('input#username', $user->name)
                ->type('input#password', $password)
                ->keys('input#password', '{enter}')
                ->waitForTextIn('.current-user', $user->name)
                ->assertAuthenticatedAs($user);
        });
    }
}
