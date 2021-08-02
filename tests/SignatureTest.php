<?php
declare(strict_types=1);

require 'lib/Signature.php';
use PHPUnit\Framework\TestCase;
use Ouzo\Utilities\Clock;

final class SignatureTest extends TestCase
{
    private string $api_key;
    private int $partner_id;

    protected function setUp(): void
    {
        Clock::freeze('2011-01-02 12:34');
        $this->api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");
        $this->partner_id = 212;
    }

    public function testGenerateSecKey(): void
    {
        $sig = new Signature($this->api_key, $this->partner_id);
        $generatedKey = $sig->generate_sec_key();
        $timestamp = Clock::now()->getTimestamp();
        $this->assertSame($timestamp, $generatedKey[1]);
        $this->assertSame(2, count($generatedKey));
    }
}
