<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StatisticsExportRequest;
use App\Http\Requests\Admin\StatisticsRequest;
use App\Services\Admin\StatisticsAdminService;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatisticsController extends AdminController
{
    public function __construct(private readonly StatisticsAdminService $statisticsAdminService) {}

    public function getStatistics(StatisticsRequest $request): Response
    {
        $user = $this->authenticatedUser();
        $range = $request->range();
        $from = $request->from();
        $to = $request->to();
        $granularity = $request->granularity();
        $institutionIds = $request->institutionIds();
        $resourceGroupIds = $request->resourceGroupIds();
        $resourceIds = $request->resourceIds();
        $compareFrom = $request->compareFrom();
        $compareTo = $request->compareTo();

        // Booking counts and the time-series chart (paired with cancellations,
        // which is scoped identically) are each fetched once per request and
        // shared between the several page props that read from them below.
        $eagerData = $this->memoize(fn (): array => $this->statisticsAdminService->getBookingCountsAndFilterState(
            $user, $range, $from, $to, $granularity, $institutionIds, $resourceGroupIds, $resourceIds,
        ));

        $timeSeriesGroupData = $this->memoize(fn (): array => $this->statisticsAdminService->getTimeSeriesGroupData(
            $user, $range, $from, $to, $granularity, $institutionIds, $resourceGroupIds, $resourceIds,
        ));

        return Inertia::render('Admin/Statistics/Index', [
            'institutions' => fn (): Collection => $eagerData()['institutions'],
            'resourceGroups' => fn (): Collection => $eagerData()['resourceGroups'],
            'resources' => fn (): Collection => $eagerData()['resources'],
            'range' => fn (): string => $eagerData()['range'],
            'from' => fn (): ?string => $eagerData()['from'],
            'to' => fn (): ?string => $eagerData()['to'],
            'granularity' => fn (): string => $eagerData()['granularity'],
            'timeSeriesSplit' => fn (): string => $eagerData()['timeSeriesSplit'],
            'timeSeriesInstitutionIds' => fn (): array => $eagerData()['timeSeriesInstitutionIds'],
            'timeSeriesResourceGroupIds' => fn (): array => $eagerData()['timeSeriesResourceGroupIds'],
            'timeSeriesResourceIds' => fn (): array => $eagerData()['timeSeriesResourceIds'],
            'timeSeriesInstitutionId' => fn (): ?string => $eagerData()['timeSeriesInstitutionId'],
            'timeSeriesResourceGroupId' => fn (): ?string => $eagerData()['timeSeriesResourceGroupId'],
            'timeSeriesResourceId' => fn (): ?string => $eagerData()['timeSeriesResourceId'],
            'timeSeries' => Inertia::defer(fn (): array => $timeSeriesGroupData()['timeSeries'], 'timeSeries'),
            'cancellations' => Inertia::defer(fn (): array => $timeSeriesGroupData()['cancellations'], 'timeSeries'),
            'heatmap' => Inertia::defer(fn (): array => $this->statisticsAdminService->getHeatmapData(
                $user, $range, $from, $to, $institutionIds, $resourceGroupIds, $resourceIds,
            )['heatmap'], 'heatmap'),
            'comparison' => $compareFrom !== null && $compareTo !== null
                ? Inertia::defer(fn (): ?array => $this->statisticsAdminService->getComparisonGroupData(
                    $user, $range, $from, $to, $granularity, $institutionIds, $resourceGroupIds, $resourceIds, $compareFrom, $compareTo,
                )['comparison'], 'comparison')
                : null,
        ]);
    }

    public function exportStatistics(StatisticsExportRequest $request): StreamedResponse
    {
        $rows = $this->statisticsAdminService->toCsvRows(
            $this->statisticsAdminService->getIndexData(
                $this->authenticatedUser(),
                $request->range(),
                $request->from(),
                $request->to(),
                $request->granularity(),
                $request->institutionIds(),
                $request->resourceGroupIds(),
                $request->resourceIds(),
                $request->compareFrom(),
                $request->compareTo(),
            ),
            $request->type(),
        );

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $request->type().'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Wraps a factory so it is invoked at most once, sharing its result
     * between the several prop closures below that read from it. Unlike the
     * global `once()` helper (which memoizes by call site), this shares the
     * result across the distinct closures that call it.
     *
     * @template T
     *
     * @param  callable(): T  $factory
     * @return callable(): T
     */
    private function memoize(callable $factory): callable
    {
        $box = [];

        return function () use ($factory, &$box) {
            if (! array_key_exists('value', $box)) {
                $box['value'] = $factory();
            }

            return $box['value'];
        };
    }
}
