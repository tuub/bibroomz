<?php

namespace App\Services\Http;

use App\Library\IpChecker;
use App\Models\Institution;
use Illuminate\Support\Collection;

class InstitutionAccessService
{
    /**
     * @param  Collection<int, Institution>  $institutions
     * @return Collection<int, Institution>
     */
    public function filterAllowed(Collection $institutions, ?string $ip = null): Collection
    {
        return $institutions
            ->filter(fn (Institution $institution): bool => $this->isIpAllowed($institution, $ip))
            ->values();
    }

    public function isIpAllowed(Institution $institution, ?string $ip = null): bool
    {
        $institution->loadMissing('settings');

        $allowedIpsValue = $institution->settings->firstWhere('key', 'allowed_ips')?->value;
        $allowedIps = explode(',', is_string($allowedIpsValue) ? $allowedIpsValue : '');
        $address = $ip ?? request()->ip();

        return is_string($address) && (new IpChecker($allowedIps))->isIpAllowed($address);
    }
}
