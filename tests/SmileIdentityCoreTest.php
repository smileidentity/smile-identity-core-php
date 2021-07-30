<?php
declare(strict_types=1);

require 'lib/SmileIdentityCore.php';
use PHPUnit\Framework\TestCase;
use Ouzo\Utilities\Clock;

final class SmileIdentityCoreTest extends TestCase
{
    protected SmileIdentityCore $sic;

    protected function setUp(): void
    {
        Clock::freeze('2011-01-02 12:34');
        $sid_server = 1;
        $partner_id = 212;
        $default_callback = 'https://google.com';
        $api_key = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlHZk1BMEdDU3FHU0liM0RRRUJBUVVBQTRHTkFEQ0JpUUtCZ1FDWDY4TEh4U1NFRkVNSFpWRks1dXdCMzZJZQprUnp3YUpHUmxwTjFReVlpVk84bDlYZXk4WkVXWjhicStFOWFteU1id2k2Z1NsQmkvV0hRTUkxWU5VQ2g0VkVCClpMOVRwTjdJNE9wY1ZHUlVWbHErbFBUTGgyZ0MzWmp5SytUSERqd0taVEdLSnFmS0FPSSs4NWE5SHoyR2RDaWYKMWtneUZkNVJUL2lVQy8rSy93SURBUUFCCi0tLS0tRU5EIFBVQkxJQyBLRVktLS0tLQo=';
        $this->sic = new SmileIdentityCore($partner_id, $default_callback, $api_key, $sid_server);
    }

    public function testInitialize(): void
    {
        $this->assertInstanceOf('SmileIdentityCore', $this->sic);
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
        $this->assertEquals($sec_key[1], $timestamp);
    }
}
