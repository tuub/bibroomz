<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminLoggingService
{
    public function log($action, $model)
    {
        $user = Auth::user();
        $modelName = $model::class;

        Log::channel('admin')
            ->info('user ' . $user->getKey() . ' ' . $action . ' ' . $modelName . ' ' . $model->getKey());
    }
}
