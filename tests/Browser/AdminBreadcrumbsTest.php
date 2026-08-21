<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    seedBrowserPrerequisites();
});

it('renders nested admin breadcrumbs from route props', function (): void {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'route' => $homeRoute,
    ] = buildBrowserCalendarFixture(resourceCount: 1);

    $admin = createBrowserSystemUser('browser.admin.breadcrumbs');
    $admin->forceFill(['is_admin' => true])->save();

    $adminRoute = route('admin.resource.index', ['resource_group_id' => $resourceGroup->id], false);

    loginThroughBrowserUi($homeRoute, $admin, browserPassword(), '#auth');

    visit($adminRoute)
        ->wait(1)
        ->assertPathIs($adminRoute)
        ->assertPresent('[data-testid="admin-breadcrumbs"]')
        ->assertSeeIn('[data-testid="admin-breadcrumbs"]', __('admin.breadcrumbs.dashboard'))
        ->assertSeeIn('[data-testid="admin-breadcrumbs"]', __('admin.breadcrumbs.institutions'))
        ->assertSeeIn('[data-testid="admin-breadcrumbs"]', $institution->getTranslation('title', 'de'))
        ->assertSeeIn('[data-testid="admin-breadcrumbs"]', $resourceGroup->getTranslation('title', 'de'))
        ->assertSeeIn('[data-testid="admin-breadcrumbs"]', __('admin.breadcrumbs.resources'));
});
