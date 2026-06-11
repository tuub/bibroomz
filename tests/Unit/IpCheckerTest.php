<?php

declare(strict_types=1);

use App\Library\IpChecker;
use App\Services\Http\InstitutionAccessService;

covers(IpChecker::class, InstitutionAccessService::class);

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

test('exact ip match without cidr notation returns true for matching ip', function (): void {
    $checker = new IpChecker(['192.168.1.100']);
    expect($checker->isIpAllowed('192.168.1.100'))->toBeTrue()
        ->and($checker->isIpAllowed('192.168.1.101'))->toBeFalse();
});

test('netmask 32 matches only the exact host', function (): void {
    $checker = new IpChecker(['192.168.0.1/32']);
    expect($checker->isIpAllowed('192.168.0.1'))->toBeTrue()
        ->and($checker->isIpAllowed('192.168.0.2'))->toBeFalse();
});

test('netmask 33 throws invalid argument exception', function (): void {
    (new IpChecker(['192.168.0.0/33']))->isIpAllowed('192.168.0.1');
})->throws(InvalidArgumentException::class);

test('negative netmask throws invalid argument exception', function (): void {
    (new IpChecker(['192.168.0.0/-1']))->isIpAllowed('192.168.0.1');
})->throws(InvalidArgumentException::class);

test('netmask 16 allows subnets but not others', function (): void {
    $checker = new IpChecker(['10.10.0.0/16']);
    expect($checker->isIpAllowed('10.10.255.255'))->toBeTrue()
        ->and($checker->isIpAllowed('10.11.0.0'))->toBeFalse();
});

test('ip not in any allowed range returns false', function (): void {
    $checker = new IpChecker(['192.168.1.0/24']);
    expect($checker->isIpAllowed('10.0.0.1'))->toBeFalse();
});

test('netmask 24 excludes adjacent subnet address', function (): void {
    // IncrementInteger: 32 becomes 33 in `2 ** (32 - $netmask) - 1`
    // With /24 original: wildcard = 2^(32-24)-1 = 255, netmask = ~255 = ffffff00
    //   192.168.1.1 & ffffff00 = 192.168.1.0 ≠ 192.168.0.0 → false (correct)
    // With /24 mutation: wildcard = 2^(33-24)-1 = 511, netmask = ~511 = fffffe00
    //   192.168.1.1 & fffffe00 = 192.168.0.0 = 192.168.0.0 → true (wrong!)
    $checker = new IpChecker(['192.168.0.0/24']);
    expect($checker->isIpAllowed('192.168.0.255'))->toBeTrue()
        ->and($checker->isIpAllowed('192.168.1.0'))->toBeFalse()
        ->and($checker->isIpAllowed('192.168.1.1'))->toBeFalse();
});
