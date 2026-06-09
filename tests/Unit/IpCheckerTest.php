<?php

declare(strict_types=1);

use App\Library\IpChecker;
use App\Services\Http\InstitutionAccessService;

covers(InstitutionAccessService::class);

test('is ip allowed for matching cidr ranges', function (): void {
    $checker = new IpChecker(['192.168.0.0/24', '10.0.0.0/8']);

    expect($checker->isIpAllowed('192.168.0.1'))->toBeTrue();
    expect($checker->isIpAllowed('10.0.0.1'))->toBeTrue();
    expect($checker->isIpAllowed('172.16.0.1'))->toBeFalse();
});

test('zero netmask allows all ips', function (): void {
    expect((new IpChecker(['0.0.0.0/0']))->isIpAllowed('192.168.0.1'))->toBeTrue();
});

test('throws on invalid ip', function (): void {
    (new IpChecker(['192.168.0.0/24', '10.0.0.0/8']))->isIpAllowed('invalid ip');
})->throws(InvalidArgumentException::class);

test('throws on invalid range', function (): void {
    (new IpChecker(['192.168.0.0/invalid']))->isIpAllowed('192.168.0.1');
})->throws(InvalidArgumentException::class);
