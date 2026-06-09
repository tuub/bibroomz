<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminLoggingService
{
    public function log(string $action, Model $model): void
    {
        $userId = Auth::id();
        $modelId = $model->getKey();
        $modelName = $model::class;

        Log::channel('admin')
            ->info(sprintf(
                'user %s %s %s %s',
                is_int($userId) || is_string($userId) ? (string) $userId : 'unknown',
                $action,
                $modelName,
                is_int($modelId) || is_string($modelId) ? (string) $modelId : 'unknown',
            ));
    }
}
