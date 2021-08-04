<?php
declare(strict_types=1);

require 'lib/SmileIdentityCore.php';
use PHPUnit\Framework\TestCase;
use Ouzo\Utilities\Clock;

final class SmileIdentityCoreTest extends TestCase
{
    protected SmileIdentityCore $sic;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        Clock::freeze('2011-01-02 12:34');
        $sid_server = 0;
        $partner_id = 212;
        $default_callback = 'https://google.com';
        $api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");
        $this->sic = new SmileIdentityCore($partner_id, $default_callback, $api_key, $sid_server);
    }

    public function testInitialize(): void
    {
        $this->assertInstanceOf('SmileIdentityCore', $this->sic);
    }

    public function testGetVersion(): void
    {
        $version = $this->sic->get_version();
        $this->assertEquals('1.1.0', $version);
    }

    public function testSubmitZip(): void
    {

    }

    public function testGetJobStatus(): void
    {

    }

    public function testGenerateKey(): void
    {
        $timestamp = Clock::now()->getTimestamp();
        $sec_key = $this->sic->generate_sec_key();
        $this->assertEquals($timestamp, $sec_key[1]);
    }
}
