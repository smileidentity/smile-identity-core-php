<?php
declare(strict_types=1);

namespace sid;

require 'lib/SmileIdentityCore.php';
use PHPUnit\Framework\TestCase;
use Ouzo\Utilities\Clock;

final class SmileIdentityCoreTest extends TestCase
{
    protected SmileIdentityCore $sic;

    protected function setUp(): void
    {
        Clock::freeze('2011-01-02 12:34');
        $this->sic = new SmileIdentityCore(1234, 2345, 5678, 1);
    }

    public function testInitialize(): void
    {
        $this->assertInstanceOf('sid\SmileIdentityCore', $this->sic);
    }

    public function testSubmitZip(): void
    {

    }

    public function testGetJobStatus()
    {

    }

    public function testGenerateKey(): void
    {
        $timestamp = Clock::now()->getTimestamp();
        $sec_key = $this->sic->generate_sec_key();
        $this->assertEquals($sec_key[1], $timestamp);
    }
}
