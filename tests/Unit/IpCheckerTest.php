<?php

namespace Tests\Unit;

use App\Library\IpChecker;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IpCheckerTest extends TestCase
{
    public function test_is_ip_allowed()
    {
        $checker = new IpChecker(['192.168.0.0/24', '10.0.0.0/8']);

        $this->assertTrue($checker->isIpAllowed('192.168.0.1'));
        $this->assertTrue($checker->isIpAllowed('10.0.0.1'));

        $this->assertFalse($checker->isIpAllowed('172.16.0.1'));
    }

    public function test_is_ip_allowed_with_zero_netmask()
    {
        $checker = new IpChecker(['0.0.0.0/0']);

        $this->assertTrue($checker->isIpAllowed('192.168.0.1'));
    }

    public function test_is_ip_allowed_with_invalid_ip()
    {
        $checker = new IpChecker(['192.168.0.0/24', '10.0.0.0/8']);

        $this->expectException(InvalidArgumentException::class);
        $checker->isIpAllowed('invalid ip');
    }

    public function test_is_ip_allowed_with_invalid_range()
    {
        $checker = new IpChecker(['192.168.0.0/invalid']);

        $this->expectException(InvalidArgumentException::class);
        $checker->isIpAllowed('192.168.0.1');
    }
}
