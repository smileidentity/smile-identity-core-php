<?php
declare(strict_types=1);

namespace sid;

require 'lib/SmileIdentityCore.php';
use PHPUnit\Framework\TestCase;

final class SmileIdentityCoreTest extends TestCase
{
    public function testInitialize(): void
    {
        $sic = new SmileIdentityCore;
        $sic->initialize(1234, 2345, 5678, 1);
        $this->assertSame(1, count(get_object_vars($sic)));
    }
}
