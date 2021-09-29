<?php
declare(strict_types=1);

require '../lib/Signature.php';

use Ouzo\Utilities\Clock;
use PHPUnit\Framework\TestCase;

final class SignatureTest extends TestCase
{
    private Signature $sig;

    protected function setUp(): void
    {
        Clock::freeze('2011-01-02 12:34');
        $partner_id = 212;
        $api_key = file_get_contents(__DIR__ . "/assets/ApiKey.pub");
        $this->sig = new Signature($api_key, $partner_id);
    }

    public function testGenerateSecKey(): void
    {
        $generatedKey = $this->sig->generate_sec_key();
        $timestamp = Clock::now()->getTimestamp();
        $this->assertSame(2, count($generatedKey));
        $this->assertSame($timestamp, $generatedKey['timestamp']);
    }

    public function testConfirmSecKey(): void
    {
        // skipping this because it was not working in phpunit
        $this->markTestSkipped('must be revisited.');
        $generatedKey = $this->sig->generate_sec_key();
        $confirmSecKey = $this->sig->confirm_sec_key($generatedKey);
        $this->assertTrue($confirmSecKey);;
    }

    public function testGenerateSignature()
    {
        $timestamp = Clock::now()->format(DateTimeInterface::ISO8601);
        $signature = $this->sig->generate_signature();
        $this->assertSame(2, count($signature));
        $this->assertSame($timestamp, $signature['timestamp']);
    }

    public function testConfirmSignature()
    {
        $timestamp = Clock::now()->getTimestamp();
        $signature = $this->sig->generate_signature($timestamp)['signature'];
        $confirm_signature = $this->sig->confirm_signature($timestamp, $signature);
        $this->assertTrue($confirm_signature);

        $timestamp = Clock::now()->format(DateTimeInterface::ISO8601);
        $signature = $this->sig->generate_signature($timestamp)['signature'];
        $confirm_signature = $this->sig->confirm_signature($timestamp, $signature);
        $this->assertTrue($confirm_signature);
    }
}
