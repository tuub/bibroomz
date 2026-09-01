<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StatisticsExportRequest;
use App\Http\Requests\Admin\StatisticsRequest;
use App\Services\Admin\StatisticsAdminService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatisticsController extends AdminController
{
    public function __construct(private readonly StatisticsAdminService $statisticsAdminService) {}

    public function getStatistics(StatisticsRequest $request): Response
    {
        return Inertia::render(
            'Admin/Statistics/Index',
            $this->statisticsAdminService->getIndexData(
                $this->authenticatedUser(),
                $request->range(),
                $request->from(),
                $request->to(),
                $request->granularity(),
                $request->institutionId(),
                $request->resourceGroupId(),
                $request->resourceId(),
                $request->compareFrom(),
                $request->compareTo(),
            ),
        );
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
                $request->institutionId(),
                $request->resourceGroupId(),
                $request->resourceId(),
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
}
