<?php

namespace Tests\Browser;

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CalendarPageTest extends DuskTestCase
{
    use DatabaseTruncation;

    private ResourceGroup $resourceGroup;
    private string $route;
    private User $user;
    private User $verifier;

    protected $tablesToTruncate = [];

    public function setUp(): void
    {
        parent::setUp();

        Model::unguard();
        (new PermissionSeeder())->run();
        (new WeekDaySeeder())->run();
        Model::reguard();

        $this->resourceGroup = ResourceGroup::factory()
            ->for(Institution::factory())
            ->has(Resource::factory()->count(2))
            ->create();

        $this->route = route('home', [
            'institution_slug' => $this->resourceGroup->institution->slug,
            'resource_group_slug' => $this->resourceGroup->slug,
        ], absolute: false);

        $this->user = User::factory()->create(['password' => bcrypt(fake()->password())]);
        $this->verifier = User::factory()->create(['password' => bcrypt(fake()->password())]);
    }

    public function testCalendarPage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit($this->route)
                ->assertPathIs($this->route)
                ->assertSee($this->resourceGroup->title);
        });
    }

    public function testCreateHappening(): void
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit($this->route)
                ->waitForText($this->resourceGroup->resources->first()->title)
                ->press('.fc-nextCustom-button')
                ->waitForTextIn('.fc-toolbar-title', now()->addDay()->dayName)
                ->drag(
                    $from = 'td.fc-timegrid-slot-lane[data-time="11:00:00"]',
                    $to = 'td.fc-timegrid-slot-lane[data-time="13:00:00"]',
                )
                ->waitFor('#modal')
                ->type('#verifier', $this->verifier->name)
                ->press(__('modal.create.action.create'))
                ->waitForText(__('toast.happening.event.created'))
                ->assertSeeIn('#user-happenings', $this->verifier->name);
        });
    }
}
