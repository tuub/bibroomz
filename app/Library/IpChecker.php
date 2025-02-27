<?php

declare(strict_types=1);

namespace App\Library;

use InvalidArgumentException;

class IpChecker
{
    public function __construct(private array $allowed_ranges)
    {
    }

    public function isIpAllowed(string $addr): bool
    {
        if (!filter_var($addr, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException("Invalid IP address: $addr");
        }

        foreach ($this->allowed_ranges as $allowed_range) {
            if (self::isIpInRange($addr, $allowed_range)) {
                return true;
            }
        }

        return false;
    }

    private static function isIpInRange(string $addr, string $range): bool
    {
        if (!str_contains($range, '/')) {
            // No mask, so we compare the IP directly
            return $addr == $range;
        }

        // Split the range into the base IP and the netmask
        [$range, $netmask] = explode('/', $range, 2);

        if (!is_numeric($netmask) || $netmask < 0 || $netmask > 32) {
            throw new InvalidArgumentException("Invalid netmask: $netmask");
        }

        // Convert IPs into long integers for bitwise operations
        $addr_decimal = ip2long($addr);
        $range_decimal = ip2long($range);

        // Calculate the decimal representation of the wildcard mask and the subnet mask
        $wildcard_decimal = 2 ** (32 - $netmask) - 1;
        $netmask_decimal = ~$wildcard_decimal;

        return (($addr_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
}
