<?php

use Illuminate\Support\Facades\Route;

/** @return list<string> */
function adminBreadcrumbRouteContractNames(): array
{
    $source = file_get_contents(base_path('resources/js/Composables/AdminBreadcrumbs.ts'));

    if ($source === false) {
        throw new RuntimeException('Unable to read admin breadcrumb route contracts.');
    }

    preg_match_all('/contract\("([^"]+)"/', $source, $matches);

    $routeNames = $matches[1];
    sort($routeNames);

    return $routeNames;
}

/** @return list<string> */
function namedAdminGetRouteNames(): array
{
    $routeNames = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        $name = $route->getName();

        if (
            is_string($name)
            && str_starts_with($name, 'admin.')
            && str_starts_with($route->uri(), 'admin')
            && in_array('GET', $route->methods(), true)
        ) {
            $routeNames[$name] = $name;
        }
    }

    sort($routeNames);

    return $routeNames;
}

test('admin breadcrumb route contracts cover every named admin GET route', function (): void {
    expect(adminBreadcrumbRouteContractNames())->toEqual(namedAdminGetRouteNames());
});
