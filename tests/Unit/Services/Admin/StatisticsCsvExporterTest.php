<?php

declare(strict_types=1);

use App\Services\Admin\StatisticsCsvExporter;
use Illuminate\Support\Collection;

covers(StatisticsCsvExporter::class);

/**
 * @param  array<string, string>  $title
 * @return array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}
 */
function buildCsvInstitutionStat(string $id, array $title): array
{
    return ['id' => $id, 'title' => $title, 'count' => 0, 'active' => 0, 'cancelled' => 0, 'cancellationRate' => 0.0];
}

/**
 * @param  array<string, string>  $title
 * @return array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}
 */
function buildCsvResourceGroupStat(string $id, array $title, string $institutionId): array
{
    return ['id' => $id, 'title' => $title, 'institution_id' => $institutionId, 'count' => 0, 'active' => 0, 'cancelled' => 0, 'cancellationRate' => 0.0];
}

/**
 * @param  array<string, string>  $title
 * @return array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}
 */
function buildCsvResourceStat(string $id, array $title, string $resourceGroupId): array
{
    return ['id' => $id, 'title' => $title, 'resource_group_id' => $resourceGroupId, 'count' => 0, 'active' => 0, 'cancelled' => 0, 'cancellationRate' => 0.0];
}

/**
 * @return array{
 *     institutions: Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}>,
 *     resourceGroups: Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
 *     resources: Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}>,
 *     timeSeries: array<int, array{label: string, count: int, segments?: array<int, array{id: string, title: array<string, string>, count: int}>}>,
 *     heatmap: array{cells: array<int, array{dayOfWeek: int, hour: int, count: int, percentage: float}>, maxCount: int, totalCount: int},
 * }
 */
function buildCsvExportData(): array
{
    /** @var Collection<int, array{id: string, title: array<string, string>, count: int, active: int, cancelled: int, cancellationRate: float}> $institutions */
    $institutions = collect();
    /** @var Collection<int, array{id: string, title: array<string, string>, institution_id: string, count: int, active: int, cancelled: int, cancellationRate: float}> $resourceGroups */
    $resourceGroups = collect();
    /** @var Collection<int, array{id: string, title: array<string, string>, resource_group_id: string, count: int, active: int, cancelled: int, cancellationRate: float}> $resources */
    $resources = collect();

    return [
        'institutions' => $institutions,
        'resourceGroups' => $resourceGroups,
        'resources' => $resources,
        'timeSeries' => [],
        'heatmap' => ['cells' => [], 'maxCount' => 0, 'totalCount' => 0],
    ];
}

test('toRows returns an empty array for an unknown export type', function (): void {
    $exporter = app(StatisticsCsvExporter::class);

    expect($exporter->toRows(buildCsvExportData(), 'not_a_real_type'))->toBe([]);
});

test('toRows builds a plain time series CSV with a label and count column', function (): void {
    $exporter = app(StatisticsCsvExporter::class);
    $data = buildCsvExportData();
    $data['timeSeries'] = [['label' => '2026-01', 'count' => 3]];

    $rows = $exporter->toRows($data, 'time_series');

    expect($rows[0])->toBe(['Label', 'Count'])
        ->and($rows[1])->toBe(['2026-01', '3']);
});

test('toRows builds a split time series CSV with total and segment columns', function (): void {
    $exporter = app(StatisticsCsvExporter::class);
    $data = buildCsvExportData();
    $data['timeSeries'] = [
        [
            'label' => '2026-01',
            'count' => 3,
            'segments' => [
                ['id' => 'a', 'title' => ['en' => 'Institution A'], 'count' => 1],
                ['id' => 'b', 'title' => ['en' => 'Institution B'], 'count' => 2],
            ],
        ],
    ];

    $rows = $exporter->toRows($data, 'time_series');

    expect($rows[0])->toBe(['Label', 'Total', 'Institution A', 'Institution B'])
        ->and($rows[1])->toBe(['2026-01', '3', '1', '2']);
});

test('toRows builds a heatmap CSV with one row per cell', function (): void {
    $exporter = app(StatisticsCsvExporter::class);
    $data = buildCsvExportData();
    $data['heatmap'] = ['cells' => [['dayOfWeek' => 1, 'hour' => 10, 'count' => 1, 'percentage' => 100.0]], 'maxCount' => 1, 'totalCount' => 1];

    $rows = $exporter->toRows($data, 'heatmap');

    expect($rows[0])->toBe(['Day of Week', 'Hour', 'Count', 'Percentage'])
        ->and($rows[1])->toBe(['1', '10', '1', '100']);
});

test('toRows uses the active locale title for institutions and falls back to english', function (): void {
    $exporter = app(StatisticsCsvExporter::class);
    $data = buildCsvExportData();
    $data['institutions'] = collect([
        buildCsvInstitutionStat('a', ['en' => 'English Title', 'de' => 'Deutscher Titel']),
        buildCsvInstitutionStat('b', ['en' => 'Only English']),
    ]);

    app()->setLocale('de');
    $rows = $exporter->toRows($data, 'institutions');
    app()->setLocale('en');

    $titles = array_column(array_slice($rows, 1), 0);
    expect($titles)->toContain('Deutscher Titel')
        ->and($titles)->toContain('Only English');
});

test('toRows falls back to the first available translation when neither the active locale nor english exists', function (): void {
    $exporter = app(StatisticsCsvExporter::class);
    $data = buildCsvExportData();
    $data['institutions'] = collect([buildCsvInstitutionStat('a', ['fr' => 'Titre Francais'])]);

    app()->setLocale('de');
    $rows = $exporter->toRows($data, 'institutions');
    app()->setLocale('en');

    expect($rows[1][0])->toBe('Titre Francais');
});

test('toRows resolves the parent institution and resource group titles by id', function (): void {
    $exporter = app(StatisticsCsvExporter::class);
    $data = buildCsvExportData();
    $data['institutions'] = collect([buildCsvInstitutionStat('inst-1', ['en' => 'Institution'])]);
    $data['resourceGroups'] = collect([buildCsvResourceGroupStat('rg-1', ['en' => 'Group'], 'inst-1')]);
    $data['resources'] = collect([buildCsvResourceStat('r-1', ['en' => 'Resource'], 'rg-1')]);

    $resourceGroupRows = $exporter->toRows($data, 'resource_groups');
    $resourceRows = $exporter->toRows($data, 'resources');

    expect($resourceGroupRows[1][1])->toBe('Institution')
        ->and($resourceRows[1][1])->toBe('Group');
});

test('toRows leaves the parent name blank when a resource group or resource has no matching parent', function (): void {
    $exporter = app(StatisticsCsvExporter::class);
    $data = buildCsvExportData();
    $data['institutions'] = collect([buildCsvInstitutionStat('other-institution', ['en' => 'Other Institution'])]);
    $data['resourceGroups'] = collect([buildCsvResourceGroupStat('rg-1', ['en' => 'Group'], 'missing')]);
    $data['resources'] = collect([buildCsvResourceStat('r-1', ['en' => 'Resource'], 'missing')]);

    expect($exporter->toRows($data, 'resource_groups')[1])->toBe(['Group', '', '0', '0', '0'])
        ->and($exporter->toRows($data, 'resources')[1])->toBe(['Resource', '', '0', '0', '0']);
});
