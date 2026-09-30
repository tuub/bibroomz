<?php

declare(strict_types=1);

namespace App\Providers;

use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        Telescope::filter(function (IncomingEntry $entry): bool {
            if ($this->app->environment('local')) {
                return true;
            }

            if ($this->app->environment('devenv')) {
                return true;
            }

            if ($this->app->environment('demo')) {
                return true;
            }
            if ($entry->isReportableException()) {
                return true;
            }
            if ($entry->isFailedRequest()) {
                return true;
            }
            if ($entry->isFailedJob()) {
                return true;
            }
            if ($entry->isScheduledTask()) {
                return true;
            }

            return $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     *
     * @return void
     */
    protected function hideSensitiveRequestDetails()
    {
        if ($this->app->environment('local')) {
            return;
        }

        if ($this->app->environment('devenv')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Leave the viewTelescope ability to AuthServiceProvider, which defines it
     * beside viewPulse, where every other ability in this application is
     * defined: an administrator may read the dashboard, and nobody else.
     *
     * The override has to stay. This provider is listed after
     * AuthServiceProvider in config/app.php, so it boots later, and what the
     * package would define here -- a hard-coded list of email addresses, empty
     * in this application -- would replace the definition made there and leave
     * it dead.
     *
     * @return void
     */
    #[\Override]
    protected function gate()
    {
        //
    }
}
